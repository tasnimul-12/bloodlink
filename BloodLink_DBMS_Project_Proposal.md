PROJECT PROPOSAL
Academic Submission for Database Management Systems (DBMS)

| Project Name: BloodLink | Document Type: DBMS Project Proposal |
| --- | --- |
| Target Stack: HTML, JS, PHP, MySQL | Development Approach: Agile Iterative Lifecycle |

1. Project Title
BloodLink: Blood Bank & Emergency Blood Coordination System
BloodLink is a comprehensive, centralized database-driven application designed to model and manage the complete blood coordination lifecycle—from donor registration and donation recording to blood bag tracking, emergency hospital requesting, transactional fulfillment, and administrative audit logging.
2. Type of Project
Role-Based Database Management System (DBMS) & Web Application
This project is a multi-tier, relational database-driven web application featuring granular Role-Based Access Control (RBAC). It integrates strict transactional integrity, normalized database architecture (3NF), structured business logic, and automated auditing.
3. Objectives
The main objective of BloodLink is to transition emergency blood management from fragmented spreadsheets to a centralized, reliable relational database model. Specific goals include:
- Centralized Lifecycle Tracking: Model the end-to-end blood coordination chain (Donor → Donation → Blood Bag → Inventory → Request → Matching → Fulfillment → Audit).
- FEFO Inventory Management: Implement First-Expire, First-Out (FEFO) automated selection logic to minimize blood wastage and ensure near-expiry alerts.
- Transactional Consistency: Ensure ACID-compliant reservation and fulfillment operations using MySQL database transactions (BEGIN, COMMIT, ROLLBACK) to eliminate double-allocation.
- Rule-Based Emergency Donor Matching: Dynamically identify and notify eligible candidate donors when physical inventory falls below emergency request thresholds.
- Comprehensive Audit & Reporting: Maintain immutable historical movement logs and system audit trails for administrative review and regulatory compliance.
4. Problem Statement
During medical emergencies, timely access to compatible blood units is critical to saving lives. However, current blood management in many regional healthcare setups suffers from severe operational inefficiencies:
- Inventory Fragmentation & Opacity: Hospitals lack real-time visibility into available blood stocks across nearby facilities, leading to life-threatening delays in emergency sourcing.
- Data Inconsistency & Double-Allocation: Without strict transactional locking, multiple hospitals frequently request and reserve the exact same limited blood units simultaneously.
- Inadequate Expiry Management: Aggregated inventory storage ('O+ = 20 units') fails to track individual unit collection and expiry dates, leading to high spoilage rates.
- Manual Sourcing Overhead: When stock is depleted, hospital staff rely on manual phone calls to contact potential donors without dynamic verification of donor eligibility rules (e.g., 90-day donation interval).
Core Solution: BloodLink addresses these challenges by replacing static spreadsheets with a centralized relational database that tracks individual blood bags, enforces FEFO prioritization, handles matching dynamically, and guarantees data integrity using multi-step transactions.
5. Deliverables
The expected outputs and software modules delivered upon completion of the project include:
- Centralized Relational Schema: Fully normalized MySQL database (3NF) containing 18 relational tables with enforced primary keys, foreign keys, CHECK constraints, and indexes.
- Individual Blood Bag Sku Tracking: Granular tracking system assigning unique Bag IDs with collection dates, expiry dates, storage locations, and operational statuses (AVAILABLE, RESERVED, ISSUED, EXPIRED, DISCARDED).
- Role-Based Web Portals: Responsive web UI with tailored dashboards for Donors, Approved Hospital Staff, and System Administrators.
- Rule-Based Sourcing Engine: Automated matching mechanism evaluating blood group compatibility, inventory stock, donor eligibility windows, and proximity/priority ranking.
- Transactional Fulfillment Workflow: Robust PHP/MySQL backend transaction module supporting atomic reservations, issuing, auto-rollbacks on concurrency failure, and inventory movement logs.
- Administrative Analytics & Auditing: SQL-driven reporting suite providing inventory alerts, demand forecasts, near-expiry alerts, and complete system audit logs.
6. Methodology
The project follows an Agile Iterative Development Lifecycle, allowing incremental database design, continuous testing, and refined UI integration across 7 structured phases:

| Phase & Focus Area | Key Operational Tasks |
| --- | --- |
| Phase 1: Requirements & Planning | Freeze scope, define user roles, establish 25+ business rules, and formalize hardware/software specs. |
| Phase 2: Database Modeling | Construct Use-Case and ER diagrams, define cardinalities, map schema, and normalize to 3NF. |
| Phase 3: Database Implementation | Execute MySQL schema scripts, create Foreign Key constraints, indexes, views, and seed sample data. |
| Phase 4: Backend Logic & API | Develop PHP controllers, database connection pools, RBAC authorization, and transactional queries. |
| Phase 5: Frontend Development | Build HTML5, JavaScript, and CSS interfaces for Donor, Hospital, and Admin dashboards. |
| Phase 6: Integration & Verification | Execute multi-user concurrency testing, transaction rollback validation, and expired unit isolation. |
| Phase 7: Documentation & Review | Compile SRS, final SQL collection, test logs, ERD documentation, and viva presentation slides. |

7. Project Overview & System Functionality
BloodLink provides discrete functional modules tailored to the three primary system roles: Donor, Hospital Staff, and System Administrator.
7.1 Donor Functionality
- Profile & Availability Management: Register account, maintain contact details, blood group, location, and toggle emergency donor availability.
- Donation History Tracking: View past donation events and transparently check next eligible donation date calculated dynamically based on safety intervals.
- In-System Emergency Alerts: Receive alerts when compatible local blood requests lack inventory stock; accept or decline participation directly.
- Impact & Recognition Tiering: Track total successful donations and unlock project recognition badges (Bronze, Silver, Gold, Platinum).
7.2 Hospital Staff Functionality
- Verified Account Operations: Access platform functionalities upon administrative approval of institutional credentials.
- Blood Request Creation: Submit blood requests specifying required blood group, component type, quantity, urgency level (CRITICAL, URGENT, NORMAL), and required timeline.
- Real-time Request Tracking: Monitor real-time status of submitted requests (PENDING, MATCHING, PARTIALLY_FULFILLED, FULFILLED, CANCELLED).
- Inventory Sourcing Visibility: Check available regional stock without exposing private donor information.
7.3 Administrator Functionality
- Hospital Registration Verification: Review pending hospital requests; activate or reject institutional accounts.
- Centralized Inventory Sourcing: Monitor overall inventory, handle near-expiry stock alerts, and authorize inter-facility inventory reallocations.
- Audit Logging & Reporting: Access immutable audit trails tracking all system transactions, stock movements, and administrative overrides.
8. Scope of the Project
8.1 What the Project INCLUDES (In-Scope)
- End-to-End Coordination: Centralized role-based database connecting donors, hospitals, and administrators.
- Granular Unit Tracking: Tracking individual blood bags with collection date, expiry date, status, and FEFO prioritization.
- Rule-Based Donor Sourcing: Algorithmic matching based on blood compatibility, eligibility intervals, and geographic availability.
- ACID Concurrency & Transactions: Secure reservation and fulfillment workflows preventing duplicate unit allocations.
- Audit & Inventory Reallocation: Tracking inventory transfers between locations with reason logs and complete system audit history.
8.2 What the Project EXCLUDES (Out-of-Scope)
- Clinical & Diagnostic Testing: System does not perform lab testing or medical eligibility diagnostics.
- Public Contact Sourcing: Donor personal phone numbers/addresses are strictly protected and never publicly exposed.
- Gimmicky Non-DBMS Features: Excludes AI machine learning models, blockchain ledger, payment gateways, and live GPS tracking to maintain focused emphasis on core DBMS rigor.
9. Technology Stack
The technical stack is selected specifically to maintain focus on database management principles while providing a responsive web interface:

| Layer | Technology / Tool | Technical Purpose in Project |
| --- | --- | --- |
| Frontend | HTML5, CSS3, JavaScript, Bootstrap | Build responsive UI layout, AJAX asynchronous requests, and dynamic form validations. |
| Backend | PHP (PHP 8.x) | Process business logic, handle session-based RBAC authentication, and communicate with MySQL database. |
| Database | MySQL Database | Provide relational storage, enforce constraints (3NF), handle views, indexes, and ACID transactions. |
| Local Server | XAMPP (Apache & MySQL) | Provide cross-platform local development server environment. |
| Tools & VCS | VS Code, Git & GitHub | Code editing, version control, schema SQL file management, and project team collaboration. |

10. SQL Coverage & Application Feature Mapping
To satisfy the academic requirements of the DBMS project curriculum, all major SQL concepts are directly mapped to concrete application workflows within BloodLink:

| SQL Category | Academic Requirement | Concrete BloodLink Feature Implementation |
| --- | --- | --- |
| DML Operations | SELECT, INSERT, UPDATE, DELETE | Standard CRUD operations across all modules: user registration (INSERT), profile update (UPDATE), soft-deleting/discarding expired blood bags (UPDATE/DELETE), and dashboard lookups (SELECT). |
| Aggregation | At least 2 aggregate functions (COUNT, SUM, AVG, MIN, MAX) with GROUP BY and HAVING | 1. Calculating total and available blood units grouped by blood type using COUNT() and SUM().<br>2. Admin reports calculating hospital monthly consumption using SUM() and filter out low-volume requests using HAVING SUM(quantity) > 10. |
| Joins | At least 2 types of JOIN (e.g., INNER JOIN + LEFT JOIN) | 1. INNER JOIN connecting blood_requests, hospitals, and request_items for hospital dashboards.<br>2. LEFT JOIN connecting donors with donations to include donors with zero donation history in community statistics. |
| Subqueries | At least one correlated or nested subquery | Correlated subquery in candidate donor matching: Sourcing donors whose blood group is compatible AND whose last donation date is older than 90 days relative to current request date. |
| Views | At least one reusable VIEW used in the application | Create reusable database VIEW `vw_available_inventory` filtering non-expired, unreserved blood bags for rapid FEFO query processing across hospital portals. |
| Transactions | Multi-step operations wrapped in BEGIN / COMMIT / ROLLBACK | Emergency Request Fulfillment: BEGIN transaction → Lock candidate blood bags → Insert fulfillment records → Update bag status to 'RESERVED' → Update request status → COMMIT (or ROLLBACK on concurrency failure). |

11. Risk & Dependencies
The potential risks and dependencies associated with the project development and deployment include:
- Concurrency & Race Conditions: Risk of simultaneous requests attempting to reserve identical blood units. Mitigated via strict database transactions and row-level locking.
- Data Privacy & Security: Risk of unauthorized exposure of donor PII. Mitigated by restricting contact details to system-mediated notifications and RBAC controls.
- User Adoption & Response Dependency: Emergency matching relies on prompt donor responses. Mitigated by maintaining clear inventory FEFO buffer stocks.
- Medical Eligibility Variations: External health regulations may alter eligibility intervals. Handled by modeling eligibility intervals as configurable parameters in database settings.
Project Team
We the members from the Department of Computer Science and Engineering (CSE) have successfully collaborated on the conceptualization, research, and development of this project proposal.
Team Members Overview

| Name | Role | Student ID | Department |
| --- | --- | --- | --- |
| Tasnimul Hasan | Project Leader | 0112410414 | Department of CSE |
| Mst Sobrun Jamil Trisha | Team Member | 0112430536 | Department of CSE |
| Md Fahim Ashhab | Team Member | 0112230975 | Department of CSE |

Individual Profiles
Tasnimul Hasan (Project Leader)
ID: 0112410414
Department: Computer Science and Engineering (CSE)
Role Description: Oversees overall project coordination, architecture design, and milestone tracking to ensure the proposal's objectives are successfully met.
Mst Sobrun Jamil Trisha
ID: 0112430536
Department: Computer Science and Engineering (CSE)
Role Description: Contributes to technical research, documentation, and system design, ensuring thorough analysis and structured planning for the project.
Md Fahim Ashhab
ID: 0112230975
Department: Computer Science and Engineering (CSE)
Role Description: Assists in core implementation planning, requirements gathering, and technical feasibility assessments for the proposed system.
