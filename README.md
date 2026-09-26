# BloodLink

BloodLink is a PHP and MySQL blood bank and emergency blood coordination system. It connects donors, hospitals, and administrators through role-based portals for donor registration, blood requests, inventory management, matching, fulfillment, notifications, and audit logging.

## Requirements

- XAMPP with Apache, MySQL, PHP 8.0 or newer, and the PDO MySQL extension
- A browser
- PHP CLI for running the test scripts

The default local configuration expects:

```text
Database: bloodlink_db
Host: localhost
Port: 3306
Username: root
Password: (empty)
```

Update `app/config/config.php` before starting the application if your MySQL credentials differ.

## Installation

1. Place the repository in the XAMPP web root, normally `C:\xampp\htdocs\bloodlink`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Create a MySQL database named `bloodlink_db`.
4. Import the database files in this order:

	 ```text
	 database/BloodLink_finalsql.sql
	 database/migrations/01_fix_fulfillment_user.sql
	 database/seeds/demo_seed.sql
	 ```

	 The equivalent command-line workflow is:

	 ```bash
	 mysql -u root -p -e "CREATE DATABASE bloodlink_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
	 mysql -u root -p bloodlink_db < database/BloodLink_finalsql.sql
	 mysql -u root -p bloodlink_db < database/migrations/01_fix_fulfillment_user.sql
	 mysql -u root -p bloodlink_db < database/seeds/demo_seed.sql
	 ```

5. Open [http://localhost/bloodlink/](http://localhost/bloodlink/) in a browser. The application can also be opened directly at [http://localhost/bloodlink/public/](http://localhost/bloodlink/public/).

## Demo Accounts

The demo seed creates these accounts:

| Role | Username | Password |
| --- | --- | --- |
| Administrator | `admin` | `Admin@123` |
| Hospital staff | `square_staff` | `Hospital@123` |
| Donor | `rahim_donor` | `Donor@123` |

These credentials are for local development only. Change or remove seeded accounts before deploying anywhere public.

## Main Features

- Public blood-group compatibility information and system overview
- Donor registration, profile management, donation history, notifications, and match responses
- Hospital registration, blood requests, donor matching, request fulfillment, and hospital inventory
- Administrator dashboards, donor and hospital management, inventory transfers and discards, donation recording, reports, settings, and audit logs
- CSRF protection, session-based authentication, role-based access control, prepared database statements, FEFO inventory ordering, and expiry exclusion

## Project Structure

```text
app/
	config/       Application and database configuration
	controllers/  Request handlers for each portal
	core/         Router, request, response, session, view, and CSRF helpers
	middleware/   Authentication, guest, and role middleware
	services/     Authentication, matching, inventory, fulfillment, audit, and compatibility logic
database/       Schema, migration, seed, and verification SQL files
docs/           Database audit and development documentation
public/         Apache-facing front controller and static assets
routes/         Web route definitions
tests/          PHP integration and service tests
views/          Portal and shared layout templates
```

## Verification

Run the schema verification script in MySQL after importing the database:

```bash
mysql -u root -p bloodlink_db < database/verify_schema.sql
```

Run the PHP tests from the repository root after the database has been seeded:

```bash
php tests/test_rbac_and_auth.php
php tests/test_fefo_expiry.php
php tests/test_fulfillment_concurrency.php
```

The tests use the configured database and may create a temporary donor account. Run them against a development database only.

## Configuration Notes

- The default base URL is `/bloodlink`, matching an XAMPP installation under `htdocs`.
- Apache rewrite rules are provided in `.htaccess`; `public/index.php` is the front controller.
- Error display is enabled by default for development in `public/index.php`. Disable `display_errors` and use secure production credentials before deployment.
