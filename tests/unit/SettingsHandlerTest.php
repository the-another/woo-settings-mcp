<?php
/**
 * Settings Handler tests.
 *
 * @package Another\Plugin\Woo_Settings_MCP\Tests\Unit
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP\Tests\Unit;

use Another\Plugin\Woo_Settings_MCP\Settings_Handler;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test case for Settings_Handler class.
 */
class SettingsHandlerTest extends \WooSettingsMCP_TestCase {

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

		// Mock WooCommerce functions.
		$this->mock_woocommerce();

		$this->handler = new Settings_Handler();
	}

	/**
	 * Test that the settings schema contains all expected options.
	 *
	 * @return void
	 */
	public function test_schema_contains_all_expected_options(): void {
		$schema = $this->handler->get_schema();

		$expected_options = array(
			// Store Address.
			'woocommerce_store_address',
			'woocommerce_store_address_2',
			'woocommerce_store_city',
			'woocommerce_default_country',
			'woocommerce_store_postcode',
			// General Options.
			'woocommerce_allowed_countries',
			'woocommerce_all_except_countries',
			'woocommerce_specific_allowed_countries',
			'woocommerce_ship_to_countries',
			'woocommerce_specific_ship_to_countries',
			'woocommerce_default_customer_address',
			// Currency Options.
			'woocommerce_currency',
			'woocommerce_currency_pos',
			'woocommerce_price_thousand_sep',
			'woocommerce_price_decimal_sep',
			'woocommerce_price_num_decimals',
		);

		foreach ( $expected_options as $option ) {
			$this->assertArrayHasKey( $option, $schema, "Schema should contain {$option}" );
		}
	}

	/**
	 * Test that excluded options are not in schema.
	 *
	 * @return void
	 */
	public function test_excluded_options_not_in_schema(): void {
		$schema = $this->handler->get_schema();

		$excluded_options = array(
			'woocommerce_calc_taxes',
			'woocommerce_enable_coupons',
			'woocommerce_calc_discounts_sequentially',
		);

		foreach ( $excluded_options as $option ) {
			$this->assertArrayNotHasKey( $option, $schema, "Schema should NOT contain {$option}" );
		}
	}

	/**
	 * Test option_exists returns true for valid options.
	 *
	 * @return void
	 */
	public function test_option_exists_returns_true_for_valid_option(): void {
		$this->assertTrue( $this->handler->option_exists( 'woocommerce_currency' ) );
		$this->assertTrue( $this->handler->option_exists( 'woocommerce_store_address' ) );
	}

	/**
	 * Test option_exists returns false for invalid options.
	 *
	 * @return void
	 */
	public function test_option_exists_returns_false_for_invalid_option(): void {
		$this->assertFalse( $this->handler->option_exists( 'invalid_option' ) );
		$this->assertFalse( $this->handler->option_exists( 'woocommerce_calc_taxes' ) );
	}

	/**
	 * Test get_setting returns null for unknown option.
	 *
	 * @return void
	 */
	public function test_get_setting_returns_null_for_unknown_option(): void {
		$result = $this->handler->get_setting( 'unknown_option' );
		$this->assertNull( $result );
	}

	/**
	 * Test get_setting returns correct structure.
	 *
	 * @return void
	 */
	public function test_get_setting_returns_correct_structure(): void {
		Functions\when( 'get_option' )->justReturn( 'USD' );

		$result = $this->handler->get_setting( 'woocommerce_currency' );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'option_name', $result );
		$this->assertArrayHasKey( 'value', $result );
		$this->assertArrayHasKey( 'type', $result );
		$this->assertArrayHasKey( 'label', $result );
		$this->assertArrayHasKey( 'description', $result );
		$this->assertArrayHasKey( 'group', $result );
		$this->assertEquals( 'woocommerce_currency', $result['option_name'] );
		$this->assertEquals( 'USD', $result['value'] );
	}

	/**
	 * Test list_settings returns all settings.
	 *
	 * @return void
	 */
	public function test_list_settings_returns_all_settings(): void {
		Functions\when( 'get_option' )->justReturn( '' );

		$settings = $this->handler->list_settings();

		$this->assertIsArray( $settings );
		$this->assertCount( 16, $settings ); // 16 settings total.
	}

	/**
	 * Test update_setting fails for unknown option.
	 *
	 * @return void
	 */
	public function test_update_setting_fails_for_unknown_option(): void {
		$result = $this->handler->update_setting( 'unknown_option', 'value' );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Unknown setting', $result['message'] );
	}

	/**
	 * Test update_setting validates currency position.
	 *
	 * @return void
	 */
	public function test_update_setting_validates_currency_position(): void {
		$result = $this->handler->update_setting( 'woocommerce_currency_pos', 'invalid' );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Invalid value', $result['message'] );
	}

	/**
	 * Test update_setting accepts valid currency position.
	 *
	 * @return void
	 */
	public function test_update_setting_accepts_valid_currency_position(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting( 'woocommerce_currency_pos', 'left' );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test update_setting validates allowed_countries.
	 *
	 * @return void
	 */
	public function test_update_setting_validates_allowed_countries(): void {
		$result = $this->handler->update_setting( 'woocommerce_allowed_countries', 'invalid_value' );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'Invalid value', $result['message'] );
	}

	/**
	 * Test update_setting accepts valid allowed_countries.
	 *
	 * @return void
	 */
	public function test_update_setting_accepts_valid_allowed_countries(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$valid_values = array( 'all', 'all_except', 'specific' );

		foreach ( $valid_values as $value ) {
			$result = $this->handler->update_setting( 'woocommerce_allowed_countries', $value );
			$this->assertTrue( $result['success'], "Should accept value: {$value}" );
		}
	}

	/**
	 * Test update_setting validates ship_to_countries.
	 *
	 * @return void
	 */
	public function test_update_setting_validates_ship_to_countries(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$valid_values = array( '', 'all', 'specific', 'disabled' );

		foreach ( $valid_values as $value ) {
			$result = $this->handler->update_setting( 'woocommerce_ship_to_countries', $value );
			$this->assertTrue( $result['success'], "Should accept value: {$value}" );
		}
	}

	/**
	 * Test update_setting validates default_customer_address.
	 *
	 * @return void
	 */
	public function test_update_setting_validates_default_customer_address(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$valid_values = array( '', 'base', 'geolocation', 'geolocation_ajax' );

		foreach ( $valid_values as $value ) {
			$result = $this->handler->update_setting( 'woocommerce_default_customer_address', $value );
			$this->assertTrue( $result['success'], "Should accept value: {$value}" );
		}
	}

	/**
	 * Test update_setting validates price_num_decimals min value.
	 *
	 * @return void
	 */
	public function test_update_setting_validates_num_decimals_min(): void {
		$result = $this->handler->update_setting( 'woocommerce_price_num_decimals', -1 );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'below minimum', $result['message'] );
	}

	/**
	 * Test update_setting validates price_num_decimals max value.
	 *
	 * @return void
	 */
	public function test_update_setting_validates_num_decimals_max(): void {
		$result = $this->handler->update_setting( 'woocommerce_price_num_decimals', 10 );

		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'above maximum', $result['message'] );
	}

	/**
	 * Test update_setting accepts valid num_decimals.
	 *
	 * @return void
	 */
	public function test_update_setting_accepts_valid_num_decimals(): void {
		Functions\when( 'update_option' )->justReturn( true );

		for ( $i = 0; $i <= 8; $i++ ) {
			$result = $this->handler->update_setting( 'woocommerce_price_num_decimals', $i );
			$this->assertTrue( $result['success'], "Should accept value: {$i}" );
		}
	}

	/**
	 * Test update_setting validates type for integer fields.
	 *
	 * @return void
	 */
	public function test_update_setting_validates_integer_type(): void {
		$result = $this->handler->update_setting( 'woocommerce_price_num_decimals', 'not_an_integer' );

		$this->assertFalse( $result['success'] );
	}

	/**
	 * Test update_setting accepts string integer for num_decimals.
	 *
	 * @return void
	 */
	public function test_update_setting_accepts_string_integer(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$result = $this->handler->update_setting( 'woocommerce_price_num_decimals', '2' );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test get_currencies returns array.
	 *
	 * @return void
	 */
	public function test_get_currencies_returns_array(): void {
		$currencies = $this->handler->get_currencies();

		$this->assertIsArray( $currencies );
		$this->assertArrayHasKey( 'USD', $currencies );
		$this->assertArrayHasKey( 'EUR', $currencies );
	}

	/**
	 * Test update_setting fires action on success.
	 *
	 * @return void
	 */
	public function test_update_setting_fires_action_on_success(): void {
		Functions\when( 'update_option' )->justReturn( true );

		$action_fired = false;
		Monkey\Actions\expectDone( 'woo_settings_mcp_setting_updated' )
			->once()
			->whenHappen(
				static function () use ( &$action_fired ): void {
					$action_fired = true;
				}
			);

		$this->handler->update_setting( 'woocommerce_store_city', 'New York' );

		$this->assertTrue( $action_fired );
	}

	/**
	 * Test update_setting handles same value gracefully.
	 *
	 * @return void
	 */
	public function test_update_setting_handles_same_value(): void {
		Functions\when( 'update_option' )->justReturn( false );
		Functions\when( 'get_option' )->justReturn( 'New York' );

		$result = $this->handler->update_setting( 'woocommerce_store_city', 'New York' );

		$this->assertTrue( $result['success'] );
		$this->assertStringContainsString( 'already has this value', $result['message'] );
	}

	/**
	 * Test schema groups are correct.
	 *
	 * @return void
	 */
	public function test_schema_groups_are_correct(): void {
		$schema = $this->handler->get_schema();

		$store_address_options = array(
			'woocommerce_store_address',
			'woocommerce_store_address_2',
			'woocommerce_store_city',
			'woocommerce_default_country',
			'woocommerce_store_postcode',
		);

		$general_options = array(
			'woocommerce_allowed_countries',
			'woocommerce_all_except_countries',
			'woocommerce_specific_allowed_countries',
			'woocommerce_ship_to_countries',
			'woocommerce_specific_ship_to_countries',
			'woocommerce_default_customer_address',
		);

		$currency_options = array(
			'woocommerce_currency',
			'woocommerce_currency_pos',
			'woocommerce_price_thousand_sep',
			'woocommerce_price_decimal_sep',
			'woocommerce_price_num_decimals',
		);

		foreach ( $store_address_options as $option ) {
			$this->assertEquals( 'store_address', $schema[ $option ]['group'] );
		}

		foreach ( $general_options as $option ) {
			$this->assertEquals( 'general_options', $schema[ $option ]['group'] );
		}

		foreach ( $currency_options as $option ) {
			$this->assertEquals( 'currency_options', $schema[ $option ]['group'] );
		}
	}
}

