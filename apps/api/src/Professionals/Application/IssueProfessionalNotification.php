<?php

declare(strict_types=1);

namespace App\Professionals\Application;

use App\Cases\Domain\ManagedCase;
use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalNotification;
use App\Professionals\Domain\ProfessionalNotificationRepository;
use App\Professionals\Domain\ProfessionalNotificationType;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class IssueProfessionalNotification
{
    public function __construct(private ProfessionalNotificationRepository $notifications)
    {
    }

    /**
     * Records a minimised notification for a single recipient. No report or case
     * content is copied: the row carries a type, an instant and the case whose
     * access is re-authorised on every read.
     */
    public function issue(Professional $recipient, ManagedCase $managedCase, ProfessionalNotificationType $type, DateTimeImmutable $now): void
    {
        if (!$this->notifications->enabled($recipient, $type)) {
            return;
        }

        $this->notifications->save(new ProfessionalNotification(Uuid::v7(), $recipient, $managedCase, $type, $now));
    }
}
