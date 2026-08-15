<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

use Symfony\Component\Uid\Uuid;

interface ProfessionalNotificationRepository
{
    /**
     * Most recent notifications first, newest identifier breaking ties.
     *
     * @return list<ProfessionalNotification>
     */
    public function findFor(Professional $professional, int $limit): array;

    /** Resolves a notification only when the professional is its recipient. */
    public function findForRecipient(Uuid $id, Professional $professional): ?ProfessionalNotification;

    public function save(ProfessionalNotification $notification): void;

    /** Safeguarding-required types are always enabled; optional types default to enabled. */
    public function enabled(Professional $professional, ProfessionalNotificationType $type): bool;

    public function changePreference(Professional $professional, ProfessionalNotificationType $type, bool $enabled): void;
}
