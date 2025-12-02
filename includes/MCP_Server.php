<?php
/**
 * MCP Server class.
 *
 * @package Another\Plugin\Woo_Settings_MCP
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP;

/**
 * MCP (Model Context Protocol) server implementation.
 *
 * Handles JSON-RPC 2.0 messages for the MCP protocol,
 * exposing WooCommerce settings as MCP tools.
 */
class MCP_Server {

	/**
	 * JSON-RPC version.
	 *
	 * @var string
	 */
	private const JSONRPC_VERSION = '2.0';

	/**
	 * MCP protocol version.
	 *
	 * @var string
	 */
	private const MCP_VERSION = '2024-11-05';

	/**
	 * Server name.
	 *
	 * @var string
	 */
	private const SERVER_NAME = 'woo-settings-mcp';

	/**
	 * Server version.
	 *
	 * @var string
	 */
	private const SERVER_VERSION = '1.0.0';

	/**
	 * Settings handler instance.
	 *
	 * @var Settings_Handler
	 */
	private Settings_Handler $settings_handler;

	/**
	 * Whether the server has been initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Constructor.
	 *
	 * @param Settings_Handler $settings_handler The settings handler instance.
	 */
	public function __construct( Settings_Handler $settings_handler ) {
		$this->settings_handler = $settings_handler;
	}

	/**
	 * Process an MCP message.
	 *
	 * @param string $json The JSON-RPC message.
	 *
	 * @return string|null The JSON-RPC response or null for notifications.
	 */
	public function process_message( string $json ): ?string {
		$request = json_decode( $json, true );

		if ( null === $request || ! is_array( $request ) ) {
			return $this->error_response( null, -32700, 'Parse error' );
		}

		// Validate JSON-RPC structure.
		if ( ! isset( $request['jsonrpc'] ) || self::JSONRPC_VERSION !== $request['jsonrpc'] ) {
			return $this->error_response( $request['id'] ?? null, -32600, 'Invalid Request: Invalid JSON-RPC version' );
		}

		if ( ! isset( $request['method'] ) || ! is_string( $request['method'] ) ) {
			return $this->error_response( $request['id'] ?? null, -32600, 'Invalid Request: Missing method' );
		}

		$method = $request['method'];
		$params = $request['params'] ?? [];
		$id     = $request['id'] ?? null;

		// Handle the method.
		$result = $this->handle_method( $method, $params );

		// If this is a notification (no id), don't return a response.
		if ( null === $id ) {
			return null;
		}

		// Check for error result.
		if ( isset( $result['error'] ) ) {
			return $this->error_response( $id, $result['error']['code'], $result['error']['message'], $result['error']['data'] ?? null );
		}

		return $this->success_response( $id, $result );
	}

	/**
	 * Handle an MCP method.
	 *
	 * @param string               $method The method name.
	 * @param array<string, mixed> $params The method parameters.
	 *
	 * @return array<string, mixed> The result or error.
	 */
	private function handle_method( string $method, array $params ): array {
		return match ( $method ) {
			'initialize'              => $this->handle_initialize( $params ),
			'initialized'             => [], // Notification acknowledgment.
			'tools/list'              => $this->handle_tools_list(),
			'tools/call'              => $this->handle_tools_call( $params ),
			'ping'                    => [ 'pong' => true ],
			default                   => [
				'error' => [
					'code'    => -32601,
					'message' => 'Method not found: ' . $method,
				],
			],
		};
	}

	/**
	 * Handle the initialize method.
	 *
	 * @param array<string, mixed> $params The initialization parameters.
	 *
	 * @return array<string, mixed>
	 */
	private function handle_initialize( array $params ): array {
		$this->initialized = true;

		return [
			'protocolVersion' => self::MCP_VERSION,
			'capabilities'    => [
				'tools' => [
					'listChanged' => false,
				],
			],
			'serverInfo'      => [
				'name'    => self::SERVER_NAME,
				'version' => self::SERVER_VERSION,
			],
		];
	}

	/**
	 * Handle the tools/list method.
	 *
	 * @return array<string, mixed>
	 */
	private function handle_tools_list(): array {
		return [
			'tools' => [
				[
					'name'        => 'list_settings',
					'description' => 'List all WooCommerce general settings with their current values, types, and allowed values.',
					'inputSchema' => [
						'type'       => 'object',
						'properties' => [
							'group' => [
								'type'        => 'string',
								'description' => 'Optional. Filter by group: store_address, general_options, or currency_options.',
								'enum'        => [ 'store_address', 'general_options', 'currency_options' ],
							],
						],
						'required'   => [],
					],
				],
				[
					'name'        => 'get_setting',
					'description' => 'Get a specific WooCommerce setting by its option name.',
					'inputSchema' => [
						'type'       => 'object',
						'properties' => [
							'option_name' => [
								'type'        => 'string',
								'description' => 'The WooCommerce option name (e.g., woocommerce_currency, woocommerce_store_address).',
							],
						],
						'required'   => [ 'option_name' ],
					],
				],
				[
					'name'        => 'update_setting',
					'description' => 'Update a WooCommerce setting value. The value will be validated against WooCommerce rules.',
					'inputSchema' => [
						'type'       => 'object',
						'properties' => [
							'option_name' => [
								'type'        => 'string',
								'description' => 'The WooCommerce option name to update.',
							],
							'value'       => [
								'description' => 'The new value for the setting. Type depends on the setting.',
							],
						],
						'required'   => [ 'option_name', 'value' ],
					],
				],
			],
		];
	}

	/**
	 * Handle the tools/call method.
	 *
	 * @param array<string, mixed> $params The call parameters.
	 *
	 * @return array<string, mixed>
	 */
	private function handle_tools_call( array $params ): array {
		if ( ! isset( $params['name'] ) ) {
			return [
				'error' => [
					'code'    => -32602,
					'message' => 'Invalid params: missing tool name',
				],
			];
		}

		$tool_name  = $params['name'];
		$tool_input = $params['arguments'] ?? [];

		$result = match ( $tool_name ) {
			'list_settings'  => $this->tool_list_settings( $tool_input ),
			'get_setting'    => $this->tool_get_setting( $tool_input ),
			'update_setting' => $this->tool_update_setting( $tool_input ),
			default          => [
				'isError' => true,
				'content' => [
					[
						'type' => 'text',
						'text' => 'Unknown tool: ' . $tool_name,
					],
				],
			],
		};

		return $result;
	}

	/**
	 * Tool: List all settings.
	 *
	 * @param array<string, mixed> $input The tool input.
	 *
	 * @return array<string, mixed>
	 */
	private function tool_list_settings( array $input ): array {
		$settings = $this->settings_handler->list_settings();

		// Filter by group if specified.
		if ( isset( $input['group'] ) && '' !== $input['group'] ) {
			$group    = $input['group'];
			$settings = array_filter(
				$settings,
				static fn( array $setting ): bool => $setting['group'] === $group
			);
		}

		return [
			'content' => [
				[
					'type' => 'text',
					'text' => wp_json_encode( $settings, JSON_PRETTY_PRINT ),
				],
			],
		];
	}

	/**
	 * Tool: Get a specific setting.
	 *
	 * @param array<string, mixed> $input The tool input.
	 *
	 * @return array<string, mixed>
	 */
	private function tool_get_setting( array $input ): array {
		if ( ! isset( $input['option_name'] ) ) {
			return [
				'isError' => true,
				'content' => [
					[
						'type' => 'text',
						'text' => 'Missing required parameter: option_name',
					],
				],
			];
		}

		$setting = $this->settings_handler->get_setting( $input['option_name'] );

		if ( null === $setting ) {
			return [
				'isError' => true,
				'content' => [
					[
						'type' => 'text',
						'text' => 'Unknown setting: ' . $input['option_name'],
					],
				],
			];
		}

		return [
			'content' => [
				[
					'type' => 'text',
					'text' => wp_json_encode( $setting, JSON_PRETTY_PRINT ),
				],
			],
		];
	}

	/**
	 * Tool: Update a setting.
	 *
	 * @param array<string, mixed> $input The tool input.
	 *
	 * @return array<string, mixed>
	 */
	private function tool_update_setting( array $input ): array {
		if ( ! isset( $input['option_name'] ) ) {
			return [
				'isError' => true,
				'content' => [
					[
						'type' => 'text',
						'text' => 'Missing required parameter: option_name',
					],
				],
			];
		}

		if ( ! array_key_exists( 'value', $input ) ) {
			return [
				'isError' => true,
				'content' => [
					[
						'type' => 'text',
						'text' => 'Missing required parameter: value',
					],
				],
			];
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return [
				'isError' => true,
				'content' => [
					[
						'type' => 'text',
						'text' => 'Permission denied: You do not have permission to update WooCommerce settings.',
					],
				],
			];
		}

		$result = $this->settings_handler->update_setting( $input['option_name'], $input['value'] );

		if ( ! $result['success'] ) {
			return [
				'isError' => true,
				'content' => [
					[
						'type' => 'text',
						'text' => $result['message'],
					],
				],
			];
		}

		return [
			'content' => [
				[
					'type' => 'text',
					'text' => wp_json_encode(
						[
							'success' => true,
							'message' => $result['message'],
							'value'   => $result['value'] ?? null,
						],
						JSON_PRETTY_PRINT
					),
				],
			],
		];
	}

	/**
	 * Create a success response.
	 *
	 * @param string|int           $id     The request ID.
	 * @param array<string, mixed> $result The result.
	 *
	 * @return string JSON response.
	 */
	private function success_response( string|int $id, array $result ): string {
		$encoded = wp_json_encode(
			[
				'jsonrpc' => self::JSONRPC_VERSION,
				'id'      => $id,
				'result'  => $result,
			]
		);

		return false !== $encoded ? $encoded : '{}';
	}

	/**
	 * Create an error response.
	 *
	 * @param string|int|null $id      The request ID.
	 * @param int             $code    The error code.
	 * @param string          $message The error message.
	 * @param mixed           $data    Optional error data.
	 *
	 * @return string JSON response.
	 */
	private function error_response( string|int|null $id, int $code, string $message, mixed $data = null ): string {
		$error = [
			'code'    => $code,
			'message' => $message,
		];

		if ( null !== $data ) {
			$error['data'] = $data;
		}

		$encoded = wp_json_encode(
			[
				'jsonrpc' => self::JSONRPC_VERSION,
				'id'      => $id,
				'error'   => $error,
			]
		);

		return false !== $encoded ? $encoded : '{}';
	}

	/**
	 * Check if server is initialized.
	 *
	 * @return bool
	 */
	public function is_initialized(): bool {
		return $this->initialized;
	}

	/**
	 * Get the settings handler.
	 *
	 * @return Settings_Handler
	 */
	public function get_settings_handler(): Settings_Handler {
		return $this->settings_handler;
	}
}
