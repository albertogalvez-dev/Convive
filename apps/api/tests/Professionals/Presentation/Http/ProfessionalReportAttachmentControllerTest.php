<?php

declare(strict_types=1);

namespace App\Tests\Professionals\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use App\Reporting\Application\AttachmentStorage;
use App\Reporting\Domain\AttachmentMediaType;
use App\Reporting\Domain\Report;
use App\Reporting\Domain\ReportAttachment;
use App\Reporting\Domain\ReportAttachmentRepository;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class ProfessionalReportAttachmentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private AttachmentStorage $storage;
    private ReportAttachmentRepository $attachments;

    /** @var list<string> */
    private array $storedAttachmentIds = [];

    /** @var list<string> */
    private array $temporaryPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->client->setServerParameter('REMOTE_ADDR', $this->uniqueTestClientIp());
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $storage = self::getContainer()->get(AttachmentStorage::class);
        $attachments = self::getContainer()->get(ReportAttachmentRepository::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        self::assertInstanceOf(AttachmentStorage::class, $storage);
        self::assertInstanceOf(ReportAttachmentRepository::class, $attachments);
        $this->entityManager = $entityManager;
        $this->storage = $storage;
        $this->attachments = $attachments;
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
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

    public function testAnActiveTriageProfessionalCanListAndDownloadAvailableEvidence(): void
    {
        $organisation = $this->persistOrganisation('Triage Boundary School');
        $professional = $this->persistProfessional($organisation, ProfessionalRole::Triage);
        $report = $this->persistReport($organisation);
        $attachment = $this->persistAttachment($report, true);
        $this->client->loginUser($professional);

        $this->client->request('GET', $this->attachmentPath($report));

        self::assertResponseIsSuccessful();
        $items = $this->responsePayload()['items'];
        self::assertCount(1, $items);
        self::assertSame($attachment->id()->toRfc4122(), $items[0]['id']);
        self::assertSame('application/pdf', $items[0]['mediaType']);
        self::assertSame(30, $items[0]['byteSize']);

        $this->client->request('GET', $this->attachmentPath($report).'/'.$attachment->id()->toRfc4122().'/download');

        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('content-type'));
        self::assertStringContainsString('attachment;', (string) $this->client->getResponse()->headers->get('content-disposition'));
        self::assertSame('%PDF-1.7\nfictional evidence\n', $this->client->getInternalResponse()->getContent());
    }

    public function testAProfessionalCannotSeeOrDownloadAQuarantinedAttachment(): void
    {
        $organisation = $this->persistOrganisation('Quarantine Boundary School');
        $professional = $this->persistProfessional($organisation, ProfessionalRole::Triage);
        $report = $this->persistReport($organisation);
        $attachment = $this->persistAttachment($report, false);
        $this->client->loginUser($professional);

        $this->client->request('GET', $this->attachmentPath($report));
        self::assertResponseIsSuccessful();
        self::assertSame([], $this->responsePayload()['items']);

        $this->client->request('GET', $this->attachmentPath($report).'/'.$attachment->id()->toRfc4122().'/download');
        $this->assertProblem(404, 'urn:convive:problem:attachment-unavailable');
    }

    public function testForeignAndUnknownReportAttachmentRoutesAreIndistinguishable(): void
    {
        $ownedOrganisation = $this->persistOrganisation('Owned Boundary School');
        $foreignOrganisation = $this->persistOrganisation('Foreign Boundary School');
        $professional = $this->persistProfessional($ownedOrganisation, ProfessionalRole::Triage);
        $foreignReport = $this->persistReport($foreignOrganisation);
        $foreignAttachment = $this->persistAttachment($foreignReport, true);
        $this->client->loginUser($professional);

        $this->client->request('GET', $this->attachmentPath($foreignReport).'/'.$foreignAttachment->id()->toRfc4122().'/download');
        $foreign = $this->assertProblem(404, 'urn:convive:problem:professional-report-not-found');

        $this->client->request('GET', '/api/v1/professional/reports/0192a5c0-9999-7000-8000-000000000037/attachments/'.$foreignAttachment->id()->toRfc4122().'/download');
        $unknown = $this->assertProblem(404, 'urn:convive:problem:professional-report-not-found');

        self::assertSame($foreign, $unknown);
    }

    public function testAProfessionalWithoutTriageScopeCannotAccessAttachmentRoutes(): void
    {
        $organisation = $this->persistOrganisation('Administrator Boundary School');
        $administrator = $this->persistProfessional($organisation, ProfessionalRole::Administrator);
        $report = $this->persistReport($organisation);
        $this->persistAttachment($report, true);
        $this->client->loginUser($administrator);

        $this->client->request('GET', $this->attachmentPath($report));

        $this->assertProblem(404, 'urn:convive:problem:professional-report-not-found');
    }

    private function persistOrganisation(string $name): Organisation
    {
        $organisation = new Organisation(Uuid::v7(), $name, PublicReportingIdentifier::generate());
        $this->entityManager->persist($organisation);
        $this->entityManager->flush();

        return $organisation;
    }

    private function persistProfessional(Organisation $organisation, ProfessionalRole $role): Professional
    {
        $professional = new Professional(
            Uuid::v7(),
            'Attachment Professional',
            ProfessionalEmail::fromString(sprintf('%s@attachment-test.example', Uuid::v7()->toRfc4122())),
            new DateTimeImmutable(),
        );
        $membership = new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            $role,
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($professional);
        $this->entityManager->persist($membership);
        $this->entityManager->flush();

        return $professional;
    }

    private function persistReport(Organisation $organisation): Report
    {
        $report = Report::create(
            $organisation,
            SituationDescription::fromString('A fictional professional attachment boundary test.'),
            SituationContext::InPerson,
        )->report;
        $this->entityManager->persist($report);
        $this->entityManager->flush();

        return $report;
    }

    private function persistAttachment(Report $report, bool $available): ReportAttachment
    {
        $path = tempnam(sys_get_temp_dir(), 'convive-professional-attachment-');
        self::assertNotFalse($path);
        $this->temporaryPaths[] = $path;
        self::assertSame(30, file_put_contents($path, '%PDF-1.7\nfictional evidence\n'));
        $id = Uuid::v7();
        $this->storedAttachmentIds[] = $id->toRfc4122();
        $stored = $this->storage->storeQuarantine($id, $path);
        $attachment = ReportAttachment::quarantine(
            $id,
            $report,
            AttachmentMediaType::Pdf,
            $stored->byteSize,
            $stored->contentHash,
            new DateTimeImmutable(),
        );
        $this->attachments->saveQuarantinedWithReportCapacity([$attachment]);

        if (!$available) {
            return $attachment;
        }

        $attachment->beginScanning(new DateTimeImmutable());
        $this->storage->promoteToAvailable($attachment);
        $attachment->markAvailable(new DateTimeImmutable());
        $this->attachments->save($attachment);

        return $attachment;
    }

    private function attachmentPath(Report $report): string
    {
        return '/api/v1/professional/reports/'.$report->id()->toRfc4122().'/attachments';
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

        return $payload;
    }

    private function uniqueTestClientIp(): string
    {
        return sprintf('198.18.%d.%d', random_int(0, 255), random_int(0, 255));
    }
}
