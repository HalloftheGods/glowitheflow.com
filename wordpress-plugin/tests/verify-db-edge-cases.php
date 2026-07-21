<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

require_once dirname(__DIR__) . '/includes/class-glow-db.php';

class MockWpdb {
    public $prefix = 'wp_';
    public $queries = [];
    public $inserts = [];
    public $should_fail_insert = false;
    public $should_throw_exception = false;

    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }

    public function prepare($query, ...$args) {
        $query = str_replace('%d', '%s', $query);
        $query = str_replace('%f', '%s', $query);
        return vsprintf($query, $args);
    }

    public function get_row($query, $output_type = 'OBJECT') {
        $this->queries[] = ['get_row', $query];
        return null;
    }

    public function insert($table, $data, $format = null) {
        $this->inserts[] = [
            'table' => $table,
            'data' => $data,
            'format' => $format
        ];
        if ($this->should_throw_exception) {
            throw new \RuntimeException("Database connection lost");
        }
        if ($this->should_fail_insert) {
            return false;
        }
        return 1;
    }

    public function query($query) {
        $this->queries[] = ['query', $query];
        return true;
    }
}

global $wpdb;
$wpdb = new MockWpdb();

function run_test($name, $callback) {
    global $wpdb;
    $wpdb->queries = [];
    $wpdb->inserts = [];
    $wpdb->should_fail_insert = false;
    $wpdb->should_throw_exception = false;
    
    try {
        $callback($wpdb);
        echo "PASS: $name\n";
    } catch (\Exception $e) {
        echo "FAIL: $name - " . $e->getMessage() . "\n";
    }
}

run_test("get_user_stats invalid IDs", function() {
    $res1 = Glow_DB::get_user_stats(0);
    $res2 = Glow_DB::get_user_stats(-5);
    $both_null = $res1 === null && $res2 === null;
    if (!$both_null) {
        throw new \Exception("Expected null for invalid IDs");
    }
});

run_test("initialize_user_stats user_id overflow", function($wpdb) {
    $huge_id = '9223372036854775808';
    $wpdb->should_fail_insert = true;
    $res = Glow_DB::initialize_user_stats($huge_id);
    $is_null = $res === null;
    if (!$is_null) {
        throw new \Exception("Expected null due to DB insert failure");
    }
    $last_insert = end($wpdb->inserts);
    $is_id_preserved = $last_insert['data']['user_id'] === '9223372036854775808';
    if (!$is_id_preserved) {
        throw new \Exception("Expected user_id to be preserved as string");
    }
});

run_test("log_transaction amount overflow", function($wpdb) {
    $res = Glow_DB::log_transaction(1, 3000000000, 'droplet', 'initial', 'Overflow amount');
    $is_false = $res === false;
    if (!$is_false) {
        throw new \Exception("Expected false when amount overflows");
    }
    $is_inserts_empty = empty($wpdb->inserts);
    if (!$is_inserts_empty) {
        throw new \Exception("Expected no inserts to be made due to early validation return");
    }
});

run_test("initialize_user_stats database exception rolls back transaction", function($wpdb) {
    $wpdb->should_throw_exception = true;
    $thrown = false;
    try {
        Glow_DB::initialize_user_stats(1);
    } catch (\RuntimeException $e) {
        $thrown = true;
    }
    if (!$thrown) {
        throw new \Exception("Expected RuntimeException to be thrown");
    }
    $rollback_called = false;
    foreach ($wpdb->queries as $q) {
        $is_rollback = $q[0] === 'query' && $q[1] === 'ROLLBACK';
        if ($is_rollback) {
            $rollback_called = true;
        }
    }
    if (!$rollback_called) {
        throw new \Exception("Expected ROLLBACK to be called when database exceptions are thrown");
    }
});
