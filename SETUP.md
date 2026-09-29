# Setup

Installation walkthrough for **Circuit Cart** (CodeIgniter 3 + MySQL). For a feature overview see [README.md](README.md); for the test suite see [TESTING.md](TESTING.md).

## Requirements

- PHP >= 7.2 (developed on PHP 8.1)
- MySQL / MariaDB
- XAMPP (or any Apache + PHP + MySQL stack)

## Installation

1. Clone the repo into your web root (e.g. `C:/xampp/htdocs/ci3`).
2. Install dependencies:
   ```bash
   composer install
   ```
3. Create a database named `ci3_db` (or edit `application/config/database.php`) and create the `tech_parts` table:
   ```sql
   CREATE TABLE tech_parts (
       part_id        INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       sku            VARCHAR(32) NOT NULL,
       part_name      VARCHAR(128) NOT NULL,
       device_type    VARCHAR(64) NOT NULL,
       category       VARCHAR(64) NOT NULL,
       cost_price     DECIMAL(10,2) NOT NULL,
       selling_price  DECIMAL(10,2) NOT NULL,
       stock_quantity INT NOT NULL DEFAULT 0,
       is_active      TINYINT(1) NOT NULL DEFAULT 1
   );
   ```
4. Add the email-verification flag to the `tbl_users` table:
   ```sql
   ALTER TABLE tbl_users ADD COLUMN verified_email TINYINT(1) NOT NULL DEFAULT 0 AFTER email;
   ```
5. Create the repair-booking table:
   ```sql
   CREATE TABLE tbl_booking (
       id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       username        VARCHAR(20)   NOT NULL,
       device          VARCHAR(128)  NOT NULL,
       issue           TEXT          NOT NULL,
       preferred_date  DATE          NOT NULL,
       preferred_time  TIME          NOT NULL,
       status          ENUM('pending','approved','rescheduled','declined','finished') NOT NULL DEFAULT 'pending',
       images          JSON          NULL,
       created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
   );
   ```
   If the table was created before the `finished` status existed, migrate it with:
    ```sql
    ALTER TABLE tbl_booking
        MODIFY COLUMN status ENUM('pending','approved','rescheduled','declined','finished') NOT NULL DEFAULT 'pending';
    ```
    If the table was created with the older `image1`/`image2`/`image3` columns, migrate it with:
   ```sql
   ALTER TABLE tbl_booking
       ADD COLUMN images JSON NULL AFTER status,
       DROP COLUMN image1,
       DROP COLUMN image2,
       DROP COLUMN image3;
   ```
6. Ensure `storage/booking/` exists and is writable (device photos are stored there, 5 MB max each). The folder is already git-ignored.
7. Create the bug-report table:
   ```sql
   CREATE TABLE tbl_bug_reports (
       id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       username    VARCHAR(20)   NULL,
       email       VARCHAR(255)  NULL,
       title       VARCHAR(150)  NOT NULL,
       description TEXT          NOT NULL,
       images      JSON          NULL,
       status      ENUM('new','in_progress','fixed') NOT NULL DEFAULT 'new',
       created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
   );
   ```
   `username`/`email` hold the submitter (guests fill in the email, signed-in users are recorded by username). No IP addresses are ever stored.
8. Ensure `storage/bugs/` exists and is writable (bug-report screenshots are stored there, 3 MB max each, up to 3 per report, only for signed-in users). The folder is already git-ignored.
9. Create the employee and sales tables (or run the bundled `db_schema.sql`, which also seeds 6 demo employees and a customer `demo` account):
   ```sql
   CREATE TABLE tbl_employees (
       employee_id        INT(6) UNSIGNED ZEROFILL AUTO_INCREMENT PRIMARY KEY,
       password           VARCHAR(255) NOT NULL,
       firstname          VARCHAR(50)  NOT NULL,
       middlename         VARCHAR(50)  NULL,
       lastname           VARCHAR(50)  NOT NULL,
       contactnumber      VARCHAR(10)  NULL,
       email              VARCHAR(255) NOT NULL UNIQUE,
       verified_email     TINYINT(1)   NOT NULL DEFAULT 0,
       role               ENUM('cashier','manager','technician','admin','developer','logistics') NOT NULL DEFAULT 'cashier',
       is_active          TINYINT(1)   NOT NULL DEFAULT 1,
       attendance_history JSON         NULL,
       created_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
   );

   CREATE TABLE tbl_sales (
       id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       receipt_no        VARCHAR(32)      NOT NULL UNIQUE,
       origin            ENUM('store','online') NOT NULL DEFAULT 'store',
       sold_by           VARCHAR(255)     NULL,
       customer_email    VARCHAR(255)     NULL,
       customer_address  TEXT             NULL,
       items             JSON             NOT NULL,
       subtotal          DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
       total             DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
       status            ENUM('processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'processing',
       created_at        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP
   );
   ```
   Demo accounts — every demo password is `password123`. Employees sign in with
   their 6-digit Employee ID (there is no employee username); the customer signs
   in with username `demo`:

   | Portal | Login | Role |
   |---|---|---|
   | Employee | `000001` | Admin |
   | Employee | `000002` | Cashier |
   | Employee | `000003` | Manager |
   | Employee | `000004` | Technician |
   | Employee | `000005` | Developer |
   | Employee | `000006` | Logistics |
   | Customer | `demo` | Customer (user) |
10. Point your browser at `http://localhost/ci3/`.

> The DB config uses XAMPP defaults (`root`, empty password). Do not ship that to production.
