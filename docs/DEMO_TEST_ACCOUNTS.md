# BloodLink Demo Test Accounts and Scenarios

These accounts are created by `database/seeds/demo_seed.sql` after importing the base schema and migration. They are for local testing only.

## Administrator

| Username | Password | Purpose |
| --- | --- | --- |
| `admin` | `Admin@123` | Admin dashboard and administration workflows |

## Hospital Staff

All hospital staff passwords are `Hospital@123`.

| Username | Staff member | Hospital | Hospital approval | Staff status | Test purpose |
| --- | --- | --- | --- | --- | --- |
| `square_staff` | Dr. Arman Hossain | Square Hospital Ltd. | Approved | Active | Standard approved hospital workflow |
| `dmc_staff` | Dr. Shamima Nasrin | Dhaka Medical College Hospital | Approved | Active | DMCH requests and fulfillment; request `104` is ready for testing |
| `evercare_staff` | Dr. Tariq Rahman | Evercare Hospital Dhaka | Approved | Active | Request `3`; confirm Nadia's accepted donation |
| `pending_staff` | Dr. Farida Akter | Chittagong General Hospital | Pending | Active | Verify pending hospitals cannot create/fulfill requests; admin can approve |
| `rejected_staff` | Dr. Mahmud Karim | North Bengal Medical Centre | Rejected | Active | Verify rejected-hospital behavior |
| `inactive_staff` | Dr. Retired Staff | Square Hospital Ltd. | Approved | Inactive | Inactive staff account case |

## Donors

All donor passwords are `Donor@123`. Account status is included because suspended accounts should not be treated as eligible matches.

| Username | Donor | Blood group | Email | Availability | Eligibility / account |
| --- | --- | --- | --- | --- | --- |
| `rahim_donor` | Rahim Ahmed | A+ | `rahim@example.com` | Available | Eligible |
| `karim_donor` | Karim Chowdhury | O- | `karim@example.com` | Available | Eligible; has a pending match invitation |
| `sarah_donor` | Sarah Khan | AB+ | `sarah@example.com` | Available | Eligible |
| `tanvir_donor` | Tanvir Islam | B+ | `tanvir@example.com` | Unavailable | Not eligible |
| `nusrat_donor` | Nusrat Jahan | O+ | `nusrat@example.com` | Available | Eligible |
| `zero_donor` | Farhan Kabir | A+ | `zero@example.com` | Available | Eligible; no seeded donation history |
| `nadia_bnegative` | Nadia Rahman | B- | `nadia@example.com` | Available | Eligible |
| `omar_abnegative` | Omar Hasan | AB- | `omar@example.com` | Available | Eligible |
| `lima_opositive` | Lima Akter | O+ | `lima@example.com` | Available | Not eligible; future eligibility date |
| `sakib_unavailable` | Sakib Hossain | B+ | `sakib@example.com` | Unavailable | Eligible |
| `mita_suspended` | Mita Sultana | AB+ | Available | Eligible; user account suspended, so login should be blocked |

## Seeded Workflow Cases

| Feature | Fixture(s) |
| --- | --- |
| Request statuses | `1` Pending, `2` Fulfilled, `3` Matching, `101` Partially fulfilled, `102` Cancelled, `103` Expired, `104` Pending for DMCH |
| DMCH fulfillment | Sign in as `dmc_staff` and open hospital request `104` (AB+ Plasma, 250 mL) |
| Donor-confirmed collection | Sign in as `evercare_staff` and open request `3`; Nadia (`nadia_bnegative`) has accepted and is awaiting collection confirmation |
| Donation confirmation | On request `3`, enter the actual collected component, volume, and collection time, then confirm only after collection and screening are complete; the app updates Nadia's eligibility/recognition, adds a blood bag to inventory, and attempts FEFO fulfillment |
| Request component progress | Request `3` detail shows completed donor-collected volume separately from inventory fulfillment; compatible available bags are issued by FEFO and update the fulfilled amount and request status |
| Closed donor invitations | When a request becomes fully fulfilled, outstanding invitations and not-yet-collected scheduled donations are cancelled and those donors are notified |
| Fulfillment test script | Request `1` contains 900 mL A+ Whole Blood and is intended for `tests/test_fulfillment_concurrency.php` |
| Blood bag statuses | Seed includes Available, Reserved, Issued, Expired, and Discarded bags; several available bags have near/future expiries for FEFO checks |
| Donor match statuses | `1` Notified, `101` Accepted, `102` Declined, `103` Expired, `104` Cancelled, `105` Suggested |
| Donation statuses | Nadia's donation `105` is scheduled/pending after accepting match `101`; seed also includes completed/passed, rejected/failed, and cancelled cases |
| Hospital approvals | Approved hospitals, a pending hospital, and a rejected hospital are seeded |
| Donor edge cases | Eligible, not eligible, unavailable, and suspended-account donors are seeded |

## Reset and Reload the Local Demo Database

**Warning:** the base schema script drops and recreates `bloodlink_db`, deleting the existing database contents. Back up anything you need first. From PowerShell at the repository root, the project-local XAMPP MySQL client can be used as follows:

```powershell
Get-Content database/BloodLink_finalsql.sql | & 'C:\xampp\mysql\bin\mysql.exe' -u root
Get-Content database/migrations/01_fix_fulfillment_user.sql | & 'C:\xampp\mysql\bin\mysql.exe' -u root bloodlink_db
Get-Content database/migrations/02_link_donations_to_matches.sql | & 'C:\xampp\mysql\bin\mysql.exe' -u root bloodlink_db
Get-Content database/migrations/03_add_rbc_donation_type.sql | & 'C:\xampp\mysql\bin\mysql.exe' -u root bloodlink_db
Get-Content database/migrations/04_create_donation_events.sql | & 'C:\xampp\mysql\bin\mysql.exe' -u root bloodlink_db
Get-Content database/seeds/demo_seed.sql | & 'C:\xampp\mysql\bin\mysql.exe' -u root bloodlink_db
```

The application defaults to MySQL user `root` with an empty password. Add `-p` to each MySQL command if your local MySQL installation requires a password.

The donor-confirmation integration test changes match `1` and its donation record. Reload the schema, migrations, and seed before running it again.
