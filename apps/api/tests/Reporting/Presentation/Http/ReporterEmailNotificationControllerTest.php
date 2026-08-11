<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\SituationContext;
use App\Reporting\Domain\SituationDescription;
use App\Reporting\Domain\Report;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\Uid\Uuid;

final class ReporterEmailNotificationControllerTest extends WebTestCase
{
    private const ENDPOINT = '/api/v1/reporter/report/email-notifications';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->client->setServerParameter('REMOTE_ADDR', $this->uniqueTestClientIp());
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $connection = $this->entityManager->getConnection();
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->entityManager->clear();
        }

        parent::tearDown();
    }

    public function testItRequiresReportAccessAndNeverReturnsAnAddress(): void
    {
        $this->client->request('GET', self::ENDPOINT);
        self::assertResponseStatusCodeSame(401);

        $this->authorise($this->persistReport());
        $this->client->request('GET', self::ENDPOINT);
        self::assertResponseIsSuccessful();
        self::assertSame(['enabled' => true, 'status' => 'none'], $this->payload());
        self::assertStringNotContainsString('@', (string) $this->client->getResponse()->getContent());
    }

    public function testItRecordsServerOwnedConsentEvidenceAndAllowsImmediateRemoval(): void
    {
        $report = $this->persistReport();
        $this->authorise($report);

        $this->client->jsonRequest('PUT', self::ENDPOINT, [
            'email' => 'Reporter@Example.test',
            'consentAccepted' => true,
        ], $this->sameOriginHeaders());
        self::assertResponseStatusCodeSame(202);
        self::assertSame(['enabled' => true, 'status' => 'pending'], $this->payload());

        $row = $this->entityManager->getConnection()->fetchAssociative(
            'SELECT email, consent_notice_version FROM reporter_email_contacts WHERE report_id = ?',
            [$report->id()->toRfc4122()],
        );
        self::assertIsArray($row);
        self::assertSame('reporter@example.test', $row['email']);
        self::assertSame('reporter-email-v1', $row['consent_notice_version']);

        $this->client->request('DELETE', self::ENDPOINT, server: $this->sameOriginHeaders());
        self::assertResponseStatusCodeSame(204);
        self::assertSame(0, (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM reporter_email_contacts WHERE report_id = ?',
            [$report->id()->toRfc4122()],
        ));
    }

    public function testItRejectsConfigurationWithoutExplicitConsent(): void
    {
        $this->authorise($this->persistReport());

        $this->client->jsonRequest('PUT', self::ENDPOINT, [
            'email' => 'reporter@example.test',
            'consentAccepted' => false,
        ], $this->sameOriginHeaders());

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM reporter_email_contacts',
        ));
    }

    public function testItRejectsCrossSiteConfigurationBeforePersistingTheAddress(): void
    {
        $this->authorise($this->persistReport());

        $this->client->jsonRequest(
            'PUT',
            self::ENDPOINT,
            [
                'email' => 'reporter@example.test',
                'consentAccepted' => true,
            ],
            ['HTTP_SEC_FETCH_SITE' => 'cross-site'],
        );

        self::assertResponseStatusCodeSame(403);
        self::assertSame(0, (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM reporter_email_contacts',
        ));
    }

    public function testUnknownVerificationTokenReturnsNoReportInformation(): void
    {
        $this->client->jsonRequest('POST', '/api/v1/public/reporter-email-verifications', [
            'token' => str_repeat('a', 64),
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(['verified' => false], $this->payload());
        self::assertResponseHeaderSame('referrer-policy', 'no-referrer');
    }

    private function persistReport(): Report
    {
        $organisation = new Organisation(
            Uuid::v7(),
            'IES Horizonte',
            PublicReportingIdentifier::generate(),
        );
        $this->entityManager->persist($organisation);
        $creation = Report::create(
            $organisation,
            SituationDescription::fromString('A fictional situation for HTTP notification tests.'),
            SituationContext::Digital,
        );
        $this->entityManager->persist($creation->report);
        $this->entityManager->flush();

        return $creation->report;
    }

    private function authorise(Report $report): void
    {
        $capability = ReportAccessCapability::generate();
        $this->entityManager->persist(ReportAccessGrant::issue(
            $report,
            $capability,
            new DateTimeImmutable(),
        ));
        $this->entityManager->flush();
        $this->client->getCookieJar()->set(new BrowserKitCookie(
            'report_access',
            $capability->reveal(),
        ));
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertNotFalse($content);
        $payload = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }

    private function uniqueTestClientIp(): string
    {
        return sprintf('198.18.%d.%d', random_int(0, 255), random_int(0, 255));
    }

    /** @return array<string, string> */
    private function sameOriginHeaders(): array
    {
        return [
            'HTTP_ORIGIN' => 'http://localhost',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
        ];
    }
}
