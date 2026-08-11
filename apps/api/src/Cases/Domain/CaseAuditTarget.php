<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseAuditTarget: string
{
    case Case = 'case';
    case TriageDecision = 'triage_decision';
    case Assignment = 'assignment';
    case Task = 'task';
    case Attachment = 'attachment';
    case AuditTrail = 'audit_trail';
    case CaseRecord = 'case_record';
}
