-- ============================================================================
-- Circuit Cart - database schema (structure only, no rows)
--
-- Run:  mysql -u root -e "CREATE DATABASE circuit_cart"
--       mysql -u root circuit_cart < db_schema.sql
--       mysql -u root circuit_cart < db_seed.sql   # optional demo seed data
--
-- Creates every table the app uses. Idempotent (IF NOT EXISTS), so it is safe
-- to re-run against an existing database. db_seed.sql adds the demo catalog,
-- employees, customer account and workflow data on top of this schema.
-- ============================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Customers. userhistory is a JSON array of {action, timestamp} entries.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_users (
    id              INT(11)      NOT NULL AUTO_INCREMENT,
    username        VARCHAR(20)   NOT NULL,
    firstname       VARCHAR(100)  NOT NULL,
    middlename      VARCHAR(100)  NULL,
    lastname        VARCHAR(100)  NOT NULL,
    email           VARCHAR(100)  NOT NULL,
    verified_email  TINYINT(1)    NOT NULL DEFAULT 0,
    contactnumber   VARCHAR(100)  NOT NULL,
    address         VARCHAR(100)  NULL,
    password        VARCHAR(255)  NOT NULL,
    role            VARCHAR(50)   NOT NULL DEFAULT 'user',
    userhistory     JSON          NULL,
    account_created DATE          NULL DEFAULT (CURRENT_DATE),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Parts catalog. One row per sellable part; part_id is the public 6-digit ID.
-- compatibility is a JSON array of supported device names, technical_specs a
-- JSON object of spec details - both are validated as JSON by the column type.
-- is_active is a soft delete flag used by the inventory screens.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_sku (
    part_id          INT(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    sku              VARCHAR(64)  NOT NULL,
    part_name        VARCHAR(255) NOT NULL,
    category         VARCHAR(50)  NOT NULL,
    device_type      VARCHAR(50)  NOT NULL,
    cost_price       DECIMAL(10,2) NOT NULL COMMENT 'Part cost in PHP',
    selling_price    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_quantity   INT(11)      NOT NULL DEFAULT 0,
    image_path       VARCHAR(255) NULL,
    compatibility    JSON         NOT NULL,
    technical_specs  JSON         NOT NULL,
    is_active        TINYINT(1)   DEFAULT 1,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (part_id),
    UNIQUE KEY sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Product image gallery. tbl_sku.image_path stays the primary/legacy image so
-- existing views keep working; this table holds the ordered extra photos shown
-- as thumbnails on the product detail page. sort_order decides display order.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_product_images (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    part_id     INT(6) UNSIGNED ZEROFILL NOT NULL,
    image_path  VARCHAR(255) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_product_images_part (part_id),
    CONSTRAINT fk_product_images_part FOREIGN KEY (part_id)
        REFERENCES tbl_sku(part_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Employees. Mirrors tbl_users, but userhistory is replaced by
-- attendance_history (JSON array of {date, time_in, time_out}).
-- employee_id is the 6-digit login ID (ZEROFILL display, e.g. 000001).
-- There is deliberately no username column.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_employees (
    employee_id        INT(6) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT,
    firstname          VARCHAR(100)  NOT NULL,
    middlename         VARCHAR(100)  NULL,
    lastname           VARCHAR(100)  NOT NULL,
    email              VARCHAR(100)  NOT NULL,
    verified_email     TINYINT(1)    NOT NULL DEFAULT 0,
    contactnumber      VARCHAR(100)  NOT NULL,
    password           VARCHAR(255)  NOT NULL,
    refund_pin_hash    VARCHAR(255)  NULL,
    role               VARCHAR(20)   NOT NULL DEFAULT 'cashier',
    attendance_history JSON          NULL,
    is_active          TINYINT(1)    NOT NULL DEFAULT 1,
    account_created    DATE          NULL DEFAULT (CURRENT_DATE),
    PRIMARY KEY (employee_id),
    UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Payment methods. Configurable tender types for POS.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_payment_methods (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(50)  NOT NULL,
    code            VARCHAR(20)  NOT NULL,
    requires_ref    TINYINT(1)   NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order      INT UNSIGNED NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Tax rates. Configurable tax rates for store/online/both.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_tax_rates (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(50)  NOT NULL,
    rate_percent    DECIMAL(5,2) NOT NULL,
    applies_to      ENUM('store','online','both') NOT NULL DEFAULT 'both',
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Sales / transactions. Store (POS) sales always count toward totals.
-- Online sales only count when status = 'delivered'.
-- sold_by holds the employee's full name.
-- payment_methods stores JSON array of [{method_id, amount, reference}]
-- tax_breakdown stores JSON array of [{tax_id, name, rate, amount}]
-- history stores a JSON array of {action, timestamp, employee_id, note} for
-- auditable reversals (refunds/voids/cancellations).
-- Refund columns are populated when a store sale is refunded or voided.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_sales (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    receipt_no       VARCHAR(32)   NOT NULL,
    origin           ENUM('store','online') NOT NULL,
    status           ENUM('processing','shipped','delivered','completed','cancelled','refunded','voided') NOT NULL DEFAULT 'processing',
    sold_by          VARCHAR(20)   NULL,
    employee_id      INT UNSIGNED  NULL,
    customer_email   VARCHAR(255)  NULL,
    customer_address VARCHAR(255)  NULL,
    items            JSON          NULL,
    subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_total        DECIMAL(10,2) NOT NULL DEFAULT 0,
    total            DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_methods  JSON          NULL,
    tax_breakdown    JSON          NULL,
    promo_code       VARCHAR(32)   NULL,
    discount_total   DECIMAL(10,2) NOT NULL DEFAULT 0,
    history          JSON          NULL,
    refund_amount    DECIMAL(10,2) NULL,
    refund_reason    TEXT          NULL,
    refunded_at      TIMESTAMP     NULL,
    refunded_by      INT UNSIGNED  NULL,
    refund_authorized_by INT UNSIGNED NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY receipt_no (receipt_no),
    KEY employee_id (employee_id),
    KEY refunded_by (refunded_by),
    KEY refund_authorized_by (refund_authorized_by),
    CONSTRAINT fk_sales_employee FOREIGN KEY (employee_id) REFERENCES tbl_employees(employee_id) ON DELETE SET NULL,
    CONSTRAINT fk_sales_refunded_by FOREIGN KEY (refunded_by) REFERENCES tbl_employees(employee_id) ON DELETE SET NULL,
    CONSTRAINT fk_sales_refund_authorized_by FOREIGN KEY (refund_authorized_by) REFERENCES tbl_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Promo codes. Server-side validated at checkout; discount applies to the
-- subtotal before tax. used_count is incremented inside the sale transaction.
-- max_total_uses/per_user_limit NULL mean unlimited. Discounts are stored on
-- the sale as promo_code/discount_total so order history stays auditable.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_promo_codes (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    code           VARCHAR(32)   NOT NULL,
    discount_type  ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
    min_subtotal   DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_total_uses INT UNSIGNED  NULL,
    per_user_limit INT UNSIGNED  NULL,
    used_count     INT UNSIGNED  NOT NULL DEFAULT 0,
    starts_at      DATETIME      NULL,
    expires_at     DATETIME      NULL,
    is_active      TINYINT(1)    NOT NULL DEFAULT 1,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY promo_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Customer wishlist. username is the signed-in customer (VARCHAR, not a FK,
-- matching tbl_booking, so it stays within the signup rule's 20-character
-- limit). The unique pair prevents a product being saved twice.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_wishlist (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username   VARCHAR(20)  NOT NULL,
    part_id    INT(6) UNSIGNED ZEROFILL NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_wishlist_user_part (username, part_id),
    KEY idx_wishlist_username (username),
    KEY idx_wishlist_part (part_id),
    CONSTRAINT fk_wishlist_part FOREIGN KEY (part_id)
        REFERENCES tbl_sku(part_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Product reviews. Auto-published on submit (status is only used to hide a
-- review from the storefront). order_verified is 1 only when the reviewer has
-- a DELIVERED online order containing the part, so "verified purchase" is
-- server-computed and never trusted from the client.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_product_reviews (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    part_id        INT(6) UNSIGNED ZEROFILL NOT NULL,
    username       VARCHAR(20)  NOT NULL,
    rating         TINYINT UNSIGNED NOT NULL,
    title          VARCHAR(128) NOT NULL DEFAULT '',
    body           TEXT         NOT NULL,
    status         ENUM('published','hidden') NOT NULL DEFAULT 'published',
    order_verified TINYINT(1)   NOT NULL DEFAULT 0,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_review_user_part (username, part_id),
    KEY idx_reviews_part (part_id),
    KEY idx_reviews_user (username),
    KEY idx_reviews_status (status),
    CONSTRAINT fk_reviews_part FOREIGN KEY (part_id)
        REFERENCES tbl_sku(part_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Repair services. Optional catalog of services a customer can pick when
-- booking a repair; base_price is only a display hint and is never trusted for
-- billing. Deactivated rows are hidden from the booking form but kept for the
-- bookings that reference them.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_repair_services (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(128) NOT NULL,
    description TEXT         NULL,
    base_price  DECIMAL(10,2) NOT NULL DEFAULT 0,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order  INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Repair bookings. username is the customer who booked (VARCHAR, not a FK, so
-- the column still matches the signup rule's 20-character limit).
-- service_id is the requested service (nullable, SET NULL if the service is
-- removed so booking history survives). history records status changes made by
-- staff or the customer (cancel/reschedule) for an auditable trail.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_booking (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username       VARCHAR(20)  NOT NULL,
    service_id     INT UNSIGNED NULL,
    device         VARCHAR(128) NOT NULL,
    issue          TEXT         NOT NULL,
    preferred_date DATE         NOT NULL,
    preferred_time TIME         NOT NULL,
    status         ENUM('pending','approved','rescheduled','picked','declined','cancelled','finished') NOT NULL DEFAULT 'pending',
    images         JSON         NULL,
    history        JSON         NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY service_id (service_id),
    CONSTRAINT fk_booking_service FOREIGN KEY (service_id) REFERENCES tbl_repair_services(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Appointment slots. Defines available booking windows per day.
-- capacity = how many bookings can be accepted for this slot.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_appointment_slots (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slot_date      DATE         NOT NULL,
    start_time     TIME         NOT NULL,
    end_time       TIME         NOT NULL,
    capacity       INT UNSIGNED NOT NULL DEFAULT 1,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_slot (slot_date, start_time, end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Repair jobs. Created when a booking is approved/finished, tracks the full
-- repair workflow: diagnosis, estimate, approval, parts, labor, status.
-- public_token is the unguessable key for the customer-facing status page.
-- estimate_token (with expiry) backs the emailed approve/decline links; it is
-- cleared once the customer makes a decision so a link cannot be replayed.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_repair_jobs (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_reference       VARCHAR(32)  NOT NULL,
    booking_id          INT UNSIGNED NOT NULL,
    technician_id       INT UNSIGNED NULL,
    device              VARCHAR(128) NOT NULL,
    issue               TEXT         NOT NULL,
    diagnosis           TEXT         NULL,
    estimate_total      DECIMAL(10,2) NULL,
    estimate_approved   TINYINT(1)   NOT NULL DEFAULT 0,
    estimate_approved_at TIMESTAMP   NULL,
    estimate_token      VARCHAR(64)  NULL,
    estimate_token_expires_at TIMESTAMP NULL,
    estimate_declined_at TIMESTAMP   NULL,
    estimate_declined_reason TEXT    NULL,
    parts_used          JSON         NULL,
    labor_items         JSON         NULL,
    technician_notes    TEXT         NULL,
    status              ENUM('diagnosis','estimate_pending','approved','in_progress','ready_for_pickup','completed','cancelled') NOT NULL DEFAULT 'diagnosis',
    status_history      JSON         NULL,
    public_token        VARCHAR(64)  NULL,
    started_at          TIMESTAMP    NULL,
    completed_at        TIMESTAMP    NULL,
    picked_up_at        TIMESTAMP    NULL,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY job_reference (job_reference),
    UNIQUE KEY public_token (public_token),
    KEY estimate_token (estimate_token),
    KEY booking_id (booking_id),
    KEY technician_id (technician_id),
    KEY status (status),
    CONSTRAINT fk_repair_job_booking FOREIGN KEY (booking_id) REFERENCES tbl_booking(id) ON DELETE RESTRICT,
    CONSTRAINT fk_repair_job_technician FOREIGN KEY (technician_id) REFERENCES tbl_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Repair payments. Money collected against a repair job: a deposit taken when
-- the estimate is approved, or the final balance at pickup. kind distinguishes
-- the two. balance on a job = (parts + labor) - sum(payments). method_id and
-- sale_id are optional so a payment can be tied to a POS receipt when the
-- tender was rung through the till. Card numbers/security codes are never
-- stored; reference holds a provider/transfer reference only.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_repair_payments (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    job_id      INT UNSIGNED  NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    method_id   INT UNSIGNED  NULL,
    reference   VARCHAR(64)   NULL,
    sale_id     INT UNSIGNED  NULL,
    kind        ENUM('deposit','payment') NOT NULL DEFAULT 'payment',
    recorded_by INT UNSIGNED  NULL,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY job_id (job_id),
    KEY method_id (method_id),
    KEY sale_id (sale_id),
    KEY recorded_by (recorded_by),
    CONSTRAINT fk_repair_payment_job FOREIGN KEY (job_id) REFERENCES tbl_repair_jobs(id) ON DELETE CASCADE,
    CONSTRAINT fk_repair_payment_method FOREIGN KEY (method_id) REFERENCES tbl_payment_methods(id) ON DELETE SET NULL,
    CONSTRAINT fk_repair_payment_sale FOREIGN KEY (sale_id) REFERENCES tbl_sales(id) ON DELETE SET NULL,
    CONSTRAINT fk_repair_payment_employee FOREIGN KEY (recorded_by) REFERENCES tbl_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Bug reports. username/email hold the submitter (guests supply the email,
-- signed-in users are recorded by username). No IP addresses are stored.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_bug_reports (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username    VARCHAR(20)  NULL,
    email       VARCHAR(255) NULL,
    title       VARCHAR(150) NOT NULL,
    description TEXT         NOT NULL,
    images      JSON         NULL,
    status      ENUM('new','in_progress','fixed') NOT NULL DEFAULT 'new',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Stock movements. Audit trail for all inventory changes.
-- reason: sale, adjustment, receive, repair_use, refund, return, count, write_off
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_stock_movements (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    part_id         INT UNSIGNED NOT NULL,
    qty_delta       INT          NOT NULL,
    reason          ENUM('sale','adjustment','receive','repair_use','refund','return','count','write_off') NOT NULL,
    reference_type  ENUM('sale','purchase_order','repair_job','adjustment','return','stock_count') NULL,
    reference_id    INT UNSIGNED NULL,
    employee_id     INT UNSIGNED NULL,
    note            TEXT         NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY part_id (part_id),
    KEY reason (reason),
    KEY reference (reference_type, reference_id),
    KEY employee_id (employee_id),
    KEY created_at (created_at),
    CONSTRAINT fk_stock_movement_part FOREIGN KEY (part_id) REFERENCES tbl_sku(part_id) ON DELETE RESTRICT,
    CONSTRAINT fk_stock_movement_employee FOREIGN KEY (employee_id) REFERENCES tbl_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password reset tokens (DB-backed single-use)
CREATE TABLE IF NOT EXISTS `tbl_password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_user` (`user_id`),
  KEY `idx_pr_hash` (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
