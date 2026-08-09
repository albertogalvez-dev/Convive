<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Reporting\Domain\ReportListCursor;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class ProfessionalReportCursorCodec
{
    public function encode(ReportListCursor $cursor): string
    {
        $json = json_encode([
            'createdAt' => $cursor->createdAt->format(DATE_RFC3339_EXTENDED),
            'id' => $cursor->id->toRfc4122(),
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    public function decode(string $value): ?ReportListCursor
    {
        if ($value === '' || strlen($value) > 512) {
            return null;
        }

        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(
            strtr($value, '-_', '+/').str_repeat('=', $padding),
            true,
        );

        if ($decoded === false) {
            return null;
        }

        try {
            $data = json_decode($decoded, true, flags: JSON_THROW_ON_ERROR);

            if (
                !is_array($data)
                || !is_string($data['createdAt'] ?? null)
                || !is_string($data['id'] ?? null)
                || !Uuid::isValid($data['id'])
            ) {
                return null;
            }

            $createdAt = DateTimeImmutable::createFromFormat(
                DATE_RFC3339_EXTENDED,
                $data['createdAt'],
            );

            if (
                $createdAt === false
                || $createdAt->format(DATE_RFC3339_EXTENDED) !== $data['createdAt']
            ) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return new ReportListCursor($createdAt, Uuid::fromString($data['id']));
    }
}
