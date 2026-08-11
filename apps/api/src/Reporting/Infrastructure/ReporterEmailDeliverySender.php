<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class ReporterEmailDeliverySender
{
    public function __construct(
        private MailerInterface $mailer,
        #[Autowire(param: 'reporter_email.from')]
        private string $from,
        #[Autowire(param: 'reporter_email.public_url')]
        private string $publicUrl,
        #[Autowire(param: 'reporter_email.follow_up_url')]
        private string $followUpUrl,
    ) {
    }

    public function send(ReporterEmailDelivery $delivery, ?string $verificationToken): void
    {
        $email = (new Email())
            ->from(new Address($this->from, 'Convive'))
            ->to($delivery->email);

        if ($delivery->kind === 'verification') {
            if ($verificationToken === null) {
                throw new \LogicException('A verification delivery requires its transient token.');
            }

            $email
                ->subject('Confirma los avisos de Convive')
                ->text(
                    "Confirma que quieres recibir avisos de Convive.\n\n"
                    .$this->publicUrl.'#token='.$verificationToken."\n\n"
                    ."Este enlace caduca en 24 horas. No permite abrir ninguna comunicación.\n",
                );
        } else {
            $email
                ->subject('Tienes una novedad en Convive')
                ->text(
                    "Hay una novedad en Convive.\n\n"
                    ."Entra en {$this->followUpUrl} con el secreto que guardaste.\n\n"
                    ."Este mensaje no permite abrir ninguna comunicación.\n",
                );
        }

        $this->mailer->send($email);
    }
}
