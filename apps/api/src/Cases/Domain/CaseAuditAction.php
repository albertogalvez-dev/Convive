<?php

declare(strict_types=1);

namespace App\Cases\Domain;

enum CaseAuditAction: string
{
    case CaseCreated = 'case_created';
    case ReportLinked = 'report_linked';
    case AssignmentCreated = 'assignment_created';
    case AssignmentChanged = 'assignment_changed';
    case AssignmentRevoked = 'assignment_revoked';
    case TaskCreated = 'task_created';
    case TaskCompleted = 'task_completed';
    case TaskMarkedNotApplicable = 'task_marked_not_applicable';
    case EvidenceDownloadAuthorised = 'evidence_download_authorised';
    case AuditExported = 'audit_exported';
    case CaseRecordExported = 'case_record_exported';
    case PersonAdded = 'person_added';
    case PersonCorrected = 'person_corrected';
    case PersonRemoved = 'person_removed';
    case StatusChanged = 'status_changed';
}
