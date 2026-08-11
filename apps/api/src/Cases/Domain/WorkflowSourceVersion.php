<?php

declare(strict_types=1);

namespace App\Cases\Domain;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'case_workflow_source_versions')]
#[ORM\UniqueConstraint(name: 'uniq_case_workflow_source_version', columns: ['code', 'version'])]
class WorkflowSourceVersion
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, length: 80)]
    private string $code;

    #[ORM\Column(type: Types::STRING, length: 40)]
    private string $version;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $title;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $uri;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $territory;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: WorkflowSourceAuthority::class)]
    private WorkflowSourceAuthority $authority;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $publishedOn;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private DateTimeImmutable $reviewedOn;

    public function __construct(
        Uuid $id,
        string $code,
        string $version,
        string $title,
        ?string $uri,
        string $territory,
        WorkflowSourceAuthority $authority,
        DateTimeImmutable $publishedOn,
        DateTimeImmutable $reviewedOn,
    ) {
        $this->id = $id;
        $this->code = self::bounded($code, 80, 'Source code');
        $this->version = self::bounded($version, 40, 'Source version');
        $this->title = self::bounded($title, 180, 'Source title');
        $this->uri = $uri === null ? null : self::bounded($uri, 500, 'Source URI');
        $this->territory = self::bounded($territory, 20, 'Source territory');
        $this->authority = $authority;
        $this->publishedOn = $publishedOn;
        $this->reviewedOn = $reviewedOn;

        if ($authority !== WorkflowSourceAuthority::Internal && $uri === null) {
            throw new InvalidArgumentException('An official workflow source requires a URI.');
        }

        if ($reviewedOn < $publishedOn) {
            throw new InvalidArgumentException('A workflow source cannot be reviewed before publication.');
        }
    }

    public function id(): Uuid { return $this->id; }
    public function code(): string { return $this->code; }
    public function version(): string { return $this->version; }
    public function title(): string { return $this->title; }
    public function uri(): ?string { return $this->uri; }
    public function territory(): string { return $this->territory; }
    public function authority(): WorkflowSourceAuthority { return $this->authority; }
    public function publishedOn(): DateTimeImmutable { return $this->publishedOn; }
    public function reviewedOn(): DateTimeImmutable { return $this->reviewedOn; }

    private static function bounded(string $value, int $maximum, string $field): string
    {
        $value = trim($value);

        if ($value === '' || mb_strlen($value) > $maximum) {
            throw new InvalidArgumentException(sprintf('%s must contain between 1 and %d characters.', $field, $maximum));
        }

        return $value;
    }
}
