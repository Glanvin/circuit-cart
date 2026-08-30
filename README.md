# Circuit Cart

An all-in-one web app for a tech repair shop — customers can browse the parts catalog (phone/laptop screens, batteries, Windows licenses, etc.), while employees manage inventory and stock.

Built on **CodeIgniter 3** with **Bootstrap 5**, **MySQL**, and a custom **Toastify** flash-notification library.

## Features

- **Customer side**
  - Home page with repair booking pitch and catalog links
  - Browse page: product card grid with client-side filtering by device type and category
- **Employee side**
  - Stock management table (`/inventory/stocks`): SKU, cost/retail prices, stock quantities
  - Employee dashboard & user/employee management *(work in progress)*

## Requirements

- PHP >= 7.2 (developed on PHP 8.1)
- MySQL / MariaDB
- XAMPP (or any Apache + PHP + MySQL stack)

## Setup

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
4. Point your browser at `http://localhost/ci3/`.

> The DB config uses XAMPP defaults (`root`, empty password). Do not ship that to production.

## Routes

| URL | Controller |
| --- | --- |
| `/` | `HomeController` |
| `/browse` | `BrowseController` |
| `/employee/dashboard` | `DashboardController` |
| `/employee/manage/users` | `ManagementController` |
| `/inventory/stocks` | `StockController` |
| `/inventory/stocks/new` | `StockController::newSKU()` |

## Project structure

```
application/
├── controllers/       # HomeController, BrowseController, StockController, ...
├── libraries/         # Toastify.php — flashdata-driven Bootstrap toasts
├── models/            # PartsModel.php — tech_parts queries
├── views/
│   ├── templates/     # header, footer, navbars (customer + employee)
│   └── pages/         # home, browse, inventory, employee dashboard
assets/                # Bootstrap, Chart.js, toast.js, style.css
system/                # CodeIgniter 3 core (do not modify)
tests/                 # PHPUnit tests (see below)
vendor/                # Composer dependencies incl. PHPUnit
```

## Testing

The app code depends on CodeIgniter's global singleton (`get_instance()`, `$this->db`, `$this->session`), which is hard to boot in isolation. Instead of loading the whole framework, the test suite uses a **stub bootstrap**: fake `CI_Controller` / `CI_Model` classes plus lightweight fakes for the session and database layer.

### Running the tests

```bash
vendor/bin/phpunit     # or: composer test
```

Run a single class or method:

```bash
vendor/bin/phpunit --filter ToastifyTest
vendor/bin/phpunit --filter testRenderEscapesHtmlInMessage
```

### How it works

```
phpunit.xml.dist              # points PHPUnit at the bootstrap, registers test suites
tests/bootstrap.php           # defines BASEPATH/APPPATH, stubs CI_Controller/CI_Model/get_instance(),
                              #   provides FakeSession, loads the real app library/model files
tests/support/FakeDb.php      # FakeDb: records where() calls; FakeDbResult: returns canned rows;
                              #   set $db->failQuery = TRUE to simulate a failed query
tests/libraries/ToastifyTest.php
tests/models/PartsModelTest.php
```

Key idea from `tests/bootstrap.php`: a plain `CI_Controller` stub stores itself as the singleton, so when `Toastify` calls `get_instance()` it finds our fake controller with a `FakeSession` attached. Likewise, `PartsModel` extends the stubbed `CI_Model`, whose magic `__get` pulls `$this->db` from the same instance — where we've attached a `FakeDb`.

### Writing a new test

Tests follow the **Arrange–Act–Assert** pattern:

```php
use PHPUnit\Framework\TestCase;

class MyModelTest extends TestCase {

    private $model;

    protected function setUp(): void {
        // Arrange: fresh CI instance + fakes per test
        $ci = new CI_Controller();
        $ci->db = new FakeDb();
        $ci->db->rows = [['id' => 1, 'name' => 'Screen']]; // what get() will return
        $this->model = new MyModel();
    }

    public function testSomething() {
        // Act
        $result = $this->model->getSomething();

        // Assert
        $this->assertSame('Screen', $result[0]['name']);
    }
}
```

Tips:

- One `setUp()` per test keeps tests isolated — state never leaks between them.
- Use `$this->db->whereCalls` to assert *which* filters were applied, and `$db->lastTable` to assert which table was queried.
- Set `$this->db->failQuery = TRUE;` then `expectException(Exception::class)` to test error paths.
- Save the file under `tests/libraries/` or `tests/models/` — both directories are registered in `phpunit.xml.dist`.
