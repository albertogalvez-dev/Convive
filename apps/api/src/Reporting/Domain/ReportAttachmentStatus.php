<?php

declare(strict_types=1);

namespace App\Reporting\Domain;

enum ReportAttachmentStatus: string
{
    case Quarantined = 'quarantined';
    case Scanning = 'scanning';
    case Available = 'available';
    case Rejected = 'rejected';
    case DeletionPending = 'deletion_pending';
    case Deleted = 'deleted';
}
