<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'professionals')]
class Professional implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    /** @var non-empty-string */
    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $active;

    #[ORM\Column(type: Types::INTEGER)]
    private int $securityRevision;

    public function __construct(
        Uuid $id,
        string $name,
        ProfessionalEmail $email,
        DateTimeImmutable $createdAt,
        string $passwordHash = '!unavailable',
        bool $active = true,
        int $securityRevision = 1,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email->toString();
        $this->createdAt = $createdAt;
        $this->passwordHash = $passwordHash;
        $this->active = $active;
        $this->securityRevision = $securityRevision;
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

    /** @return non-empty-string */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return ['ROLE_PROFESSIONAL'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function replacePasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
        ++$this->securityRevision;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function securityRevision(): int
    {
        return $this->securityRevision;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof self) {
            return false;
        }

        $passwordMatches = hash_equals($this->passwordHash, $user->passwordHash)
            || hash_equals($this->passwordHash, hash('crc32c', $user->passwordHash));

        return $this->id->equals($user->id)
            && hash_equals($this->email, $user->email)
            && $this->active === $user->active
            && $this->securityRevision === $user->securityRevision
            && $passwordMatches;
    }

    /** @return array<string, mixed> */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0passwordHash"] = hash('crc32c', $this->passwordHash);

        return $data;
    }
}
