<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Application\AttachmentDownloadConcurrencyLimiter;
use App\Reporting\Application\AttachmentDownloadPermit;
use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentRepository;
use App\Reporting\Domain\ReportAttachmentPolicy;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ReporterReportAttachmentControllerTest extends WebTestCase
{
    private const UPLOAD_ENDPOINT = '/api/v1/reporter/report/attachments';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private AttachmentStorage $storage;
    private AttachmentDownloadConcurrencyLimiter $downloadConcurrencyLimiter;
    private ReportAttachmentRepository $attachments;

    /** @var list<string> */
    private array $storedAttachmentIds = [];

    /** @var list<string> */
    private array $temporaryPaths = [];

    /** @var list<AttachmentDownloadPermit> */
    private array $downloadPermits = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->client->setServerParameter('REMOTE_ADDR', $this->uniqueTestClientIp());
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $storage = self::getContainer()->get(AttachmentStorage::class);
        $downloadConcurrencyLimiter = self::getContainer()->get(AttachmentDownloadConcurrencyLimiter::class);
        $attachments = self::getContainer()->get(ReportAttachmentRepository::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        self::assertInstanceOf(AttachmentStorage::class, $storage);
        self::assertInstanceOf(AttachmentDownloadConcurrencyLimiter::class, $downloadConcurrencyLimiter);
        self::assertInstanceOf(ReportAttachmentRepository::class, $attachments);
        $this->entityManager = $entityManager;
        $this->storage = $storage;
        $this->downloadConcurrencyLimiter = $downloadConcurrencyLimiter;
        $this->attachments = $attachments;
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        foreach ($this->downloadPermits as $permit) {
            $permit->release();
        }

        if (isset($this->entityManager)) {
            foreach ($this->storedAttachmentIds as $id) {
                $attachment = $this->entityManager->find(
                    ReportAttachment::class,
                    Uuid::fromString($id),
                );

                if ($attachment instanceof ReportAttachment) {
                    $this->storage->delete($attachment);
                }
            }

            $connection = $this->entityManager->getConnection();

            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }

            $this->entityManager->clear();
        }

        foreach ($this->temporaryPaths as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    #[Test]
    public function itQuarantinesAReporterUploadWithoutPersistingClientMetadata(): void
    {
        $report = $this->persistReport();
        $this->useCapability($this->issueGrant($report));
        $source = $this->upload('%PDF-1.7\nfictional evidence\n', 'misleading-name.exe', 'text/html');

        $this->requestUpload([$source]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('cache-control'));
        $payload = $this->responsePayload();
        self::assertSame(['items'], array_keys($payload));
        self::assertCount(1, $payload['items']);
        self::assertSame('processing', $payload['items'][0]['status']);
        self::assertArrayNotHasKey('mediaType', $payload['items'][0]);
        self::assertArrayNotHasKey('byteSize', $payload['items'][0]);
        $this->storedAttachmentIds[] = $payload['items'][0]['id'];

        $this->entityManager->clear();
        $attachment = $this->entityManager->find(
            ReportAttachment::class,
            Uuid::fromString($payload['items'][0]['id']),
        );
        self::assertInstanceOf(ReportAttachment::class, $attachment);
        self::assertSame('quarantined', $attachment->status()->value);
        self::assertSame('application/pdf', $attachment->mediaType()->value);
        self::assertStringStartsWith('quarantine/', $attachment->storageKey());
        self::assertStringNotContainsString('misleading-name.exe', $attachment->storageKey());
        self::assertStringNotContainsString('misleading-name.exe', serialize($attachment));
    }

    #[Test]
    public function itRejectsUnsupportedAndOversizedUploadsWithSafeProblemDetails(): void
    {
        $report = $this->persistReport();
        $this->useCapability($this->issueGrant($report));

        $this->requestUpload([$this->upload('not a PDF', 'evidence.pdf', 'application/pdf')]);
        $unsupported = $this->assertProblem(415, 'urn:convive:problem:attachment-type-not-accepted');
        self::assertStringNotContainsString('evidence.pdf', $unsupported['detail']);

        $this->requestUpload([$this->oversizedUpload()]);
        $this->assertProblem(413, 'urn:convive:problem:attachment-too-large');
    }

    #[Test]
    public function itRejectsCrossSiteUploadsBeforeWritingPrivateBytes(): void
    {
        $report = $this->persistReport();
        $this->useCapability($this->issueGrant($report));

        $this->client->request(
            'POST',
            self::UPLOAD_ENDPOINT,
            files: ['attachments' => [$this->upload('%PDF-1.7\nfictional evidence\n', 'evidence.pdf')]],
            server: [
                'CONTENT_TYPE' => 'multipart/form-data; boundary=convive',
                'HTTP_SEC_FETCH_SITE' => 'cross-site',
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertSame(0, (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM report_attachments WHERE report_id = ?',
            [$report->id()->toRfc4122()],
        ));
    }

    #[Test]
    public function itDoesNotMakeAQuarantinedAttachmentDownloadable(): void
    {
        $report = $this->persistReport();
        $this->useCapability($this->issueGrant($report));
        $this->requestUpload([$this->upload('%PDF-1.7\nfictional evidence\n', 'evidence.pdf')]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $id = $this->responsePayload()['items'][0]['id'];
        $this->storedAttachmentIds[] = $id;

        $this->client->request('GET', self::UPLOAD_ENDPOINT.'/'.$id.'/download');

        $payload = $this->assertProblem(404, 'urn:convive:problem:attachment-unavailable');
        self::assertStringNotContainsString($id, $payload['detail']);
    }

    #[Test]
    public function itListsOnlyTheAttachmentStatesOfTheCapabilityReport(): void
    {
        $ownedReport = $this->persistReport();
        $otherReport = $this->persistReport();
        $ownedAttachment = $this->persistAvailableAttachment($ownedReport);
        $this->persistAvailableAttachment($otherReport);
        $this->useCapability($this->issueGrant($ownedReport));

        $this->client->request('GET', self::UPLOAD_ENDPOINT);

        self::assertResponseIsSuccessful();
        $payload = $this->responsePayload();
        self::assertCount(1, $payload['items']);
        self::assertSame($ownedAttachment->id()->toRfc4122(), $payload['items'][0]['id']);
        self::assertSame('available', $payload['items'][0]['status']);
        self::assertSame('application/pdf', $payload['items'][0]['mediaType']);
    }

    #[Test]
    public function itStreamsOnlyAnAvailableOwnedAttachmentWithSafeHeaders(): void
    {
        $report = $this->persistReport();
        $attachment = $this->persistAvailableAttachment($report);
        $this->useCapability($this->issueGrant($report));

        $this->client->request('GET', self::UPLOAD_ENDPOINT.'/'.$attachment->id()->toRfc4122().'/download');

        self::assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        self::assertSame('application/pdf', $response->headers->get('content-type'));
        self::assertStringContainsString('private', (string) $response->headers->get('cache-control'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('cache-control'));
        self::assertSame('nosniff', $response->headers->get('x-content-type-options'));
        self::assertSame("sandbox; default-src 'none'", $response->headers->get('content-security-policy'));
        self::assertSame('same-origin', $response->headers->get('cross-origin-resource-policy'));
        self::assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        self::assertStringNotContainsString('fictional-source.pdf', (string) $response->headers->get('content-disposition'));
        self::assertSame('30', $response->headers->get('content-length'));

        self::assertSame(
            '%PDF-1.7\nfictional evidence\n',
            $this->client->getInternalResponse()->getContent(),
        );
    }

    #[Test]
    public function itRejectsAnAvailableDownloadWhenThePrivateStreamLimitIsReached(): void
    {
        $report = $this->persistReport();
        $attachment = $this->persistAvailableAttachment($report);
        $this->useCapability($this->issueGrant($report));

        for ($permit = 0; $permit < ReportAttachmentPolicy::MAXIMUM_CONCURRENT_DOWNLOADS; ++$permit) {
            $this->downloadPermits[] = $this->downloadConcurrencyLimiter->acquire();
        }

        $this->client->request('GET', self::UPLOAD_ENDPOINT.'/'.$attachment->id()->toRfc4122().'/download');

        $payload = $this->assertProblem(429, 'urn:convive:problem:attachment-download-busy');
        self::assertSame('1', $this->client->getResponse()->headers->get('retry-after'));
        self::assertStringNotContainsString($attachment->id()->toRfc4122(), $payload['detail']);
    }

    #[Test]
    public function itMakesForeignAndUnknownAttachmentDownloadsIndistinguishable(): void
    {
        $ownedReport = $this->persistReport();
        $otherReport = $this->persistReport();
        $foreignAttachment = $this->persistAvailableAttachment($otherReport);
        $this->useCapability($this->issueGrant($ownedReport));

        $this->client->request('GET', self::UPLOAD_ENDPOINT.'/'.$foreignAttachment->id()->toRfc4122().'/download');
        $foreign = $this->assertProblem(404, 'urn:convive:problem:attachment-unavailable');

        $this->client->request('GET', self::UPLOAD_ENDPOINT.'/0192a5c0-9999-7000-8000-000000000037/download');
        $unknown = $this->assertProblem(404, 'urn:convive:problem:attachment-unavailable');

        self::assertSame($foreign, $unknown);
    }

    private function persistReport(): Report
    {
        $organisation = new Organisation(
            Uuid::v7(),
            'IES Attachment Boundary',
            PublicReportingIdentifier::generate(),
        );
        $created = Report::create(
            $organisation,
            SituationDescription::fromString('A fictional attachment HTTP boundary test.'),
            SituationContext::Digital,
        );
        $this->entityManager->persist($organisation);
        $this->entityManager->persist($created->report);
        $this->entityManager->flush();

        return $created->report;
    }

    private function issueGrant(Report $report): string
    {
        $capability = ReportAccessCapability::generate();
        $this->entityManager->persist(ReportAccessGrant::issue(
            $report,
            $capability,
            new DateTimeImmutable(),
        ));
        $this->entityManager->flush();

        return $capability->reveal();
    }

    private function useCapability(string $capability): void
    {
        $this->client->getCookieJar()->set(new BrowserKitCookie('report_access', $capability));
    }

    /** @param list<UploadedFile> $files */
    private function requestUpload(array $files): void
    {
        $this->client->request(
            'POST',
            self::UPLOAD_ENDPOINT,
            files: ['attachments' => $files],
            server: [
                'CONTENT_TYPE' => 'multipart/form-data; boundary=convive',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
                'HTTP_ACCEPT' => 'application/problem+json',
            ],
        );
    }

    private function upload(string $content, string $name, string $mimeType = 'application/pdf'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'convive-http-attachment-');
        self::assertNotFalse($path);
        $this->temporaryPaths[] = $path;
        self::assertSame(strlen($content), file_put_contents($path, $content));

        return new UploadedFile($path, $name, $mimeType, null, true);
    }

    private function oversizedUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'convive-http-attachment-');
        self::assertNotFalse($path);
        $this->temporaryPaths[] = $path;
        $handle = fopen($path, 'c');
        self::assertNotFalse($handle);
        self::assertTrue(ftruncate($handle, 5 * 1024 * 1024 + 1));
        fclose($handle);

        return new UploadedFile($path, 'oversized.pdf', 'application/pdf', null, true);
    }

    private function persistAvailableAttachment(Report $report): ReportAttachment
    {
        $source = $this->upload('%PDF-1.7\nfictional evidence\n', 'fictional-source.pdf');
        $id = Uuid::v7();
        $this->storedAttachmentIds[] = $id->toRfc4122();
        $stored = $this->storage->storeQuarantine($id, $source->getPathname());
        $attachment = ReportAttachment::quarantine(
            $id,
            $report,
            AttachmentMediaType::Pdf,
            $stored->byteSize,
            $stored->contentHash,
            new DateTimeImmutable(),
        );
        $this->attachments->saveQuarantinedWithReportCapacity([$attachment]);
        $attachment->beginScanning(new DateTimeImmutable());
        $this->storage->promoteToAvailable($attachment);
        $attachment->markAvailable(new DateTimeImmutable());
        $this->attachments->save($attachment);

        return $attachment;
    }

    /** @return array<string, mixed> */
    private function responsePayload(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }

    /** @return array<string, mixed> */
    private function assertProblem(int $status, string $type): array
    {
        self::assertResponseStatusCodeSame($status);
        self::assertResponseHeaderSame('content-type', 'application/problem+json');
        $payload = $this->responsePayload();
        self::assertSame($type, $payload['type']);
        self::assertSame($status, $payload['status']);

        return $payload;
    }

    private function uniqueTestClientIp(): string
    {
        return sprintf('198.51.%d.%d', random_int(0, 255), random_int(0, 255));
    }
}
