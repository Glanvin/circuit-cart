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
    compatibility    JSON         NOT NULL,
    technical_specs  JSON         NOT NULL,
    is_active        TINYINT(1)   DEFAULT 1,
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (part_id),
    UNIQUE KEY sku (sku)
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
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_sales (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    receipt_no       VARCHAR(32)   NOT NULL,
    origin           ENUM('store','online') NOT NULL,
    status           ENUM('processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'processing',
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
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY receipt_no (receipt_no),
    KEY employee_id (employee_id),
    CONSTRAINT fk_sales_employee FOREIGN KEY (employee_id) REFERENCES tbl_employees(employee_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Repair bookings. username is the customer who booked (VARCHAR, not a FK, so
-- the column still matches the signup rule's 20-character limit).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_booking (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username       VARCHAR(20)  NOT NULL,
    device         VARCHAR(128) NOT NULL,
    issue          TEXT         NOT NULL,
    preferred_date DATE         NOT NULL,
    preferred_time TIME         NOT NULL,
    status         ENUM('pending','approved','rescheduled','declined','finished') NOT NULL DEFAULT 'pending',
    images         JSON         NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
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
    parts_used          JSON         NULL,
    labor_items         JSON         NULL,
    technician_notes    TEXT         NULL,
    status              ENUM('diagnosis','estimate_pending','approved','in_progress','ready_for_pickup','completed','cancelled') NOT NULL DEFAULT 'diagnosis',
    status_history      JSON         NULL,
    started_at          TIMESTAMP    NULL,
    completed_at        TIMESTAMP    NULL,
    picked_up_at        TIMESTAMP    NULL,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY job_reference (job_reference),
    KEY booking_id (booking_id),
    KEY technician_id (technician_id),
    KEY status (status),
    CONSTRAINT fk_repair_job_booking FOREIGN KEY (booking_id) REFERENCES tbl_booking(id) ON DELETE RESTRICT,
    CONSTRAINT fk_repair_job_technician FOREIGN KEY (technician_id) REFERENCES tbl_employees(employee_id) ON DELETE SET NULL
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
    ('Cash', 'cash', 0, 1, 1),
    ('Credit/Debit Card', 'card', 1, 1, 2),
    ('GCash', 'gcash', 1, 1, 3),
    ('Bank Transfer', 'bank', 1, 1, 4),
    ('Gift Card', 'giftcard', 1, 1, 5)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active), sort_order = VALUES(sort_order);

-- ---------------------------------------------------------------------------
-- Default tax rates (Philippines VAT 12%)
-- ---------------------------------------------------------------------------
INSERT INTO tbl_tax_rates (name, rate_percent, applies_to, is_active) VALUES
    ('VAT 12%', 12.00, 'both', 1)
ON DUPLICATE KEY UPDATE rate_percent = VALUES(rate_percent), is_active = VALUES(is_active);

-- ---------------------------------------------------------------------------
-- Stock movements. Audit trail for all inventory changes.
-- reason: sale, adjustment, receive, repair_use, return, count, write_off
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_stock_movements (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    part_id         INT UNSIGNED NOT NULL,
    qty_delta       INT          NOT NULL,
    reason          ENUM('sale','adjustment','receive','repair_use','return','count','write_off') NOT NULL,
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
