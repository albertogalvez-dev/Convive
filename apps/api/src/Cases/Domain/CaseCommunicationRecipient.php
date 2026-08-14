<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseCommunicationRecipient: string
{
    case Family = 'family';
    case ExternalService = 'external_service';
    case EducationInspectorate = 'education_inspectorate';
    case Other = 'other';
}
