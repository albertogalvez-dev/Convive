<?php

declare(strict_types=1);

namespace App\Organisations\Domain;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'organisations')]
class Organisation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(
        name: 'public_reporting_identifier',
        type: 'string',
        length: PublicReportingIdentifier::LENGTH,
        unique: true,
    )]
    private string $publicReportingIdentifier;

    public function __construct(
        Uuid $id,
        string $name,
        PublicReportingIdentifier $publicReportingIdentifier,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->publicReportingIdentifier = $publicReportingIdentifier->toString();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function publicReportingIdentifier(): PublicReportingIdentifier
    {
        return PublicReportingIdentifier::fromString(
            $this->publicReportingIdentifier,
        );
    }
}
