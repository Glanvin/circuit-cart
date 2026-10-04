# Testing

The PHPUnit suite for **Circuit Cart**. For the feature overview see [README.md](README.md); for installation see [SETUP.md](SETUP.md).

The app code depends on CodeIgniter's global singleton (`get_instance()`, `$this->db`, `$this->session`), which is hard to boot in isolation. Instead of loading the whole framework, the test suite uses a **stub bootstrap**: fake `CI_Controller` / `CI_Model` classes plus lightweight fakes for the session and database layer.

## Running the tests

```bash
vendor/bin/phpunit     # or: composer test
```

Run a single class or method:

```bash
vendor/bin/phpunit --filter ToastifyTest
vendor/bin/phpunit --filter testRenderEscapesHtmlInMessage
```

> Known failures (111 tests, 10 errors + 1 failure), none of them a regression:
> - `ControllerPagesTest` / `ControllerErrorPathsTest` — `FakeDb` has no `order_by()`, plus an `addStock()` arity mismatch. Both fakes need widening before these classes can pass.
> - `ConfigIntegrityTest::testRoutesMapToExpectedControllers` — expects `employee/manage/users` → `ManagementController`, but the route points at `ManagementController/users`.
> - `EmailControllerTest` — stale. It exercises `sendWelcome()` / `sendOTP()`, which moved into the helpers when `EmailController` was retired, and it injects its fakes after the constructor has already called `check_session()`. The `EmailController` that exists now is the employee attachment composer, so the class needs rewriting against that API.
>
> Not covered at all yet: the spreadsheet user import (`UserImporter`,
> `ManagementController::importUsers`) and the email composer. A release gate
> needs `tests/libraries/UserImporterTest.php` at minimum, since the row
> validation and the bcrypt 72-byte cut-off are pure logic and cheap to test.

## How it works

```
phpunit.xml.dist              # points PHPUnit at the bootstrap, registers test suites
tests/bootstrap.php           # defines BASEPATH/APPPATH, stubs CI_Controller/CI_Model/get_instance(),
                              #   provides FakeSession, FakeDb, FakeLoader/Input/Validation/Config,
                              #   a redirect() stub, and loads the real app classes
tests/support/FakeDb.php      # FakeDb: records where() calls; FakeDbResult: returns canned rows;
                              #   set $db->failQuery = TRUE to simulate a failed query
tests/libraries/ToastifyTest.php
tests/models/                 # PartsModelTest, UserModelTest (SQLi binding, edge cases, concurrency)
tests/security/               # AuthController + MY_Controller auth, form-rules & route integrity
tests/controllers/            # render + error-path tests for the page controllers
tests/helpers/                # check_session() and is_invalid() helper tests
```

Key idea from `tests/bootstrap.php`: a plain `CI_Controller` stub stores itself as the singleton, so when `Toastify` calls `get_instance()` it finds our fake controller with a `FakeSession` attached. Likewise, `PartsModel` extends the stubbed `CI_Model`, whose magic `__get` pulls `$this->db` from the same instance — where we've attached a `FakeDb`.

Controllers extend `CI_Controller`/`MY_Controller`, whose constructors validate the session before any method runs. `make_my_controller()` injects the `FakeSession`/`FakeToastify`/`FakeDb`/`FakeLoader` before invoking the constructor, so authorization and `render()` behaviour is testable. `redirect()` is stubbed: it records the destination (assert via `redirect_target()`) and, unless `redirect_stop(FALSE)` is called, throws a `RedirectException` to mimic CI halting the request.

## Writing a new test

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
