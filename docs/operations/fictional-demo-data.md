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
- `APP_DEMO_MODE=1` is explicitly present in the runtime environment;
- `DEMO_PROFESSIONAL_PASSWORD` is supplied through the API container's secret
  environment and contains at least 20 characters;
- every reserved UUID, email, public reporting identifier and report reference
  is either unused or already belongs to the expected demo record.

The password must come from the production secret mount. Never commit it, put
it in a release manifest, pass it as a command argument or copy it into logs.
The command never prints credentials.

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
6. Confirm that the public page is visibly labelled as a fictional
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
