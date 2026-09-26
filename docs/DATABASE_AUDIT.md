# BloodLink — Comprehensive Database Audit Report

**System Name:** BloodLink — Blood Bank & Emergency Blood Coordination System  
**Author:** Antigravity Senior Full-Stack & Database Engineering Team  
**Date:** September 2026  
**DBMS Target:** MySQL 8.x / MariaDB 10.4+ (XAMPP Environment)  
**Primary Sources:** `BloodLink_Project_Proposal_Presentation_pdf main.pdf` & `BloodLink_finalsql.sql`

---

## 1. Executive Summary

This audit assesses the alignment between the **BloodLink Project Proposal Presentation** (18 slides) and the provided database implementation script **`BloodLink_finalsql.sql`** (20 tables, 2 views, 1 stored procedure, 1 trigger).

The existing schema is fundamentally well-structured and normalized to 3NF, utilizing InnoDB engine, explicit foreign keys, check constraints, and composite indexes. However, several operational and logical gaps were identified that must be resolved to ensure genuine transactional integrity, concurrency safety, and a seamless university DBMS demonstration.

---

## 2. Table-by-Table Architecture Audit

| Table # | Entity Name | Primary Key | Foreign Keys | Engine | 3NF Status | Operational Findings & Notes |
|:---:|:---|:---|:---|:---:|:---:|:---|
| 1 | `roles` | `role_id` | None | InnoDB | Yes | Seeded with `ADMIN`, `DONOR`, `HOSPITAL_STAFF`. Clean lookup table. |
| 2 | `users` | `user_id` | `role_id` -> `roles` | InnoDB | Yes | Supports `account_status` (`ACTIVE`, `INACTIVE`, `SUSPENDED`, `LOCKED`). Passwords stored in `password_hash` (255 chars for bcrypt/Argon2). |
| 3 | `blood_groups` | `blood_group_id` | None | InnoDB | Yes | Contains 8 standard groups (`A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, `O+`, `O-`). Enforces unique `(abo_type, rh_factor)`. |
| 4 | `donors` | `donor_id` | `user_id`, `blood_group_id` | InnoDB | Yes | 1-to-1 with `users`. Contains availability, eligibility, next eligible date, address, and city. |
| 5 | `hospitals` | `hospital_id` | `approved_by` -> `users` | InnoDB | Yes | Verification workflow supported via `approval_status` (`PENDING`, `APPROVED`, `REJECTED`, `SUSPENDED`). |
| 6 | `hospital_staff` | `staff_id` | `hospital_id`, `user_id` | InnoDB | Yes | Maps staff users to hospital facilities. Multi-tenant isolation anchor. |
| 7 | `donations` | `donation_id` | `donor_id` | InnoDB | Yes | Enforces `quantity_ml > 0`. Dual status tracking: `screening_status` (`PENDING`, `PASSED`, `FAILED`) and `donation_status` (`SCHEDULED`, `COMPLETED`, `CANCELLED`, `REJECTED`). |
| 8 | `storage_locations` | `storage_location_id` | None | InnoDB | Yes | Tracks physical locations (refrigerators, freezers, agitators) with temperature thresholds and capacity. |
| 9 | `blood_bags` | `blood_bag_id` | `donation_id`, `blood_group_id`, `storage_location_id` | InnoDB | Yes | Core inventory unit. Tracks collection, expiry, quantity, and status (`AVAILABLE`, `RESERVED`, `ISSUED`, `EXPIRED`, `DISCARDED`). Check constraint `expiry_date > collection_date`. |
| 10 | `inventory_movements` | `movement_id` | `blood_bag_id`, `from_location_id`, `to_location_id`, `performed_by` | InnoDB | Yes | Audit ledger for bag lifecycle movements (`RECEIVED`, `TRANSFERRED`, `RESERVED`, `ISSUED`, `DISCARDED`). |
| 11 | `blood_requests` | `request_id` | `hospital_id`, `requested_by` | InnoDB | Yes | Tracks hospital blood requests with urgency, status (`PENDING`, `MATCHING`, `PARTIALLY_FULFILLED`, `FULFILLED`, `CANCELLED`, `EXPIRED`), and required schedule. |
| 12 | `request_items` | `request_item_id` | `request_id`, `blood_group_id` | InnoDB | Yes | Line items per request by component type. Check constraint enforces `0 <= quantity_fulfilled <= quantity_requested`. |
| 13 | `donor_matches` | `match_id` | `request_item_id`, `donor_id` | InnoDB | Yes | Sourcing candidates. Unique constraint `(request_item_id, donor_id)` prevents duplicate notifications. Check constraint on `match_score` (0–100). |
| 14 | `notifications` | `notification_id` | `user_id`, `related_match_id` | InnoDB | Yes | Role-based in-system notification center. Supports unread tracking and match linking. |
| 15 | `fulfillments` | `fulfillment_id` | `request_id`, `fulfilled_by` | InnoDB | Yes | Transaction header for blood allocation. `fulfilled_by` references `hospital_staff(staff_id)`. |
| 16 | `fulfillment_items` | `fulfillment_item_id` | `fulfillment_id`, `request_item_id`, `blood_bag_id` | InnoDB | Yes | Line-item allocations binding specific blood bags to requested items. Enforces `quantity_issued > 0`. |
| 17 | `recognition_levels` | `recognition_level_id` | None | InnoDB | Yes | Gamification tiers (`Bronze` = 1, `Silver` = 3, `Gold` = 5, `Platinum` = 10). |
| 18 | `donor_recognition` | `donor_recognition_id` | `donor_id`, `recognition_level_id` | InnoDB | Yes | Unique constraint `(donor_id, recognition_level_id)` ensures a donor receives each tier award exactly once. |
| 19 | `audit_logs` | `audit_id` | `user_id` (nullable) | InnoDB | Yes | System-wide audit trail with JSON snapshot payloads for `old_value` and `new_value`. |
| 20 | `system_settings` | `setting_id` | `updated_by` -> `users` | InnoDB | Yes | Configurable parameters: `MATCHING_RADIUS_KM`, `FEFO_ENABLED`, `DONATION_MIN_INTERVAL_DAYS`. |

---

## 3. Discrepancies Between Proposal and SQL Schema

1. **Table Count Difference:**
   - *Proposal Slide 12:* States "18 relational tables".
   - *SQL File:* Contains **20 tables**.
   - *Analysis:* The SQL file separates recognition tiers into two tables (`recognition_levels` and `donor_recognition`) and adds a standalone `system_settings` table. This is superior design (proper 3NF normalization without hardcoding tiers). We retain the 20 tables.

2. **Proximity and Distance Calculation:**
   - *Proposal Slide 12:* Mentions "Rule-Based Sourcing Engine: Evaluate blood compatibility, inventory availability, donor eligibility, and proximity/priority".
   - *SQL Schema:* `system_settings` defines `MATCHING_RADIUS_KM = '25'`. However, `donors` and `hospitals` tables store only `city` and `address` (VARCHAR) without latitude/longitude coordinates.
   - *Analysis:* As mandated by the university guidelines, we will not fake GPS coordinates. Instead, proximity matching uses an exact city match with priority scoring (100 points for matching city, eligibility, and availability).

3. **Absence of Blood-Group Compatibility Table:**
   - *Proposal:* Stresses rule-based compatibility matching across component types (Red Blood Cells vs Plasma vs Platelets).
   - *SQL Schema:* Lacks a compatibility matrix table.
   - *Analysis:* Component-specific compatibility rules (e.g., O- is universal donor for RBC; AB+ is universal recipient for RBC; AB is universal donor for Plasma) will be implemented via a robust PHP business service (`CompatibilityService`) and clearly documented in the user documentation/help center.

---

## 4. Critical Logical, Operational, and Concurrency Findings

### A. Blood Bag Quantity Indivisibility vs Partial Fulfillment
* **Issue:** `blood_bags` has `quantity_ml DECIMAL(7,2)`. `request_items` has `quantity_requested` and `quantity_fulfilled`.
* **Medical / Biological Reality:** Blood bags are sealed, sterile, single-use containers. A 450 mL whole-blood bag cannot be opened, partially drained to 150 mL for Hospital A, and stored at 300 mL for Hospital B.
* **Resolution:** Bags are **indivisible atomic units**. A bag is allocated in its entirety (`quantity_issued = blood_bag.quantity_ml`). Partial fulfillment occurs when a request demands multiple bags (e.g. 900 mL = 2 bags) but only 1 bag (450 mL) is currently available in stock. The request status updates to `PARTIALLY_FULFILLED`.

### B. Fulfillment Actor Authorization (`fulfillments.fulfilled_by`)
* **Issue:** `fulfillments.fulfilled_by` references `hospital_staff(staff_id)` with `ON DELETE RESTRICT`.
* **Workflow Analysis:** If an Administrator or Central Blood Bank Officer authorizes and issues blood bags from central storage, their `user_id` does not exist in `hospital_staff`. If they attempt to insert a fulfillment, MySQL throws an FK constraint error (`fk_fulfillments_staff`).
* **Resolution:**
  - Option 1 (Zero schema change): Create a designated Central Blood Bank Staff record linked to admin actions, or have hospital staff self-confirm inventory allocation for their approved requests.
  - Option 2 (Architectural enhancement migration): Alter `fulfillments.fulfilled_by` to reference `users(user_id)`.
  - *Recommendation:* We provide a clean migration script `database/migrations/01_fix_fulfillment_user.sql` that allows `fulfilled_by` to reference `users(user_id)` so both Admin and Hospital Staff can be recorded transparently, while preserving backwards compatibility.

### C. Concurrency in `sp_find_fefo_inventory`
* **Issue:** The existing stored procedure in `BloodLink_finalsql.sql` selects all available bags for a blood group with `FOR UPDATE` without a `LIMIT` clause and ignores the input parameter `p_required_ml`.
* **Concurrency Risk:** If there are 50 available bags of O+ blood, calling this procedure locks all 50 rows. A second hospital requesting O+ at the same time is forced to wait on lock release, degrading throughput.
* **Resolution:** In our transactional fulfillment service, we execute deterministic row-level locking (`SELECT ... FOR UPDATE`) ordered by `expiry_date ASC, blood_bag_id ASC` limited to the exact number of bags required to meet the request.

### D. Audit Trigger and Session Context
* **Issue:** Trigger `trg_blood_bag_status_audit` sets `user_id = NULL` because MySQL triggers do not inherently know the active PHP session user.
* **Resolution:** The PHP application sets a MySQL session variable (`SET @app_user_id = :user_id;`) upon executing state changes, allowing triggers or PHP audit services to record the exact authenticated user ID.

### E. Seed Data Gaps
* **Issue:** `BloodLink_finalsql.sql` contains seed data for roles, blood groups, recognition tiers, and system settings, but **zero demo users, hospitals, donors, storage locations, or blood bags**.
* **Resolution:** We must build a comprehensive, realistic seed file (`database/seeds/demo_seed.sql`) containing:
  - Securely hashed passwords using PHP `password_hash()` (e.g. `Admin@123`, `Hospital@123`, `Donor@123`).
  - Realistic multi-city data (Dhaka, Chittagong, Sylhet, etc.).
  - Active and near-expiry blood bags for FEFO demonstration.
  - Complete, pending, and partial requests to test all status transitions.

---

## 5. Security & Privacy Audit

1. **Donor PII Protection (HIPAA / GDPR Best Practice):**
   - Donors' phone numbers, dates of birth, and home addresses must **never** be exposed in public inventory queries or to hospital staff browsing blood stocks.
   - Hospital staff may only see the blood group, component, expiry date, and storage location.
   - When emergency donor matches are generated, the hospital views only anonymized match statistics (e.g., "3 compatible donors notified"), not private donor phone numbers, until a donation is formally scheduled.

2. **Access Control (RBAC):**
   - Server-side role checks must gate all routes:
     - `ADMIN`: Full access (inventory, donations, hospitals, users, settings, audit logs).
     - `HOSPITAL_STAFF`: Restricted strictly to their own hospital's requests and public inventory availability. Forbidden from touching other hospitals' requests or admin controls.
     - `DONOR`: Restricted strictly to their own profile, donation history, recognition, and notifications.

3. **SQL Injection Defense:**
   - All dynamic input must pass through PDO prepared statements with strongly-typed parameters. No raw string concatenation in queries.

4. **CSRF & XSS Prevention:**
   - Anti-CSRF session tokens on all POST/PUT forms.
   - HTML output escaping (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`) across all views.

---

## 6. Audit Conclusion & Action Items

The database schema is 95% production-ready for an academic DBMS project. With the addition of:
1. Indivisible bag allocation rules in the fulfillment engine,
2. An optional FK improvement migration for `fulfillments.fulfilled_by`,
3. A comprehensive demo seed script with bcrypt passwords, and
4. Verification queries in `database/verify_schema.sql`,

the BloodLink database will provide a rock-solid, demonstrable relational foundation.
