# BloodLink — Master Development Plan

**Project:** BloodLink — Blood Bank & Emergency Blood Coordination System  
**Document:** Master Engineering & Implementation Plan  
**Target Environment:** PHP 8.2+ • MySQL 8.x / MariaDB 10.4+ • Apache (XAMPP) • Bootstrap 5  
**Version:** 1.0.0  

---

## 1. System Architecture

BloodLink is engineered as a modular Model-View-Controller (MVC) and service-oriented PHP application adhering to strict separation of concerns, 3NF relational data design, and server-enforced Role-Based Access Control (RBAC).

```text
BloodLink/
├── app/
│   ├── config/             # Database PDO credentials, constants, system defaults
│   ├── core/               # Router, Request, Response, Controller, View, Session, CSRF
│   ├── middleware/         # AuthMiddleware, RoleMiddleware, GuestMiddleware
│   ├── models/             # User, Donor, Hospital, BloodBag, Request, Donation, etc.
│   ├── services/           # AuthService, InventoryService, FulfillmentService, MatchingService, AuditService
│   └── validators/         # Input validation rules (Registration, Requests, Inventory)
├── public/                 # Web root directory (Front Controller)
│   ├── index.php           # Routing entry point
│   ├── assets/             # CSS (custom + Bootstrap 5), JS, icons, SVG illustrations
├── routes/                 # Web and API route definitions
│   └── web.php
├── views/                  # PHP server-rendered semantic templates
│   ├── layouts/            # header.php, navbar.php, sidebar.php, footer.php
│   ├── public/             # Landing page, about, blood compatibility guide, login, register
│   ├── donor/              # Dashboard, profile, donation history, match alerts
│   ├── hospital/           # Dashboard, request creation, request tracking, inventory lookup
│   ├── admin/              # Dashboard, hospital approvals, inventory management, donations, reports, audit logs
│   └── errors/             # 403, 404, 500 error pages
├── database/
│   ├── BloodLink_finalsql.sql # Original source schema
│   ├── verify_schema.sql      # Schema integrity and constraint verification
│   ├── migrations/            # Versioned SQL migrations (e.g., fulfillment actor flexibility)
│   └── seeds/                 # Demo seeds with securely hashed passwords
├── docs/                   # Academic documentation, audit, DBMS mapping, API reference, test reports
└── tests/                  # Verification and simulation test scripts (concurrency, rollback, FEFO)
```

---

## 2. Database Audit Summary & Schema Enhancements

Based on our thorough audit in `docs/DATABASE_AUDIT.md`:
1. **Schema Preservation:** Retain the full 20-table relational schema from `BloodLink_finalsql.sql` (roles, users, blood_groups, donors, hospitals, hospital_staff, donations, storage_locations, blood_bags, inventory_movements, blood_requests, request_items, donor_matches, notifications, fulfillments, fulfillment_items, recognition_levels, donor_recognition, audit_logs, system_settings).
2. **Indivisible Blood Bags:** Enforce atomic bag issuance. A bag cannot be split into arbitrary fractional milliliters across different hospitals. Partial fulfillment is tracked at the request level (e.g. 1 out of 2 requested bags fulfilled).
3. **Fulfillment User Actor:** Provide a migration `database/migrations/01_fix_fulfillment_user.sql` that updates `fulfillments.fulfilled_by` to reference `users(user_id)` or adds a fallback mapping so both central Administrators and Hospital Staff can execute and be audited on fulfillments.
4. **Audit Context Trigger:** Support setting `@app_user_id` so the `trg_blood_bag_status_audit` trigger accurately captures the acting user ID.
5. **Seed Data:** Provide complete demo seeds with bcrypt-hashed passwords for instant demonstration.

---

## 3. Core Business Rules

1. **RBAC:** Three primary roles: `DONOR`, `HOSPITAL_STAFF`, `ADMIN`. No route or API endpoint can be accessed without passing server-side role validation.
2. **Hospital Approval Barrier:** Newly registered hospital staff or hospitals start in `PENDING` status. Staff cannot request blood or inspect inventory until an Administrator reviews and approves the hospital (`approval_status = 'APPROVED'`).
3. **Donor Eligibility Safety Interval:** A donor's `next_eligible_date` is dynamically calculated based on their last `COMPLETED` donation plus `system_settings.DONATION_MIN_INTERVAL_DAYS` (default: 90 days).
4. **FEFO Inventory Policy:** When allocating inventory, blood bags are strictly selected ordered by `expiry_date ASC, blood_bag_id ASC`. Expired (`expiry_date < CURRENT_DATE`) or non-available bags are excluded.
5. **Atomic Fulfillment:** Fulfillments are enclosed in ACID database transactions (`BEGIN` ... `COMMIT`/`ROLLBACK`). Candidate blood bags are locked using `SELECT ... FOR UPDATE` to prevent double-allocation race conditions.
6. **Privacy Boundary:** Donor Personally Identifiable Information (phone, address, email) is shielded from hospitals and public views.
7. **Recognition Engine:** Donors unlock recognition tiers (`Bronze` = 1, `Silver` = 3, `Gold` = 5, `Platinum` = 10) solely through completed, medically screened donations.

---

## 4. Modules & Development Phases

### Phase 1: Workspace Analysis & Audit (Current Phase)
- Review proposal PDF and `BloodLink_finalsql.sql`.
- Produce `docs/DATABASE_AUDIT.md` and `docs/DEVELOPMENT_PLAN.md`.
- Establish local environment baseline (PHP 8.2 + MariaDB/MySQL in XAMPP verified).

### Phase 2: Database Initialization, Migrations & Seeds
- Execute `BloodLink_finalsql.sql` on the MySQL database (`bloodlink_db`).
- Apply migration for fulfillment flexibility.
- Create and execute `database/seeds/demo_seed.sql` with hashed passwords for demo accounts.
- Create and execute `database/verify_schema.sql` to validate tables, foreign keys, views, stored procedures, and triggers.

### Phase 3: Core MVC Framework & Security Engine
- Setup PDO connection wrapper with UTF-8 character encoding, error mode exception, and prepared statements.
- Build Session management, CSRF protection token generator/verifier, and password hashing helpers.
- Implement Authentication & RBAC middleware.
- Build Central Audit Logging service.

### Phase 4: Public Portal & Authentication Workflows
- **Public Landing Page:** Overview, how BloodLink works, blood group compatibility chart, emergency workflow explanation.
- **Registration Flow:**
  - Donor registration (demographics, blood group, city, emergency availability).
  - Hospital Staff registration (hospital selection or hospital registration, designation).
- **Login & Logout:** Secure authentication, session regeneration, and role-based redirecting (`/donor/dashboard`, `/hospital/dashboard`, `/admin/dashboard`).

### Phase 5: Role-Based Portals & Dashboards
- **Donor Portal:**
  - Personal profile and availability toggle.
  - Donation history and next eligible donation date badge.
  - Recognition tier badge and progress bar.
  - Emergency notification center with Accept/Decline action buttons.
- **Hospital Portal:**
  - Status alert (if pending approval).
  - Blood request creation form (urgency, required date/time, multi-item blood groups & components).
  - Request history and status tracking (`PENDING`, `MATCHING`, `PARTIALLY_FULFILLED`, `FULFILLED`, `CANCELLED`).
  - Permitted regional inventory viewer (anonymized, no donor PII).
  - Fulfillment trigger for available items.
- **Administrator Portal:**
  - High-level KPIs (total donors, active hospitals, inventory by group/component, critical requests).
  - Hospital verification management (Approve / Reject / Suspend).
  - Inventory management (FEFO overview, add blood bag, view near-expiry units, discard expired bags).
  - Donation management (record donations, screening status, auto-update donor eligibility).
  - Storage location transfers with transactional audit logging.
  - Audit trail viewer with JSON old/new diff inspection.
  - System configuration editor (`system_settings`).

### Phase 6: Emergency Matching & ACID Transactional Fulfillment
- **Matching Engine:** Triggered when inventory is insufficient. Evaluates compatible blood groups by component, donor eligibility (`vw_eligible_donors`), city proximity, and active availability. Generates `donor_matches` and in-system `notifications`.
- **Fulfillment Engine:**
  1. Opens transaction (`$pdo->beginTransaction()`).
  2. Evaluates requested items against available inventory via FEFO.
  3. Executes `SELECT ... FOR UPDATE` on candidate `blood_bags`.
  4. Inserts `fulfillments` and `fulfillment_items`.
  5. Updates bag statuses to `ISSUED` (or `RESERVED`).
  6. Updates `request_items.quantity_fulfilled`.
  7. Updates `blood_requests.status`.
  8. Inserts `inventory_movements`.
  9. Inserts `audit_logs`.
  10. Commits (`$pdo->commit()`) or rolls back on any exception.

### Phase 7: Academic DBMS Feature Demonstration & Reporting
- Comprehensive DBMS mapping document (`docs/DBMS_FEATURE_MAPPING.md`):
  - DML CRUD operations
  - Aggregate queries (`COUNT`, `SUM`, `AVG`, `MIN`, `MAX`) with `GROUP BY` and `HAVING`
  - Inner and Left Joins (e.g. donors with zero donation history)
  - Nested and correlated subqueries
  - Database views (`vw_available_inventory`, `vw_eligible_donors`)
  - Stored procedures and triggers
  - ACID transaction rollback demonstrations
- Test Report (`docs/TEST_REPORT.md`) covering authentication, RBAC, inventory, and concurrency.

---

## 5. Testing & Verification Strategy

1. **Schema & Integrity Testing:** Automated SQL verification script checking all 20 tables, 20+ foreign keys, constraints, and views.
2. **Authentication & RBAC Testing:** Positive and negative test cases for login, session timeout, CSRF invalidation, and role escalation prevention.
3. **Inventory & FEFO Testing:** Verify earliest expiring units are selected first; verify expired units are excluded.
4. **Transaction & Concurrency Simulation:** Test concurrent attempts to fulfill the same blood bag to verify `SELECT ... FOR UPDATE` prevents double allocation.
5. **Rollback Verification:** Simulate fulfillment failure (e.g. deliberate constraint violation) and verify zero orphan records are inserted.

---

## 6. Known Academic Limitations & Boundaries

1. **Medical Screening:** This system is an academic DBMS coordination tool. It does not replace clinical laboratory screening, cross-matching, or infectious disease testing.
2. **Proximity:** In the absence of latitude/longitude coordinates in the 3NF schema, proximity is determined by city-level matching and priority rules.
3. **Notifications:** Notifications are delivered in-system via the notifications table and user dashboards. Real-world SMS/Email gateways are stubbed for academic portability.
