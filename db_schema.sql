-- ============================================================================
-- Circuit Cart - full database schema
--
-- Run:  mysql -u root -e "CREATE DATABASE circuit_cart"
--       mysql -u root circuit_cart < db_schema.sql
--
-- Creates every table the app uses and seeds the demo catalog, employees and
-- customer account. Idempotent (IF NOT EXISTS / ON DUPLICATE KEY UPDATE), so
-- it is safe to re-run against an existing database.
--
-- Demo logins (all passwords are password123):
--   Employees sign in with the 6-digit Employee ID (000001-000006) - there is
--   no employee username. The customer signs in with username 'demo'.
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

-- ============================================================================
-- Seed data
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Demo employees. All passwords are password123. Employees log in with the
-- 6-digit ID (000001-000006).
-- ---------------------------------------------------------------------------
INSERT INTO tbl_employees
    (employee_id, firstname, middlename, lastname, email, verified_email, contactnumber, password, role, attendance_history)
VALUES
    (1, 'Ada',     '', 'Admin',      'admin@circuit-cart.dev',      1, '09170000001', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'admin',      NULL),
    (2, 'Casey',   '', 'Cashier',    'cashier@circuit-cart.dev',    1, '09170000002', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'cashier',    NULL),
    (3, 'Morgan',  '', 'Manager',    'manager@circuit-cart.dev',    1, '09170000003', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'manager',    NULL),
    (4, 'Taylor',  '', 'Technician', 'technician@circuit-cart.dev', 1, '09170000004', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'technician', NULL),
    (5, 'Dev',     '', 'Developer',  'developer@circuit-cart.dev',  1, '09170000005', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'developer',  NULL),
    (6, 'Louis',   '', 'Logistics',  'logistics@circuit-cart.dev',  1, '09170000006', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'logistics',  NULL)
    ON DUPLICATE KEY UPDATE password = VALUES(password);

-- ---------------------------------------------------------------------------
-- Demo customer. Signs in with username 'demo'.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_users
    (id, username, firstname, middlename, lastname, email, verified_email, contactnumber, address, password, role, userhistory, account_created)
VALUES
    (1, 'demo', 'Demo', '', 'User', 'demo@circuit-cart.dev', 1, '09170000000', '019 Corrales Ext, Cagayan de Oro City', '$2y$10$dOGrfXBZb0F7CItHbzTDF.rctBa.tCjGU5cJHxmJTRi3PT0NxTy7W', 'user', NULL, NULL)
    ON DUPLICATE KEY UPDATE username = VALUES(username);

-- ---------------------------------------------------------------------------
-- Demo parts catalog. part_id values are fixed so POS receipts and the
-- analytics low-stock chart stay stable across rebuilds.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_sku
    (part_id, sku, part_name, category, device_type, cost_price, selling_price, stock_quantity, compatibility, technical_specs, is_active)
VALUES
    (100001, 'SCR-APL-IP11',   'iPhone 11 Liquid Retina LCD Display',                   'Screen',   'Smartphone',       3500.00,  4025.00,  0, '["iPhone 11"]',                                                                                                    '{"screen_size_in": 6.1, "panel_type": "IPS LCD", "resolution": "1792x828"}',                                                          1),
    (100002, 'SCR-APL-IP1213', 'iPhone 12 / 13 Super Retina XDR OLED Display',          'Screen',   'Smartphone',       8000.00,  9200.00,  6, '["iPhone 12", "iPhone 13"]',                                                                                         '{"screen_size_in": 6.1, "panel_type": "OLED", "resolution": "2532x1170"}',                                                        1),
    (100003, 'BAT-APL-IP14PM', 'iPhone 14 Pro Max High-Capacity Replacement Battery',    'Battery',  'Smartphone',       3200.00,  3680.00, 18, '["iPhone 14 Pro Max"]',                                                                                                '{"capacity_mah": 4323, "voltage_v": 3.86, "chemistry": "Li-ion"}',                                                        1),
    (100004, 'SCR-APL-IP15P',  'iPhone 15 Pro OLED Display Assembly',                   'Screen',   'Smartphone',      14000.00, 16100.00,  7, '["iPhone 15 Pro"]',                                                                                                   '{"screen_size_in": 6.1, "panel_type": "LTPO OLED", "refresh_rate_hz": 120}',                                                1),
    (100005, 'CAM-APL-IP16',   'iPhone 16 Rear Main Camera Module',                      'Camera',   'Smartphone',       8500.00,  9775.00,  0, '["iPhone 16", "iPhone 16 Plus"]',                                                                                     '{"resolution_mp": 48, "sensor_shift_ois": true, "aperture": "f/1.6"}',                                                     1),
    (100006, 'SCR-APL-IP17P',  'iPhone 17 Pro Super Retina ProMotion Display',          'Screen',   'Smartphone',      18000.00, 20700.00,  4, '["iPhone 17 Pro"]',                                                                                                   '{"screen_size_in": 6.3, "panel_type": "LTPO OLED", "refresh_rate_hz": 120}',                                                1),
    (100007, 'SCR-GGL-PX78',   'Google Pixel 7 / 8 AMOLED Screen Assembly',              'Screen',   'Smartphone',       7500.00,  8625.00,  9, '["Google Pixel 7", "Google Pixel 8"]',                                                                                 '{"screen_size_in": 6.2, "panel_type": "OLED", "refresh_rate_hz": 120}',                                                   1),
    (100008, 'BAT-GGL-PX910',  'Google Pixel 9 / 10 Li-ion Replacement Battery',         'Battery',  'Smartphone',       2800.00,  3220.00,  0, '["Google Pixel 9", "Google Pixel 10"]',                                                                                 '{"capacity_mah": 4700, "voltage_v": 3.89}',                                                                             1),
    (100009, 'SCR-SAM-S2122',  'Samsung Galaxy S21 / S22 Dynamic AMOLED 2X Display',      'Screen',   'Smartphone',       8500.00,  9775.00,  9, '["Samsung Galaxy S21", "Samsung Galaxy S22"]',                                                                          '{"screen_size_in": 6.2, "panel_type": "Dynamic AMOLED", "refresh_rate_hz": 120}',                                         1),
    (100010, 'BAT-SAM-S2324U', 'Samsung Galaxy S23 Ultra / S24 Ultra Battery Pack',      'Battery',  'Smartphone',       3000.00,  3450.00, 17, '["Samsung Galaxy S23 Ultra", "Samsung Galaxy S24 Ultra"]',                                                              '{"capacity_mah": 5000, "voltage_v": 3.88}',                                                                             1),
    (100011, 'SCR-SAM-S2526',  'Samsung Galaxy S25 / S26 Dynamic AMOLED Screen Assembly','Screen',   'Smartphone',      15500.00, 17825.00,  4, '["Samsung Galaxy S25", "Samsung Galaxy S26"]',                                                                          '{"screen_size_in": 6.3, "panel_type": "Dynamic AMOLED 2X", "refresh_rate_hz": 120}',                                      1),
    (100012, 'KBD-LNV-TP14',   'Lenovo ThinkPad T14 / X1 Carbon Backlit Keyboard',       'Keyboard', 'Laptop',            4200.00,  4830.00, 15, '["Lenovo ThinkPad T14", "Lenovo ThinkPad X1 Carbon"]',                                                                   '{"layout": "US English", "backlit": true, "trackpoint": true}',                                                          1),
    (100013, 'PWR-LNV-65W',    'Lenovo ThinkPad 65W USB-C Power Adapter',                'Charger',  'Laptop',            2200.00,  2530.00, 25, '["Lenovo ThinkPad T14", "Lenovo ThinkPad E14", "Lenovo IdeaPad Slim 5"]',                                                '{"wattage": 65, "connector": "USB-C", "input_voltage": "100-240V"}',                                                     1),
    (100014, 'SCR-LNV-IDP35',  'Lenovo IdeaPad Slim 3 / 5 Full HD IPS Screen Panel',     'Screen',   'Laptop',            5500.00,  6325.00,  7, '["Lenovo IdeaPad Slim 3", "Lenovo IdeaPad Slim 5"]',                                                                    '{"screen_size_in": 15.6, "resolution": "1920x1080", "pin_connector": 30}',                                                1),
    (100015, 'BAT-LNV-IDP52',  'Lenovo IdeaPad 52.5Wh 3-Cell Internal Battery',          'Battery',  'Laptop',            3100.00,  3565.00, 11, '["Lenovo IdeaPad 3", "Lenovo IdeaPad 5"]',                                                                            '{"capacity_wh": 52.5, "voltage_v": 11.52, "chemistry": "Li-Polymer"}',                                                 1),
    (100016, 'SCR-VLV-SDOLED', 'Steam Deck OLED Anti-Glare Touchscreen Assembly',         'Screen',   'Handheld Console',  6500.00,  7475.00,  6, '["Steam Deck OLED", "Steam Deck LCD"]',                                                                                '{"screen_size_in": 7.4, "panel_type": "OLED", "refresh_rate_hz": 90}',                                                  1),
    (100017, 'JOY-VLV-SDHALL', 'Steam Deck Hall Effect Joystick Module Pair',            'Control',  'Handheld Console',  1800.00,  2070.00, 16, '["Steam Deck LCD", "Steam Deck OLED"]',                                                                                '{"sensor_type": "Hall Effect", "drift_resistant": true}',                                                              1),
    (100018, 'SCR-NTD-SW1',    'Nintendo Switch v1 / v2 Touch LCD Assembly',             'Screen',   'Handheld Console',  2500.00,  2875.00,  0, '["Nintendo Switch v1", "Nintendo Switch v2"]',                                                                           '{"screen_size_in": 6.2, "panel_type": "LCD", "resolution": "1280x720"}',                                                1),
    (100019, 'SCR-NTD-SW2',    'Nintendo Switch 2 OLED Touchscreen Assembly',            'Screen',   'Handheld Console',  5800.00,  6670.00,  8, '["Nintendo Switch 2"]',                                                                                                '{"screen_size_in": 8.0, "panel_type": "OLED", "hdr_support": true}',                                                  1)
    ON DUPLICATE KEY UPDATE
        part_name       = VALUES(part_name),
        category        = VALUES(category),
        device_type     = VALUES(device_type),
        cost_price      = VALUES(cost_price),
        selling_price   = VALUES(selling_price),
        compatibility   = VALUES(compatibility),
        technical_specs = VALUES(technical_specs);

-- Product images are not seeded. Products start without an image so stores
-- upload their own exact supplier/product photos (primary + gallery) via
-- Inventory > Stocks once a product exists.

-- ---------------------------------------------------------------------------
-- Default appointment slots (next 30 weekdays, 9:00-17:00, 1-hour slots, capacity 2)
-- ---------------------------------------------------------------------------
-- Note: This is a sample seed. In production, use the admin UI to manage slots.
INSERT INTO tbl_appointment_slots (slot_date, start_time, end_time, capacity, is_active)
SELECT
    d.slot_date,
    t.start_time,
    t.end_time,
    2,
    1
FROM (
    SELECT DATE_ADD(CURDATE(), INTERVAL seq DAY) AS slot_date
    FROM (
        SELECT 0 AS seq UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
        UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
        UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14
        UNION ALL SELECT 15 UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19
        UNION ALL SELECT 20 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24
        UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28 UNION ALL SELECT 29
    ) AS days
) AS d
JOIN (
    SELECT '09:00:00' AS start_time, '10:00:00' AS end_time UNION ALL
    SELECT '10:00:00', '11:00:00' UNION ALL
    SELECT '11:00:00', '12:00:00' UNION ALL
    SELECT '13:00:00', '14:00:00' UNION ALL
    SELECT '14:00:00', '15:00:00' UNION ALL
    SELECT '15:00:00', '16:00:00' UNION ALL
    SELECT '16:00:00', '17:00:00'
) AS t
WHERE DAYOFWEEK(d.slot_date) BETWEEN 2 AND 6  -- Monday=2 to Friday=6
ON DUPLICATE KEY UPDATE capacity = VALUES(capacity), is_active = VALUES(is_active);

-- ---------------------------------------------------------------------------
-- Default payment methods
-- ---------------------------------------------------------------------------
INSERT INTO tbl_payment_methods (name, code, requires_ref, is_active, sort_order) VALUES
    ('Cash on Delivery', 'cod', 0, 1, 1),
    ('Cash', 'cash', 0, 1, 1),
    ('Credit/Debit Card', 'card', 1, 1, 2),
    ('GCash', 'gcash', 1, 1, 3),
    ('GoTyme', 'gotyme', 1, 1, 4),
    ('Bank Transfer', 'bank', 1, 1, 5),
    ('Gift Card', 'giftcard', 1, 1, 6)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active), sort_order = VALUES(sort_order);

-- ---------------------------------------------------------------------------
-- Default tax rates (Philippines VAT 12%)
-- ---------------------------------------------------------------------------
-- tbl_tax_rates has no natural unique key, so the row is guarded with NOT
-- EXISTS to stay idempotent across re-runs.
INSERT INTO tbl_tax_rates (name, rate_percent, applies_to, is_active)
SELECT 'VAT 12%', 12.00, 'both', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tbl_tax_rates WHERE name = 'VAT 12%');

-- ---------------------------------------------------------------------------
-- Default repair services. base_price is a customer-facing starting hint; the
-- actual charge is always built from parts and labor on the repair job.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_repair_services (name, description, base_price, is_active, sort_order) VALUES
    ('Diagnostics', 'Initial inspection and fault diagnosis of the device.', 0.00, 1, 1),
    ('Screen Replacement', 'Replace a cracked or faulty display assembly.', 1500.00, 1, 2),
    ('Battery Replacement', 'Replace a worn or swollen battery.', 800.00, 1, 3),
    ('Charging Port Repair', 'Repair or replace a charging port that will not charge.', 600.00, 1, 4),
    ('Water Damage Assessment', 'Assess and treat a liquid-damaged device.', 500.00, 1, 5),
    ('Software / OS Reinstall', 'Reinstall or repair the operating system and software.', 400.00, 1, 6)
ON DUPLICATE KEY UPDATE description = VALUES(description), base_price = VALUES(base_price), is_active = VALUES(is_active), sort_order = VALUES(sort_order);

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


-- ---------------------------------------------------------------------------
-- Demo promo codes. WELCOME10 backs the promo order in the demo sales below;
-- SAVE200 shows a fixed-amount, minimum-subtotal code.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_promo_codes
    (code, discount_type, discount_value, min_subtotal, max_total_uses, per_user_limit, used_count, starts_at, expires_at, is_active)
VALUES
    ('WELCOME10', 'percent', 10.00,    0.00, NULL, 1,    1, NULL, NULL, 1),
    ('SAVE200',   'fixed',  200.00, 3000.00, NULL, NULL, 0, NULL, NULL, 1)
ON DUPLICATE KEY UPDATE
    discount_type  = VALUES(discount_type),
    discount_value = VALUES(discount_value),
    min_subtotal   = VALUES(min_subtotal),
    per_user_limit = VALUES(per_user_limit),
    is_active      = VALUES(is_active);

-- ---------------------------------------------------------------------------
-- Demo sales history. A mix of completed store sales and online orders spread
-- across the last three weeks so Analytics, POS history and the demo customer's
-- order history are populated out of the box. created_at is relative to now so
-- the rows stay inside the default 30-day analytics window on every rebuild.
-- Totals use the 12% VAT from tbl_tax_rates; online orders add the flat
-- shipping fee. Stock is NOT adjusted here (the seeded catalog is the source).
-- ---------------------------------------------------------------------------
INSERT INTO tbl_sales
    (receipt_no, origin, status, sold_by, employee_id, customer_email, customer_address,
     items, subtotal, tax_total, total, payment_methods, tax_breakdown,
     promo_code, discount_total, history, created_at)
VALUES
    ('ST-SEED-0001', 'store', 'completed', 'cashier', 2, NULL, NULL,
     JSON_ARRAY(
        JSON_OBJECT('part_id', 100010, 'name', 'Samsung Galaxy S23 Ultra / S24 Ultra Battery Pack', 'qty', 1, 'price', 3450.00),
        JSON_OBJECT('part_id', 100013, 'name', 'Lenovo ThinkPad 65W USB-C Power Adapter', 'qty', 2, 'price', 2530.00)
     ),
     8510.00, 1021.20, 9531.20,
     JSON_ARRAY(JSON_OBJECT('method_id', 2, 'code', 'cash', 'amount', 9531.20, 'reference', NULL)),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 1021.20)),
     NULL, 0.00,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', 2, 'note', 'Store sale completed')),
     DATE_SUB(NOW(), INTERVAL 2 DAY)),

    ('ST-SEED-0002', 'store', 'completed', 'cashier', 2, NULL, NULL,
     JSON_ARRAY(JSON_OBJECT('part_id', 100017, 'name', 'Steam Deck Hall Effect Joystick Module Pair', 'qty', 1, 'price', 2070.00)),
     2070.00, 248.40, 2318.40,
     JSON_ARRAY(JSON_OBJECT('method_id', 2, 'code', 'cash', 'amount', 2318.40, 'reference', NULL)),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 248.40)),
     NULL, 0.00,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', 2, 'note', 'Store sale completed')),
     DATE_SUB(NOW(), INTERVAL 5 DAY)),

    ('ST-SEED-0003', 'store', 'completed', 'cashier', 2, NULL, NULL,
     JSON_ARRAY(
        JSON_OBJECT('part_id', 100012, 'name', 'Lenovo ThinkPad T14 / X1 Carbon Backlit Keyboard', 'qty', 1, 'price', 4830.00),
        JSON_OBJECT('part_id', 100015, 'name', 'Lenovo IdeaPad 52.5Wh 3-Cell Internal Battery', 'qty', 1, 'price', 3565.00)
     ),
     8395.00, 1007.40, 9402.40,
     JSON_ARRAY(JSON_OBJECT('method_id', 2, 'code', 'cash', 'amount', 9402.40, 'reference', NULL)),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 1007.40)),
     NULL, 0.00,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 8 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', 2, 'note', 'Store sale completed')),
     DATE_SUB(NOW(), INTERVAL 8 DAY)),

    ('ST-SEED-0004', 'store', 'completed', 'cashier', 2, NULL, NULL,
     JSON_ARRAY(JSON_OBJECT('part_id', 100019, 'name', 'Nintendo Switch 2 OLED Touchscreen Assembly', 'qty', 1, 'price', 6670.00)),
     6670.00, 800.40, 7470.40,
     JSON_ARRAY(JSON_OBJECT('method_id', 2, 'code', 'cash', 'amount', 7470.40, 'reference', NULL)),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 800.40)),
     NULL, 0.00,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', 2, 'note', 'Store sale completed')),
     DATE_SUB(NOW(), INTERVAL 12 DAY)),

    ('ST-SEED-0005', 'store', 'completed', 'cashier', 2, NULL, NULL,
     JSON_ARRAY(
        JSON_OBJECT('part_id', 100003, 'name', 'iPhone 14 Pro Max High-Capacity Replacement Battery', 'qty', 1, 'price', 3680.00),
        JSON_OBJECT('part_id', 100013, 'name', 'Lenovo ThinkPad 65W USB-C Power Adapter', 'qty', 1, 'price', 2530.00)
     ),
     6210.00, 745.20, 6955.20,
     JSON_ARRAY(JSON_OBJECT('method_id', 2, 'code', 'cash', 'amount', 6955.20, 'reference', NULL)),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 745.20)),
     NULL, 0.00,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s'), 'employee_id', 2, 'note', 'Store sale completed')),
     NOW()),

    ('ST-SEED-0006', 'store', 'completed', 'cashier', 2, NULL, NULL,
     JSON_ARRAY(JSON_OBJECT('part_id', 100009, 'name', 'Samsung Galaxy S21 / S22 Dynamic AMOLED 2X Display', 'qty', 1, 'price', 9775.00)),
     9775.00, 1173.00, 10948.00,
     JSON_ARRAY(JSON_OBJECT('method_id', 2, 'code', 'cash', 'amount', 10948.00, 'reference', NULL)),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 1173.00)),
     NULL, 0.00,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 18 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', 2, 'note', 'Store sale completed')),
     DATE_SUB(NOW(), INTERVAL 18 DAY)),

    ('ON-SEED-0001', 'online', 'delivered', 'demo', NULL, 'demo@circuit-cart.dev', '019 Corrales Ext, Cagayan de Oro City',
     JSON_ARRAY(JSON_OBJECT('part_id', 100002, 'name', 'iPhone 12 / 13 Super Retina XDR OLED Display', 'qty', 1, 'price', 9200.00)),
     9200.00, 1104.00, 10314.00,
     JSON_ARRAY(JSON_OBJECT('method_id', 4, 'code', 'gcash', 'amount', 10314.00, 'reference', NULL, 'status', 'pending')),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 1104.00)),
     NULL, 0.00,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', NULL, 'note', 'Online order placed')),
     DATE_SUB(NOW(), INTERVAL 1 DAY)),

    ('ON-SEED-0002', 'online', 'shipped', 'demo', NULL, 'demo@circuit-cart.dev', '019 Corrales Ext, Cagayan de Oro City',
     JSON_ARRAY(JSON_OBJECT('part_id', 100006, 'name', 'iPhone 17 Pro Super Retina ProMotion Display', 'qty', 1, 'price', 20700.00)),
     20700.00, 2484.00, 23194.00,
     JSON_ARRAY(JSON_OBJECT('method_id', 1, 'code', 'cod', 'amount', 23194.00, 'reference', NULL, 'status', 'pending')),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 2484.00)),
     NULL, 0.00,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', NULL, 'note', 'Online order placed')),
     DATE_SUB(NOW(), INTERVAL 3 DAY)),

    ('ON-SEED-0003', 'online', 'delivered', NULL, NULL, 'juan@example.com', '12 Rizal St, Cagayan de Oro City',
     JSON_ARRAY(JSON_OBJECT('part_id', 100007, 'name', 'Google Pixel 7 / 8 AMOLED Screen Assembly', 'qty', 1, 'price', 8625.00)),
     8625.00, 1035.00, 9670.00,
     JSON_ARRAY(JSON_OBJECT('method_id', 4, 'code', 'gcash', 'amount', 9670.00, 'reference', NULL, 'status', 'pending')),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 1035.00)),
     NULL, 0.00,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 6 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', NULL, 'note', 'Online order placed')),
     DATE_SUB(NOW(), INTERVAL 6 DAY)),

    ('ON-SEED-0004', 'online', 'processing', 'demo', NULL, 'demo@circuit-cart.dev', '019 Corrales Ext, Cagayan de Oro City',
     JSON_ARRAY(JSON_OBJECT('part_id', 100014, 'name', 'Lenovo IdeaPad Slim 3 / 5 Full HD IPS Screen Panel', 'qty', 1, 'price', 6325.00)),
     6325.00, 683.10, 6385.60,
     JSON_ARRAY(JSON_OBJECT('method_id', 4, 'code', 'gcash', 'amount', 6385.60, 'reference', NULL, 'status', 'pending')),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 683.10)),
     'WELCOME10', 632.50,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 10 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', NULL, 'note', 'Online order placed')),
     DATE_SUB(NOW(), INTERVAL 10 DAY)),

    ('ON-SEED-0005', 'online', 'cancelled', NULL, NULL, 'karen@example.com', '88 Luna St, Cagayan de Oro City',
     JSON_ARRAY(JSON_OBJECT('part_id', 100019, 'name', 'Nintendo Switch 2 OLED Touchscreen Assembly', 'qty', 1, 'price', 6670.00)),
     6670.00, 800.40, 7480.40,
     JSON_ARRAY(JSON_OBJECT('method_id', 1, 'code', 'cod', 'amount', 7480.40, 'reference', NULL, 'status', 'pending')),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 800.40)),
     NULL, 0.00,
     JSON_ARRAY(
        JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 15 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', NULL, 'note', 'Online order placed'),
        JSON_OBJECT('action', 'cancelled', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 14 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', 1, 'note', 'Cancelled by admin')
     ),
     DATE_SUB(NOW(), INTERVAL 15 DAY)),

    ('ON-SEED-0006', 'online', 'delivered', 'demo', NULL, 'demo@circuit-cart.dev', '019 Corrales Ext, Cagayan de Oro City',
     JSON_ARRAY(JSON_OBJECT('part_id', 100015, 'name', 'Lenovo IdeaPad 52.5Wh 3-Cell Internal Battery', 'qty', 1, 'price', 3565.00)),
     3565.00, 427.80, 4002.80,
     JSON_ARRAY(JSON_OBJECT('method_id', 4, 'code', 'gcash', 'amount', 4002.80, 'reference', NULL, 'status', 'pending')),
     JSON_ARRAY(JSON_OBJECT('tax_id', 1, 'name', 'VAT 12%', 'rate', 12, 'amount', 427.80)),
     NULL, 0.00,
     JSON_ARRAY(JSON_OBJECT('action', 'created', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 20 DAY), '%Y-%m-%d %H:%i:%s'), 'employee_id', NULL, 'note', 'Online order placed')),
     DATE_SUB(NOW(), INTERVAL 20 DAY))
ON DUPLICATE KEY UPDATE receipt_no = VALUES(receipt_no);

-- ============================================================================
-- Demo workflow data
--
-- Populates bookings, repair jobs, repair payments, wishlist, reviews, stock
-- movements and bug reports for the demo customer so every portal and
-- storefront screen has data on a fresh install. Fixed primary keys plus
-- ON DUPLICATE KEY UPDATE id = id make the block safe to re-run: a conflict is
-- a no-op, so real rows are never duplicated or overwritten. Foreign keys that
-- point at the seeded lookup rows (repair services, payment methods) are
-- resolved by their unique name/code rather than a hard-coded id, so they
-- survive any auto-increment offset. product_images is intentionally left
-- empty (stores upload their own photos) and tbl_users is left untouched.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Demo repair bookings, spread across the booking lifecycle.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_booking
    (id, username, service_id, device, issue, preferred_date, preferred_time, status, images, history, created_at)
VALUES
    (1, 'demo', (SELECT id FROM tbl_repair_services WHERE name = 'Screen Replacement' LIMIT 1),
     'iPhone 12', 'Dropped the phone; the screen is cracked but touch still works.',
     DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 'pending', NULL,
     JSON_ARRAY(JSON_OBJECT('status', 'pending', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Booking requested')),
     DATE_SUB(NOW(), INTERVAL 1 DAY)),

    (2, 'demo', (SELECT id FROM tbl_repair_services WHERE name = 'Battery Replacement' LIMIT 1),
     'Samsung Galaxy S23 Ultra', 'Battery drains from full to empty in about two hours.',
     DATE_ADD(CURDATE(), INTERVAL 2 DAY), '11:00:00', 'approved', NULL,
     JSON_ARRAY(
        JSON_OBJECT('status', 'pending', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Booking requested'),
        JSON_OBJECT('status', 'approved', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Approved by staff')
     ),
     DATE_SUB(NOW(), INTERVAL 3 DAY)),

    (3, 'demo', (SELECT id FROM tbl_repair_services WHERE name = 'Charging Port Repair' LIMIT 1),
     'Google Pixel 8', 'Charging only works when the cable is held at an angle.',
     DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', 'rescheduled', NULL,
     JSON_ARRAY(
        JSON_OBJECT('status', 'pending', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 4 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Booking requested'),
        JSON_OBJECT('status', 'rescheduled', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Moved to a later slot by the customer')
     ),
     DATE_SUB(NOW(), INTERVAL 4 DAY)),

    (4, 'demo', (SELECT id FROM tbl_repair_services WHERE name = 'Diagnostics' LIMIT 1),
     'Lenovo ThinkPad T14', 'Shuts down randomly under load.',
     DATE_SUB(CURDATE(), INTERVAL 5 DAY), '09:00:00', 'picked', NULL,
     JSON_ARRAY(
        JSON_OBJECT('status', 'pending', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 6 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Booking requested'),
        JSON_OBJECT('status', 'approved', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Approved by staff'),
        JSON_OBJECT('status', 'picked', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Device checked in at the counter')
     ),
     DATE_SUB(NOW(), INTERVAL 6 DAY)),

    (5, 'demo', (SELECT id FROM tbl_repair_services WHERE name = 'Water Damage Assessment' LIMIT 1),
     'Steam Deck OLED', 'Liquid spill; the unit would not power on.',
     DATE_SUB(CURDATE(), INTERVAL 12 DAY), '15:00:00', 'finished', NULL,
     JSON_ARRAY(
        JSON_OBJECT('status', 'pending', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 13 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Booking requested'),
        JSON_OBJECT('status', 'approved', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Approved by staff'),
        JSON_OBJECT('status', 'picked', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Device checked in at the counter'),
        JSON_OBJECT('status', 'finished', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 10 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Repair completed and device released')
     ),
     DATE_SUB(NOW(), INTERVAL 13 DAY)),

    (6, 'demo', (SELECT id FROM tbl_repair_services WHERE name = 'Software / OS Reinstall' LIMIT 1),
     'Nintendo Switch 2', 'Console is stuck on the boot logo screen.',
     DATE_SUB(CURDATE(), INTERVAL 14 DAY), '16:00:00', 'cancelled', NULL,
     JSON_ARRAY(
        JSON_OBJECT('status', 'pending', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 15 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Booking requested'),
        JSON_OBJECT('status', 'cancelled', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 14 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Cancelled by the customer')
     ),
     DATE_SUB(NOW(), INTERVAL 15 DAY))
ON DUPLICATE KEY UPDATE id = id;

-- ---------------------------------------------------------------------------
-- Demo repair jobs. One per active/finished booking in the demo set, covering
-- the diagnosis, estimate, in-progress, ready-for-pickup and completed states.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_repair_jobs
    (id, job_reference, booking_id, technician_id, device, issue, diagnosis, estimate_total, estimate_approved,
     estimate_approved_at, estimate_token, estimate_token_expires_at, estimate_declined_at, estimate_declined_reason,
     parts_used, labor_items, technician_notes, status, status_history, public_token, started_at, completed_at,
     picked_up_at, created_at)
VALUES
    (1, 'RJ-SEED-0001', 2, 4, 'Samsung Galaxy S23 Ultra', 'Battery drains from full to empty in about two hours.',
     'Battery health reads 61%; a replacement cell is required.', 3680.00, 1,
     DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, NULL, NULL, NULL,
     JSON_ARRAY(JSON_OBJECT('part_id', 100010, 'name', 'Samsung Galaxy S23 Ultra / S24 Ultra Battery Pack', 'qty', 1, 'price', 3000.00, 'added_at', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 DAY), '%Y-%m-%d %H:%i:%s'))),
     JSON_ARRAY(JSON_OBJECT('description', 'Battery replacement labor', 'hours', 1, 'rate', 680.00, 'total', 680.00, 'added_at', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 DAY), '%Y-%m-%d %H:%i:%s'))),
     'Battery replaced and calibrated; a full charge/discharge cycle passed.', 'in_progress',
     JSON_ARRAY(
        JSON_OBJECT('status', 'diagnosis', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Repair job created from booking #2'),
        JSON_OBJECT('status', 'estimate_pending', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Estimate sent to the customer'),
        JSON_OBJECT('status', 'approved', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Estimate approved by the customer'),
        JSON_OBJECT('status', 'in_progress', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 2 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Repair work started')
     ),
     'rj-seed-public-0001', DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY)),

    (2, 'RJ-SEED-0002', 4, 4, 'Lenovo ThinkPad T14', 'Shuts down randomly under load.',
     'Thermal paste has dried out and the cooling fan is clogged with dust.', 1200.00, 0,
     NULL, 'rj-seed-estimate-0002', DATE_ADD(NOW(), INTERVAL 7 DAY), NULL, NULL,
     NULL, NULL, NULL, 'estimate_pending',
     JSON_ARRAY(
        JSON_OBJECT('status', 'diagnosis', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Repair job created from booking #4'),
        JSON_OBJECT('status', 'estimate_pending', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 5 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Estimate sent to the customer')
     ),
     'rj-seed-public-0002', DATE_SUB(NOW(), INTERVAL 5 DAY), NULL, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY)),

    (3, 'RJ-SEED-0003', 5, 4, 'Steam Deck OLED', 'Liquid spill; the unit would not power on.',
     'Corrosion cleaned from the mainboard; no permanent component damage found.', 1500.00, 1,
     DATE_SUB(NOW(), INTERVAL 12 DAY), NULL, NULL, NULL, NULL,
     NULL,
     JSON_ARRAY(
        JSON_OBJECT('description', 'Diagnostic and ultrasonic board cleaning', 'hours', 2, 'rate', 500.00, 'total', 1000.00, 'added_at', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 DAY), '%Y-%m-%d %H:%i:%s')),
        JSON_OBJECT('description', 'Water damage treatment', 'hours', 1, 'rate', 500.00, 'total', 500.00, 'added_at', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 DAY), '%Y-%m-%d %H:%i:%s'))
     ),
     'Unit powers on, boots and passes a soak test; no parts replaced.', 'completed',
     JSON_ARRAY(
        JSON_OBJECT('status', 'diagnosis', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 13 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Repair job created from booking #5'),
        JSON_OBJECT('status', 'estimate_pending', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 13 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Estimate sent to the customer'),
        JSON_OBJECT('status', 'approved', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Estimate approved by the customer'),
        JSON_OBJECT('status', 'in_progress', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 12 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Repair work started'),
        JSON_OBJECT('status', 'ready_for_pickup', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 11 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Device ready for pickup'),
        JSON_OBJECT('status', 'completed', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 10 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Balance settled and device released')
     ),
     'rj-seed-public-0003', DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 13 DAY)),

    (4, 'RJ-SEED-0004', 3, 4, 'Google Pixel 8', 'Charging only works when the cable is held at an angle.',
     'The charging port is worn; the connector needs to be replaced.', 950.00, 1,
     DATE_SUB(NOW(), INTERVAL 3 DAY), NULL, NULL, NULL, NULL,
     NULL,
     JSON_ARRAY(JSON_OBJECT('description', 'Charging port replacement labor', 'hours', 1, 'rate', 950.00, 'total', 950.00, 'added_at', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 DAY), '%Y-%m-%d %H:%i:%s'))),
     'New charging port fitted; charging confirmed with multiple cables.', 'ready_for_pickup',
     JSON_ARRAY(
        JSON_OBJECT('status', 'diagnosis', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 4 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Repair job created from booking #3'),
        JSON_OBJECT('status', 'estimate_pending', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 4 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Estimate sent to the customer'),
        JSON_OBJECT('status', 'approved', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Estimate approved by the customer'),
        JSON_OBJECT('status', 'in_progress', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Repair work started'),
        JSON_OBJECT('status', 'ready_for_pickup', 'timestamp', DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 3 DAY), '%Y-%m-%d %H:%i:%s'), 'note', 'Device ready for pickup')
     ),
     'rj-seed-public-0004', DATE_SUB(NOW(), INTERVAL 4 DAY), NULL, NULL, DATE_SUB(NOW(), INTERVAL 4 DAY))
ON DUPLICATE KEY UPDATE id = id;

-- ---------------------------------------------------------------------------
-- Demo repair payments. Job 1 (estimate 3680.00) is fully paid, job 3 is
-- settled, and job 4 carries a 500.00 deposit against a 950.00 balance.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_repair_payments
    (id, job_id, amount, method_id, reference, sale_id, kind, recorded_by, created_at)
VALUES
    (1, 1, 1000.00, (SELECT id FROM tbl_payment_methods WHERE code = 'cash' LIMIT 1),  NULL,             NULL, 'deposit', 2, DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (2, 1, 2680.00, (SELECT id FROM tbl_payment_methods WHERE code = 'gcash' LIMIT 1), 'GC-DEMO-0001',    NULL, 'payment', 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (3, 3,  500.00, (SELECT id FROM tbl_payment_methods WHERE code = 'cash' LIMIT 1),  NULL,              NULL, 'deposit', 2, DATE_SUB(NOW(), INTERVAL 11 DAY)),
    (4, 3, 1000.00, (SELECT id FROM tbl_payment_methods WHERE code = 'cash' LIMIT 1),  NULL,              NULL, 'payment', 2, DATE_SUB(NOW(), INTERVAL 10 DAY)),
    (5, 4,  500.00, (SELECT id FROM tbl_payment_methods WHERE code = 'cash' LIMIT 1),  NULL,              NULL, 'deposit', 2, DATE_SUB(NOW(), INTERVAL 3 DAY))
ON DUPLICATE KEY UPDATE id = id;

-- ---------------------------------------------------------------------------
-- Demo wishlist for the demo customer.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_wishlist (id, username, part_id, created_at)
VALUES
    (1, 'demo', 100002, DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (2, 'demo', 100016, DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (3, 'demo', 100019, DATE_SUB(NOW(), INTERVAL 5 DAY))
ON DUPLICATE KEY UPDATE id = id;

-- ---------------------------------------------------------------------------
-- Demo product reviews. Every catalog item ships with reviews (5-10 each) so
-- product pages have ratings and feedback out of the box. order_verified is a
-- stored flag the app normally computes from delivered online orders: it is
-- real for the demo customer's own parts (100006, 100015) and a scattered
-- subset of the sample reviewers is marked verified for badge variety. All
-- rows are published. Ids are fixed so re-runs are a no-op.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_product_reviews
    (id, part_id, username, rating, title, body, status, order_verified, created_at)
VALUES
    (1, 100006, 'demo', 5, 'Flawless replacement panel', 'Brightness and touch feel identical to the original; installation was straightforward.', 'published', 1, DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (2, 100015, 'demo', 4, 'Solid replacement battery', 'Holds a full day of use again. Only wish it shipped with the tools.', 'published', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
    (3, 100017, 'demo', 5, 'Stick drift is gone', 'Installed these on my Steam Deck and the drift disappeared completely.', 'published', 0, DATE_SUB(NOW(), INTERVAL 9 DAY)),
    (4, 100009, 'juan', 3, 'Good panel, slow delivery', 'The screen works well but shipping took longer than expected.', 'published', 0, DATE_SUB(NOW(), INTERVAL 6 DAY)),

    -- 100001 iPhone 11 LCD display
    (5, 100001, 'miguel', 5, 'Great budget screen', 'Colors and brightness match the original for a very fair price. Would order again.', 'published', 1, DATE_SUB(NOW(), INTERVAL 40 DAY)),
    (6, 100001, 'ana', 5, 'Perfect fit, no dead pixels', 'Dropped in cleanly on my iPhone 11 and the touch response is spot on.', 'published', 1, DATE_SUB(NOW(), INTERVAL 35 DAY)),
    (7, 100001, 'carlo', 4, 'Good quality for the price', 'Slight difference in max brightness but nothing noticeable day to day.', 'published', 0, DATE_SUB(NOW(), INTERVAL 28 DAY)),
    (8, 100001, 'liza', 5, 'Looks original', 'Hard to tell it apart from the screen I replaced. Very happy with it.', 'published', 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
    (9, 100001, 'paolo', 3, 'Works but a bit dim', 'Installed fine and everything works, just a little dimmer outdoors.', 'published', 0, DATE_SUB(NOW(), INTERVAL 14 DAY)),
    (10, 100001, 'grace', 5, 'Fast delivery', 'Arrived well packed in bubble wrap and installed without issues.', 'published', 0, DATE_SUB(NOW(), INTERVAL 7 DAY)),

    -- 100002 iPhone 12 / 13 OLED display
    (11, 100002, 'mark', 5, 'OLED looks fantastic', 'Deep blacks and vivid colors, a big step up from the old panel.', 'published', 1, DATE_SUB(NOW(), INTERVAL 55 DAY)),
    (12, 100002, 'bea', 5, 'True to description', 'Matched my iPhone 13 perfectly and True Tone works as expected.', 'published', 1, DATE_SUB(NOW(), INTERVAL 48 DAY)),
    (13, 100002, 'diana', 4, 'Solid replacement', 'Good screen overall, minor ghosting in the first hour then it settled.', 'published', 0, DATE_SUB(NOW(), INTERVAL 41 DAY)),
    (14, 100002, 'eric', 5, 'No more cracked screen', 'Replacement was straightforward and the glass feels premium.', 'published', 1, DATE_SUB(NOW(), INTERVAL 33 DAY)),
    (15, 100002, 'faith', 4, 'Bright and sharp', 'Very pleased with the clarity, though shipping took a few days.', 'published', 0, DATE_SUB(NOW(), INTERVAL 26 DAY)),
    (16, 100002, 'ivan', 2, 'Arrived with a scratch', 'The panel itself works but there was a small scratch on arrival.', 'published', 0, DATE_SUB(NOW(), INTERVAL 19 DAY)),
    (17, 100002, 'jasmine', 5, 'Highly recommend', 'Brightness and touch are excellent, no complaints after two weeks.', 'published', 1, DATE_SUB(NOW(), INTERVAL 11 DAY)),
    (18, 100002, 'kevin', 5, 'Great value OLED', 'Cheaper than the service center quoted and works flawlessly.', 'published', 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),

    -- 100003 iPhone 14 Pro Max battery
    (19, 100003, 'lorna', 5, 'Battery like new', 'Back to a full day of heavy use. Capacity reports as expected.', 'published', 1, DATE_SUB(NOW(), INTERVAL 50 DAY)),
    (20, 100003, 'marvin', 5, 'Easy swap', 'Healthy replacement cell, no overheating after a week of use.', 'published', 1, DATE_SUB(NOW(), INTERVAL 44 DAY)),
    (21, 100003, 'nina', 4, 'Good battery', 'Holds charge well, only four stars because it shipped slowly.', 'published', 0, DATE_SUB(NOW(), INTERVAL 37 DAY)),
    (22, 100003, 'oscar', 5, 'Fixed my dying battery', 'Phone no longer shuts down at twenty percent. Very satisfied.', 'published', 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
    (23, 100003, 'patricia', 3, 'Decent but average', 'Works fine so far but capacity feels a touch lower than advertised.', 'published', 0, DATE_SUB(NOW(), INTERVAL 22 DAY)),
    (24, 100003, 'quincy', 5, 'Genuine feel', 'Installed cleanly and the phone behaves like new again.', 'published', 1, DATE_SUB(NOW(), INTERVAL 13 DAY)),
    (25, 100003, 'rose', 4, 'Reliable so far', 'Two weeks in and the battery health is holding steady.', 'published', 0, DATE_SUB(NOW(), INTERVAL 6 DAY)),

    -- 100004 iPhone 15 Pro OLED display
    (26, 100004, 'samuel', 5, 'ProMotion works', 'High refresh rate still smooth after the swap, colors are accurate.', 'published', 1, DATE_SUB(NOW(), INTERVAL 46 DAY)),
    (27, 100004, 'trisha', 5, 'Premium quality', 'The panel feels and looks like the genuine part.', 'published', 1, DATE_SUB(NOW(), INTERVAL 38 DAY)),
    (28, 100004, 'ulysses', 4, 'Good, slightly pricey', 'Excellent screen but it is one of the pricier options.', 'published', 0, DATE_SUB(NOW(), INTERVAL 29 DAY)),
    (29, 100004, 'vera', 5, 'Perfect match', 'True Tone and auto brightness both work correctly.', 'published', 1, DATE_SUB(NOW(), INTERVAL 17 DAY)),
    (30, 100004, 'wesley', 3, 'Works but took time', 'Installation was fiddly and I needed a guide, but it works now.', 'published', 0, DATE_SUB(NOW(), INTERVAL 9 DAY)),

    -- 100005 iPhone 16 rear camera
    (31, 100005, 'xander', 5, 'Camera sharp again', 'Photos are crisp and stabilisation works normally after fitting this module.', 'published', 1, DATE_SUB(NOW(), INTERVAL 42 DAY)),
    (32, 100005, 'yasmin', 5, 'Crystal clear shots', 'Low light photos are back to normal. Very happy.', 'published', 1, DATE_SUB(NOW(), INTERVAL 34 DAY)),
    (33, 100005, 'zara', 4, 'Good module', 'Focus is fast and accurate, minor delay on the first shot.', 'published', 0, DATE_SUB(NOW(), INTERVAL 27 DAY)),
    (34, 100005, 'noel', 5, 'OEM quality', 'Looks and performs like the original camera.', 'published', 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),
    (35, 100005, 'cindy', 4, 'Fixed the blur', 'No more cloudy photos, focus locks quickly now.', 'published', 0, DATE_SUB(NOW(), INTERVAL 12 DAY)),
    (36, 100005, 'danilo', 5, 'Great purchase', 'Well packed, worked the first time I powered it on.', 'published', 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),

    -- 100006 iPhone 17 Pro display (plus review id 1 above)
    (37, 100006, 'elaine', 5, 'Superb ProMotion screen', 'Smooth high refresh and accurate colors, feels brand new.', 'published', 1, DATE_SUB(NOW(), INTERVAL 44 DAY)),
    (38, 100006, 'fred', 5, 'Flawless panel', 'No dead pixels and brightness is excellent indoors and out.', 'published', 1, DATE_SUB(NOW(), INTERVAL 36 DAY)),
    (39, 100006, 'gina', 4, 'Very good', 'Great display, took a little longer to arrive than expected.', 'published', 0, DATE_SUB(NOW(), INTERVAL 25 DAY)),
    (40, 100006, 'harry', 5, 'Worth it', 'Expensive part but the quality justifies it.', 'published', 1, DATE_SUB(NOW(), INTERVAL 16 DAY)),
    (41, 100006, 'iris', 5, 'Like the original', 'Cannot tell the difference from the factory screen.', 'published', 0, DATE_SUB(NOW(), INTERVAL 8 DAY)),
    (42, 100006, 'jonas', 4, 'Solid replacement', 'Works well, minor color shift at extreme angles.', 'published', 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),

    -- 100007 Pixel 7 / 8 AMOLED display
    (43, 100007, 'kat', 5, 'AMOLED is gorgeous', 'Blacks are perfect and the refresh rate feels smooth.', 'published', 1, DATE_SUB(NOW(), INTERVAL 47 DAY)),
    (44, 100007, 'leo', 5, 'Fits Pixel 8', 'Installed on my Pixel 8 with no issues at all.', 'published', 1, DATE_SUB(NOW(), INTERVAL 39 DAY)),
    (45, 100007, 'mia', 4, 'Good screen', 'Bright and responsive, though slightly reflective in sunlight.', 'published', 0, DATE_SUB(NOW(), INTERVAL 31 DAY)),
    (46, 100007, 'nate', 5, 'Fixed my cracked Pixel', 'Touch works perfectly and colors look accurate.', 'published', 1, DATE_SUB(NOW(), INTERVAL 21 DAY)),
    (47, 100007, 'olivia', 3, 'Okay overall', 'Screen works but a faint line appeared near the edge.', 'published', 0, DATE_SUB(NOW(), INTERVAL 15 DAY)),
    (48, 100007, 'peter', 5, 'Very satisfied', 'Great replacement, would buy again from this store.', 'published', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),

    -- 100008 Pixel 9 / 10 battery
    (49, 100008, 'rhea', 5, 'Battery health restored', 'Back to all-day battery life on my Pixel 9.', 'published', 1, DATE_SUB(NOW(), INTERVAL 43 DAY)),
    (50, 100008, 'sofia', 5, 'No more random shutdowns', 'The old battery was swelling; this one is solid.', 'published', 1, DATE_SUB(NOW(), INTERVAL 32 DAY)),
    (51, 100008, 'troy', 4, 'Good cell', 'Works as expected, charging speed is normal.', 'published', 0, DATE_SUB(NOW(), INTERVAL 24 DAY)),
    (52, 100008, 'uma', 5, 'Reliable', 'A week in and the battery is holding up well.', 'published', 1, DATE_SUB(NOW(), INTERVAL 14 DAY)),
    (53, 100008, 'victor', 4, 'Decent battery', 'Good value, just wish it came with adhesive strips.', 'published', 0, DATE_SUB(NOW(), INTERVAL 7 DAY)),

    -- 100009 Galaxy S21 / S22 display (plus review id 4 above)
    (54, 100009, 'wendy', 5, 'Vivid display', 'Colors pop and the high refresh rate is buttery smooth.', 'published', 1, DATE_SUB(NOW(), INTERVAL 49 DAY)),
    (55, 100009, 'aaron', 5, 'Perfect for S22', 'Exact fit and the fingerprint sensor works under the screen.', 'published', 1, DATE_SUB(NOW(), INTERVAL 40 DAY)),
    (56, 100009, 'bella', 4, 'Good quality', 'Looks great, though shipping took a few extra days.', 'published', 0, DATE_SUB(NOW(), INTERVAL 33 DAY)),
    (57, 100009, 'oscar', 5, 'Bright and clear', 'Excellent brightness and the touch is very responsive.', 'published', 1, DATE_SUB(NOW(), INTERVAL 27 DAY)),
    (58, 100009, 'grace', 5, 'Matches the original', 'Color accuracy is close enough that I cannot tell the difference.', 'published', 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),
    (59, 100009, 'paul', 3, 'Slight light bleed', 'Works well, but a little light bleed in the corner.', 'published', 0, DATE_SUB(NOW(), INTERVAL 10 DAY)),
    (60, 100009, 'rhea', 5, 'Great replacement', 'Installed cleanly and the display looks superb.', 'published', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),

    -- 100010 Galaxy S23 / S24 Ultra battery
    (61, 100010, 'miguel', 5, 'Battery lasts all day', 'Heavy use and it still finishes the day with charge left.', 'published', 1, DATE_SUB(NOW(), INTERVAL 45 DAY)),
    (62, 100010, 'ana', 5, 'Great capacity', 'Capacity matches the listing and the phone runs cool.', 'published', 1, DATE_SUB(NOW(), INTERVAL 37 DAY)),
    (63, 100010, 'carlo', 4, 'Good battery', 'Solid replacement, a little pricey but worth it.', 'published', 0, DATE_SUB(NOW(), INTERVAL 30 DAY)),
    (64, 100010, 'liza', 5, 'No more afternoon top-ups', 'Huge improvement over the worn-out original.', 'published', 1, DATE_SUB(NOW(), INTERVAL 22 DAY)),
    (65, 100010, 'paolo', 5, 'Fits perfectly', 'Dropped in without any trimming or fuss.', 'published', 1, DATE_SUB(NOW(), INTERVAL 16 DAY)),
    (66, 100010, 'grace', 3, 'Works, slightly warm', 'Holds charge well but runs a little warm while charging.', 'published', 0, DATE_SUB(NOW(), INTERVAL 11 DAY)),
    (67, 100010, 'mark', 5, 'Solid replacement', 'Two weeks of use and capacity is still strong.', 'published', 1, DATE_SUB(NOW(), INTERVAL 9 DAY)),
    (68, 100010, 'bea', 4, 'Reliable so far', 'Good battery, the install just took a while.', 'published', 0, DATE_SUB(NOW(), INTERVAL 6 DAY)),
    (69, 100010, 'diana', 5, 'Back to normal', 'The phone now lasts like it did when new.', 'published', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),

    -- 100011 Galaxy S25 / S26 display
    (70, 100011, 'eric', 5, 'Gorgeous AMOLED', 'Vivid colors and excellent brightness in sunlight.', 'published', 1, DATE_SUB(NOW(), INTERVAL 41 DAY)),
    (71, 100011, 'faith', 5, 'Perfect fit', 'Installed cleanly with no gaps and the touch is flawless.', 'published', 1, DATE_SUB(NOW(), INTERVAL 34 DAY)),
    (72, 100011, 'ivan', 4, 'Good screen', 'Bright and sharp, a touch reflective at extreme angles.', 'published', 0, DATE_SUB(NOW(), INTERVAL 26 DAY)),
    (73, 100011, 'jasmine', 5, 'Very bright', 'Reads well outdoors and the colors are accurate.', 'published', 1, DATE_SUB(NOW(), INTERVAL 17 DAY)),
    (74, 100011, 'kevin', 2, 'Arrived cracked', 'The panel was damaged in transit but support replaced it quickly.', 'published', 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),

    -- 100012 ThinkPad T14 / X1 Carbon keyboard
    (75, 100012, 'lorna', 5, 'Typing feels great', 'The key travel matches the original keyboard closely.', 'published', 1, DATE_SUB(NOW(), INTERVAL 48 DAY)),
    (76, 100012, 'marvin', 5, 'Backlight works', 'Backlight and the trackpoint both function perfectly.', 'published', 1, DATE_SUB(NOW(), INTERVAL 38 DAY)),
    (77, 100012, 'nina', 4, 'Good keyboard', 'Great replacement, installation just takes a little care.', 'published', 0, DATE_SUB(NOW(), INTERVAL 28 DAY)),
    (78, 100012, 'oscar', 5, 'Trackpoint included', 'Nice that the trackpoint came with the assembly.', 'published', 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
    (79, 100012, 'patricia', 5, 'Exact match', 'Every key registers and the feel is consistent.', 'published', 1, DATE_SUB(NOW(), INTERVAL 13 DAY)),
    (80, 100012, 'quincy', 4, 'Solid', 'Good keyboard, the backlight is slightly uneven.', 'published', 0, DATE_SUB(NOW(), INTERVAL 7 DAY)),

    -- 100013 ThinkPad 65W USB-C adapter
    (81, 100013, 'rose', 5, 'Charges fast', 'Delivers full power and the laptop stopped complaining.', 'published', 1, DATE_SUB(NOW(), INTERVAL 44 DAY)),
    (82, 100013, 'samuel', 5, 'Genuine adapter', 'Build quality is excellent and it stays cool.', 'published', 1, DATE_SUB(NOW(), INTERVAL 35 DAY)),
    (83, 100013, 'trisha', 4, 'Works well', 'Good adapter, the cable is a little short for my setup.', 'published', 0, DATE_SUB(NOW(), INTERVAL 27 DAY)),
    (84, 100013, 'ulysses', 5, 'No more low battery', 'Perfect replacement for the charger I lost.', 'published', 1, DATE_SUB(NOW(), INTERVAL 19 DAY)),
    (85, 100013, 'vera', 5, 'Compact and light', 'Easy to carry and charges reliably every time.', 'published', 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),
    (86, 100013, 'wesley', 3, 'Gets warm', 'Charges fine but runs warmer than the original.', 'published', 0, DATE_SUB(NOW(), INTERVAL 8 DAY)),
    (87, 100013, 'xander', 4, 'Good value', 'Does the job at a fair price.', 'published', 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),

    -- 100014 IdeaPad Slim 3 / 5 screen
    (88, 100014, 'yasmin', 5, 'Crisp and bright', 'Full HD looks sharp and the panel is evenly lit.', 'published', 1, DATE_SUB(NOW(), INTERVAL 41 DAY)),
    (89, 100014, 'zara', 5, 'Perfect FHD panel', 'Exact fit and no dead pixels out of the box.', 'published', 1, DATE_SUB(NOW(), INTERVAL 34 DAY)),
    (90, 100014, 'noel', 4, 'Good display', 'Great screen, took a while to arrive.', 'published', 0, DATE_SUB(NOW(), INTERVAL 26 DAY)),
    (91, 100014, 'cindy', 5, 'Fixed the cracked screen', 'The laptop looks new again after the swap.', 'published', 1, DATE_SUB(NOW(), INTERVAL 17 DAY)),
    (92, 100014, 'danilo', 4, 'Nice', 'Good brightness and solid viewing angles.', 'published', 0, DATE_SUB(NOW(), INTERVAL 9 DAY)),
    (93, 100014, 'elaine', 5, 'Very happy', 'Matched the original panel perfectly.', 'published', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),

    -- 100015 IdeaPad battery (plus review id 2 above)
    (94, 100015, 'fred', 5, 'Long battery life', 'Back to several hours of use after the replacement.', 'published', 1, DATE_SUB(NOW(), INTERVAL 43 DAY)),
    (95, 100015, 'gina', 5, 'Reliable cell', 'Holds charge well and fits the bay exactly.', 'published', 1, DATE_SUB(NOW(), INTERVAL 32 DAY)),
    (96, 100015, 'harry', 3, 'Average', 'Works but the capacity feels slightly below the listing.', 'published', 0, DATE_SUB(NOW(), INTERVAL 24 DAY)),
    (97, 100015, 'iris', 4, 'Good replacement', 'Solid battery, happy with the purchase.', 'published', 0, DATE_SUB(NOW(), INTERVAL 14 DAY)),

    -- 100016 Steam Deck OLED screen
    (98, 100016, 'jonas', 5, 'Anti-glare is great', 'Far less reflection when gaming outdoors compared to the original.', 'published', 1, DATE_SUB(NOW(), INTERVAL 47 DAY)),
    (99, 100016, 'kat', 5, 'Made my Deck like new', 'Sharp and vibrant, and the anti-glare finish is a bonus.', 'published', 1, DATE_SUB(NOW(), INTERVAL 39 DAY)),
    (100, 100016, 'leo', 4, 'Good panel', 'Great screen, installation needs a steady hand.', 'published', 0, DATE_SUB(NOW(), INTERVAL 31 DAY)),
    (101, 100016, 'mia', 5, 'Colors are vivid', 'Games look fantastic on this replacement panel.', 'published', 1, DATE_SUB(NOW(), INTERVAL 21 DAY)),
    (102, 100016, 'nate', 5, 'Perfect replacement', 'Exact fit and the touch works flawlessly.', 'published', 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),
    (103, 100016, 'olivia', 4, 'Works fine', 'Good display, slightly dim at the lowest setting.', 'published', 0, DATE_SUB(NOW(), INTERVAL 10 DAY)),
    (104, 100016, 'peter', 5, 'Excellent', 'Looks better than the panel it replaced.', 'published', 1, DATE_SUB(NOW(), INTERVAL 6 DAY)),
    (105, 100016, 'quinn', 5, 'Highly recommend', 'Well packed and easy to fit.', 'published', 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),

    -- 100017 Steam Deck joystick modules (plus review id 3 above)
    (106, 100017, 'rhea', 5, 'Drift fixed completely', 'No more stick drift after installing these modules.', 'published', 1, DATE_SUB(NOW(), INTERVAL 45 DAY)),
    (107, 100017, 'sofia', 5, 'Smooth sticks', 'Movement is precise and the tension feels right.', 'published', 1, DATE_SUB(NOW(), INTERVAL 36 DAY)),
    (108, 100017, 'troy', 4, 'Good modules', 'Works well, the caps need a firm press to reseat.', 'published', 0, DATE_SUB(NOW(), INTERVAL 28 DAY)),
    (109, 100017, 'uma', 5, 'Hall effect is the way', 'Should outlast the original sticks by a long way.', 'published', 1, DATE_SUB(NOW(), INTERVAL 19 DAY)),
    (110, 100017, 'victor', 5, 'No more drift', 'My Deck plays like new again.', 'published', 1, DATE_SUB(NOW(), INTERVAL 11 DAY)),
    (111, 100017, 'wendy', 4, 'Solid pair', 'Good quality and the installation was quick.', 'published', 0, DATE_SUB(NOW(), INTERVAL 6 DAY)),

    -- 100018 Switch v1 / v2 LCD
    (112, 100018, 'aaron', 5, 'Switch looks new', 'Replaced a badly cracked screen and it looks factory fresh.', 'published', 1, DATE_SUB(NOW(), INTERVAL 46 DAY)),
    (113, 100018, 'bella', 5, 'Great LCD', 'Bright, sharp and the touch works perfectly.', 'published', 1, DATE_SUB(NOW(), INTERVAL 38 DAY)),
    (114, 100018, 'carlo', 4, 'Good screen', 'Works well, minor color difference from the original.', 'published', 0, DATE_SUB(NOW(), INTERVAL 30 DAY)),
    (115, 100018, 'diana', 5, 'Perfect fit', 'Dropped in with no gaps and no issues.', 'published', 1, DATE_SUB(NOW(), INTERVAL 21 DAY)),
    (116, 100018, 'eric', 5, 'Bright and clear', 'Very happy with the replacement.', 'published', 1, DATE_SUB(NOW(), INTERVAL 13 DAY)),
    (117, 100018, 'faith', 3, 'Works, slight light bleed', 'Functional panel but a little light bleed in the corner.', 'published', 0, DATE_SUB(NOW(), INTERVAL 7 DAY)),

    -- 100019 Switch 2 OLED
    (118, 100019, 'grace', 5, 'OLED is stunning', 'Deep blacks and vivid color on the Switch 2 panel.', 'published', 1, DATE_SUB(NOW(), INTERVAL 49 DAY)),
    (119, 100019, 'harry', 5, 'Gorgeous colors', 'The OLED upgrade is a huge step up.', 'published', 1, DATE_SUB(NOW(), INTERVAL 41 DAY)),
    (120, 100019, 'ivan', 4, 'Good panel', 'Looks great, took a few days to arrive.', 'published', 0, DATE_SUB(NOW(), INTERVAL 34 DAY)),
    (121, 100019, 'jasmine', 5, 'Beautiful display', 'Games look incredible after the swap.', 'published', 1, DATE_SUB(NOW(), INTERVAL 27 DAY)),
    (122, 100019, 'kevin', 5, 'Exact fit', 'Installed cleanly and the touch works perfectly.', 'published', 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
    (123, 100019, 'lorna', 4, 'Very good', 'Excellent screen, slightly expensive.', 'published', 0, DATE_SUB(NOW(), INTERVAL 15 DAY)),
    (124, 100019, 'marvin', 5, 'HDR looks great', 'HDR content really shines on this panel.', 'published', 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
    (125, 100019, 'nina', 5, 'Perfect replacement', 'Matched the original and works flawlessly.', 'published', 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),
    (126, 100019, 'oscar', 4, 'Nice screen', 'Good quality, installation just takes some care.', 'published', 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
    (127, 100019, 'patricia', 5, 'Absolutely worth it', 'The best upgrade I have made to my console.', 'published', 1, DATE_SUB(NOW(), INTERVAL 1 DAY))
ON DUPLICATE KEY UPDATE id = id;

-- ---------------------------------------------------------------------------
-- Demo stock movements. Audit trail for the seeded sales/receipts above.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_stock_movements
    (id, part_id, qty_delta, reason, reference_type, reference_id, employee_id, note, created_at)
VALUES
    (1, 100010, -1, 'sale',       'sale',            NULL, 2, 'Store sale ST-SEED-0001', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (2, 100013, -2, 'sale',       'sale',            NULL, 2, 'Store sale ST-SEED-0001', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (3, 100013, 10, 'receive',    'purchase_order',  NULL, 1, 'Initial stock receipt (PO-DEMO-0001)', DATE_SUB(NOW(), INTERVAL 25 DAY)),
    (4, 100001, -3, 'adjustment', 'adjustment',      NULL, 1, 'Cycle count adjustment', DATE_SUB(NOW(), INTERVAL 7 DAY)),
    (5, 100010, -1, 'repair_use', 'repair_job',      1,    4, 'Battery used on repair job RJ-SEED-0001', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (6, 100017, -1, 'sale',       'sale',            NULL, 2, 'Store sale ST-SEED-0002', DATE_SUB(NOW(), INTERVAL 5 DAY))
ON DUPLICATE KEY UPDATE id = id;

-- ---------------------------------------------------------------------------
-- Demo bug reports.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_bug_reports
    (id, username, email, title, description, images, status, created_at)
VALUES
    (1, 'demo', 'demo@circuit-cart.dev', 'Cart badge does not refresh',
     'After removing the last item the cart badge still shows a count until the page is reloaded.',
     NULL, 'new', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (2, NULL, 'guest@example.com', 'Typo on the checkout page',
     'The word "recieve" is spelled incorrectly on the checkout summary.',
     NULL, 'in_progress', DATE_SUB(NOW(), INTERVAL 6 DAY)),
    (3, 'demo', 'demo@circuit-cart.dev', 'Product filter resets after search',
     'Choosing a category and then searching clears the selected category.',
     NULL, 'fixed', DATE_SUB(NOW(), INTERVAL 12 DAY))
ON DUPLICATE KEY UPDATE id = id;

-- ---------------------------------------------------------------------------
-- Demo password reset token: already used and expired so it can never be
-- redeemed. Shows how tbl_password_resets rows look without exposing a live
-- token (only the sha256 hash is stored).
-- ---------------------------------------------------------------------------
INSERT INTO tbl_password_resets (id, user_id, token_hash, expires_at, used_at, created_at)
VALUES
    (1, 1, SHA2('demo-used-reset-token', 256), DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY))
ON DUPLICATE KEY UPDATE id = id;
