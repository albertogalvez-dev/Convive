# Fictional demonstration data

This procedure creates the public demonstration's known fictional state. It is
separate from development fixtures: `doctrine:fixtures:load` purges a database
and must never be used for the public demonstration.

The dataset is unmistakably fictional. It reserves one organisation, two
professional identities, four reports, four conversation entries and one
managed case with one lead assignment and two case-local fictional people. It
contains no real school, student, family, reporter or professional data.

## Safety contract

`app:demo:seed` refuses to run unless all of these conditions hold:

- the Symfony environment is `prod` (the automated suite uses `test` only);
- `APP_DEMO_MODE=1` is explicitly declared as non-secret configuration in the
  production Compose contract;
- `DEMO_PROFESSIONAL_PASSWORD` is supplied through the API container's secret
  environment and contains at least 20 characters;
- every reserved UUID, email, public reporting identifier and report reference
  is either unused or already belongs to the expected demo record.

The password must come from the root-only production `api.env` secret mount.
Never commit it, put it in Compose or a release manifest, pass it as a command
argument or copy it into logs. The command never prints credentials.

## Public reporting boundary

The public demonstration sets `PUBLIC_REPORTING_MODE=fictional_demo` in the
production Compose contract. Before a reporter controller or request payload is
handled, the API rejects every reporter-facing mutation with a generic `403`
problem response. This includes report submission, access-capability exchange,
follow-up text, attachment upload, reporter-email changes and access-grant
revocation. No visitor-provided content is logged or persisted through those
paths.

`operational` is an explicit development/test-only mode. It is never inferred
from `APP_DEMO_MODE`, and an unknown configuration is treated as `disabled`.
Do not set `operational` in a deployed environment without the separate
controller, privacy and real-data-pilot approvals. The public profile exposes
the active mode so the Angular form renders a truthful non-persistent
demonstration message instead of accepting text.

Application startup and database migrations never invoke the command. A release
operator must run it deliberately after successful migrations.

## Idempotent seed

With the reviewed release's API container and production secrets loaded, run:

```bash
php bin/console app:demo:seed --env=prod --no-debug
```

The normal mode reconciles only the reserved records. It does not delete other
organisations or visitor-created reports in the demo organisation. Running the
same command repeatedly produces the same reports, conversation entries and
case records without duplicating them. Existing demo professional password
hashes are not rotated by an ordinary repeat run.

Expected non-secret output includes:

```text
Fictional demo seeded: 1 organisation, 2 professionals, 4 reports, 4 conversation entries, 1 case, 1 assignment and 2 involved people.
Public reporting identifier: ORG_DEM0000000000000
No credentials were printed.
```

## Verification

After seeding:

1. Check `/api/v1/health` through the private gateway and public hostname.
2. Request `/api/v1/public/organisations/ORG_DEM0000000000000` and verify that
   its name is `IES Horizonte Ficticio — DEMOSTRACIÓN`.
3. Sign in with the triage demo email and the password from the secret store;
   verify that the dashboard contains two new and two reviewed fictional
   communications.
4. Open one seeded communication and verify that only fictional text appears.
5. Verify that the managed-case tables contain only the reserved case, its
   triage lead and the two case-local fictional people.
6. Attempt a synthetic report submission and reporter follow-up request; both
   must return the public-reporting-unavailable problem response and no content
   may be persisted.
7. Confirm that the public page is visibly labelled as a fictional
   demonstration before sharing its URL.

Do not record the password, session cookie, report capability or any newly
generated anonymous access secret as verification evidence.

## Destructive restore to the known state

Reset is appropriate only when visitor-created fictional reports and case work
may be discarded and the demonstration must return to its baseline. It deletes
reports, capabilities, conversation entries, triage decisions, cases,
assignments, involved people and memberships belonging to the reserved demo
organisation, then recreates the known dataset. It does not purge the database
and does not delete unrelated organisations.

Before reset:

1. verify the database target and selected release;
2. verify that `APP_DEMO_MODE=1` belongs to the fictional demonstration;
3. record the reset reason and operator, without credentials;
4. take the backup required by the release runbook when the environment is
   already publicly reachable.

Run the exact guarded command:

```bash
php bin/console app:demo:seed --env=prod --no-debug --reset --confirm-reset=ERASE-ORG_DEM0000000000000
```

Any missing or different confirmation token refuses the reset before a write.
Repeat the complete verification procedure afterward. If a reserved identifier
collision is reported, stop: investigate the database and never rename, delete
or overwrite the conflicting row manually to force the seed.

## Reporting posters

A school displays a poster; a student scans it and reaches that school's
reporting entry. Generate one per school:

```
cd apps/web
npm run poster -- --identifier 08A-EXEMPLE
npm run poster -- --identifier 08A-EXEMPLE --name "IES Exemple"
```

The output is a print-ready A4 SVG at true size. Posters are not committed:
they are reproducible from the identifier, so a file in Git would only be a
second thing to keep in step.

Three things the generator enforces rather than trusts:

- **The QR and the printed address come from the same value.** A poster whose
  code and text pointed at different schools would be worse than no poster.
- **The QR is decoded before the file is written**, by rendering it to pixels
  and reading it back with an independent decoder ([ADR-0028](../architecture/decisions/0028-generate-qr-posters-at-build-time-with-a-zero-dependency-encoder.md)).
  A wrong code is not a build error; it is a poster that does not scan,
  discovered by a student standing in front of it.
- **An identifier that would need URL-escaping is refused**, because it would
  print differently from what it encodes.

The readable address under the code is not decoration. [ADR-0009](../architecture/decisions/0009-use-public-organisation-reporting-links.md)
requires a manual-entry fallback: a QR alone excludes a reader whose camera
does not work, who has no phone, or whose poster has been scratched, covered
by another notice or photocopied badly.

### Reprinting after a rotation

ADR-0009 requires reprinting when an identifier is rotated. Every poster
carries its identifier in small print at the foot, so staff can confirm from
the wall that what is displayed is current without scanning it.

### Copy

The poster carries Tier 1 safety-critical copy under
[the plain-language standard](../content/plain-language-standard.md): measured
INFLESZ 81.6 against a floor of 65, longest sentence 9 words against a limit
of 15. Changing the wording means re-measuring it.

The `--name` option is optional and off by default. Naming the school helps a
student confirm they are in the right place; it also means a photograph of the
poster identifies the school. That trade-off is recorded on #329 and is not
settled by whoever runs the command.
