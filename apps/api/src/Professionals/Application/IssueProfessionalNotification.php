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
    public function __construct(private ProfessionalNotificationRepository $notifications) {}
    public function issue(Professional $recipient, ManagedCase $case, ProfessionalNotificationType $type, DateTimeImmutable $now): void
    {
        if ($this->notifications->enabled($recipient, $type)) $this->notifications->save(new ProfessionalNotification(Uuid::v7(), $recipient, $case, $type, $now));
    }
}
