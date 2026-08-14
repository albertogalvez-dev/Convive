<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'professional_credential_invitations')]
#[ORM\Index(name: 'idx_professional_credential_invitation_hash', columns: ['secret_hash'])]
class ProfessionalCredentialInvitation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $professional;

    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'issued_by_professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $issuedBy;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ProfessionalCredentialInvitationPurpose::class)]
    private ProfessionalCredentialInvitationPurpose $purpose;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    private string $secretHash;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $usedAt = null;

    public function __construct(
        Uuid $id,
        Professional $professional,
        Professional $issuedBy,
        ProfessionalCredentialInvitationPurpose $purpose,
        string $secret,
        DateTimeImmutable $expiresAt,
    ) {
        $this->id = $id;
        $this->professional = $professional;
        $this->issuedBy = $issuedBy;
        $this->purpose = $purpose;
        $this->secretHash = hash('sha256', $secret);
        $this->expiresAt = $expiresAt;
    }

    public function accepts(string $secret, DateTimeImmutable $now): bool
    {
        return $this->usedAt === null
            && $now < $this->expiresAt
            && hash_equals($this->secretHash, hash('sha256', $secret));
    }

    public function useAt(DateTimeImmutable $now): void
    {
        if ($this->usedAt !== null || $now >= $this->expiresAt) {
            throw new \LogicException('This credential invitation is unavailable.');
        }

        $this->usedAt = $now;
    }

    public function professional(): Professional
    {
        return $this->professional;
    }

    public function purpose(): ProfessionalCredentialInvitationPurpose
    {
        return $this->purpose;
    }
}
