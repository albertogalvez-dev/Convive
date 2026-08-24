<?php

declare(strict_types=1);

namespace App\Demo\Application;

use App\Demo\Domain\FictionalDemoDataset;
use App\Professionals\Domain\Professional;
use Symfony\Component\HttpFoundation\Request;

/**
 * Server-side marker for a public visitor browsing the fixed fictional
 * professional dataset. It is never derived from a URL, a client field or a
 * professional role supplied by the browser.
 */
final class FictionalDemoProfessionalSession
{
    public const ROLE_KEY = 'convive_fictional_demo_professional_role';

    public const TRIAGE = 'triage';
    public const ADMINISTRATOR = 'administrator';
    public const CASE_LEAD = 'case_lead';
    public const CASE_CONTRIBUTOR = 'case_contributor';
    public const CASE_OBSERVER = 'case_observer';

    private function __construct()
    {
    }

    /** @return 'triage'|'administrator'|'case_lead'|'case_contributor'|'case_observer'|null */
    public static function role(Request $request): ?string
    {
        $role = $request->getSession()->get(self::ROLE_KEY);

        return is_string($role) && in_array($role, [
            self::TRIAGE,
            self::ADMINISTRATOR,
            self::CASE_LEAD,
            self::CASE_CONTRIBUTOR,
            self::CASE_OBSERVER,
        ], true)
            ? $role
            : null;
    }

    /** @return 'triage'|'administrator'|'case_lead'|'case_contributor'|'case_observer'|null */
    public static function roleFor(Request $request, Professional $professional): ?string
    {
        $role = self::role($request);
        if ($role === null) {
            return null;
        }

        return match ($role) {
            self::TRIAGE => $professional->email()->toString() === FictionalDemoDataset::TRIAGE_PROFESSIONAL_EMAIL
                ? $role
                : null,
            self::ADMINISTRATOR => $professional->email()->toString() === FictionalDemoDataset::ADMINISTRATOR_PROFESSIONAL_EMAIL
                ? $role
                : null,
            self::CASE_LEAD => $professional->email()->toString() === FictionalDemoDataset::CASE_LEAD_PROFESSIONAL_EMAIL
                ? $role
                : null,
            self::CASE_CONTRIBUTOR => $professional->email()->toString() === FictionalDemoDataset::CASE_CONTRIBUTOR_PROFESSIONAL_EMAIL
                ? $role
                : null,
            self::CASE_OBSERVER => $professional->email()->toString() === FictionalDemoDataset::CASE_OBSERVER_PROFESSIONAL_EMAIL
                ? $role
                : null,
        };
    }

    public static function clear(Request $request): void
    {
        $request->getSession()->remove(self::ROLE_KEY);
    }
}
