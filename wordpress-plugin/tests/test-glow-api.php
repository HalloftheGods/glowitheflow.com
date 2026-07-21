<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class Test_Glow_API extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_register_routes() {
        Monkey\Functions\expect('register_rest_route')
            ->times(6)
            ->with(
                'glow/v1',
                Mockery::type('string'),
                Mockery::type('array')
            );

        $api = new Glow_API();
        $api->register_routes();
    }

    public function test_check_user_logged_in_true() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(42);

        $api = new Glow_API();
        $this->assertTrue($api->check_user_logged_in());
    }

    public function test_check_user_logged_in_false() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(0);

        $api = new Glow_API();
        $this->assertFalse($api->check_user_logged_in());
    }

    public function test_get_user_unauthorized() {
        Monkey\Functions\expect('get_current_user_id')
            ->once()
            ->andReturn(0);

        $request = Mockery::mock('WP_REST_Request');
        $api = new Glow_API();
        $response = $api->get_user($request);

        $this->assertInstanceOf('WP_Error', $response);
        $this->assertEquals('glow_unauthorized', $response->get_error_code());
    }
}
