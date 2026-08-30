<?php

use PHPUnit\Framework\TestCase;

/**
 * Performance benchmarks for the application components.
 *
 * Uses the real CI3 Benchmark class to measure execution time
 * and memory usage across key subsystems. Run with:
 *
 *     composer test:benchmark
 */
class BenchmarkTest extends TestCase {

    /** @var CI_Benchmark */
    private $bm;

    protected function setUp(): void {
        require_once BASEPATH . 'core/Benchmark.php';
        $this->bm = new CI_Benchmark();
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Run $iterations calls of $callback and return elapsed time.
     */
    private function bench(callable $callback, int $iterations): float {
        $start = 'bench_' . spl_object_id($callback) . '_start';
        $end   = $start . '_end';

        $this->bm->mark($start);
        for ($i = 0; $i < $iterations; $i++) {
            $callback();
        }
        $this->bm->mark($end);

        return (float) $this->bm->elapsed_time($start, $end, 6);
    }

    private function memoryMB(): string {
        return number_format(memory_get_usage(true) / 1048576, 2) . ' MB';
    }

    private function report(string $label, float $seconds, int $iterations): void {
        $per = $seconds / $iterations;
        echo sprintf(
            "  %-44s %8d runs  %10.6fs total  %12.6f us/op\n",
            $label,
            $iterations,
            $seconds,
            $per * 1_000_000
        );
    }

    // ----------------------------------------------------------------
    // Benchmarks
    // ----------------------------------------------------------------

    public function test_helper_performance() {
        echo "\n--- Helpers ---\n";

        $ci = new CI_Controller();
        $ci->session = new FakeSession();

        $this->report(
            'check_session() [valid]',
            $this->bench(function () use ($ci) {
                $ci->session->set_userdata('username', 'alice');
                check_session();
            }, 10_000),
            10_000
        );

        $this->report(
            'is_invalid() [no error]',
            $this->bench(function () {
                is_invalid('username');
            }, 10_000),
            10_000
        );

        echo "  Memory: " . $this->memoryMB() . "\n";
        $this->assertTrue(TRUE);
    }

    public function test_toastify_performance() {
        echo "\n--- Toastify Library ---\n";

        $ci = new CI_Controller();
        $ci->session = new FakeSession();
        $toast = new Toastify();

        $this->report(
            'success()',
            $this->bench(function () use ($toast) {
                $toast->success('ok');
            }, 10_000),
            10_000
        );

        $ci->session = new FakeSession();
        $toast = new Toastify();

        $this->report(
            'error()',
            $this->bench(function () use ($toast) {
                $toast->error('fail');
            }, 10_000),
            10_000
        );

        $ci->session = new FakeSession();
        $toast = new Toastify();
        $toast->success('a');
        $toast->error('b');
        $toast->info('c');

        $this->report(
            'render() [3 toasts]',
            $this->bench(function () use ($toast) {
                $toast->render();
            }, 5_000),
            5_000
        );

        echo "  Memory: " . $this->memoryMB() . "\n";
        $this->assertTrue(TRUE);
    }

    public function test_model_query_performance() {
        echo "\n--- PartsModel (FakeDb) ---\n";

        $ci = new CI_Controller();
        $db = new FakeDb();
        $db->rows = [
            ['part_id' => 1, 'part_name' => 'Test Part'],
            ['part_id' => 2, 'part_name' => 'Another Part'],
        ];
        $ci->db = $db;
        $model = new PartsModel();

        $this->report(
            'getAllParts()',
            $this->bench(function () use ($model) {
                $model->getAllParts();
            }, 5_000),
            5_000
        );

        $db->rows = [['part_id' => 100001, 'part_name' => 'Fan']];

        $this->report(
            'getPartById()',
            $this->bench(function () use ($model) {
                $model->getPartById(100001);
            }, 5_000),
            5_000
        );

        echo "  Memory: " . $this->memoryMB() . "\n";
        $this->assertTrue(TRUE);
    }

    public function test_form_validation_overhead() {
        echo "\n--- Form Validation Setup ---\n";

        $ci = new CI_Controller();
        $fv = new stdClass();
        $fv->rules = [];

        $rules = [
            ['field' => 'username', 'label' => 'Username', 'rules' => 'required|min_length[5]'],
            ['field' => 'email',    'label' => 'Email',    'rules' => 'required|valid_email'],
            ['field' => 'password', 'label' => 'Password', 'rules' => 'required|min_length[6]'],
        ];

        $this->report(
            'set_rules() × 3 fields',
            $this->bench(function () use ($rules) {
                // Simulate the cost of parsing rule arrays
                foreach ($rules as $rule) {
                    explode('|', $rule['rules']);
                }
            }, 10_000),
            10_000
        );

        echo "  Memory: " . $this->memoryMB() . "\n";
        $this->assertTrue(TRUE);
    }

    public function test_ci_benchmark_overhead() {
        echo "\n--- CI3 Benchmark Class Itself ---\n";

        $this->report(
            'mark()',
            $this->bench(function () {
                $this->bm->mark('test_marker');
            }, 50_000),
            50_000
        );

        $this->bm->mark('btwn_start');
        $this->bm->mark('btwn_end');

        $this->report(
            'elapsed_time()',
            $this->bench(function () {
                $this->bm->elapsed_time('btwn_start', 'btwn_end', 6);
            }, 50_000),
            50_000
        );

        echo "  Memory: " . $this->memoryMB() . "\n";
        $this->assertTrue(TRUE);
    }

    public function test_many_user_signin_throughput() {
        echo "\n--- Many User Sign-In Throughput (UserModel / FakeDb) ---\n";

        $users  = 10_000;
        $db     = new FakeDb();
        $ci     = new CI_Controller();
        $ci->db = $db;
        $model  = new UserModel();

        $callback = function () use ($model, $db) {
            $db->rows = [['username' => 'benchuser', 'password' => 'secret', 'role' => 'staff']];
            $model->signInUser('benchuser');
        };

        $this->report(
            'signInUser()',
            $this->bench($callback, $users),
            $users
        );

        echo "  Memory: " . $this->memoryMB() . "\n";
        $this->assertTrue(TRUE);
    }
}
