<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Illes Balears as an eighth territorial protocol profile (#265)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        // Source verified directly against the Conselleria d'Educació i
        // Universitats' own Convivèxit site (caib.es), not a secondary
        // aggregator: fetched and read the full 42-page text of "Protocol
        // de prevenció, detecció i intervenció de l'assetjament i el
        // ciberassetjament escolar de les Illes Balears", issued by the
        // Direcció General de Primera Infància i Atenció a la Diversitat.
        // https://www.caib.es/sites/convivexit/f/439010
        //
        // The issue asked whether the 2016 protocol is still current or has
        // since been revised. It HAS been revised: the version currently
        // published and in force is the **September 2023 revision**, which
        // the document states on its own cover page ("Revisió setembre de
        // 2023"). The 2016 text the prior research pass found is
        // superseded, so this profile cites the 2023 revision.
        //
        // The protocol is Conselleria-issued guidance rather than a
        // gazette-published instrument in its own right, so authority is
        // 'recommended'. (It implements Decret 121/2010, de 10 de desembre,
        // BOIB núm. 187 of 23 December 2010, which requires every school to
        // have such a protocol inside its pla de convivència -- that decree
        // is a general rights-and-duties instrument, not a bullying
        // protocol, and is not modelled here.)
        //
        // Unlike Cantabria and Extremadura, this protocol sets several real
        // numeric deadlines, all in working days ("dies hàbils"), confirmed
        // verbatim:
        // - Same day as the notification: the director passes the case to
        //   the referent, assesses protection measures, and notifies the
        //   Departament d'Inspecció Educativa ("El mateix dia", II.1).
        // - Within 4 working days: the referent completes the acolliment i
        //   valoració actions, and the tutor administers a sociogram if
        //   none is recent ("com a màxim en el termini de quatre dies
        //   hàbils", II.2).
        // - Within 5 working days of the first notification: the first case
        //   management meeting ("en el termini màxim de cinc dies hàbils",
        //   II.3).
        // - Within 7 working days of the notification: the individual Pikas
        //   interviews with the students who are causing harm ("en un
        //   termini màxim de set dies hàbils", II.2 and III.4.1).
        // - At least 5 working days after those interviews: the follow-up
        //   interview with the affected student ("s'ha d'esperar almenys
        //   cinc dies hàbils", III.4.3).
        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_source_versions
    (id, code, version, title, uri, territory, authority, published_on, reviewed_on)
VALUES
    (
        '019c4c24-4fd4-7f6d-a0d1-000000000001',
        'ES-IB-PROTOCOL-2023-09-CONVIVEXIT',
        'CONVIVEXIT-2023-09',
        'Illes Balears school bullying and cyberbullying protocol (September 2023 revision)',
        'https://www.caib.es/sites/convivexit/f/439010',
        'ES-IB',
        'recommended',
        '2023-09-01',
        '2026-08-17'
    )
SQL);

        $this->addSql(<<<'SQL'
INSERT INTO case_workflow_task_templates (id, source_version_id, stage, kind, title, approved)
VALUES
    (
        '019ffe77-0000-7000-8000-000000000001',
        '019c4c24-4fd4-7f6d-a0d1-000000000001',
        'immediate_actions',
        'internal_action',
        'Record the fictional same-day designation of the persona referent for the case.',
        true
    ),
    (
        '019ffe77-0000-7000-8000-000000000002',
        '019c4c24-4fd4-7f6d-a0d1-000000000001',
        'inspection_communication',
        'external_communication',
        'Confirm the fictional same-day notification to the Departament d''Inspecció Educativa.',
        true
    ),
    (
        '019ffe77-0000-7000-8000-000000000003',
        '019c4c24-4fd4-7f6d-a0d1-000000000001',
        'urgent_protection',
        'internal_action',
        'Confirm the fictional protection and observation measures assessed for the affected student.',
        true
    ),
    (
        '019ffe77-0000-7000-8000-000000000004',
        '019c4c24-4fd4-7f6d-a0d1-000000000001',
        'information_collection',
        'internal_action',
        'Track the fictional 4-working-day acolliment i valoració deadline.',
        true
    ),
    (
        '019ffe77-0000-7000-8000-000000000005',
        '019c4c24-4fd4-7f6d-a0d1-000000000001',
        'assessment',
        'internal_action',
        'Track the fictional 5-working-day deadline for the first case management meeting.',
        true
    ),
    (
        '019ffe77-0000-7000-8000-000000000006',
        '019c4c24-4fd4-7f6d-a0d1-000000000001',
        'educational_measures',
        'internal_action',
        'Track the fictional 7-working-day deadline for the individual Pikas-method interviews.',
        true
    ),
    (
        '019ffe77-0000-7000-8000-000000000007',
        '019c4c24-4fd4-7f6d-a0d1-000000000001',
        'family_communication',
        'external_communication',
        'Confirm the fictional interviews with the families of the students involved.',
        true
    ),
    (
        '019ffe77-0000-7000-8000-000000000008',
        '019c4c24-4fd4-7f6d-a0d1-000000000001',
        'inspection_follow_up',
        'external_communication',
        'Confirm the fictional closure report sent to the school inspector.',
        true
    )
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'This migration can only be executed safely on PostgreSQL.',
        );

        $this->addSql("DELETE FROM case_workflow_task_templates WHERE source_version_id = '019c4c24-4fd4-7f6d-a0d1-000000000001'");
        $this->addSql("DELETE FROM case_workflow_source_versions WHERE id = '019c4c24-4fd4-7f6d-a0d1-000000000001'");
    }
}
