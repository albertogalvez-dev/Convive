<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Reporting\Infrastructure\ReporterEmailDelivery;
use App\Reporting\Infrastructure\ReporterEmailDeliverySender;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Uid\Uuid;

final class ReporterEmailDeliverySenderTest extends TestCase
{
    public function testVerificationMessageContainsOnlyTheFragmentTokenAndGenericCopy(): void
    {
        $mailer = new CapturingMailer();
        $sender = new ReporterEmailDeliverySender(
            $mailer,
            'no-reply@conviveaula.example',
            'https://app.conviveaula.com/verificar-correo',
            'https://app.conviveaula.com/seguimiento',
        );
        $token = str_repeat('a', 64);

        $sender->send($this->delivery('verification'), $token);

        $message = $mailer->email();
        self::assertSame('Confirma los avisos de Convive', $message->getSubject());
        self::assertStringContainsString('/verificar-correo#token='.$token, $this->textBody($message));
        $this->assertNoReportMaterial($message);
    }

    public function testUpdateMessageContainsNoReportMaterialOrAuthenticationValue(): void
    {
        $mailer = new CapturingMailer();
        $sender = new ReporterEmailDeliverySender(
            $mailer,
            'no-reply@conviveaula.example',
            'https://app.conviveaula.com/verificar-correo',
            'https://app.conviveaula.com/seguimiento',
        );

        $sender->send($this->delivery('report_update'), null);

        $message = $mailer->email();
        self::assertSame('Tienes una novedad en Convive', $message->getSubject());
        self::assertStringContainsString(
            'https://app.conviveaula.com/seguimiento',
            $this->textBody($message),
        );
        $this->assertNoReportMaterial($message);
    }

    private function delivery(string $kind): ReporterEmailDelivery
    {
        return new ReporterEmailDelivery(
            Uuid::v7(),
            Uuid::v7(),
            'reporter@example.test',
            $kind,
            1,
        );
    }

    private function assertNoReportMaterial(Email $message): void
    {
        $serialised = $message->toString();
        self::assertStringNotContainsString('PUBLIC_REFERENCE_123', $serialised);
        self::assertStringNotContainsString('ACCESS_SECRET_123', $serialised);
        self::assertStringNotContainsString('Sensitive report content', $serialised);
        self::assertStringNotContainsString('IES Horizonte', $serialised);
    }

    private function textBody(Email $message): string
    {
        $body = $message->getTextBody();
        self::assertIsString($body);

        return $body;
    }
}

final class CapturingMailer implements MailerInterface
{
    private ?Email $message = null;

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        Assert::assertInstanceOf(Email::class, $message);
        $this->message = $message;
    }

    public function email(): Email
    {
        Assert::assertNotNull($this->message);

        return $this->message;
    }
}
