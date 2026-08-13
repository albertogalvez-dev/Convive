# Public-endpoint anti-abuse threat model

Scope: the unauthenticated submission/verification surfaces and the
capability-authenticated anonymous follow-up read/append surfaces that
exist today. Attachment upload is out of scope; it doesn't exist yet
(tracked separately in #36–#40).

## Endpoints in scope

| Endpoint                                    | Method | Sensitivity                                                                 |
| ------------------------------------------- | ------ | --------------------------------------------------------------------------- |
| `/api/v1/public/organisations/{id}/reports` | `POST` | Creates a report; low-entropy path parameter (organisation identifier)      |
| `/api/v1/public/report-access-grants`       | `POST` | Verifies a high-entropy secret; the primary brute-force target              |
| `/api/v1/reporter/report`                   | `GET`  | Reads confidential report and follow-up content through a capability cookie |
| `/api/v1/reporter/report/follow-up-entries` | `POST` | Appends reporter-authored content through a capability cookie               |

## Threats and mitigations

### 1. Brute-forcing the access secret

A 64-character hex secret (256 bits of entropy) is not practically
guessable by brute force regardless of rate limiting — the mitigation
here is about raising the cost of automated scanning and detecting it,
not making guessing theoretically impossible.

- **Mitigation**: dedicated rate limit on `report-access-grants`
  (`report_access_verification`, 5/minute/IP), applied before any
  database lookup. Delivered in #23.
- **Status**: done (#23).

### 2. Enumerating organisations or secrets through response differences

If malformed input and unknown input produced different responses, an
attacker could distinguish "wrong guess" from "no such thing exists"
and narrow a search.

- **Mitigation**: both endpoints already collapse malformed and
  unknown input to one identical response (verified by
  `testItTreatsMalformedAndUnknownIdentifiersEqually` for the
  organisation identifier, and `ReportAccessDenied` for the secret).
  There is no mechanism to revoke an access secret today (only an
  issued capability grant can be revoked — see #23's acceptance-audit
  comment on issue #23), so a "revoked secret" case does not yet exist
  to test against.
- **Status**: done (#22, #23) for the cases that exist today. Nothing
  new required by this issue.

### 3. Automated or accidental mass report submission

Submission has no rate limit today. A script, or a client retrying
after a slow response, can create an unbounded number of reports.

- **Mitigation**: a dedicated rate limit on the submission endpoint
  (`report_submission`), same shape as #23's verification limiter.
- **Status**: delivered by this issue.

### 4. Duplicate reports from retried submissions

A network timeout or an impatient double-click can submit the same
report twice. Reporters have no session and no way to detect or
correct this after the fact — one of the two duplicate reports would
be silently orphaned with a secret the reporter never saw.

- **Mitigation**: optional `Idempotency-Key` request header, scoped per
  organisation identifier. A repeated request with the same key
  returns `200` with the existing report's public reference instead of
  creating a second report.
- **Deliberately does not replay the access secret.** The secret is a
  one-time credential (ADR-0008/ADR-0010): it must never be persisted
  anywhere in a readable form beyond its single original display, so
  the idempotency store only ever remembers the non-secret public
  reference, never the response body. A retried request that hits an
  existing idempotency key gets confirmation the report exists, but
  not the secret — the same "a lost secret cannot be recovered"
  position already taken for #23, not a new recovery channel
  introduced through the back door of retry handling.
- **Known limitation, accepted rather than hidden**: this protects the
  realistic case (sequential retry after failure), not two literally
  concurrent requests racing on the same key. Closing that gap would
  require a locking or unique-constraint mechanism disproportionate to
  the actual risk at this stage of the product; it is not attempted
  here and must not be assumed solved.
- **Status**: delivered by this issue.

### 5. Rate-limit bypass through a misconfigured or absent reverse proxy

Rate limiting keys on the resolved client IP. Behind a reverse proxy
that isn't explicitly trusted, `X-Forwarded-For` can be forged by the
client, making every request appear to originate from a different IP
and defeating the limit entirely.

- **Mitigation**: `framework.trusted_proxies` configured explicitly per
  environment — unset in development and test (no proxy exists in that
  topology, so nothing should be trusted), sourced from an environment
  variable in production once a reverse proxy is introduced (#63).
- **Status**: delivered by this issue.

### 6. Sensitive data reaching logs

Rate-limit trips, access/capability denials, CSRF rejections and
idempotent replays are worth logging for operational visibility, but
the request payloads they relate to — free-text situation
descriptions, access secrets, capability handles — must never appear
in a log line.

- **Mitigation**: a dedicated `security` log channel that only ever
  logs structured, pre-selected fields (event type, path, client IP,
  timestamp) — never the request body, never a secret, never a
  capability handle. Verified by a test that asserts a submitted
  situation description is absent from a captured log record.
- **Retention**: not enforced by this issue's code — actual log
  rotation/retention is an infrastructure concern for #65
  (observability, alerting, incident response). The intended policy
  documented here: keep security-channel logs long enough to
  investigate an incident (a rolling 30-day window is a reasonable
  starting point) and no longer, since they contain client IP
  addresses. #65 must implement and enforce this, not merely inherit
  the assumption.

### 7. Follow-up read and append exhaustion

A valid capability previously allowed unlimited reads and appended rows. Reads
loaded the complete history and persisted grant activity every time, allowing a
single capability holder to amplify database, response-memory and storage work.

- **Request limits**: reads allow 120 requests/minute/IP and 60/minute for each
  IP + capability pair; appends allow 20/minute/IP and 10/minute for each pair.
  Both budgets are consumed before database lookup. This bounds clients that
  rotate invalid cookies while preserving a separate, tighter budget for each
  valid capability. Pair keys are one-way hashes; raw capabilities are never
  stored in limiter keys or logs.
- **Report capacity**: a report accepts at most 100 follow-up entries. The
  Doctrine repository locks the report row, counts and persists in one
  transaction, preventing concurrent requests from crossing the boundary
  together. Capacity failures return a generic `409` Problem Details response.
- **Bounded reads**: at most 100 entries are loaded, ordered by creation time
  ascending and then UUID ascending when timestamps match.
- **Activity writes**: successful use is persisted at most once per minute.
  This preserves the 15-minute idle window while bounding write amplification;
  persisted activity can be at most one minute behind after an unexpected
  process failure.
- **Deployment boundary**: #205 owns authenticated Redis-backed, shared and
  restart-resistant limiter and idempotency storage before public production.
- **Status**: delivered by #99, subject to #205 before public production.

## Explicitly out of scope

- Behavioural scoring or automated credibility assessment.
- Third-party CAPTCHA by default.
- Production WAF procurement.
- Attachment upload abuse (#36–#40 model the feature first).
- Concurrent-request idempotency guarantees (see §4).
