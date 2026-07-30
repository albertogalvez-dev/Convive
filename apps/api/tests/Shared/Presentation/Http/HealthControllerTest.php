<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpointReturnsOkResponse(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/health');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            '{"status":"ok"}',
            $client->getResponse()->getContent(),
        );
    }

    public function testHealthEndpointRejectsUnsupportedMethod(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/health');

        self::assertResponseStatusCodeSame(405);
    }
}
