<?php

declare(strict_types=1);

namespace App\Professionals\Domain;

/**
 * Least-privilege initial roles (#29). Administrator manages an
 * organisation's professional memberships; it does not by itself grant
 * access to report or case content — a professional needing both holds
 * both roles explicitly (ADR-0008: "Administration permission does not
 * automatically grant access to case content").
 */
enum ProfessionalRole: string
{
    case Triage = 'triage';
    case Administrator = 'administrator';
}
