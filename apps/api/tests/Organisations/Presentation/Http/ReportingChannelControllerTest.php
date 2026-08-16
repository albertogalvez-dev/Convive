<?php

declare(strict_types=1);

namespace App\Tests\Organisations\Presentation\Http;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\PublicReportingIdentifier;
use App\Organisations\Domain\ReportingChannelStatus;
use App\Professionals\Domain\OrganisationMembership;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRole;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class ReportingChannelControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();
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

    public function testEveryRefusedChannelStateAnswersIdenticallyToOneThatNeverExisted(): void
    {
        $active = $this->createOrganisation();
        $paused = $this->createOrganisation();
        $paused->pauseReportingChannel();
        $retired = $this->createOrganisation();
        $retired->retireReportingChannel();
        $this->entityManager->flush();

        $neverExisted = $this->profileResponseFor(PublicReportingIdentifier::generate()->toString());
        $pausedResponse = $this->profileResponseFor($paused->publicReportingIdentifier()->toString());
        $retiredResponse = $this->profileResponseFor($retired->publicReportingIdentifier()->toString());

        // Byte-identical, not merely all 404: a rotation must never become an
        // oracle telling an outsider which centres exist or which used to.
        self::assertSame($neverExisted, $pausedResponse);
        self::assertSame($neverExisted, $retiredResponse);

        // The active one still resolves, so the assertions above are not vacuous.
        $this->client->request('GET', '/api/v1/public/organisations/'.$active->publicReportingIdentifier()->toString());
        self::assertResponseIsSuccessful();
    }

    public function testAPausedChannelRefusesANewReportAndReactivationRestoresIt(): void
    {
        $organisation = $this->createOrganisation();
        $administrator = $this->createProfessional($organisation);
        $this->entityManager->flush();
        $identifier = $organisation->publicReportingIdentifier()->toString();

        $this->client->loginUser($administrator);
        $this->client->jsonRequest('PATCH', $this->channelEndpoint($organisation), ['action' => 'pause']);
        self::assertResponseIsSuccessful();
        self::assertFalse($this->responsePayload()['acceptsNewReports']);

        self::assertSame(Response::HTTP_NOT_FOUND, $this->submitStatusFor($identifier));

        // The session from the first sign-in is still open.
        $this->client->jsonRequest('PATCH', $this->channelEndpoint($organisation), ['action' => 'activate']);
        self::assertResponseIsSuccessful();
        self::assertSame(Response::HTTP_CREATED, $this->submitStatusFor($identifier));
    }

    public function testRotationIssuesANewLinkAndStopsTheOldOne(): void
    {
        $organisation = $this->createOrganisation();
        $administrator = $this->createProfessional($organisation);
        $this->entityManager->flush();
        $previous = $organisation->publicReportingIdentifier()->toString();

        $this->client->loginUser($administrator);
        $this->client->jsonRequest('PATCH', $this->channelEndpoint($organisation), ['action' => 'rotate']);

        self::assertResponseIsSuccessful();
        $replacement = $this->responsePayload()['identifier'];
        self::assertNotSame($previous, $replacement);
        self::assertSame('active', $this->responsePayload()['status']);
        self::assertSame(Response::HTTP_NOT_FOUND, $this->submitStatusFor($previous));
        self::assertSame(Response::HTTP_CREATED, $this->submitStatusFor($replacement));
    }

    public function testARetiredChannelCannotBeReactivated(): void
    {
        $organisation = $this->createOrganisation();
        $administrator = $this->createProfessional($organisation);
        $this->entityManager->flush();

        $this->client->loginUser($administrator);
        $this->client->jsonRequest('PATCH', $this->channelEndpoint($organisation), ['action' => 'retire']);
        self::assertResponseIsSuccessful();

        $this->client->jsonRequest('PATCH', $this->channelEndpoint($organisation), ['action' => 'activate']);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertSame(ReportingChannelStatus::Retired, $organisation->reportingChannelStatus());
    }

    public function testAnAdministratorOfAnotherCentreCannotReachTheChannel(): void
    {
        $organisation = $this->createOrganisation();
        $other = $this->createOrganisation();
        $outsider = $this->createProfessional($other);
        $this->entityManager->flush();

        $this->client->loginUser($outsider);
        $this->client->request('GET', $this->channelEndpoint($organisation));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testANonAdministratorCannotReachTheChannel(): void
    {
        $organisation = $this->createOrganisation();
        $triage = $this->createProfessional($organisation, ProfessionalRole::Triage);
        $this->entityManager->flush();

        $this->client->loginUser($triage);
        $this->client->request('GET', $this->channelEndpoint($organisation));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function channelEndpoint(Organisation $organisation): string
    {
        return '/api/v1/professional/organisations/'.$organisation->id()->toRfc4122().'/reporting-channel';
    }

    private function profileResponseFor(string $identifier): string
    {
        $this->client->request('GET', '/api/v1/public/organisations/'.$identifier);

        return $this->client->getResponse()->getStatusCode()
            .'|'.(string) $this->client->getResponse()->getContent();
    }

    private function submitStatusFor(string $identifier): int
    {
        // A fresh client address per submission, so the anti-abuse limiter does
        // not mask the channel-state answer this test is about.
        $this->client->setServerParameter('REMOTE_ADDR', $this->uniqueTestClientIp());
        $this->client->jsonRequest(
            'POST',
            '/api/v1/public/organisations/'.$identifier.'/reports',
            [
                'situationDescription' => 'Una situacion ficticia para comprobar el canal.',
                'situationContext' => 'unknown',
            ],
        );

        return $this->client->getResponse()->getStatusCode();
    }

    private function createOrganisation(): Organisation
    {
        $organisation = new Organisation(
            Uuid::v7(),
            'Fictional Channel School '.Uuid::v7()->toRfc4122(),
            PublicReportingIdentifier::generate(),
        );
        $this->entityManager->persist($organisation);

        return $organisation;
    }

    private function createProfessional(
        Organisation $organisation,
        ProfessionalRole $role = ProfessionalRole::Administrator,
    ): Professional {
        $professional = new Professional(
            Uuid::v7(),
            'Channel Administrator',
            ProfessionalEmail::fromString('channel-'.Uuid::v7()->toRfc4122().'@channel-test.example'),
            new DateTimeImmutable(),
        );
        $this->entityManager->persist($professional);
        $this->entityManager->persist(new OrganisationMembership(
            Uuid::v7(),
            $professional,
            $organisation,
            $role,
            new DateTimeImmutable(),
        ));

        return $professional;
    }

    private function uniqueTestClientIp(): string
    {
        return sprintf('198.18.%d.%d', random_int(0, 255), random_int(0, 255));
    }

    /** @return array<string, mixed> */
    private function responsePayload(): array
    {
        $content = $this->client->getResponse()->getContent();
        self::assertIsString($content);

        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }
}
