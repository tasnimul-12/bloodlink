# BloodLink Resolved Issues

- 2026-09-28: Blocked direct HTTP access to internal project directories (`app`, `database`, `docs`, `routes`, `tests`, and `views`) and `.sql`/`.md` files through the root Apache rewrite rules.
- 2026-09-28: New hospital staff accounts now require administrator activation before sign-in. Added admin staff-access controls, restricted activation to staff of approved hospitals, revoke sessions after staff deactivation or facility suspension/rejection, and covered inactive registration/login in the RBAC test.
- 2026-09-28: Hospital-triggered donor matching now requires active staff at an approved facility and verifies request ownership in the matching service. Added a cross-hospital authorization regression assertion.
- 2026-09-28: Hospital blood requests now validate dates, enums, aligned line-item arrays, blood groups, components, and quantities before creating a request, preventing empty or partially invalid requests.
- 2026-09-28: Disabled visitor-facing PHP and database exception details while retaining server-side error logging.
- New donors were not automatically notified about compatible open blood requests. Matching now runs after registration and when a donor visits the dashboard.

## Pending Review

- Platelet donor compatibility currently shares the whole-blood/RBC rules. Validate the intended platelet policy against an approved transfusion-medicine guideline before changing it or using the feature for clinical decisions.
