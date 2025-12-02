<?php
/**
 * MCP Server tests.
 *
 * @package Another\Plugin\Woo_Settings_MCP\Tests\Unit
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP\Tests\Unit;

use Another\Plugin\Woo_Settings_MCP\MCP_Server;
use Another\Plugin\Woo_Settings_MCP\Settings_Handler;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test case for MCP_Server class.
 */
class McpServerTest extends \WooSettingsMCP_TestCase {

	/**
	 * MCP server instance.
	 *
	 * @var MCP_Server
	 */
	private MCP_Server $server;

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

		$this->mock_woocommerce();

		$this->settings_handler = \Mockery::mock( Settings_Handler::class );
		$this->server           = new MCP_Server( $this->settings_handler );
	}

	/**
	 * Test process_message handles invalid JSON.
	 *
	 * @return void
	 */
	public function test_process_message_handles_invalid_json(): void {
		$response = $this->server->process_message( 'not valid json' );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'error', $decoded );
		$this->assertEquals( -32700, $decoded['error']['code'] );
		$this->assertStringContainsString( 'Parse error', $decoded['error']['message'] );
	}

	/**
	 * Test process_message handles missing jsonrpc version.
	 *
	 * @return void
	 */
	public function test_process_message_handles_missing_jsonrpc_version(): void {
		$request  = json_encode( array( 'method' => 'ping', 'id' => 1 ) );
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'error', $decoded );
		$this->assertEquals( -32600, $decoded['error']['code'] );
	}

	/**
	 * Test process_message handles invalid jsonrpc version.
	 *
	 * @return void
	 */
	public function test_process_message_handles_invalid_jsonrpc_version(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '1.0',
				'method'  => 'ping',
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'error', $decoded );
		$this->assertEquals( -32600, $decoded['error']['code'] );
	}

	/**
	 * Test process_message handles missing method.
	 *
	 * @return void
	 */
	public function test_process_message_handles_missing_method(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'error', $decoded );
		$this->assertEquals( -32600, $decoded['error']['code'] );
	}

	/**
	 * Test process_message handles unknown method.
	 *
	 * @return void
	 */
	public function test_process_message_handles_unknown_method(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'unknown_method',
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'error', $decoded );
		$this->assertEquals( -32601, $decoded['error']['code'] );
		$this->assertStringContainsString( 'Method not found', $decoded['error']['message'] );
	}

	/**
	 * Test process_message handles ping method.
	 *
	 * @return void
	 */
	public function test_process_message_handles_ping(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'ping',
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'result', $decoded );
		$this->assertTrue( $decoded['result']['pong'] );
	}

	/**
	 * Test process_message handles initialize method.
	 *
	 * @return void
	 */
	public function test_process_message_handles_initialize(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'initialize',
				'params'  => array(
					'protocolVersion' => '2024-11-05',
					'clientInfo'      => array(
						'name'    => 'test-client',
						'version' => '1.0.0',
					),
				),
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'result', $decoded );
		$this->assertArrayHasKey( 'protocolVersion', $decoded['result'] );
		$this->assertArrayHasKey( 'capabilities', $decoded['result'] );
		$this->assertArrayHasKey( 'serverInfo', $decoded['result'] );
		$this->assertEquals( 'woo-settings-mcp', $decoded['result']['serverInfo']['name'] );
	}

	/**
	 * Test is_initialized returns false initially.
	 *
	 * @return void
	 */
	public function test_is_initialized_returns_false_initially(): void {
		$this->assertFalse( $this->server->is_initialized() );
	}

	/**
	 * Test is_initialized returns true after initialize.
	 *
	 * @return void
	 */
	public function test_is_initialized_returns_true_after_initialize(): void {
		$request = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'initialize',
				'params'  => array(),
				'id'      => 1,
			)
		);
		$this->server->process_message( $request );

		$this->assertTrue( $this->server->is_initialized() );
	}

	/**
	 * Test process_message handles tools/list method.
	 *
	 * @return void
	 */
	public function test_process_message_handles_tools_list(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/list',
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'result', $decoded );
		$this->assertArrayHasKey( 'tools', $decoded['result'] );
		$this->assertCount( 3, $decoded['result']['tools'] );

		$tool_names = array_column( $decoded['result']['tools'], 'name' );
		$this->assertContains( 'list_settings', $tool_names );
		$this->assertContains( 'get_setting', $tool_names );
		$this->assertContains( 'update_setting', $tool_names );
	}

	/**
	 * Test tools/call with list_settings.
	 *
	 * @return void
	 */
	public function test_tools_call_list_settings(): void {
		$this->settings_handler
			->shouldReceive( 'list_settings' )
			->once()
			->andReturn(
				array(
					'woocommerce_currency' => array(
						'value' => 'USD',
						'type'  => 'string',
					),
				)
			);

		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'params'  => array(
					'name'      => 'list_settings',
					'arguments' => array(),
				),
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'result', $decoded );
		$this->assertArrayHasKey( 'content', $decoded['result'] );
	}

	/**
	 * Test tools/call with list_settings filtered by group.
	 *
	 * @return void
	 */
	public function test_tools_call_list_settings_filtered(): void {
		$this->settings_handler
			->shouldReceive( 'list_settings' )
			->once()
			->andReturn(
				array(
					'woocommerce_currency'     => array(
						'value' => 'USD',
						'type'  => 'string',
						'group' => 'currency_options',
					),
					'woocommerce_store_city'   => array(
						'value' => 'New York',
						'type'  => 'string',
						'group' => 'store_address',
					),
				)
			);

		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'params'  => array(
					'name'      => 'list_settings',
					'arguments' => array( 'group' => 'currency_options' ),
				),
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'result', $decoded );
		$content_json = $decoded['result']['content'][0]['text'];
		$content      = json_decode( $content_json, true );

		$this->assertCount( 1, $content );
		$this->assertArrayHasKey( 'woocommerce_currency', $content );
	}

	/**
	 * Test tools/call with get_setting.
	 *
	 * @return void
	 */
	public function test_tools_call_get_setting(): void {
		$this->settings_handler
			->shouldReceive( 'get_setting' )
			->with( 'woocommerce_currency' )
			->once()
			->andReturn(
				array(
					'option_name' => 'woocommerce_currency',
					'value'       => 'USD',
					'type'        => 'string',
				)
			);

		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'params'  => array(
					'name'      => 'get_setting',
					'arguments' => array( 'option_name' => 'woocommerce_currency' ),
				),
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'result', $decoded );
		$this->assertArrayNotHasKey( 'isError', $decoded['result'] );
	}

	/**
	 * Test tools/call with get_setting unknown option.
	 *
	 * @return void
	 */
	public function test_tools_call_get_setting_unknown(): void {
		$this->settings_handler
			->shouldReceive( 'get_setting' )
			->with( 'unknown_option' )
			->once()
			->andReturn( null );

		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'params'  => array(
					'name'      => 'get_setting',
					'arguments' => array( 'option_name' => 'unknown_option' ),
				),
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertTrue( $decoded['result']['isError'] );
	}

	/**
	 * Test tools/call with get_setting missing option_name.
	 *
	 * @return void
	 */
	public function test_tools_call_get_setting_missing_option_name(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'params'  => array(
					'name'      => 'get_setting',
					'arguments' => array(),
				),
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertTrue( $decoded['result']['isError'] );
		$this->assertStringContainsString( 'option_name', $decoded['result']['content'][0]['text'] );
	}

	/**
	 * Test tools/call with update_setting without permission.
	 *
	 * @return void
	 */
	public function test_tools_call_update_setting_without_permission(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'params'  => array(
					'name'      => 'update_setting',
					'arguments' => array(
						'option_name' => 'woocommerce_currency',
						'value'       => 'EUR',
					),
				),
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertTrue( $decoded['result']['isError'] );
		$this->assertStringContainsString( 'Permission denied', $decoded['result']['content'][0]['text'] );
	}

	/**
	 * Test tools/call with update_setting with permission.
	 *
	 * @return void
	 */
	public function test_tools_call_update_setting_with_permission(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->settings_handler
			->shouldReceive( 'update_setting' )
			->with( 'woocommerce_currency', 'EUR' )
			->once()
			->andReturn(
				array(
					'success' => true,
					'message' => 'Setting updated.',
					'value'   => 'EUR',
				)
			);

		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'params'  => array(
					'name'      => 'update_setting',
					'arguments' => array(
						'option_name' => 'woocommerce_currency',
						'value'       => 'EUR',
					),
				),
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayNotHasKey( 'isError', $decoded['result'] );
	}

	/**
	 * Test tools/call with unknown tool.
	 *
	 * @return void
	 */
	public function test_tools_call_unknown_tool(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'params'  => array(
					'name'      => 'unknown_tool',
					'arguments' => array(),
				),
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertTrue( $decoded['result']['isError'] );
		$this->assertStringContainsString( 'Unknown tool', $decoded['result']['content'][0]['text'] );
	}

	/**
	 * Test tools/call without tool name.
	 *
	 * @return void
	 */
	public function test_tools_call_missing_tool_name(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'tools/call',
				'params'  => array(
					'arguments' => array(),
				),
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertArrayHasKey( 'error', $decoded );
		$this->assertEquals( -32602, $decoded['error']['code'] );
	}

	/**
	 * Test notification returns null.
	 *
	 * @return void
	 */
	public function test_notification_returns_null(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'initialized',
				// No id = notification.
			)
		);
		$response = $this->server->process_message( $request );

		$this->assertNull( $response );
	}

	/**
	 * Test response includes correct jsonrpc version.
	 *
	 * @return void
	 */
	public function test_response_includes_jsonrpc_version(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'ping',
				'id'      => 1,
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertEquals( '2.0', $decoded['jsonrpc'] );
	}

	/**
	 * Test response includes request id.
	 *
	 * @return void
	 */
	public function test_response_includes_request_id(): void {
		$request  = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'ping',
				'id'      => 'custom-id-123',
			)
		);
		$response = $this->server->process_message( $request );
		$decoded  = json_decode( $response, true );

		$this->assertEquals( 'custom-id-123', $decoded['id'] );
	}

	/**
	 * Test get_settings_handler returns handler instance.
	 *
	 * @return void
	 */
	public function test_get_settings_handler_returns_instance(): void {
		$handler = $this->server->get_settings_handler();

		$this->assertSame( $this->settings_handler, $handler );
	}
}

