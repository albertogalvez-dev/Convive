<?php

declare(strict_types=1);

namespace App\Organisations\Application;

use App\Organisations\Domain\Organisation;
use App\Organisations\Domain\OrganisationRepository;
use App\Organisations\Domain\PublicReportingIdentifier;

/**
 * Administration of a centre's public reporting link.
 *
 * None of these operations touches report access. A reporter holding an access
 * code reaches their own report through `/api/v1/public/report-access-grants`,
 * which never takes a centre identifier, so pausing, rotating or retiring a
 * link stops new reports arriving through it and changes nothing for anyone
 * already in a conversation.
 */
final readonly class ManageReportingChannel
{
    public function __construct(private OrganisationRepository $organisations)
    {
    }

    public function pause(Organisation $organisation): void
    {
        $organisation->pauseReportingChannel();
        $this->organisations->save($organisation);
    }

    public function activate(Organisation $organisation): void
    {
        $organisation->activateReportingChannel();
        $this->organisations->save($organisation);
    }

    public function retire(Organisation $organisation): void
    {
        $organisation->retireReportingChannel();
        $this->organisations->save($organisation);
    }

    /** Issues a fresh link and leaves the channel active. */
    public function rotate(Organisation $organisation): PublicReportingIdentifier
    {
        $organisation->rotateReportingIdentifier(PublicReportingIdentifier::generate());
        $this->organisations->save($organisation);

        return $organisation->publicReportingIdentifier();
    }
}
