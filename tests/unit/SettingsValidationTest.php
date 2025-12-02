<?php
/**
 * Settings Validation edge case tests.
 *
 * @package Another\Plugin\Woo_Settings_MCP\Tests\Unit
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP\Tests\Unit;

use Another\Plugin\Woo_Settings_MCP\Settings_Handler;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test case for Settings_Handler validation edge cases.
 */
class SettingsValidationTest extends \WooSettingsMCP_TestCase {

	/**
	 * Settings handler instance.
	 *
	 * @var Settings_Handler
	 */
	private Settings_Handler $handler;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mock_woocommerce();

		$this->handler = new Settings_Handler();
	}

	/**
	 * Test validate country_state with valid country only.
	 *
	 * @return void
	 */
	public function test_validate_country_state_valid_country_only(): void {
		$mock_countries = $this->create_mock_wc_countries();

		// Mock WC_Countries class existence.
		$handler = new class() extends Settings_Handler {
			/**
			 * Override get_countries for testing.
			 *
			 * @return array<string, string>
			 */
			public function get_countries(): array {
				return array(
					'US' => 'United States',
					'CA' => 'Canada',
					'GB' => 'United Kingdom',
				);
			}
		};

		Functions\when( 'update_option' )->justReturn( true );

		$result = $handler->update_setting( 'woocommerce_default_country', 'US' );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test validate country_state with invalid country.
	 *
	 * @return void
	 */
	public function test_validate_country_state_invalid_country(): void {
		$handler = new class() extends Settings_Handler {
			/**
			 * Override get_countries for testing.
			 *
			 * @return array<string, string>
			 */
			public function get_countries(): array {
				return array(
					'US' => 'United States',
					'CA' => 'Canada',
				);
			}
		};

		$result = $handler->update_setting( 'woocommerce_default_country', 'XX' );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Invalid country code', $result['message'] );
	}

	/**
	 * Test validate country codes with empty array.
	 *
	 * @return void
	 */
	public function test_validate_country_codes_empty_array(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting( 'woocommerce_specific_allowed_countries', array() );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test validate country codes with mixed valid and invalid.
	 *
	 * @return void
	 */
	public function test_validate_country_codes_mixed_valid_invalid(): void {
		$handler = new class() extends Settings_Handler {
			/**
			 * Override get_countries for testing.
			 *
			 * @return array<string, string>
			 */
			public function get_countries(): array {
				return array(
					'US' => 'United States',
					'CA' => 'Canada',
				);
			}
		};

		$result = $handler->update_setting(
			'woocommerce_specific_allowed_countries',
			array( 'US', 'XX', 'YY' )
		);

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Invalid country codes', $result['message'] );
		$this->assertStringContainsString( 'XX', $result['message'] );
		$this->assertStringContainsString( 'YY', $result['message'] );
	}

	/**
	 * Test validate currency with invalid code.
	 *
	 * @return void
	 */
	public function test_validate_currency_invalid_code(): void {
		$result = $this->handler->update_setting( 'woocommerce_currency', 'INVALID' );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Invalid currency code', $result['message'] );
	}

	/**
	 * Test validate currency with valid code.
	 *
	 * @return void
	 */
	public function test_validate_currency_valid_code(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting( 'woocommerce_currency', 'EUR' );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test sanitization of text fields.
	 *
	 * @return void
	 */
	public function test_sanitization_strips_tags(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting(
			'woocommerce_store_city',
			'<script>alert("xss")</script>New York'
		);

		$this->assertTrue( $result['success'] );
		$this->assertStringNotContainsString( '<script>', $result['value'] );
	}

	/**
	 * Test integer type coercion.
	 *
	 * @return void
	 */
	public function test_integer_type_coercion(): void {
		Functions\when( 'update_option' )->justReturn( true );

		// String number should be coerced.
		$result = $this->handler->update_setting( 'woocommerce_price_num_decimals', '3' );

		$this->assertTrue( $result['success'] );
		$this->assertSame( 3, $result['value'] );
	}

	/**
	 * Test array type validation.
	 *
	 * @return void
	 */
	public function test_array_type_validation(): void {
		$result = $this->handler->update_setting(
			'woocommerce_specific_allowed_countries',
			'not an array'
		);

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Expected type array', $result['message'] );
	}

	/**
	 * Test string type validation for integer field.
	 *
	 * @return void
	 */
	public function test_non_numeric_string_for_integer(): void {
		$result = $this->handler->update_setting(
			'woocommerce_price_num_decimals',
			'abc'
		);

		$this->assertFalse( $result['success'] );
	}

	/**
	 * Test boundary value for decimals - zero.
	 *
	 * @return void
	 */
	public function test_decimals_boundary_zero(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting( 'woocommerce_price_num_decimals', 0 );

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 0, $result['value'] );
	}

	/**
	 * Test boundary value for decimals - eight.
	 *
	 * @return void
	 */
	public function test_decimals_boundary_eight(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting( 'woocommerce_price_num_decimals', 8 );

		$this->assertTrue( $result['success'] );
		$this->assertEquals( 8, $result['value'] );
	}

	/**
	 * Test currency position left_space.
	 *
	 * @return void
	 */
	public function test_currency_position_left_space(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting( 'woocommerce_currency_pos', 'left_space' );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test currency position right_space.
	 *
	 * @return void
	 */
	public function test_currency_position_right_space(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting( 'woocommerce_currency_pos', 'right_space' );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test special characters in separators.
	 *
	 * @return void
	 */
	public function test_special_characters_in_separators(): void {
		Functions\when( 'update_option' )->justReturn( true );

		// Test thousand separator with space.
		$result = $this->handler->update_setting( 'woocommerce_price_thousand_sep', ' ' );
		$this->assertTrue( $result['success'] );

		// Test decimal separator with comma.
		$result = $this->handler->update_setting( 'woocommerce_price_decimal_sep', ',' );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test empty string for optional fields.
	 *
	 * @return void
	 */
	public function test_empty_string_for_optional_fields(): void {
		Functions\when( 'update_option' )->justReturn( true );

		// Address line 2 is optional.
		$result = $this->handler->update_setting( 'woocommerce_store_address_2', '' );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test ship_to_countries empty string means selling locations.
	 *
	 * @return void
	 */
	public function test_ship_to_countries_empty_string(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting( 'woocommerce_ship_to_countries', '' );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test default_customer_address empty string means no default.
	 *
	 * @return void
	 */
	public function test_default_customer_address_empty_string(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting( 'woocommerce_default_customer_address', '' );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test long address is accepted.
	 *
	 * @return void
	 */
	public function test_long_address_accepted(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$long_address = str_repeat( 'A', 500 );
		$result       = $this->handler->update_setting( 'woocommerce_store_address', $long_address );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test unicode characters in address.
	 *
	 * @return void
	 */
	public function test_unicode_characters_in_address(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$unicode_address = '東京都渋谷区';
		$result          = $this->handler->update_setting( 'woocommerce_store_city', $unicode_address );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test get_currencies without WooCommerce function.
	 *
	 * @return void
	 */
	public function test_get_currencies_without_woocommerce(): void {
		// Create handler without mocked WooCommerce.
		$handler = new class() extends Settings_Handler {
			/**
			 * Override to return empty for testing fallback.
			 *
			 * @return array<string, string>
			 */
			public function get_currencies(): array {
				// Simulate WooCommerce not being available.
				return array();
			}
		};

		$currencies = $handler->get_currencies();
		$this->assertIsArray( $currencies );
		$this->assertEmpty( $currencies );
	}

	/**
	 * Test get_countries without WC_Countries class.
	 *
	 * @return void
	 */
	public function test_get_countries_without_wc_countries(): void {
		$handler = new class() extends Settings_Handler {
			/**
			 * Override to return empty for testing fallback.
			 *
			 * @return array<string, string>
			 */
			public function get_countries(): array {
				// Simulate WC_Countries not being available.
				return array();
			}
		};

		$countries = $handler->get_countries();
		$this->assertIsArray( $countries );
		$this->assertEmpty( $countries );
	}
}

