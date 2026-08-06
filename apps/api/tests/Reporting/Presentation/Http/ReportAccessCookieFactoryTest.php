<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Presentation\Http;

use App\Reporting\Presentation\Http\ReportAccessCookieFactory;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

final class ReportAccessCookieFactoryTest extends TestCase
{
    public function testTheDevelopmentCookieIsNotSecure(): void
    {
        $factory = new ReportAccessCookieFactory('report_access', false);

        $cookie = $factory->issue(
            str_repeat('a', 64),
            new DateTimeImmutable('+2 hours'),
        );

        self::assertSame('report_access', $cookie->getName());
        self::assertFalse($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('/', $cookie->getPath());
        self::assertNull($cookie->getDomain());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    /**
     * Production must use the __Host- prefix, which the browser only
     * honours when the cookie is Secure, has Path=/ and carries no Domain
     * attribute — all three are asserted together here.
     */
    public function testTheProductionCookieMeetsTheHostPrefixRequirements(): void
    {
        $factory = new ReportAccessCookieFactory(
            '__Host-report_access',
            true,
        );

        $cookie = $factory->issue(
            str_repeat('a', 64),
            new DateTimeImmutable('+2 hours'),
        );

        self::assertSame('__Host-report_access', $cookie->getName());
        self::assertTrue($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
        self::assertSame('/', $cookie->getPath());
        self::assertNull($cookie->getDomain());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
    }

    public function testClearingProducesAnExpiredCookieWithTheSameName(): void
    {
        $factory = new ReportAccessCookieFactory(
            '__Host-report_access',
            true,
        );

        $cookie = $factory->clear();

        self::assertSame('__Host-report_access', $cookie->getName());
        self::assertNull($cookie->getValue());
        self::assertLessThan(time(), $cookie->getExpiresTime());
    }

    public function testItReadsTheConfiguredCookieFromTheRequest(): void
    {
        $factory = new ReportAccessCookieFactory('report_access', false);

        $request = Request::create(
            '/',
            cookies: ['report_access' => str_repeat('a', 64)],
        );

        self::assertSame(
            str_repeat('a', 64),
            $factory->readFrom($request),
        );
        self::assertNull(
            $factory->readFrom(Request::create('/')),
        );
    }
}
