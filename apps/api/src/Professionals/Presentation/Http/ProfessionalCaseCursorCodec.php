<?php

declare(strict_types=1);

namespace App\Professionals\Presentation\Http;

use App\Cases\Domain\CaseOperationalView;
use App\Cases\Domain\CaseWorkspaceCursor;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final class ProfessionalCaseCursorCodec
{
    public function encode(CaseWorkspaceCursor $cursor): string
    {
        $json = json_encode([
            'view' => $cursor->view->value,
            'sortAt' => $cursor->sortAt->format(DATE_RFC3339_EXTENDED),
            'caseId' => $cursor->caseId->toRfc4122(),
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    public function decode(string $value): ?CaseWorkspaceCursor
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
            $view = is_array($data) && is_string($data['view'] ?? null)
                ? CaseOperationalView::tryFrom($data['view'])
                : null;
            if (
                $view === null
                || !is_string($data['sortAt'] ?? null)
                || !is_string($data['caseId'] ?? null)
                || !Uuid::isValid($data['caseId'])
            ) {
                return null;
            }

            $sortAt = DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, $data['sortAt']);
            if ($sortAt === false || $sortAt->format(DATE_RFC3339_EXTENDED) !== $data['sortAt']) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return new CaseWorkspaceCursor($view, $sortAt, Uuid::fromString($data['caseId']));
    }
}
