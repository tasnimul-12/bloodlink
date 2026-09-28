# BloodLink Resolved Issues

- 2026-09-28: Added admin hospital-linked blood request creation with donor matching, audited screened-donation accession into capacity-checked FEFO inventory, and an admin donation-event scheduler with a public motivational events page.
- 2026-09-28: Preserved the requested component type for accepted donations, including RBC, reconciled legacy RBC matches previously recorded as whole blood, and made FEFO fulfillment allocate only the requested remaining volume while retaining excess volume in available inventory.
- 2026-09-28: Added hospital staff cancellation for scheduled matched donations. Cancellation informs the donor, closes the cancelled match, and searches for other eligible donors without re-notifying the cancelled donor.
- 2026-09-28: Prevented donors from accepting another match while a donation awaits hospital confirmation. Confirming one donation now cancels other active invitations and scheduled donations for that donor.
- 2026-09-28: Blocked direct HTTP access to internal project directories (`app`, `database`, `docs`, `routes`, `tests`, and `views`) and `.sql`/`.md` files through the root Apache rewrite rules.
- 2026-09-28: New hospital staff accounts now require administrator activation before sign-in. Added admin staff-access controls, restricted activation to staff of approved hospitals, revoke sessions after staff deactivation or facility suspension/rejection, and covered inactive registration/login in the RBAC test.
- 2026-09-28: Hospital-triggered donor matching now requires active staff at an approved facility and verifies request ownership in the matching service. Added a cross-hospital authorization regression assertion.
- 2026-09-28: Hospital blood requests now validate dates, enums, aligned line-item arrays, blood groups, components, and quantities before creating a request, preventing empty or partially invalid requests.
- 2026-09-28: Disabled visitor-facing PHP and database exception details while retaining server-side error logging.
- New donors were not automatically notified about compatible open blood requests. Matching now runs after registration and when a donor visits the dashboard.

## Pending Review

- Platelet donor compatibility currently shares the whole-blood/RBC rules. Validate the intended platelet policy against an approved transfusion-medicine guideline before changing it or using the feature for clinical decisions.
