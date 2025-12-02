<?php
/**
 * Settings Handler class.
 *
 * @package Another\Plugin\Woo_Settings_MCP
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP;

/**
 * Handles WooCommerce settings access and validation.
 *
 * Provides methods to list, get, and update WooCommerce general settings
 * with proper validation using WooCommerce's internal mechanisms.
 */
class Settings_Handler {

	/**
	 * Supported settings schema.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private array $settings_schema;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings_schema = $this->get_settings_schema();
	}

	/**
	 * Get the settings schema definition.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_settings_schema(): array {
		return [
			// Store Address settings.
			'woocommerce_store_address'              => [
				'type'        => 'string',
				'label'       => __( 'Address line 1', 'woo-settings-mcp' ),
				'description' => __( 'The street address for your business location.', 'woo-settings-mcp' ),
				'group'       => 'store_address',
				'sanitize'    => 'sanitize_text_field',
			],
			'woocommerce_store_address_2'            => [
				'type'        => 'string',
				'label'       => __( 'Address line 2', 'woo-settings-mcp' ),
				'description' => __( 'An additional, optional address line for your business location.', 'woo-settings-mcp' ),
				'group'       => 'store_address',
				'sanitize'    => 'sanitize_text_field',
			],
			'woocommerce_store_city'                 => [
				'type'        => 'string',
				'label'       => __( 'City', 'woo-settings-mcp' ),
				'description' => __( 'The city in which your business is located.', 'woo-settings-mcp' ),
				'group'       => 'store_address',
				'sanitize'    => 'sanitize_text_field',
			],
			'woocommerce_default_country'            => [
				'type'        => 'string',
				'label'       => __( 'Country / State', 'woo-settings-mcp' ),
				'description' => __( 'The country and state or province, if any, in which your business is located (format: CC:SS, e.g., US:CA).', 'woo-settings-mcp' ),
				'group'       => 'store_address',
				'sanitize'    => 'sanitize_text_field',
				'validate'    => 'validate_country_state',
			],
			'woocommerce_store_postcode'             => [
				'type'        => 'string',
				'label'       => __( 'Postcode / ZIP', 'woo-settings-mcp' ),
				'description' => __( 'The postal code, if any, in which your business is located.', 'woo-settings-mcp' ),
				'group'       => 'store_address',
				'sanitize'    => 'sanitize_text_field',
			],
			// General Options settings.
			'woocommerce_allowed_countries'          => [
				'type'           => 'string',
				'label'          => __( 'Selling location(s)', 'woo-settings-mcp' ),
				'description'    => __( 'This option lets you limit which countries you are willing to sell to.', 'woo-settings-mcp' ),
				'group'          => 'general_options',
				'allowed_values' => [ 'all', 'all_except', 'specific' ],
				'sanitize'       => 'sanitize_text_field',
			],
			'woocommerce_all_except_countries'       => [
				'type'        => 'array',
				'label'       => __( 'Sell to all countries except', 'woo-settings-mcp' ),
				'description' => __( 'List of country codes to exclude from selling (when "all_except" is selected).', 'woo-settings-mcp' ),
				'group'       => 'general_options',
				'validate'    => 'validate_country_codes',
			],
			'woocommerce_specific_allowed_countries' => [
				'type'        => 'array',
				'label'       => __( 'Sell to specific countries', 'woo-settings-mcp' ),
				'description' => __( 'List of specific country codes to sell to (when "specific" is selected).', 'woo-settings-mcp' ),
				'group'       => 'general_options',
				'validate'    => 'validate_country_codes',
			],
			'woocommerce_ship_to_countries'          => [
				'type'           => 'string',
				'label'          => __( 'Shipping location(s)', 'woo-settings-mcp' ),
				'description'    => __( 'Choose which countries you want to ship to, or choose to disable shipping.', 'woo-settings-mcp' ),
				'group'          => 'general_options',
				'allowed_values' => [ '', 'all', 'specific', 'disabled' ],
				'sanitize'       => 'sanitize_text_field',
			],
			'woocommerce_specific_ship_to_countries' => [
				'type'        => 'array',
				'label'       => __( 'Ship to specific countries', 'woo-settings-mcp' ),
				'description' => __( 'List of specific country codes to ship to.', 'woo-settings-mcp' ),
				'group'       => 'general_options',
				'validate'    => 'validate_country_codes',
			],
			'woocommerce_default_customer_address'   => [
				'type'           => 'string',
				'label'          => __( 'Default customer location', 'woo-settings-mcp' ),
				'description'    => __( 'This option determines a customers default location.', 'woo-settings-mcp' ),
				'group'          => 'general_options',
				'allowed_values' => [ '', 'base', 'geolocation', 'geolocation_ajax' ],
				'sanitize'       => 'sanitize_text_field',
			],
			// Currency Options settings.
			'woocommerce_currency'                   => [
				'type'        => 'string',
				'label'       => __( 'Currency', 'woo-settings-mcp' ),
				'description' => __( 'This controls what currency prices are listed at in the catalog and which currency gateways will take payments in.', 'woo-settings-mcp' ),
				'group'       => 'currency_options',
				'validate'    => 'validate_currency',
				'sanitize'    => 'sanitize_text_field',
			],
			'woocommerce_currency_pos'               => [
				'type'           => 'string',
				'label'          => __( 'Currency position', 'woo-settings-mcp' ),
				'description'    => __( 'This controls the position of the currency symbol.', 'woo-settings-mcp' ),
				'group'          => 'currency_options',
				'allowed_values' => [ 'left', 'right', 'left_space', 'right_space' ],
				'sanitize'       => 'sanitize_text_field',
			],
			'woocommerce_price_thousand_sep'         => [
				'type'        => 'string',
				'label'       => __( 'Thousand separator', 'woo-settings-mcp' ),
				'description' => __( 'This sets the thousand separator of displayed prices.', 'woo-settings-mcp' ),
				'group'       => 'currency_options',
				'sanitize'    => 'wp_kses_post',
			],
			'woocommerce_price_decimal_sep'          => [
				'type'        => 'string',
				'label'       => __( 'Decimal separator', 'woo-settings-mcp' ),
				'description' => __( 'This sets the decimal separator of displayed prices.', 'woo-settings-mcp' ),
				'group'       => 'currency_options',
				'sanitize'    => 'wp_kses_post',
			],
			'woocommerce_price_num_decimals'         => [
				'type'        => 'integer',
				'label'       => __( 'Number of decimals', 'woo-settings-mcp' ),
				'description' => __( 'This sets the number of decimal points shown in displayed prices.', 'woo-settings-mcp' ),
				'group'       => 'currency_options',
				'min'         => 0,
				'max'         => 8,
				'sanitize'    => 'absint',
			],
		];
	}

	/**
	 * List all settings with their current values and metadata.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function list_settings(): array {
		$settings = [];

		foreach ( $this->settings_schema as $option_name => $schema ) {
			$settings[ $option_name ] = $this->get_setting_data( $option_name );
		}

		return $settings;
	}

	/**
	 * Get a specific setting with its metadata.
	 *
	 * @param string $option_name The option name.
	 *
	 * @return array<string, mixed>|null Setting data or null if not found.
	 */
	public function get_setting( string $option_name ): ?array {
		if ( ! isset( $this->settings_schema[ $option_name ] ) ) {
			return null;
		}

		return $this->get_setting_data( $option_name );
	}

	/**
	 * Get setting data including current value and allowed values.
	 *
	 * @param string $option_name The option name.
	 *
	 * @return array<string, mixed>
	 */
	private function get_setting_data( string $option_name ): array {
		$schema = $this->settings_schema[ $option_name ];
		$value  = get_option( $option_name, '' );

		$data = [
			'option_name' => $option_name,
			'value'       => $value,
			'type'        => $schema['type'],
			'label'       => $schema['label'],
			'description' => $schema['description'],
			'group'       => $schema['group'],
		];

		// Add allowed values if defined statically.
		if ( isset( $schema['allowed_values'] ) ) {
			$data['allowed_values'] = $schema['allowed_values'];
		}

		// Add dynamic allowed values for specific options.
		$data['allowed_values'] = $this->get_allowed_values( $option_name, $data['allowed_values'] ?? null );

		// Add min/max for numeric types.
		if ( isset( $schema['min'] ) ) {
			$data['min'] = $schema['min'];
		}
		if ( isset( $schema['max'] ) ) {
			$data['max'] = $schema['max'];
		}

		return $data;
	}

	/**
	 * Get allowed values for an option, applying WooCommerce filters.
	 *
	 * @param string             $option_name The option name.
	 * @param array<string>|null $static_values Static allowed values if defined.
	 *
	 * @return array<string, string>|array<string>|null
	 */
	private function get_allowed_values( string $option_name, ?array $static_values ): ?array {
		switch ( $option_name ) {
			case 'woocommerce_currency':
				return $this->get_currencies();

			case 'woocommerce_default_country':
			case 'woocommerce_all_except_countries':
			case 'woocommerce_specific_allowed_countries':
			case 'woocommerce_specific_ship_to_countries':
				return $this->get_countries();

			default:
				return $static_values;
		}
	}

	/**
	 * Get available currencies using WooCommerce filter.
	 *
	 * @return array<string, string>
	 */
	public function get_currencies(): array {
		if ( function_exists( 'get_woocommerce_currencies' ) ) {
			return get_woocommerce_currencies();
		}

		// Fallback if WooCommerce function is not available.
		return [];
	}

	/**
	 * Get available countries using WooCommerce filter.
	 *
	 * @return array<string, string>
	 */
	public function get_countries(): array {
		if ( class_exists( 'WC_Countries' ) ) {
			$wc_countries = new \WC_Countries();
			return $wc_countries->get_countries();
		}

		// Fallback if WooCommerce class is not available.
		return [];
	}

	/**
	 * Update a setting value.
	 *
	 * @param string $option_name The option name.
	 * @param mixed  $value       The new value.
	 *
	 * @return array{success: bool, message: string, value?: mixed}
	 */
	public function update_setting( string $option_name, mixed $value ): array {
		// Check if option exists in schema.
		if ( ! isset( $this->settings_schema[ $option_name ] ) ) {
			return [
				'success' => false,
				'message' => sprintf(
					/* translators: %s: option name */
					__( 'Unknown setting: %s', 'woo-settings-mcp' ),
					$option_name
				),
			];
		}

		$schema = $this->settings_schema[ $option_name ];

		// Validate the value.
		$validation_result = $this->validate_value( $option_name, $value, $schema );
		if ( ! $validation_result['valid'] ) {
			return [
				'success' => false,
				'message' => $validation_result['message'],
			];
		}

		// Sanitize the value.
		$sanitized_value = $this->sanitize_value( $value, $schema );

		// Update the option.
		$updated = update_option( $option_name, $sanitized_value );

		if ( $updated ) {
			/**
			 * Fires after a WooCommerce setting is updated via MCP.
			 *
			 * @param string $option_name    The option name.
			 * @param mixed  $sanitized_value The new sanitized value.
			 * @param mixed  $value          The original value before sanitization.
			 */
			do_action( 'woo_settings_mcp_setting_updated', $option_name, $sanitized_value, $value );

			return [
				'success' => true,
				'message' => sprintf(
					/* translators: %s: option name */
					__( 'Setting %s updated successfully.', 'woo-settings-mcp' ),
					$option_name
				),
				'value'   => $sanitized_value,
			];
		}

		// Check if the value is the same (no update needed).
		$current_value = get_option( $option_name );
		if ( $current_value === $sanitized_value ) {
			return [
				'success' => true,
				'message' => sprintf(
					/* translators: %s: option name */
					__( 'Setting %s already has this value.', 'woo-settings-mcp' ),
					$option_name
				),
				'value'   => $sanitized_value,
			];
		}

		return [
			'success' => false,
			'message' => sprintf(
				/* translators: %s: option name */
				__( 'Failed to update setting: %s', 'woo-settings-mcp' ),
				$option_name
			),
		];
	}

	/**
	 * Validate a value against the schema.
	 *
	 * @param string               $option_name The option name.
	 * @param mixed                $value       The value to validate.
	 * @param array<string, mixed> $schema      The schema definition.
	 *
	 * @return array{valid: bool, message: string}
	 */
	private function validate_value( string $option_name, mixed $value, array $schema ): array {
		// Type validation.
		$type_valid = $this->validate_type( $value, $schema['type'] );
		if ( ! $type_valid['valid'] ) {
			return $type_valid;
		}

		// Allowed values validation.
		if ( isset( $schema['allowed_values'] ) ) {
			$allowed = $schema['allowed_values'];
			if ( is_array( $allowed ) && ! in_array( $value, $allowed, true ) ) {
				return [
					'valid'   => false,
					'message' => sprintf(
						/* translators: 1: value, 2: allowed values */
						__( 'Invalid value "%1$s". Allowed values: %2$s', 'woo-settings-mcp' ),
						is_string( $value ) ? $value : wp_json_encode( $value ),
						implode( ', ', $allowed )
					),
				];
			}
		}

		// Min/Max validation for integers.
		if ( 'integer' === $schema['type'] ) {
			if ( isset( $schema['min'] ) && $value < $schema['min'] ) {
				return [
					'valid'   => false,
					'message' => sprintf(
						/* translators: 1: value, 2: minimum value */
						__( 'Value %1$d is below minimum %2$d.', 'woo-settings-mcp' ),
						$value,
						$schema['min']
					),
				];
			}
			if ( isset( $schema['max'] ) && $value > $schema['max'] ) {
				return [
					'valid'   => false,
					'message' => sprintf(
						/* translators: 1: value, 2: maximum value */
						__( 'Value %1$d is above maximum %2$d.', 'woo-settings-mcp' ),
						$value,
						$schema['max']
					),
				];
			}
		}

		// Custom validation methods.
		if ( isset( $schema['validate'] ) ) {
			$method = $schema['validate'];
			if ( method_exists( $this, $method ) ) {
				return $this->$method( $value );
			}
		}

		return [
			'valid'   => true,
			'message' => '',
		];
	}

	/**
	 * Validate value type.
	 *
	 * @param mixed  $value The value to validate.
	 * @param string $type  The expected type.
	 *
	 * @return array{valid: bool, message: string}
	 */
	private function validate_type( mixed $value, string $type ): array {
		$valid = match ( $type ) {
			'string'  => is_string( $value ),
			'integer' => is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ),
			'array'   => is_array( $value ),
			'boolean' => is_bool( $value ) || in_array( $value, [ 'yes', 'no', '1', '0', 1, 0 ], true ),
			default   => true,
		};

		if ( ! $valid ) {
			return [
				'valid'   => false,
				'message' => sprintf(
					/* translators: 1: expected type, 2: actual type */
					__( 'Expected type %1$s, got %2$s.', 'woo-settings-mcp' ),
					$type,
					gettype( $value )
				),
			];
		}

		return [
			'valid'   => true,
			'message' => '',
		];
	}

	/**
	 * Validate country/state format (CC:SS or CC).
	 *
	 * @param string $value The country:state value.
	 *
	 * @return array{valid: bool, message: string}
	 */
	private function validate_country_state( string $value ): array {
		$countries = $this->get_countries();

		// Parse country code.
		$parts        = explode( ':', $value );
		$country_code = $parts[0];

		if ( ! isset( $countries[ $country_code ] ) ) {
			return [
				'valid'   => false,
				'message' => sprintf(
					/* translators: %s: country code */
					__( 'Invalid country code: %s', 'woo-settings-mcp' ),
					$country_code
				),
			];
		}

		// Validate state if provided.
		if ( isset( $parts[1] ) && '' !== $parts[1] ) {
			if ( class_exists( 'WC_Countries' ) ) {
				$wc_countries = new \WC_Countries();
				$states       = $wc_countries->get_states( $country_code );

				if ( ! empty( $states ) && ! isset( $states[ $parts[1] ] ) ) {
					return [
						'valid'   => false,
						'message' => sprintf(
							/* translators: 1: state code, 2: country code */
							__( 'Invalid state code "%1$s" for country "%2$s".', 'woo-settings-mcp' ),
							$parts[1],
							$country_code
						),
					];
				}
			}
		}

		return [
			'valid'   => true,
			'message' => '',
		];
	}

	/**
	 * Validate array of country codes.
	 *
	 * @param array<string> $value The country codes array.
	 *
	 * @return array{valid: bool, message: string}
	 */
	private function validate_country_codes( array $value ): array {
		$countries     = $this->get_countries();
		$invalid_codes = [];

		foreach ( $value as $code ) {
			if ( ! isset( $countries[ $code ] ) ) {
				$invalid_codes[] = $code;
			}
		}

		if ( ! empty( $invalid_codes ) ) {
			return [
				'valid'   => false,
				'message' => sprintf(
					/* translators: %s: invalid country codes */
					__( 'Invalid country codes: %s', 'woo-settings-mcp' ),
					implode( ', ', $invalid_codes )
				),
			];
		}

		return [
			'valid'   => true,
			'message' => '',
		];
	}

	/**
	 * Validate currency code.
	 *
	 * @param string $value The currency code.
	 *
	 * @return array{valid: bool, message: string}
	 */
	private function validate_currency( string $value ): array {
		$currencies = $this->get_currencies();

		if ( ! isset( $currencies[ $value ] ) ) {
			return [
				'valid'   => false,
				'message' => sprintf(
					/* translators: %s: currency code */
					__( 'Invalid currency code: %s', 'woo-settings-mcp' ),
					$value
				),
			];
		}

		return [
			'valid'   => true,
			'message' => '',
		];
	}

	/**
	 * Sanitize a value based on schema.
	 *
	 * @param mixed                $value  The value to sanitize.
	 * @param array<string, mixed> $schema The schema definition.
	 *
	 * @return mixed The sanitized value.
	 */
	private function sanitize_value( mixed $value, array $schema ): mixed {
		if ( isset( $schema['sanitize'] ) ) {
			$sanitize_func = $schema['sanitize'];

			if ( is_callable( $sanitize_func ) ) {
				if ( 'array' === $schema['type'] && is_array( $value ) ) {
					return array_map( $sanitize_func, $value );
				}
				return call_user_func( $sanitize_func, $value );
			}
		}

		// Default sanitization by type.
		return match ( $schema['type'] ) {
			'string'  => sanitize_text_field( (string) $value ),
			'integer' => absint( $value ),
			'array'   => array_map( 'sanitize_text_field', (array) $value ),
			'boolean' => in_array( $value, [ 'yes', '1', 1, true ], true ) ? 'yes' : 'no',
			default   => $value,
		};
	}

	/**
	 * Get the settings schema.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_schema(): array {
		return $this->settings_schema;
	}

	/**
	 * Check if an option exists in the schema.
	 *
	 * @param string $option_name The option name.
	 *
	 * @return bool
	 */
	public function option_exists( string $option_name ): bool {
		return isset( $this->settings_schema[ $option_name ] );
	}
}
