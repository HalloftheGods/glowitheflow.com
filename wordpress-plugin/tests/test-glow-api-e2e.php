<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class Test_Glow_API_E2E extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        $this->markTestIncomplete('Placeholder API tests for Milestone 2');
        Monkey\Functions\expect('home_url')
            ->andReturn('https://example.com');
        Monkey\Functions\expect('get_transient')
            ->andReturn(false);
        Monkey\Functions\expect('set_transient')
            ->andReturn(true);
        Monkey\Functions\expect('delete_transient')
            ->andReturn(true);
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_api_user_endpoint_data_structure() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        $mock_user = Mockery::mock('stdClass');
        $mock_user->user_login = 'testuser';

        Monkey\Functions\expect('get_userdata')
            ->once()
            ->with(1)
            ->andReturn($mock_user);

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn([
                'user_id'          => '1',
                'droplet_balance'  => '50',
                'driplet_balance'  => '500',
                'depth_multiplier' => '1.2',
                'last_interaction_at' => null
            ]);

        $request = Mockery::mock('WP_REST_Request');
        $api = new Glow_API();
        $response = $api->get_user($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals(1, $data['user_id']);
        $this->assertEquals('testuser', $data['username']);
        $this->assertEquals(50, $data['droplet_balance']);
        $this->assertEquals(500, $data['driplet_balance']);
        $this->assertEquals(1.2, $data['depth_multiplier']);
    }

    public function test_feed_endpoint_returns_json() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_glow_posts ORDER BY glow_score DESC, created_at DESC LIMIT 20 OFFSET 0');

        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([
                [
                    'id'              => '1',
                    'user_id'         => '1',
                    'type'            => 'thought',
                    'content'         => 'First thought',
                    'link'            => null,
                    'title'           => null,
                    'tributary'       => null,
                    'passenger_count' => '1',
                    'glow_score'      => '5',
                    'created_at'      => '2026-07-05 12:00:00'
                ]
            ]);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('page')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('tributary')
            ->andReturn(null);

        $api = new Glow_API();
        $response = $api->get_feed($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals('First thought', $data[0]['content']);
    }

    public function test_submit_thought_success() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 12:00:00');

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->insert_id = 42;
        $wpdb->last_error = '';

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION')
            ->andReturn(true);

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_posts',
                [
                    'user_id'         => 1,
                    'type'            => 'thought',
                    'content'         => 'A valid thought',
                    'link'            => null,
                    'title'           => null,
                    'tributary'       => null,
                    'passenger_count' => 1,
                    'glow_score'      => 0,
                    'created_at'      => '2026-07-05 12:00:00'
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
            )
            ->andReturn(1);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('COMMIT')
            ->andReturn(true);

        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn([
                'user_id'          => '1',
                'droplet_balance'  => '50',
                'driplet_balance'  => '0',
                'depth_multiplier' => '1.0',
                'last_interaction_at' => null
            ]);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn([
                'type'    => 'thought',
                'content' => 'A valid thought'
            ]);

        $api = new Glow_API();
        $response = $api->submit_post($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals(42, $data['post_id']);
    }

    public function test_boost_post_increases_score() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('prepare')
            ->twice()
            ->andReturn(
                'SELECT * FROM wp_glow_posts WHERE id = 42',
                'SELECT * FROM wp_glow_user_stats WHERE user_id = 1'
            );

        $wpdb->shouldReceive('get_row')
            ->twice()
            ->andReturn(
                [
                    'id' => '42',
                    'user_id' => '2',
                    'type' => 'thought',
                    'content' => 'Another thought',
                    'passenger_count' => '1',
                    'glow_score' => '10',
                    'created_at' => '2026-07-05 12:00:00'
                ],
                [
                    'user_id'          => '1',
                    'droplet_balance'  => '50',
                    'driplet_balance'  => '0',
                    'depth_multiplier' => '1.0',
                    'last_interaction_at' => null
                ]
            );

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION')
            ->andReturn(true);

        $wpdb->shouldReceive('update')
            ->once()
            ->with('wp_glow_posts', ['glow_score' => 11], ['id' => 42], ['%d'], ['%d'])
            ->andReturn(1);

        $wpdb->shouldReceive('update')
            ->once()
            ->with('wp_glow_user_stats', ['droplet_balance' => 50, 'driplet_balance' => 15], ['user_id' => 1], ['%d', '%d'], ['%s'])
            ->andReturn(1);

        Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 12:00:00');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_transactions',
                [
                    'user_id' => 1,
                    'amount' => 15,
                    'unit' => 'driplet',
                    'type' => 'boost_reward',
                    'details' => 'Boosted post 42',
                    'created_at' => '2026-07-05 12:00:00'
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            )
            ->andReturn(1);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('COMMIT')
            ->andReturn(true);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn(['post_id' => 42]);

        $api = new Glow_API();
        $response = $api->boost_post($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals(15, $data['earned_driplets']);
        $this->assertEquals(11, $data['new_glow_score']);
    }

    public function test_stripe_checkout_session_url() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn([
                'pack_id' => 'starter',
                'success_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel'
            ]);

        $api = new Glow_API();
        $response = $api->stripe_checkout($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('mock-session-starter', $data['checkout_url']);
    }

    public function test_user_id_zero_returns_error() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(0);

        $request = Mockery::mock('WP_REST_Request');
        $api = new Glow_API();
        $response = $api->get_user($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_unauthorized', $response->get_error_code());
    }

    public function test_feed_negative_page_default_to_one() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $wpdb->shouldReceive('prepare')
            ->once()
            ->with(
                'SELECT * FROM wp_glow_posts ORDER BY glow_score DESC, created_at DESC LIMIT %d OFFSET %d',
                20,
                0
            )
            ->andReturn('SELECT * FROM wp_glow_posts ORDER BY glow_score DESC, created_at DESC LIMIT 20 OFFSET 0');

        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([]);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('page')
            ->andReturn(-5);
        $request->shouldReceive('get_param')
            ->with('tributary')
            ->andReturn(null);

        $api = new Glow_API();
        $response = $api->get_feed($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
    }

    public function test_submit_empty_content_rejected() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn([
                'type'    => 'thought',
                'content' => '   '
            ]);

        $api = new Glow_API();
        $response = $api->submit_post($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_invalid_data', $response->get_error_code());
    }

    public function test_boost_invalid_post_id_returns_404() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';

        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_glow_posts WHERE id = 999');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn(null);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn(['post_id' => 999]);

        $api = new Glow_API();
        $response = $api->boost_post($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_post_not_found', $response->get_error_code());
    }

    public function test_stripe_webhook_invalid_sig_rejected() {
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('stripe-signature')
            ->andReturn(null);

        $api = new Glow_API();
        $response = $api->stripe_webhook($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_invalid_signature', $response->get_error_code());
    }

    public function test_submit_link_updates_user_balance_and_registers_post() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 12:00:00');

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->insert_id = 43;
        $wpdb->last_error = '';

        $wpdb->shouldReceive('prepare')
            ->twice()
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->twice()
            ->andReturn(
                [
                    'user_id'          => '1',
                    'droplet_balance'  => '50',
                    'driplet_balance'  => '0',
                    'depth_multiplier' => '1.0',
                    'last_interaction_at' => null
                ],
                [
                    'user_id'          => '1',
                    'droplet_balance'  => '40',
                    'driplet_balance'  => '0',
                    'depth_multiplier' => '1.0',
                    'last_interaction_at' => null
                ]
            );

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION')
            ->andReturn(true);

        $wpdb->shouldReceive('update')
            ->once()
            ->with('wp_glow_user_stats', ['droplet_balance' => 40], ['user_id' => 1], ['%d'], ['%s'])
            ->andReturn(1);

        $wpdb->shouldReceive('insert')
            ->twice()
            ->andReturn(1);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('COMMIT')
            ->andReturn(true);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn([
                'type'      => 'drop',
                'content'   => 'Check this out',
                'link'      => 'https://example.com',
                'title'     => 'Example Site',
                'tributary' => 't/dev'
            ]);

        $api = new Glow_API();
        $response = $api->submit_post($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertEquals(40, $data['balance']['droplet_balance']);
    }

    public function test_boost_reward_adds_driplets_and_logs_tx() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('prepare')
            ->twice()
            ->andReturn(
                'SELECT * FROM wp_glow_posts WHERE id = 42',
                'SELECT * FROM wp_glow_user_stats WHERE user_id = 1'
            );

        $wpdb->shouldReceive('get_row')
            ->twice()
            ->andReturn(
                [
                    'id' => '42',
                    'user_id' => '2',
                    'type' => 'thought',
                    'content' => 'A thought',
                    'passenger_count' => '1',
                    'glow_score' => '10',
                    'created_at' => '2026-07-05 12:00:00'
                ],
                [
                    'user_id'          => '1',
                    'droplet_balance'  => '50',
                    'driplet_balance'  => '90',
                    'depth_multiplier' => '1.0',
                    'last_interaction_at' => null
                ]
            );

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION')
            ->andReturn(true);

        $wpdb->shouldReceive('update')
            ->once()
            ->with('wp_glow_posts', ['glow_score' => 11], ['id' => 42], ['%d'], ['%d'])
            ->andReturn(1);

        $wpdb->shouldReceive('update')
            ->once()
            ->with('wp_glow_user_stats', ['droplet_balance' => 51, 'driplet_balance' => 5], ['user_id' => 1], ['%d', '%d'], ['%s'])
            ->andReturn(1);

        Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 12:00:00');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_transactions',
                [
                    'user_id' => 1,
                    'amount' => 15,
                    'unit' => 'driplet',
                    'type' => 'boost_reward',
                    'details' => 'Boosted post 42',
                    'created_at' => '2026-07-05 12:00:00'
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            )
            ->andReturn(1);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('COMMIT')
            ->andReturn(true);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn(['post_id' => 42]);

        $api = new Glow_API();
        $response = $api->boost_post($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        
        $data = $response->get_data();
        $this->assertEquals(51, $data['user_balance']['droplet_balance']);
        $this->assertEquals(5, $data['user_balance']['driplet_balance']);
    }

    public function test_stripe_checkout_with_coupon_save20() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT id FROM wp_glow_transactions WHERE user_id = 1 AND details LIKE \'%[coupon: SAVE20]%\'');

        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn([
                'pack_id' => 'pro',
                'coupon' => 'save20',
                'success_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel'
            ]);

        $api = new Glow_API();
        $response = $api->stripe_checkout($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('mock-session-pro-coupon-save20', $data['checkout_url']);
    }

    public function test_stripe_checkout_invalid_coupon_rejected() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn([
                'pack_id' => 'pro',
                'coupon' => 'INVALIDCOUPON',
                'success_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel'
            ]);

        $api = new Glow_API();
        $response = $api->stripe_checkout($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_invalid_coupon', $response->get_error_code());
        $this->assertEquals(400, $response->get_error_data()['status']);
    }

    public function test_stripe_checkout_free_coupon_fulfillment() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('prepare')
            ->twice()
            ->andReturn(
                'SELECT id FROM wp_glow_transactions WHERE user_id = 1 AND details LIKE \'%[coupon: MYFREEDRIPS]%\'',
                'SELECT * FROM wp_glow_user_stats WHERE user_id = 1'
            );

        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null);

        $wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn([
                'user_id' => '1',
                'droplet_balance' => '50',
                'driplet_balance' => '0',
                'depth_multiplier' => '1.0',
                'last_interaction_at' => null,
            ]);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION')
            ->andReturn(true);

        $wpdb->shouldReceive('update')
            ->once()
            ->with('wp_glow_user_stats', ['droplet_balance' => 1050], ['user_id' => 1], ['%d'], ['%s'])
            ->andReturn(1);

        Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 12:00:00');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_transactions',
                [
                    'user_id' => 1,
                    'amount' => 1000,
                    'unit' => 'droplet',
                    'type' => 'purchase',
                    'details' => 'Free droplet pack: whale with [coupon: MYFREEDRIPS]',
                    'created_at' => '2026-07-05 12:00:00',
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            )
            ->andReturn(1);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('COMMIT')
            ->andReturn(true);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn([
                'pack_id' => 'whale',
                'coupon' => 'MYFREEDRIPS',
                'success_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel'
            ]);

        $api = new Glow_API();
        $response = $api->stripe_checkout($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertStringContainsString('mock-session-free-whale', $data['checkout_url']);
    }

    public function test_purchase_droplets_via_stripe_webhook_fulfillment() {
        if (!defined('GLOW_STRIPE_WEBHOOK_SECRET')) {
            define('GLOW_STRIPE_WEBHOOK_SECRET', 'whsec_test');
        }

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION')
            ->andReturn(true);

        $wpdb->shouldReceive('prepare')
            ->twice()
            ->andReturn(
                'SELECT id FROM wp_glow_transactions WHERE details LIKE \'%cs_test_123%\'',
                'SELECT * FROM wp_glow_user_stats WHERE user_id = 1'
            );

        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null);

        $wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn([
                'user_id'          => '1',
                'droplet_balance'  => '50',
                'driplet_balance'  => '0',
                'depth_multiplier' => '1.0',
                'last_interaction_at' => null
            ]);

        $wpdb->shouldReceive('update')
            ->once()
            ->with('wp_glow_user_stats', ['droplet_balance' => 100], ['user_id' => 1], ['%d'], ['%s'])
            ->andReturn(1);

        Monkey\Functions\expect('current_time')
            ->once()
            ->with('mysql')
            ->andReturn('2026-07-05 12:00:00');

        $wpdb->shouldReceive('insert')
            ->once()
            ->with(
                'wp_glow_transactions',
                [
                    'user_id'    => 1,
                    'amount'     => 50,
                    'unit'       => 'droplet',
                    'type'       => 'purchase',
                    'details'    => 'Stripe Session ID: cs_test_123 | Purchased pack: starter',
                    'created_at' => '2026-07-05 12:00:00'
                ],
                ['%s', '%d', '%s', '%s', '%s', '%s']
            )
            ->andReturn(1);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('COMMIT')
            ->andReturn(true);

        $raw_body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'user_id' => '1',
                        'pack_id' => 'starter'
                    ]
                ]
            ]
        ]);

        $timestamp = time();
        $signed_payload = $timestamp . '.' . $raw_body;
        $signature = hash_hmac('sha256', $signed_payload, GLOW_STRIPE_WEBHOOK_SECRET);
        $sig_header = 't=' . $timestamp . ',v1=' . $signature;

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('stripe-signature')
            ->andReturn($sig_header);
        $request->shouldReceive('get_body')
            ->andReturn($raw_body);

        $api = new Glow_API();
        $response = $api->stripe_webhook($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    public function test_stripe_webhook_expired_sig_rejected() {
        if (!defined('GLOW_STRIPE_WEBHOOK_SECRET')) {
            define('GLOW_STRIPE_WEBHOOK_SECRET', 'whsec_test');
        }

        $raw_body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'payment_status' => 'paid',
                    'metadata' => [
                        'user_id' => '1',
                        'pack_id' => 'starter'
                    ]
                ]
            ]
        ]);

        $timestamp = time() - 400;
        $signed_payload = $timestamp . '.' . $raw_body;
        $signature = hash_hmac('sha256', $signed_payload, GLOW_STRIPE_WEBHOOK_SECRET);
        $sig_header = 't=' . $timestamp . ',v1=' . $signature;

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('stripe-signature')
            ->andReturn($sig_header);
        $request->shouldReceive('get_body')
            ->andReturn($raw_body);

        $api = new Glow_API();
        $response = $api->stripe_webhook($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_invalid_signature', $response->get_error_code());
    }

    public function test_stripe_webhook_rollback_on_update_failure() {
        if (!defined('GLOW_STRIPE_WEBHOOK_SECRET')) {
            define('GLOW_STRIPE_WEBHOOK_SECRET', 'whsec_test');
        }

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION')
            ->andReturn(true);

        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn([
                'user_id'          => '1',
                'droplet_balance'  => '50',
                'driplet_balance'  => '0',
                'depth_multiplier' => '1.0',
                'last_interaction_at' => null
            ]);

        $wpdb->shouldReceive('update')
            ->once()
            ->with('wp_glow_user_stats', ['droplet_balance' => 100], ['user_id' => 1], ['%d'], ['%s'])
            ->andReturn(false);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('ROLLBACK')
            ->andReturn(true);

        $raw_body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'payment_status' => 'paid',
                    'metadata' => [
                        'user_id' => '1',
                        'pack_id' => 'starter'
                    ]
                ]
            ]
        ]);

        $timestamp = time();
        $signed_payload = $timestamp . '.' . $raw_body;
        $signature = hash_hmac('sha256', $signed_payload, GLOW_STRIPE_WEBHOOK_SECRET);
        $sig_header = 't=' . $timestamp . ',v1=' . $signature;

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('stripe-signature')
            ->andReturn($sig_header);
        $request->shouldReceive('get_body')
            ->andReturn($raw_body);

        $api = new Glow_API();
        $response = $api->stripe_webhook($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_db_error', $response->get_error_code());
    }

    public function test_consecutive_thought_merging() {
        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_glow_posts ORDER BY glow_score DESC, created_at DESC LIMIT 20 OFFSET 0');
        $wpdb->shouldReceive('get_results')
            ->once()
            ->andReturn([
                [
                    'id'              => '1',
                    'user_id'         => '1',
                    'type'            => 'thought',
                    'content'         => 'Thought one',
                    'link'            => null,
                    'title'           => null,
                    'tributary'       => null,
                    'passenger_count' => '2',
                    'glow_score'      => '5',
                    'created_at'      => '2026-07-05 12:00:00'
                ],
                [
                    'id'              => '2',
                    'user_id'         => '1',
                    'type'            => 'thought',
                    'content'         => 'Thought two',
                    'link'            => null,
                    'title'           => null,
                    'tributary'       => null,
                    'passenger_count' => '3',
                    'glow_score'      => '10',
                    'created_at'      => '2026-07-05 12:01:00'
                ]
            ]);
        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_param')
            ->with('page')
            ->andReturn(1);
        $request->shouldReceive('get_param')
            ->with('tributary')
            ->andReturn(null);
        $api = new Glow_API();
        $response = $api->get_feed($request);
        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertCount(1, $data);
        $this->assertEquals('Thought one | Thought two', $data[0]['content']);
        $this->assertEquals(5, $data[0]['passenger_count']);
        $this->assertEquals(15, $data[0]['glow_score']);
    }

    public function test_stripe_webhook_idempotency() {
        if (!defined('GLOW_STRIPE_WEBHOOK_SECRET')) {
            define('GLOW_STRIPE_WEBHOOK_SECRET', 'whsec_test');
        }

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT id FROM wp_glow_transactions WHERE details LIKE \'%session_123%\'');

        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(42);

        $raw_body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'session_123',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'user_id' => '1',
                        'pack_id' => 'starter'
                    ]
                ]
            ]
        ]);

        $timestamp = time();
        $signed_payload = $timestamp . '.' . $raw_body;
        $signature = hash_hmac('sha256', $signed_payload, GLOW_STRIPE_WEBHOOK_SECRET);
        $sig_header = 't=' . $timestamp . ',v1=' . $signature;

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('stripe-signature')
            ->andReturn($sig_header);
        $request->shouldReceive('get_body')
            ->andReturn($raw_body);

        $api = new Glow_API();
        $response = $api->stripe_webhook($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertTrue($data['already_processed']);
    }

    public function test_stripe_checkout_coupon_replay_protection() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT id FROM wp_glow_transactions WHERE user_id = 1 AND details LIKE \'%[coupon: SAVE20]%\'');

        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(100);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn([
                'pack_id' => 'pro',
                'coupon' => 'save20',
                'success_url' => 'https://example.com/success',
                'cancel_url' => 'https://example.com/cancel'
            ]);

        $api = new Glow_API();
        $response = $api->stripe_checkout($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_coupon_already_used', $response->get_error_code());
        $this->assertEquals(400, $response->get_error_data()['status']);
    }

    public function test_stripe_checkout_invalid_redirect_url() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn([
                'pack_id' => 'starter',
                'success_url' => '/relative-success',
                'cancel_url' => 'https://example.com/cancel'
            ]);

        $api = new Glow_API();
        $response = $api->stripe_checkout($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_invalid_url', $response->get_error_code());
        $this->assertEquals(400, $response->get_error_data()['status']);
    }

    public function test_stripe_checkout_external_redirect_url_rejected() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(1);

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_json_params')
            ->once()
            ->andReturn([
                'pack_id' => 'starter',
                'success_url' => 'https://malicious.com/success',
                'cancel_url' => 'https://example.com/cancel'
            ]);

        $api = new Glow_API();
        $response = $api->stripe_checkout($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_invalid_url', $response->get_error_code());
        $this->assertEquals(400, $response->get_error_data()['status']);
    }

    public function test_stripe_webhook_coupon_replay_protection() {
        if (!defined('GLOW_STRIPE_WEBHOOK_SECRET')) {
            define('GLOW_STRIPE_WEBHOOK_SECRET', 'whsec_test');
        }

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('prepare')
            ->twice()
            ->andReturn(
                'SELECT id FROM wp_glow_transactions WHERE details LIKE \'%session_124%\'',
                'SELECT id FROM wp_glow_transactions WHERE user_id = 1 AND details LIKE \'%[coupon: SAVE20]%\''
            );

        $wpdb->shouldReceive('get_var')
            ->twice()
            ->andReturn(null, 100);

        Monkey\Functions\expect('get_transient')
            ->once()
            ->with('glow_stripe_lock_' . md5('session_124'))
            ->andReturn(false);

        Monkey\Functions\expect('set_transient')
            ->once()
            ->with('glow_stripe_lock_' . md5('session_124'), '1', 60)
            ->andReturn(true);

        Monkey\Functions\expect('delete_transient')
            ->once()
            ->with('glow_stripe_lock_' . md5('session_124'))
            ->andReturn(true);

        $raw_body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'session_124',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'user_id' => '1',
                        'pack_id' => 'pro',
                        'coupon' => 'save20'
                    ]
                ]
            ]
        ]);

        $timestamp = time();
        $signed_payload = $timestamp . '.' . $raw_body;
        $signature = hash_hmac('sha256', $signed_payload, GLOW_STRIPE_WEBHOOK_SECRET);
        $sig_header = 't=' . $timestamp . ',v1=' . $signature;

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('stripe-signature')
            ->andReturn($sig_header);
        $request->shouldReceive('get_body')
            ->andReturn($raw_body);

        $api = new Glow_API();
        $response = $api->stripe_webhook($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_coupon_already_used', $response->get_error_code());
        $this->assertEquals(400, $response->get_error_data()['status']);
    }

    public function test_stripe_webhook_concurrency_lock() {
        if (!defined('GLOW_STRIPE_WEBHOOK_SECRET')) {
            define('GLOW_STRIPE_WEBHOOK_SECRET', 'whsec_test');
        }

        Monkey\Functions\expect('get_transient')
            ->once()
            ->with('glow_stripe_lock_' . md5('session_125'))
            ->andReturn('1');

        $raw_body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'session_125',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'user_id' => '1',
                        'pack_id' => 'starter'
                    ]
                ]
            ]
        ]);

        $timestamp = time();
        $signed_payload = $timestamp . '.' . $raw_body;
        $signature = hash_hmac('sha256', $signed_payload, GLOW_STRIPE_WEBHOOK_SECRET);
        $sig_header = 't=' . $timestamp . ',v1=' . $signature;

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('stripe-signature')
            ->andReturn($sig_header);
        $request->shouldReceive('get_body')
            ->andReturn($raw_body);

        $api = new Glow_API();
        $response = $api->stripe_webhook($request);

        $this->assertInstanceOf('WP_REST_Response', $response);
        $this->assertEquals(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertTrue($data['already_processed']);
    }

    public function test_stripe_webhook_rollback_releases_lock() {
        if (!defined('GLOW_STRIPE_WEBHOOK_SECRET')) {
            define('GLOW_STRIPE_WEBHOOK_SECRET', 'whsec_test');
        }

        global $wpdb;
        $wpdb = Mockery::mock('stdClass');
        $wpdb->prefix = 'wp_';
        $wpdb->last_error = '';

        $wpdb->shouldReceive('query')
            ->once()
            ->with('START TRANSACTION')
            ->andReturn(true);

        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT id FROM wp_glow_transactions WHERE details LIKE \'%session_126%\'');

        $wpdb->shouldReceive('get_var')
            ->once()
            ->andReturn(null);

        $wpdb->shouldReceive('prepare')
            ->once()
            ->andReturn('SELECT * FROM wp_glow_user_stats WHERE user_id = 1');

        $wpdb->shouldReceive('get_row')
            ->once()
            ->andReturn([
                'user_id'          => '1',
                'droplet_balance'  => '50',
                'driplet_balance'  => '0',
                'depth_multiplier' => '1.0',
                'last_interaction_at' => null
            ]);

        $wpdb->shouldReceive('update')
            ->once()
            ->with('wp_glow_user_stats', ['droplet_balance' => 100], ['user_id' => 1], ['%d'], ['%s'])
            ->andReturn(false);

        $wpdb->shouldReceive('query')
            ->once()
            ->with('ROLLBACK')
            ->andReturn(true);

        Monkey\Functions\expect('get_transient')
            ->once()
            ->with('glow_stripe_lock_' . md5('session_126'))
            ->andReturn(false);

        Monkey\Functions\expect('set_transient')
            ->once()
            ->with('glow_stripe_lock_' . md5('session_126'), '1', 60)
            ->andReturn(true);

        Monkey\Functions\expect('delete_transient')
            ->once()
            ->with('glow_stripe_lock_' . md5('session_126'))
            ->andReturn(true);

        $raw_body = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'session_126',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'user_id' => '1',
                        'pack_id' => 'starter'
                    ]
                ]
            ]
        ]);

        $timestamp = time();
        $signed_payload = $timestamp . '.' . $raw_body;
        $signature = hash_hmac('sha256', $signed_payload, GLOW_STRIPE_WEBHOOK_SECRET);
        $sig_header = 't=' . $timestamp . ',v1=' . $signature;

        $request = Mockery::mock('WP_REST_Request');
        $request->shouldReceive('get_header')
            ->with('stripe-signature')
            ->andReturn($sig_header);
        $request->shouldReceive('get_body')
            ->andReturn($raw_body);

        $api = new Glow_API();
        $response = $api->stripe_webhook($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_db_error', $response->get_error_code());
    }
}
