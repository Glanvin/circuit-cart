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
    firstname          VARCHAR(100)  NOT NULL,
    middlename         VARCHAR(100)  NULL,
    lastname           VARCHAR(100)  NOT NULL,
    email              VARCHAR(100)  NOT NULL UNIQUE,
    verified_email     TINYINT(1)    NOT NULL DEFAULT 0,
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
-- Seed demo accounts. All passwords are password123. Employees log in with
-- the 6-digit ID (000001-000006); the customer logs in with username demo.
-- ---------------------------------------------------------------------------
INSERT INTO tbl_employees
    (employee_id, firstname, middlename, lastname, email, verified_email, contactnumber, password, role, attendance_history)
VALUES
    (NULL, 'Ada',        '', 'Admin',      'admin@circuit-cart.dev',      1, '09170000001', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'admin',      NULL),
    (NULL, 'Casey',      '', 'Cashier',    'cashier@circuit-cart.dev',    1, '09170000002', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'cashier',    NULL),
    (NULL, 'Morgan',     '', 'Manager',    'manager@circuit-cart.dev',    1, '09170000003', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'manager',    NULL),
    (NULL, 'Taylor',     '', 'Technician', 'technician@circuit-cart.dev', 1, '09170000004', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'technician', NULL),
    (NULL, 'Dev',        '', 'Developer',  'developer@circuit-cart.dev',  1, '09170000005', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'developer',  NULL),
    (NULL, 'Louis',      '', 'Logistics',  'logistics@circuit-cart.dev',  1, '09170000006', '$2y$10$BF1Bn8hQc91vxhEJZGoZZOnFNSr64taTu2BpiQC15XMjovmCXiaXS', 'logistics',  NULL)
    ON DUPLICATE KEY UPDATE password = VALUES(password);

INSERT INTO tbl_users
    (id, username, firstname, middlename, lastname, email, verified_email, contactnumber, address, password, role, userhistory, account_created)
VALUES
    (1, 'demo', 'Demo', '', 'User', 'demo@circuit-cart.dev', 1, '09170000000', '019 Corrales Ext, Cagayan de Oro City', '$2y$10$dOGrfXBZb0F7CItHbzTDF.rctBa.tCjGU5cJHxmJTRi3PT0NxTy7W', 'user', NULL, NULL)
    ON DUPLICATE KEY UPDATE username = VALUES(username);