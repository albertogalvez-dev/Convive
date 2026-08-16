<?php

declare(strict_types=1);

namespace App\Professionals\Application;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalEmailAlreadyUsed;
use App\Professionals\Domain\ProfessionalRepository;

final readonly class UpdateProfessionalProfile
{
    public function __construct(private ProfessionalRepository $professionals)
    {
    }

    /**
     * Self-service profile changes. A professional can only ever act on their
     * own account here: role, organisation membership and case access are
     * administrator-controlled and are not reachable through this command.
     *
     * @throws ProfessionalEmailAlreadyUsed when the address belongs to someone else
     */
    public function update(Professional $professional, string $name, ProfessionalEmail $email): void
    {
        $existing = $this->professionals->findByEmail($email);
        if ($existing !== null && !$existing->id()->equals($professional->id())) {
            throw new ProfessionalEmailAlreadyUsed();
        }

        $professional->rename($name);
        $professional->changeEmail($email);
        $this->professionals->save($professional);
    }
}
