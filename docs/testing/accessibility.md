# Accessibility and responsive quality baseline

Convive treats accessibility and responsive behaviour as release quality, not
as a certification claim. Automated checks catch repeatable regressions;
keyboard, screen-reader and visual checks remain a human responsibility before
declaring a changed journey ready.

## Automated CI checks

The isolated `End-to-end` job runs Axe against these authenticated or
anonymous states using WCAG 2 A, 2 AA and 2.1 AA rules:

- the ready anonymous reporting form;
- the anonymous follow-up credential entry;
- the authenticated professional dashboard.

The same suite exercises the public reporting form at a 390 x 844 viewport. It
uses the keyboard to reach and activate the continue action, verifies that the
validation error is exposed as an alert, checks for horizontal overflow and
emulates `prefers-reduced-motion: reduce`. Global styles remove animation and
transition duration under that preference.

An Axe pass does not prove that a journey is accessible. A failure must be
fixed or investigated as a real defect; do not suppress a rule globally. A
specific false positive requires the same scoped, issue-backed exception policy
as the [code-quality baseline](../development/code-quality.md).

## Manual verification matrix

Run the relevant rows whenever a changed interface affects layout, interaction,
copy that communicates state, or a custom control. Use only fictional data.

| Surface | Viewport or assistive setting | Verify |
| --- | --- | --- |
| Public reporting and follow-up | Desktop, 1440 x 900 | Reading order, visible focus, error/status announcement, no unintended horizontal scroll |
| Professional access and workspace | Desktop, 1440 x 900 | Navigation landmarks, sidebar collapse, alerts, notification and session actions are keyboard reachable |
| Changed public or professional page | 1280 x 720 at 200% browser zoom | Reflow to one usable column, no clipped controls or two-dimensional scrolling |
| Public reporting, follow-up and professional access | Mobile, 390 x 844 | Touch target spacing, form labels, errors, scroll and fixed/sticky elements |
| A compact changed layout | Mobile, 320 x 568 | Essential actions remain available without horizontal scrolling |
| Any changed form or dialog | Keyboard only | Logical Tab and Shift+Tab order, visible focus, Enter/Space activation, Escape only where a dismissible overlay exists |
| Any changed state or validation | Screen reader with the supported browser/OS combination | Name, role and value of controls; labelled form fields; alert/status text is understandable without colour or position |
| Any changed motion | `prefers-reduced-motion: reduce` | No essential meaning depends on animation; motion is removed or reduced |

Record a concise result in the pull request when a row applies. Screenshots or
recordings must be redacted and use stable fictional content only; never capture
an anonymous access secret or professional credential.

## Defect handling and priority

Treat a blocked critical action, inaccessible authentication or reporting path,
lost keyboard focus, missing programmatic error/status information, or
unreadable mobile reflow as a release blocker. File focused accessibility issues
for lesser defects with the affected journey, assistive setting, expected
behaviour and fictional reproduction steps. Do not defer a defect by replacing
it with generic explanatory copy or a visual-only workaround.

This baseline complements the [layered testing strategy](strategy.md): choose
the narrowest test layer that proves the risk, then add a browser check whenever
the behaviour crosses the rendered interface.
