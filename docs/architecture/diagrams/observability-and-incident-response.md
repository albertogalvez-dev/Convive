# Observability, alerting and incident response

Verified against `infrastructure/observability/`, the systemd units and the
[incident-response runbook](../../operations/incident-response.md) on
24 August 2026.

**The property this diagram makes explicit: an operational failure stops the
unsafe action, retains only redacted evidence, and then requires triage.**

```mermaid
flowchart LR
    timer["Systemd timer"] --> check["Versioned health and evidence check"]
    check --> healthy{"Healthy and recent evidence?"}
    healthy -->|"yes"| evidence["Redacted local evidence"]
    healthy -->|"no"| alert["Redacted alert publisher"]
    alert --> triage["Maintainer triage"]
    triage --> classify{"Routine, security or safeguarding?"}
    classify -->|"routine"| remediate["Repair and rerun complete check"]
    classify -->|"security or safeguarding"| preserve["Preserve redacted evidence and escalate"]
    remediate --> verify{"Verification passes?"}
    verify -->|"yes"| close["Record resolution"]
    verify -->|"no"| alert
    preserve --> incident["Follow incident response runbook"]
    evidence --> maintenance["Routine maintenance evidence"]
```

Checks and evidence stay on the controlled host. They exclude report content,
access secrets, session identifiers, attachment identifiers, credentials and
unredacted environment data. An alert is not evidence of a staffed 24/7
service, and the fictional demonstration remains bounded by its operational
runbooks.

## Verification sources

- [Incident response and observability](../../operations/incident-response.md)
- `infrastructure/observability/check.sh`
- `infrastructure/observability/alert.sh`
- `infrastructure/observability/exercise-failure.sh`
- `infrastructure/observability/systemd/`
