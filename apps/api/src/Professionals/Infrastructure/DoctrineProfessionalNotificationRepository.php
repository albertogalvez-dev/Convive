<?php

declare(strict_types=1);

namespace App\Professionals\Infrastructure;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalNotification;
use App\Professionals\Domain\ProfessionalNotificationPreference;
use App\Professionals\Domain\ProfessionalNotificationRepository;
use App\Professionals\Domain\ProfessionalNotificationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineProfessionalNotificationRepository implements ProfessionalNotificationRepository
{
    public function __construct(private EntityManagerInterface $entityManager) {}
    public function findFor(Professional $professional, int $limit): array
    {
        return $this->entityManager->getRepository(ProfessionalNotification::class)->findBy(['recipient' => $professional], ['createdAt' => 'DESC', 'id' => 'DESC'], $limit);
    }
    public function findForRecipient(Uuid $id, Professional $professional): ?ProfessionalNotification
    {
        return $this->entityManager->getRepository(ProfessionalNotification::class)->findOneBy(['id' => $id, 'recipient' => $professional]);
    }
    public function save(ProfessionalNotification $notification): void { $this->entityManager->persist($notification); $this->entityManager->flush(); }
    public function enabled(Professional $professional, ProfessionalNotificationType $type): bool
    {
        if ($type->isRequired()) return true;
        $preference = $this->entityManager->getRepository(ProfessionalNotificationPreference::class)->findOneBy(['professional' => $professional, 'type' => $type]);
        return $preference?->enabled() ?? true;
    }
    public function changePreference(Professional $professional, ProfessionalNotificationType $type, bool $enabled): void
    {
        if ($type->isRequired()) throw new \LogicException('Required notifications cannot be disabled.');
        $preference = $this->entityManager->getRepository(ProfessionalNotificationPreference::class)->findOneBy(['professional' => $professional, 'type' => $type]);
        if ($preference === null) { $this->entityManager->persist(new ProfessionalNotificationPreference($professional, $type, $enabled)); } else { $preference->change($enabled); }
        $this->entityManager->flush();
    }
}
