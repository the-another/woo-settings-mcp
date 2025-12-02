<?php
/**
 * HTTP Transport tests.
 *
 * @package Another\Plugin\Woo_Settings_MCP\Tests\Unit
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP\Tests\Unit;

use Another\Plugin\Woo_Settings_MCP\Transport_Http;
use Another\Plugin\Woo_Settings_MCP\MCP_Server;
use Another\Plugin\Woo_Settings_MCP\Settings_Handler;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test case for Transport_Http class.
 */
class TransportHttpTest extends \WooSettingsMCP_TestCase {

	/**
	 * HTTP transport instance.
	 *
	 * @var Transport_Http
	 */
	private Transport_Http $transport;

	/**
	 * MCP server mock.
	 *
	 * @var MCP_Server|\Mockery\MockInterface
	 */
	private $mcp_server;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mcp_server = \Mockery::mock( MCP_Server::class );
		$this->transport  = new Transport_Http( $this->mcp_server );
	}

	/**
	 * Create a mock WP_REST_Request.
	 *
	 * @param string $body The request body.
	 *
	 * @return \Mockery\MockInterface
	 */
	private function create_mock_request( string $body ): \Mockery\MockInterface {
		$request = \Mockery::mock( 'WP_REST_Request' );
		$request->shouldReceive( 'get_body' )->andReturn( $body );

		return $request;
	}

	/**
	 * Test handle_request with empty body.
	 *
	 * @return void
	 */
	public function test_handle_request_with_empty_body(): void {
		$request = $this->create_mock_request( '' );

		$response = $this->transport->handle_request( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertEquals( 'empty_body', $response->get_error_code() );
	}

	/**
	 * Test handle_request with invalid JSON.
	 *
	 * @return void
	 */
	public function test_handle_request_with_invalid_json(): void {
		$request = $this->create_mock_request( 'not valid json' );

		$response = $this->transport->handle_request( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );

		$data = $response->get_data();
		$this->assertArrayHasKey( 'error', $data );
		$this->assertEquals( -32700, $data['error']['code'] );
	}

	/**
	 * Test handle_request with valid request.
	 *
	 * @return void
	 */
	public function test_handle_request_with_valid_request(): void {
		$body = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'ping',
				'id'      => 1,
			)
		);

		$this->mcp_server
			->shouldReceive( 'process_message' )
			->once()
			->andReturn(
				json_encode(
					array(
						'jsonrpc' => '2.0',
						'id'      => 1,
						'result'  => array( 'pong' => true ),
					)
				)
			);

		$request  = $this->create_mock_request( $body );
		$response = $this->transport->handle_request( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( '2.0', $data['jsonrpc'] );
		$this->assertTrue( $data['result']['pong'] );
	}

	/**
	 * Test handle_request with notification returns 204.
	 *
	 * @return void
	 */
	public function test_handle_request_with_notification_returns_204(): void {
		$body = json_encode(
			array(
				'jsonrpc' => '2.0',
				'method'  => 'initialized',
			)
		);

		$this->mcp_server
			->shouldReceive( 'process_message' )
			->once()
			->andReturn( null );

		$request  = $this->create_mock_request( $body );
		$response = $this->transport->handle_request( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertEquals( 204, $response->get_status() );
	}

	/**
	 * Test check_permission with read capability.
	 *
	 * @return void
	 */
	public function test_check_permission_with_read_capability(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$request = $this->create_mock_request( '' );
		$result  = $this->transport->check_permission( $request );

		$this->assertTrue( $result );
	}

	/**
	 * Test check_permission without capability.
	 *
	 * @return void
	 */
	public function test_check_permission_without_capability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$request = $this->create_mock_request( '' );
		$result  = $this->transport->check_permission( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'rest_forbidden', $result->get_error_code() );
	}

	/**
	 * Test get_mcp_server returns server instance.
	 *
	 * @return void
	 */
	public function test_get_mcp_server_returns_instance(): void {
		$this->assertSame( $this->mcp_server, $this->transport->get_mcp_server() );
	}
}

