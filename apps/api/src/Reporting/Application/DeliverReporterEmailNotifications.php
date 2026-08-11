<?php

declare(strict_types=1);

namespace App\Reporting\Application;

use App\Reporting\Infrastructure\DoctrineReporterEmailNotifications;
use App\Reporting\Infrastructure\ReporterEmailDeliverySender;
use Psr\Log\LoggerInterface;

final readonly class DeliverReporterEmailNotifications
{
    public function __construct(
        private DoctrineReporterEmailNotifications $notifications,
        private ReporterEmailDeliverySender $sender,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(int $limit): int
    {
        $this->notifications->purgeExpired();
        $delivered = 0;

        for ($position = 0; $position < $limit; ++$position) {
            $delivery = $this->notifications->claim();

            if ($delivery === null) {
                break;
            }

            try {
                $token = $delivery->kind === 'verification'
                    ? $this->notifications->prepareVerificationToken($delivery->contactId)
                    : null;
                $this->sender->send($delivery, $token);
                $this->notifications->markDelivered($delivery->id);
                ++$delivered;
            } catch (\Throwable $exception) {
                $this->notifications->markFailed($delivery);
                $this->logger->warning('Reporter email delivery failed.', [
                    'delivery_id' => $delivery->id->toRfc4122(),
                    'kind' => $delivery->kind,
                    'attempt' => $delivery->attempt,
                    'exception_class' => $exception::class,
                ]);
            }
        }

        return $delivered;
    }
}
