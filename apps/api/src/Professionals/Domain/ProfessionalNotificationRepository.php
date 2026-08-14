<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use Symfony\Component\Uid\Uuid;

interface ProfessionalNotificationRepository
{
    /** @return list<ProfessionalNotification> */
    public function findFor(Professional $professional, int $limit): array;
    public function findForRecipient(Uuid $id, Professional $professional): ?ProfessionalNotification;
    public function save(ProfessionalNotification $notification): void;
    public function enabled(Professional $professional, ProfessionalNotificationType $type): bool;
    public function changePreference(Professional $professional, ProfessionalNotificationType $type, bool $enabled): void;
}
