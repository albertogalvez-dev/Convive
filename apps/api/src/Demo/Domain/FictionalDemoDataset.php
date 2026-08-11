<?php

declare(strict_types=1);

namespace App\Demo\Domain;

final class FictionalDemoDataset
{
    public const ORGANISATION_ID = '019fe900-0000-7000-8000-000000000070';
    public const ORGANISATION_NAME = 'IES Horizonte Ficticio — DEMOSTRACIÓN';
    public const PUBLIC_REPORTING_IDENTIFIER = 'ORG_DEM0000000000000';
    public const RESET_CONFIRMATION = 'ERASE-'.self::PUBLIC_REPORTING_IDENTIFIER;

    public const TRIAGE_PROFESSIONAL_ID = '019fe900-0000-7000-8000-000000000071';
    public const TRIAGE_PROFESSIONAL_EMAIL = 'lucia.demo@convive.example';
    public const ADMINISTRATOR_PROFESSIONAL_ID = '019fe900-0000-7000-8000-000000000072';
    public const ADMINISTRATOR_PROFESSIONAL_EMAIL = 'carlos.demo@convive.example';
    public const MANAGED_CASE_ID = '019fe900-0000-7000-8000-000000000083';
    public const CASE_ASSIGNMENT_ID = '019fe900-0000-7000-8000-000000000084';
    public const CASE_AFFECTED_PERSON_ID = '019fe900-0000-7000-8000-000000000085';
    public const CASE_WITNESS_PERSON_ID = '019fe900-0000-7000-8000-000000000086';
    public const CASE_TRIAGE_DECISION_ID = '019fe900-0000-7000-8000-000000000087';

    /** @var list<string> */
    public const REPORT_IDS = [
        '019fe900-0000-7000-8000-000000000073',
        '019fe900-0000-7000-8000-000000000074',
        '019fe900-0000-7000-8000-000000000075',
        '019fe900-0000-7000-8000-000000000076',
    ];

    /** @var list<string> */
    public const REPORT_REFERENCES = [
        'D0000000000000000001',
        'D0000000000000000002',
        'D0000000000000000003',
        'D0000000000000000004',
    ];

    private function __construct()
    {
    }
}
