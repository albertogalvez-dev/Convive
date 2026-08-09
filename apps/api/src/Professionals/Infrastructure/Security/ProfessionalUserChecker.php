<?php

declare(strict_types=1);

namespace App\Professionals\Infrastructure\Security;

use App\Professionals\Domain\Professional;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ProfessionalUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if ($user instanceof Professional && !$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Authentication failed.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
