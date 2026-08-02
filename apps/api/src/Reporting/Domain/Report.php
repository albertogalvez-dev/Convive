<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

use App\Organisations\Domain\Organisation;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Random\RandomException;
use RuntimeException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'reports')]
class Report
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Organisation::class)]
    #[ORM\JoinColumn(
        name: 'organisation_id',
        referencedColumnName: 'id',
        nullable: false,
    )]
    private Organisation $organisation;

    #[ORM\Column(type: Types::TEXT)]
    private string $situationDescription;

    #[ORM\Column(
        type: Types::STRING,
        length: 20,
        enumType: SituationContext::class,
    )]
    private SituationContext $situationContext;

    #[ORM\Column(
        type: Types::STRING,
        length: 20,
        enumType: ReportStatus::class,
    )]
    private ReportStatus $status;

    #[ORM\Column(type: Types::STRING, length: 32, unique: true)]
    private string $publicReference;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $accessSecretHash;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        Organisation $organisation,
        SituationDescription $situationDescription,
        SituationContext $situationContext,
        string $publicReference,
        string $accessSecretHash,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->organisation = $organisation;
        $this->situationDescription = $situationDescription->toString();
        $this->situationContext = $situationContext;
        $this->status = ReportStatus::Received;
        $this->publicReference = $publicReference;
        $this->accessSecretHash = $accessSecretHash;
        $this->createdAt = $createdAt;
    }

    public static function create(
        Organisation $organisation,
        SituationDescription $situationDescription,
        SituationContext $situationContext,
    ): ReportCreationResult {
        try {
            $publicReferenceBytes = random_bytes(10);
            $plainAccessSecretBytes = random_bytes(32);
        } catch (RandomException $exception) {
            throw new RuntimeException(
                'Unable to generate secure report credentials.',
                previous: $exception,
            );
        }

        $publicReference = bin2hex($publicReferenceBytes);
        $publicReference = strtoupper($publicReference);
        $plainAccessSecret = bin2hex($plainAccessSecretBytes);

        $report = new self(
            Uuid::v7(),
            $organisation,
            $situationDescription,
            $situationContext,
            $publicReference,
            hash('sha256', $plainAccessSecret),
            DateTimeImmutable::createFromTimestamp(microtime(true)),
        );

        return new ReportCreationResult(
            $report,
            $plainAccessSecret,
        );
    }

    public function verifyAccessSecret(string $plainAccessSecret): bool
    {
        return hash_equals(
            $this->accessSecretHash,
            hash('sha256', $plainAccessSecret),
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function organisation(): Organisation
    {
        return $this->organisation;
    }

    public function situationDescription(): SituationDescription
    {
        return SituationDescription::fromString(
            $this->situationDescription,
        );
    }

    public function situationContext(): SituationContext
    {
        return $this->situationContext;
    }

    public function status(): ReportStatus
    {
        return $this->status;
    }

    public function publicReference(): string
    {
        return $this->publicReference;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
