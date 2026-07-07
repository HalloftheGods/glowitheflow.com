<?php

use PHPUnit\Framework\TestCase;

class Test_Glow_DB extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Brain\Monkey\setUp();
    }

    protected function tearDown(): void {
        Brain\Monkey\tearDown();
        parent::tearDown();
    }

    public function test_hook_registration() {
        Brain\Monkey\Functions\expect('register_activation_hook')
            ->once()
            ->with(Mockery::type('string'), ['Glow_DB', 'activate']);

        Brain\Monkey\Functions\expect('register_deactivation_hook')
            ->once()
            ->with(Mockery::type('string'), ['Glow_DB', 'deactivate']);

        require dirname(__DIR__) . '/glowitheflow.php';
    }

    public function test_get_table_name() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $this->assertEquals('wp_glow_user_stats', Glow_DB::get_table_name('user_stats'));
    }

    public function test_create_tables() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('get_charset_collate')->andReturn('DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $assert_sql_structure = function($sql) {
            $has_uppercase_keywords = strpos($sql, 'CREATE TABLE') !== false 
                && strpos($sql, 'PRIMARY KEY') !== false;

            $has_lowercase_types = strpos($sql, 'bigint') !== false 
                && strpos($sql, 'int') !== false 
                && strpos($sql, 'float') !== false 
                && strpos($sql, 'varchar') !== false;

            $has_two_spaces_primary_key = strpos($sql, 'PRIMARY KEY  (') !== false;
            $has_no_backticks = strpos($sql, '`') === false;

            $has_stats_table = strpos($sql, 'wp_glow_user_stats') !== false;
            $has_posts_table = strpos($sql, 'wp_glow_posts') !== false;
            $has_tx_table = strpos($sql, 'wp_glow_transactions') !== false;

            $has_quoted_defaults = strpos($sql, "droplet_balance int DEFAULT '50' NOT NULL") !== false
                && strpos($sql, "driplet_balance int DEFAULT '0' NOT NULL") !== false
                && strpos($sql, "depth_multiplier float DEFAULT '1' NOT NULL") !== false
                && strpos($sql, "passenger_count int DEFAULT '1' NOT NULL") !== false
                && strpos($sql, "glow_score int DEFAULT '0' NOT NULL") !== false
                && strpos($sql, "user_id bigint unsigned DEFAULT '0' NOT NULL") !== false
                && strpos($sql, "type varchar(20) DEFAULT '' NOT NULL") !== false
                && strpos($sql, "content text DEFAULT NULL") !== false
                && strpos($sql, "amount int DEFAULT '0' NOT NULL") !== false
                && strpos($sql, "unit varchar(20) DEFAULT '' NOT NULL") !== false;

            $has_nullable_datetimes = strpos($sql, "last_interaction_at datetime DEFAULT NULL") !== false
                && strpos($sql, "created_at datetime DEFAULT NULL") !== false;

            $has_no_zero_dates = strpos($sql, '0000-00-00 00:00:00') === false;

            $has_created_at_index = strpos($sql, 'KEY created_at (created_at)') !== false;

            $has_innodb = substr_count($sql, 'ENGINE=InnoDB') === 3;

            return $has_uppercase_keywords 
                && $has_lowercase_types 
                && $has_two_spaces_primary_key 
                && $has_no_backticks 
                && $has_stats_table 
                && $has_posts_table 
                && $has_tx_table 
                && $has_quoted_defaults
                && $has_nullable_datetimes
                && $has_no_zero_dates
                && $has_created_at_index
                && $has_innodb;
        };

        Brain\Monkey\Functions\expect('dbDelta')
            ->once()
            ->with(Mockery::on($assert_sql_structure));

        Glow_DB::create_tables();
    }

    public function test_log_transaction() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        Brain\Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 09:00:00');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_transactions',
                [
                    'user_id' => 1,
                    'amount' => 50,
                    'unit' => 'droplet',
                    'type' => 'initial',
                    'details' => 'Initial droplet balance',
                    'created_at' => '2026-07-05 09:00:00'
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            )
            ->andReturn(1);

        Glow_DB::log_transaction(1, 50, 'droplet', 'initial', 'Initial droplet balance');
    }

    public function test_initialize_user_stats() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_user_stats',
                [
                    'user_id' => 1,
                    'droplet_balance' => 50,
                    'driplet_balance' => 0,
                    'depth_multiplier' => 1.0,
                    'last_interaction_at' => null
                ],
                ['%s', '%d', '%d', '%f', '%s']
            )
            ->andReturn(1);

        Brain\Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 09:00:00');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_transactions',
                [
                    'user_id' => 1,
                    'amount' => 50,
                    'unit' => 'droplet',
                    'type' => 'initial',
                    'details' => 'Initial droplet balance',
                    'created_at' => '2026-07-05 09:00:00'
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            )
            ->andReturn(1);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('COMMIT');

        $result = Glow_DB::initialize_user_stats(1);
        $expected = [
            'user_id' => 1,
            'droplet_balance' => 50,
            'driplet_balance' => 0,
            'depth_multiplier' => 1.0,
            'last_interaction_at' => null
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_get_user_stats_existing() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $expected_row = [
            'user_id' => '1',
            'droplet_balance' => '50',
            'driplet_balance' => '0',
            'depth_multiplier' => '1.0',
            'last_interaction_at' => null
        ];

        $wpdb->shouldReceive('prepare')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = %s', 1)
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = 1', ARRAY_A)
            ->andReturn($expected_row);

        $result = Glow_DB::get_user_stats(1);
        $expected = [
            'user_id' => 1,
            'droplet_balance' => 50,
            'driplet_balance' => 0,
            'depth_multiplier' => 1.0,
            'last_interaction_at' => null
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_get_user_stats_not_existing() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('prepare')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = %s', 1)
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = 1', ARRAY_A)
            ->andReturn(null);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_user_stats',
                [
                    'user_id' => 1,
                    'droplet_balance' => 50,
                    'driplet_balance' => 0,
                    'depth_multiplier' => 1.0,
                    'last_interaction_at' => null
                ],
                ['%s', '%d', '%d', '%f', '%s']
            )
            ->andReturn(1);

        Brain\Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 09:00:00');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_transactions',
                [
                    'user_id' => 1,
                    'amount' => 50,
                    'unit' => 'droplet',
                    'type' => 'initial',
                    'details' => 'Initial droplet balance',
                    'created_at' => '2026-07-05 09:00:00'
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            )
            ->andReturn(1);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('COMMIT');

        $result = Glow_DB::get_user_stats(1);
        $expected = [
            'user_id' => 1,
            'droplet_balance' => 50,
            'driplet_balance' => 0,
            'depth_multiplier' => 1.0,
            'last_interaction_at' => null
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_get_user_stats_invalid_id() {
        $this->assertNull(Glow_DB::get_user_stats(0));
        $this->assertNull(Glow_DB::get_user_stats(-5));
    }

    public function test_initialize_user_stats_invalid_id() {
        $this->assertNull(Glow_DB::initialize_user_stats(0));
        $this->assertNull(Glow_DB::initialize_user_stats(-5));
    }

    public function test_log_transaction_invalid_id() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->shouldNotReceive('insert');
        Glow_DB::log_transaction(0, 50, 'droplet', 'initial', 'Initial droplet balance');
        Glow_DB::log_transaction(-5, 50, 'droplet', 'initial', 'Initial droplet balance');
    }

    public function test_initialize_user_stats_insert_fails() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_user_stats',
                [
                    'user_id' => 1,
                    'droplet_balance' => 50,
                    'driplet_balance' => 0,
                    'depth_multiplier' => 1.0,
                    'last_interaction_at' => null
                ],
                ['%s', '%d', '%d', '%f', '%s']
            )
            ->andReturn(false);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('ROLLBACK');

        $wpdb->shouldReceive('prepare')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = %s', 1)
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = 1', ARRAY_A)
            ->andReturn(null);

        $result = Glow_DB::initialize_user_stats(1);
        $this->assertNull($result);
    }

    public function test_activate() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('get_charset_collate')->andReturn('DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        Brain\Monkey\Functions\expect('dbDelta')
            ->once();
        Glow_DB::activate();
        $this->assertTrue(true);
    }

    public function test_deactivate() {
        Glow_DB::deactivate();
        $this->assertTrue(true);
    }

    public function test_get_user_stats_initialization_race_condition_success() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $expected_row = [
            'user_id' => '1',
            'droplet_balance' => '50',
            'driplet_balance' => '0',
            'depth_multiplier' => '1.0',
            'last_interaction_at' => null
        ];

        $wpdb->shouldReceive('prepare')
            ->twice()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = %s', 1)
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->twice()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = 1', ARRAY_A)
            ->andReturn(null, $expected_row);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_user_stats',
                [
                    'user_id' => 1,
                    'droplet_balance' => 50,
                    'driplet_balance' => 0,
                    'depth_multiplier' => 1.0,
                    'last_interaction_at' => null
                ],
                ['%s', '%d', '%d', '%f', '%s']
            )
            ->andReturn(false);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('ROLLBACK');

        $result = Glow_DB::get_user_stats(1);
        $expected = [
            'user_id' => 1,
            'droplet_balance' => 50,
            'driplet_balance' => 0,
            'depth_multiplier' => 1.0,
            'last_interaction_at' => null
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_get_user_stats_initialization_race_condition_failure() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('prepare')
            ->twice()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = %s', 1)
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->times(3)
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = 1', ARRAY_A)
            ->andReturn(null, null, null);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_user_stats',
                [
                    'user_id' => 1,
                    'droplet_balance' => 50,
                    'driplet_balance' => 0,
                    'depth_multiplier' => 1.0,
                    'last_interaction_at' => null
                ],
                ['%s', '%d', '%d', '%f', '%s']
            )
            ->andReturn(false);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('ROLLBACK');

        $result = Glow_DB::get_user_stats(1);
        $this->assertNull($result);
    }

    public function test_initialize_user_stats_transaction_logging_fails() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_user_stats',
                [
                    'user_id' => 1,
                    'droplet_balance' => 50,
                    'driplet_balance' => 0,
                    'depth_multiplier' => 1.0,
                    'last_interaction_at' => null
                ],
                ['%s', '%d', '%d', '%f', '%s']
            )
            ->andReturn(1);

        Brain\Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 09:00:00');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_transactions',
                [
                    'user_id' => 1,
                    'amount' => 50,
                    'unit' => 'droplet',
                    'type' => 'initial',
                    'details' => 'Initial droplet balance',
                    'created_at' => '2026-07-05 09:00:00'
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            )
            ->andReturn(false);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('ROLLBACK');

        $result = Glow_DB::initialize_user_stats(1);
        $this->assertNull($result);
    }

    public function test_get_user_stats_db_error() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = 'Deadlock found when trying to get lock; try restarting transaction';

        $wpdb->shouldReceive('prepare')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = %s', 1)
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = 1', ARRAY_A)
            ->andReturn(null);

        $wpdb->shouldNotReceive('insert');
        $wpdb->shouldNotReceive('query');

        $result = Glow_DB::get_user_stats(1);
        $this->assertNull($result);
    }

    public function test_initialize_user_stats_concurrency_fallback() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $expected_row = [
            'user_id' => '1',
            'droplet_balance' => '50',
            'driplet_balance' => '0',
            'depth_multiplier' => '1.0',
            'last_interaction_at' => null
        ];

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_user_stats',
                [
                    'user_id' => 1,
                    'droplet_balance' => 50,
                    'driplet_balance' => 0,
                    'depth_multiplier' => 1.0,
                    'last_interaction_at' => null
                ],
                ['%s', '%d', '%d', '%f', '%s']
            )
            ->andReturn(false);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('ROLLBACK');

        $wpdb->shouldReceive('prepare')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = %s', 1)
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = 1', ARRAY_A)
            ->andReturn($expected_row);

        $result = Glow_DB::initialize_user_stats(1);
        $expected = [
            'user_id' => 1,
            'droplet_balance' => 50,
            'driplet_balance' => 0,
            'depth_multiplier' => 1.0,
            'last_interaction_at' => null
        ];
        $this->assertEquals($expected, $result);
    }

    public function test_initialize_user_stats_transaction_rollback() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_user_stats',
                [
                    'user_id' => 1,
                    'droplet_balance' => 50,
                    'driplet_balance' => 0,
                    'depth_multiplier' => 1.0,
                    'last_interaction_at' => null
                ],
                ['%s', '%d', '%d', '%f', '%s']
            )
            ->andReturn(1);

        Brain\Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 09:00:00');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_transactions',
                [
                    'user_id' => 1,
                    'amount' => 50,
                    'unit' => 'droplet',
                    'type' => 'initial',
                    'details' => 'Initial droplet balance',
                    'created_at' => '2026-07-05 09:00:00'
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            )
            ->andReturn(false);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('ROLLBACK');

        $result = Glow_DB::initialize_user_stats(1);
        $this->assertNull($result);
    }

    public function test_log_transaction_amount_overflow() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $wpdb->shouldNotReceive('insert');

        $result = Glow_DB::log_transaction(1, 3000000000, 'droplet', 'initial', 'Large amount transaction');
        $this->assertFalse($result);
    }

    public function test_log_transaction_null_unit_fails() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        Brain\Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 09:00:00');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_transactions',
                [
                    'user_id' => 1,
                    'amount' => 50,
                    'unit' => null,
                    'type' => 'initial',
                    'details' => 'Null unit transaction',
                    'created_at' => '2026-07-05 09:00:00'
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            )
            ->andReturn(false);

        $result = Glow_DB::log_transaction(1, 50, null, 'initial', 'Null unit transaction');
        $this->assertFalse($result);
    }

    public function test_initialize_user_stats_extreme_user_id_overflow() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_user_stats',
                [
                    'user_id' => '9223372036854775808',
                    'droplet_balance' => 50,
                    'driplet_balance' => 0,
                    'depth_multiplier' => 1.0,
                    'last_interaction_at' => null
                ],
                ['%s', '%d', '%d', '%f', '%s']
            )
            ->andReturn(false);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('ROLLBACK');

        $wpdb->shouldReceive('prepare')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = %s', '9223372036854775808')
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 9223372036854775808');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = 9223372036854775808', ARRAY_A)
            ->andReturn(null);

        $result = Glow_DB::initialize_user_stats('9223372036854775808');
        $this->assertNull($result);
    }

    public function test_initialize_user_stats_database_exception_rolls_back_transaction() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION');

        $wpdb->shouldReceive('insert')
            ->once()
            ->andThrow(new \RuntimeException('Database connection lost'));

        $wpdb->shouldReceive('query')
            ->once()
            ->with('ROLLBACK');

        $wpdb->shouldNotReceive('query')->with('COMMIT');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database connection lost');

        Glow_DB::initialize_user_stats(1);
    }

    public function test_get_user_stats_large_user_id_within_and_outside_limits() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $expected_row_within = [
            'user_id' => '9223372036854775807',
            'droplet_balance' => '50',
            'driplet_balance' => '0',
            'depth_multiplier' => '1.0',
            'last_interaction_at' => null
        ];

        $wpdb->shouldReceive('prepare')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = %s', '9223372036854775807')
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 9223372036854775807');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = 9223372036854775807', ARRAY_A)
            ->andReturn($expected_row_within);

        $result_within = Glow_DB::get_user_stats('9223372036854775807');
        if (PHP_INT_SIZE >= 8) {
            $this->assertSame(9223372036854775807, $result_within['user_id']);
        } else {
            $this->assertSame('9223372036854775807', $result_within['user_id']);
        }

        $expected_row_outside = [
            'user_id' => '9223372036854775808',
            'droplet_balance' => '50',
            'driplet_balance' => '0',
            'depth_multiplier' => '1.0',
            'last_interaction_at' => null
        ];

        $wpdb->shouldReceive('prepare')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = %s', '9223372036854775808')
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 9223372036854775808');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->with('SELECT * FROM wp_glow_user_stats WHERE user_id = 9223372036854775808', ARRAY_A)
            ->andReturn($expected_row_outside);

        $result_outside = Glow_DB::get_user_stats('9223372036854775808');
        $this->assertSame('9223372036854775808', $result_outside['user_id']);
    }
}

