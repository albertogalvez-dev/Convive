<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final readonly class ReportAccessCookieFactory
{
    public function __construct(
        #[Autowire(param: 'report_access_cookie.name')]
        private string $cookieName,
        #[Autowire(param: 'report_access_cookie.secure')]
        private bool $cookieSecure,
    ) {
    }

    public function issue(
        string $plainCapabilityHandle,
        DateTimeImmutable $absoluteExpiresAt,
    ): Cookie {
        return Cookie::create($this->cookieName)
            ->withValue($plainCapabilityHandle)
            ->withExpires($absoluteExpiresAt)
            ->withPath('/')
            ->withDomain(null)
            ->withSecure($this->cookieSecure)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);
    }

    public function clear(): Cookie
    {
        return Cookie::create($this->cookieName)
            ->withValue(null)
            ->withExpires(1)
            ->withPath('/')
            ->withDomain(null)
            ->withSecure($this->cookieSecure)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);
    }

    public function readFrom(Request $request): ?string
    {
        $value = $request->cookies->get($this->cookieName);

        return is_string($value) ? $value : null;
    }
}
