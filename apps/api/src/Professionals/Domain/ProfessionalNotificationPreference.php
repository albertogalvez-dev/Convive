<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'professional_notification_preferences')]
#[ORM\UniqueConstraint(name: 'uniq_professional_notification_preference', columns: ['professional_id', 'notification_type'])]
class ProfessionalNotificationPreference
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Professional::class)]
    #[ORM\JoinColumn(name: 'professional_id', referencedColumnName: 'id', nullable: false)]
    private Professional $professional;

    #[ORM\Id]
    #[ORM\Column(name: 'notification_type', type: Types::STRING, length: 30, enumType: ProfessionalNotificationType::class)]
    private ProfessionalNotificationType $type;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $enabled;

    public function __construct(Professional $professional, ProfessionalNotificationType $type, bool $enabled)
    {
        if ($type->isRequired()) throw new \LogicException('Required notification preferences cannot be persisted.');
        $this->professional = $professional;
        $this->type = $type;
        $this->enabled = $enabled;
    }

    public function enabled(): bool { return $this->enabled; }
    public function change(bool $enabled): void { $this->enabled = $enabled; }
}
