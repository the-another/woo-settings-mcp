<?php
/**
 * REST Controller tests.
 *
 * @package Another\Plugin\Woo_Settings_MCP\Tests\Unit
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP\Tests\Unit;

use Another\Plugin\Woo_Settings_MCP\REST_Controller;
use Another\Plugin\Woo_Settings_MCP\MCP_Server;
use Another\Plugin\Woo_Settings_MCP\Settings_Handler;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test case for REST_Controller class.
 */
class RestControllerTest extends \WooSettingsMCP_TestCase {

	/**
	 * REST controller instance.
	 *
	 * @var REST_Controller
	 */
	private REST_Controller $controller;

	/**
	 * MCP server mock.
	 *
	 * @var MCP_Server|\Mockery\MockInterface
	 */
	private $mcp_server;

	/**
	 * Settings handler mock.
	 *
	 * @var Settings_Handler|\Mockery\MockInterface
	 */
	private $settings_handler;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		// Define constant if not defined.
		if ( ! defined( 'WOO_SETTINGS_MCP_VERSION' ) ) {
			define( 'WOO_SETTINGS_MCP_VERSION', '1.0.0' );
		}

		$this->settings_handler = \Mockery::mock( Settings_Handler::class );
		$this->mcp_server       = \Mockery::mock( MCP_Server::class );
		$this->mcp_server->shouldReceive( 'get_settings_handler' )->andReturn( $this->settings_handler );

		$this->controller = new REST_Controller( $this->mcp_server );
	}

	/**
	 * Test get_namespace returns correct namespace.
	 *
	 * @return void
	 */
	public function test_get_namespace_returns_correct_value(): void {
		$this->assertEquals( 'woo-settings-mcp/v1', $this->controller->get_namespace() );
	}

	/**
	 * Test register_routes registers expected routes.
	 *
	 * @return void
	 */
	public function test_register_routes_registers_expected_routes(): void {
		$registered_routes = array();

		Functions\when( 'register_rest_route' )->alias(
			function ( string $namespace, string $route, array $args ) use ( &$registered_routes ): bool {
				$registered_routes[] = $namespace . $route;
				return true;
			}
		);

		$this->controller->register_routes();

		$this->assertContains( 'woo-settings-mcp/v1/mcp', $registered_routes );
		$this->assertContains( 'woo-settings-mcp/v1/health', $registered_routes );
		$this->assertContains( 'woo-settings-mcp/v1/schema', $registered_routes );
	}

	/**
	 * Test health_check returns correct structure.
	 *
	 * @return void
	 */
	public function test_health_check_returns_correct_structure(): void {
		$request  = \Mockery::mock( 'WP_REST_Request' );
		$response = $this->controller->health_check( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'version', $data );
		$this->assertArrayHasKey( 'woocommerce_active', $data );
		$this->assertArrayHasKey( 'mcp_protocol', $data );
		$this->assertEquals( 'ok', $data['status'] );
	}

	/**
	 * Test health_check detects WooCommerce.
	 *
	 * @return void
	 */
	public function test_health_check_detects_woocommerce(): void {
		// WooCommerce class doesn't exist in test environment.
		$request  = \Mockery::mock( 'WP_REST_Request' );
		$response = $this->controller->health_check( $request );

		$data = $response->get_data();
		$this->assertFalse( $data['woocommerce_active'] );
	}

	/**
	 * Test get_schema returns settings schema.
	 *
	 * @return void
	 */
	public function test_get_schema_returns_settings_schema(): void {
		$schema = array(
			'woocommerce_currency' => array(
				'type'  => 'string',
				'label' => 'Currency',
			),
		);

		$this->settings_handler
			->shouldReceive( 'get_schema' )
			->once()
			->andReturn( $schema );

		$request  = \Mockery::mock( 'WP_REST_Request' );
		$response = $this->controller->get_schema( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'settings', $data );
		$this->assertEquals( $schema, $data['settings'] );
	}

	/**
	 * Test check_read_permission with manage_woocommerce capability.
	 *
	 * @return void
	 */
	public function test_check_read_permission_with_capability(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$request = \Mockery::mock( 'WP_REST_Request' );
		$result  = $this->controller->check_read_permission( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test check_read_permission without capability.
	 *
	 * @return void
	 */
	public function test_check_read_permission_without_capability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$request = \Mockery::mock( 'WP_REST_Request' );
		$result  = $this->controller->check_read_permission( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test get_mcp_server returns server instance.
	 *
	 * @return void
	 */
	public function test_get_mcp_server_returns_instance(): void {
		$this->assertSame( $this->mcp_server, $this->controller->get_mcp_server() );
	}

	/**
	 * Test get_transport returns null before register_routes.
	 *
	 * @return void
	 */
	public function test_get_transport_returns_null_before_register(): void {
		$this->assertNull( $this->controller->get_transport() );
	}

	/**
	 * Test get_transport returns instance after register_routes.
	 *
	 * @return void
	 */
	public function test_get_transport_returns_instance_after_register(): void {
		Functions\when( 'register_rest_route' )->justReturn( true );

		$this->controller->register_routes();

		$this->assertNotNull( $this->controller->get_transport() );
	}
}

