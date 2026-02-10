# Role & Permission

## Sales

| Action                        | SU | OWNER | MANAGER | OPERATOR | SPECTATOR |
| ----------------------------- | -- | ----- | ------- | -------- | --------- |
| Create sale                   | ✓  | ✗     | ✗       | ✓        | ✗         |
| Edit sale (before validation) | ✓  | ✗     | ✗       | ✓        | ✗         |
| Validate / reject sale        | ✓  | ✗     | ✓       | ✗        | ✗         |
| Cancel validated sale         | ✓  | ✗     | ✓       | ✗        | ✗         |
| View sales (detail)           | ✓  | ✓     | ✓       | ✗        | ✗         |
| View sales (summary)          | ✓  | ✓     | ✓       | ✗        | ✓         |

## Daily Closing & Settlement

| Action                  | SU | OWNER | MANAGER | OPERATOR | SPECTATOR |
| ----------------------- | -- | ----- | ------- | -------- | --------- |
| Trigger daily closing   | ✓  | ✗     | ✓       | ✗        | ✗         |
| Approve daily closing   | ✓  | ✓     | ✗       | ✗        | ✗         |
| Reopen closed day       | ✓  | ✗     | ✗       | ✗        | ✗         |
| View settlement reports | ✓  | ✓     | ✓       | ✗        | ✓         |

## Stock & Production

| Action                     | SU | OWNER | MANAGER | OPERATOR | SPECTATOR |
| -------------------------- | -- | ----- | ------- | -------- | --------- |
| Stock in/out (operational) | ✓  | ✗     | ✗       | ✓        | ✗         |
| Production execution       | ✓  | ✗     | ✗       | ✓        | ✗         |
| Stock adjustment (manual)  | ✓  | ✗     | ✗*      | ✗        | ✗         |
| View stock movements       | ✓  | ✓     | ✓       | ✗        | ✗         |
| View stock summary         | ✓  | ✓     | ✓       | ✗        | ✓         |

* MANAGER hanya boleh jika nanti dibuat approval flow, bukan langsung.

## Product & Material

| Action                  | SU | OWNER | MANAGER | OPERATOR | SPECTATOR |
| ----------------------- | -- | ----- | ------- | -------- | --------- |
| Create product/material | ✓  | ✗     | ✗       | ✗        | ✗         |
| Edit operational fields | ✓  | ✗     | ✗       | ✓        | ✗         |
| Edit price / cost / tax | ✓  | ✓     | ✗       | ✗        | ✗         |
| Activate / deactivate   | ✓  | ✓     | ✗       | ✗        | ✗         |
| View product list       | ✓  | ✓     | ✓       | ✓        | ✓         |

## Reports

| Action                   | SU | OWNER | MANAGER | OPERATOR | SPECTATOR |
| ------------------------ | -- | ----- | ------- | -------- | --------- |
| View operational reports | ✓  | ✓     | ✓       | ✗        | ✗         |
| View financial reports   | ✓  | ✓     | ✗       | ✗        | ✗         |
| Export reports           | ✓  | ✓     | ✓       | ✗        | ✗         |

## User & System

| Action               | SU | OWNER | MANAGER | OPERATOR | SPECTATOR |
| -------------------- | -- | ----- | ------- | -------- | --------- |
| Create / edit users  | ✓  | ✓     | ✗       | ✗        | ✗         |
| Assign roles         | ✓  | ✓*    | ✗       | ✗        | ✗         |
| System configuration | ✓  | ✗     | ✗       | ✗        | ✗         |
| Action               | SU | OWNER | MANAGER | OPERATOR | SPECTATOR |
| -------------------- | -- | ----- | ------- | -------- | --------- |
| Create / edit users  | ✓  | ✓     | ✗       | ✗        | ✗         |
| Assign roles         | ✓  | ✓*    | ✗       | ✗        | ✗         |
| System configuration | ✓  | ✗     | ✗       | ✗        | ✗         |

* OWNER tidak boleh assign SU.
