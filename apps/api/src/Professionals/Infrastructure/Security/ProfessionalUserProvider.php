<?php

declare(strict_types=1);

namespace App\Professionals\Infrastructure\Security;

use App\Professionals\Domain\Professional;
use App\Professionals\Domain\ProfessionalEmail;
use App\Professionals\Domain\ProfessionalRepository;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/** @implements UserProviderInterface<Professional> */
final readonly class ProfessionalUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private ProfessionalRepository $professionals)
    {
    }

    public function loadUserByIdentifier(string $identifier): Professional
    {
        try {
            $email = ProfessionalEmail::fromString($identifier);
        } catch (InvalidArgumentException) {
            throw new UserNotFoundException();
        }

        return $this->professionals->findByEmail($email)
            ?? throw new UserNotFoundException();
    }

    public function refreshUser(UserInterface $user): Professional
    {
        if (!$user instanceof Professional) {
            throw new UnsupportedUserException(sprintf(
                'Instances of "%s" are not supported.',
                $user::class,
            ));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return Professional::class === $class || is_subclass_of($class, Professional::class);
    }

    public function upgradePassword(
        PasswordAuthenticatedUserInterface $user,
        string $newHashedPassword,
    ): void {
        if (!$this->professionals instanceof PasswordUpgraderInterface) {
            throw new \LogicException('The professional repository cannot upgrade password hashes.');
        }

        $this->professionals->upgradePassword($user, $newHashedPassword);
    }
}
