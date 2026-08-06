<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use App\Reporting\Domain\ReportAccessCapability;
use App\Reporting\Domain\ReportAccessGrant;
use App\Reporting\Domain\ReportAccessGrantRepository;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves the capability grant, if any, that a request is entitled to use.
 *
 * Every rejection reason (missing cookie, malformed handle, unknown,
 * expired, revoked) collapses to the same null result: callers must not
 * distinguish why a request was denied access, only whether it was.
 */
final readonly class ReportAccessGuard
{
    public function __construct(
        private ReportAccessCookieFactory $cookieFactory,
        private ReportAccessGrantRepository $grantRepository,
    ) {
    }

    public function resolve(Request $request): ?ReportAccessGrant
    {
        $handle = $this->cookieFactory->readFrom($request);

        if ($handle === null) {
            return null;
        }

        try {
            $capability = ReportAccessCapability::fromString($handle);
        } catch (InvalidArgumentException) {
            return null;
        }

        $grant = $this->grantRepository->findByCapability($capability);

        if ($grant === null) {
            return null;
        }

        $now = DateTimeImmutable::createFromTimestamp(microtime(true));

        if (!$grant->isValidAt($now)) {
            return null;
        }

        $grant->recordUseAt($now);
        $this->grantRepository->save($grant);

        return $grant;
    }
}
