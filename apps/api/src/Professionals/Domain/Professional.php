<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * A professional's identity only. Authentication (credentials, Symfony
 * Security integration, sessions) is deliberately out of scope here and
 * belongs to #30 — see ADR-0008's professional session lifecycle.
 */
#[ORM\Entity]
#[ORM\Table(name: 'professionals')]
class Professional
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(
        Uuid $id,
        string $name,
        ProfessionalEmail $email,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email->toString();
        $this->createdAt = $createdAt;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): ProfessionalEmail
    {
        return ProfessionalEmail::fromString($this->email);
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
