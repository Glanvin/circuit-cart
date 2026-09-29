-- ============================================================================
-- Circuit Cart - employee portal schema
-- Run: mysql -u root circuit_cart < db_schema.sql
-- Idempotent (uses IF NOT EXISTS). Seeded employees log in with password123.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- Employees. Mirrors tbl_users, but userhistory is replaced by
-- attendance_history (JSON array of {date, time_in, time_out}).
-- employee_id is the 6-digit login ID (ZEROFILL display, e.g. 000001).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_employees (
    employee_id        INT(6) UNSIGNED ZEROFILL AUTO_INCREMENT PRIMARY KEY,
    username           VARCHAR(20)   NOT NULL UNIQUE,
    firstname          VARCHAR(100)  NOT NULL,
    middlename         VARCHAR(100)  NULL,
    lastname           VARCHAR(100)  NOT NULL,
    email              VARCHAR(100)  NOT NULL UNIQUE,
    contactnumber      VARCHAR(100)  NOT NULL,
    password           VARCHAR(255)  NOT NULL,
    role               VARCHAR(20)   NOT NULL DEFAULT 'cashier',
    attendance_history LONGTEXT      NULL,
    is_active          TINYINT(1)    NOT NULL DEFAULT 1,
    account_created    DATE          NULL DEFAULT (CURRENT_DATE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Sales / transactions. Store (POS) sales always count toward totals.
-- Online sales only count when status = 'delivered'.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tbl_sales (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_no       VARCHAR(32)   NOT NULL UNIQUE,
    origin           ENUM('store','online') NOT NULL,
    status           ENUM('processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'processing',
    sold_by          VARCHAR(20)   NULL,
    customer_email   VARCHAR(255)  NULL,
    customer_address VARCHAR(255)  NULL,
    items            JSON          NULL,
    subtotal         DECIMAL(10,2) NOT NULL DEFAULT 0,
    total            DECIMAL(10,2) NOT NULL DEFAULT 0,
    tendered         DECIMAL(10,2) NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Seed employees (login with the 6-digit ID, all demo accounts use
-- password123 so the app is immediately usable).
-- ---------------------------------------------------------------------------
INSERT INTO tbl_employees
    (employee_id, username, firstname, middlename, lastname, email, contactnumber, password, role, attendance_history)
VALUES
    (NULL, 'admin',      'Admin',      '', 'Root',     'admin@circuit-cart.dev',      '09170000001', '$2y$10$zmXSbAtAOKwZHNvaK/0d3e.QYrRdd5RMBp3c/dp8LVhf304OX9RrW', 'admin',
        '[{"date":"2026-09-01","time_in":"08:05","time_out":"17:02"},{"date":"2026-09-02","time_in":"08:00","time_out":"17:00"},{"date":"2026-09-03","time_in":"07:58","time_out":"16:55"},{"date":"2026-09-04","time_in":"08:12","time_out":"17:10"},{"date":"2026-09-05","time_in":"08:01","time_out":"17:00"}]'),
    (NULL, 'cashier1',   'Mae',        'D', 'Santos',  'cashier1@circuit-cart.dev',    '09170000002', '$2y$10$zmXSbAtAOKwZHNvaK/0d3e.QYrRdd5RMBp3c/dp8LVhf304OX9RrW', 'cashier',
        '[{"date":"2026-09-01","time_in":"09:00","time_out":"18:00"},{"date":"2026-09-02","time_in":"08:55","time_out":"18:05"},{"date":"2026-09-03","time_in":"09:10","time_out":"18:00"},{"date":"2026-09-04","time_in":"08:45","time_out":"17:55"},{"date":"2026-09-05","time_in":"09:02","time_out":"18:00"}]'),
    (NULL, 'manager1',   'Carl',       'R', 'Lim',     'manager1@circuit-cart.dev',    '09170000003', '$2y$10$zmXSbAtAOKwZHNvaK/0d3e.QYrRdd5RMBp3c/dp8LVhf304OX9RrW', 'manager',
        '[{"date":"2026-09-01","time_in":"07:30","time_out":"16:30"},{"date":"2026-09-02","time_in":"07:45","time_out":"16:45"},{"date":"2026-09-05","time_in":"07:40","time_out":"16:40"}]'),
    (NULL, 'tech1',      'Ben',        'A', 'Reyes',   'tech1@circuit-cart.dev',       '09170000004', '$2y$10$zmXSbAtAOKwZHNvaK/0d3e.QYrRdd5RMBp3c/dp8LVhf304OX9RrW', 'technician',
        '[{"date":"2026-09-01","time_in":"08:00","time_out":"17:00"},{"date":"2026-09-02","time_in":"08:00","time_out":"17:00"},{"date":"2026-09-04","time_in":"08:30","time_out":"17:30"}]'),
    (NULL, 'dev1',       'Sofia',      'M', 'Tan',     'dev1@circuit-cart.dev',        '09170000005', '$2y$10$zmXSbAtAOKwZHNvaK/0d3e.QYrRdd5RMBp3c/dp8LVhf304OX9RrW', 'developer',
        '[{"date":"2026-09-02","time_in":"09:00","time_out":"18:00"},{"date":"2026-09-03","time_in":"09:15","time_out":"18:10"},{"date":"2026-09-05","time_in":"09:00","time_out":"18:00"}]'),
    (NULL, 'logistics1', 'Rico',       'P', 'Garcia',  'logistics1@circuit-cart.dev',  '09170000006', '$2y$10$zmXSbAtAOKwZHNvaK/0d3e.QYrRdd5RMBp3c/dp8LVhf304OX9RrW', 'logistics',
        '[{"date":"2026-09-03","time_in":"07:00","time_out":"16:00"},{"date":"2026-09-04","time_in":"07:15","time_out":"16:30"},{"date":"2026-09-05","time_in":"07:05","time_out":"16:00"}]'),
    (NULL, 'deactivated', 'Unused',    'X', 'Employee', 'old@circuit-cart.dev',        '09170000007', '$2y$10$zmXSbAtAOKwZHNvaK/0d3e.QYrRdd5RMBp3c/dp8LVhf304OX9RrW', 'cashier', NULL)
    ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Deactivate the unused demo employee.
UPDATE tbl_employees SET is_active = 0 WHERE username = 'deactivated';

-- ---------------------------------------------------------------------------
-- Seed sales history: store (POS) + online (mostly delivered) spread over the
-- last ~30 days so the manager charts have data. Store sales ignore status.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_sales
    (receipt_no, origin, status, sold_by, customer_email, customer_address, items, subtotal, total, tendered, created_at)
VALUES
    ('ST-00001', 'store', 'processing', 'cashier1', NULL, NULL,
        '[{"part_id":100002,"name":"iPhone 12 / 13 Super Retina XDR OLED Display","qty":1,"price":9200.00}]',
        9200.00, 9200.00, 10000.00, DATE_SUB(NOW(), INTERVAL 28 DAY)),
    ('ST-00002', 'store', 'processing', 'cashier1', NULL, NULL,
        '[{"part_id":100003,"name":"iPhone 14 Pro Max High-Capacity Replacement Battery","qty":2,"price":3680.00}]',
        7360.00, 7360.00, 7360.00, DATE_SUB(NOW(), INTERVAL 26 DAY)),
    ('ST-00003', 'store', 'processing', 'cashier1', NULL, NULL,
        '[{"part_id":100013,"name":"Lenovo ThinkPad 65W USB-C Power Adapter","qty":1,"price":2530.00}]',
        2530.00, 2530.00, 3000.00, DATE_SUB(NOW(), INTERVAL 25 DAY)),
    ('ON-00001', 'online', 'delivered', 'cyanfnch', 'cyanfnch@example.com', '12 Rizal St, CDO',
        '[{"part_id":100007,"name":"Google Pixel 7 / 8 AMOLED Screen Assembly","qty":1,"price":8625.00}]',
        8625.00, 8655.00, NULL, DATE_SUB(NOW(), INTERVAL 24 DAY)),
    ('ST-00004', 'store', 'processing', 'cashier1', NULL, NULL,
        '[{"part_id":100006,"name":"iPhone 17 Pro Super Retina ProMotion Display","qty":1,"price":20700.00}]',
        20700.00, 20700.00, 21000.00, DATE_SUB(NOW(), INTERVAL 22 DAY)),
    ('ON-00002', 'online', 'delivered', 'cyanfnch', 'cyanfnch@example.com', '12 Rizal St, CDO',
        '[{"part_id":100010,"name":"Samsung Galaxy S23 Ultra / S24 Ultra Battery Pack","qty":1,"price":3450.00}]',
        3450.00, 3480.00, NULL, DATE_SUB(NOW(), INTERVAL 21 DAY)),
    ('ST-00005', 'store', 'processing', 'cashier1', NULL, NULL,
        '[{"part_id":100009,"name":"Samsung Galaxy S21 / S22 Dynamic AMOLED 2X Display","qty":1,"price":9775.00}]',
        9775.00, 9775.00, 10000.00, DATE_SUB(NOW(), INTERVAL 19 DAY)),
    ('ON-00003', 'online', 'delivered', NULL, 'guest1@gmail.com', '7 Mabini Ave, Cebu',
        '[{"part_id":100016,"name":"Steam Deck OLED Anti-Glare Touchscreen Assembly","qty":1,"price":7475.00},{"part_id":100017,"name":"Steam Deck Hall Effect Joystick Module Pair","qty":1,"price":2070.00}]',
        9545.00, 9575.00, NULL, DATE_SUB(NOW(), INTERVAL 17 DAY)),
    ('ST-00006', 'store', 'processing', 'cashier1', NULL, NULL,
        '[{"part_id":100005,"name":"iPhone 16 Rear Main Camera Module","qty":1,"price":9775.00}]',
        9775.00, 9775.00, 9775.00, DATE_SUB(NOW(), INTERVAL 15 DAY)),
    ('ON-00004', 'online', 'delivered', 'cyanfnch', 'cyanfnch@example.com', '12 Rizal St, CDO',
        '[{"part_id":100004,"name":"iPhone 15 Pro OLED Display Assembly","qty":1,"price":16100.00}]',
        16100.00, 16130.00, NULL, DATE_SUB(NOW(), INTERVAL 14 DAY)),
    ('ST-00007', 'store', 'processing', 'cashier1', NULL, NULL,
        '[{"part_id":100012,"name":"Lenovo ThinkPad T14 / X1 Carbon Backlit Keyboard","qty":2,"price":4830.00}]',
        9660.00, 9660.00, 10000.00, DATE_SUB(NOW(), INTERVAL 12 DAY)),
    ('ON-00005', 'online', 'processing', NULL, 'guest2@gmail.com', '88 Bonifacio St, Davao',
        '[{"part_id":100011,"name":"Samsung Galaxy S25 / S26 Dynamic AMOLED Screen Assembly","qty":1,"price":17825.00}]',
        17825.00, 17855.00, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY)),
    ('ST-00008', 'store', 'processing', 'cashier1', NULL, NULL,
        '[{"part_id":100015,"name":"Lenovo IdeaPad 52.5Wh 3-Cell Internal Battery","qty":3,"price":3565.00}]',
        10695.00, 10695.00, 11000.00, DATE_SUB(NOW(), INTERVAL 9 DAY)),
    ('ON-00006', 'online', 'delivered', NULL, 'guest3@gmail.com', '3 Acacia St, Manila',
        '[{"part_id":100008,"name":"Google Pixel 9 / 10 Li-ion Replacement Battery","qty":2,"price":3220.00}]',
        6440.00, 6470.00, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY)),
    ('ST-00009', 'store', 'processing', 'cashier1', NULL, NULL,
        '[{"part_id":100002,"name":"iPhone 12 / 13 Super Retina XDR OLED Display","qty":1,"price":9200.00}]',
        9200.00, 9200.00, 9500.00, DATE_SUB(NOW(), INTERVAL 6 DAY)),
    ('ON-00007', 'online', 'delivered', 'cyanfnch', 'cyanfnch@example.com', '12 Rizal St, CDO',
        '[{"part_id":100019,"name":"Nintendo Switch 2 OLED Touchscreen Assembly","qty":1,"price":6670.00}]',
        6670.00, 6700.00, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY)),
    ('ST-00010', 'store', 'processing', 'cashier1', NULL, NULL,
        '[{"part_id":100010,"name":"Samsung Galaxy S23 Ultra / S24 Ultra Battery Pack","qty":1,"price":3450.00},{"part_id":100013,"name":"Lenovo ThinkPad 65W USB-C Power Adapter","qty":1,"price":2530.00}]',
        5980.00, 5980.00, 6000.00, DATE_SUB(NOW(), INTERVAL 3 DAY)),
    ('ON-00008', 'online', 'shipped', NULL, 'guest4@gmail.com', '21 Luna St, Iloilo',
        '[{"part_id":100014,"name":"Lenovo IdeaPad Slim 3 / 5 Full HD IPS Screen Panel","qty":1,"price":6325.00}]',
        6325.00, 6355.00, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY)),
    ('ON-00009', 'online', 'delivered', NULL, 'guest5@gmail.com', '5 Osmena St, Bacolod',
        '[{"part_id":100018,"name":"Nintendo Switch v1 / v2 Touch LCD Assembly","qty":1,"price":2875.00}]',
        2875.00, 2905.00, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY)),
    ('ON-00010', 'online', 'cancelled', NULL, 'guest6@gmail.com', '44 Del Pilar St, CDO',
        '[{"part_id":100003,"name":"iPhone 14 Pro Max High-Capacity Replacement Battery","qty":1,"price":3680.00}]',
        3680.00, 3710.00, NULL, DATE_SUB(NOW(), INTERVAL 27 DAY)),
    ('ON-00011', 'online', 'delivered', 'cyanfnch', 'cyanfnch@example.com', '12 Rizal St, CDO',
        '[{"part_id":100007,"name":"Google Pixel 7 / 8 AMOLED Screen Assembly","qty":1,"price":8625.00}]',
        8625.00, 8655.00, NULL, DATE_SUB(NOW(), INTERVAL 20 DAY));