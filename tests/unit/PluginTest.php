<?php
/**
 * Plugin tests.
 *
 * @package Another\Plugin\Woo_Settings_MCP\Tests\Unit
 */

declare( strict_types = 1 );

namespace Another\Plugin\Woo_Settings_MCP\Tests\Unit;

use Another\Plugin\Woo_Settings_MCP\Plugin;
use Another\Plugin\Woo_Settings_MCP\Settings_Handler;
use Another\Plugin\Woo_Settings_MCP\MCP_Server;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Test case for Plugin class.
 */
class PluginTest extends \WooSettingsMCP_TestCase {

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mock_woocommerce();

		// Reset singleton for testing.
		$reflection = new \ReflectionClass( Plugin::class );
		$property   = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	/**
	 * Test singleton returns same instance.
	 *
	 * @return void
	 */
	public function test_singleton_returns_same_instance(): void {
		// Mock WordPress functions needed for initialization.
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$params = array(
			'version'     => '1.0.0',
			'text_domain' => 'test-domain',
			'plugin_name' => 'Test Plugin',
			'plugin_file' => '/path/to/plugin.php',
			'plugin_path' => '/path/to/',
			'plugin_url'  => 'http://example.com/plugin/',
		);

		$instance1 = Plugin::instance( $params );
		$instance2 = Plugin::instance();

		$this->assertSame( $instance1, $instance2 );
	}

	/**
	 * Test get_version returns correct value.
	 *
	 * @return void
	 */
	public function test_get_version_returns_correct_value(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$plugin = Plugin::instance(
			array(
				'version' => '2.0.0',
			)
		);

		$this->assertEquals( '2.0.0', $plugin->get_version() );
	}

	/**
	 * Test get_text_domain returns correct value.
	 *
	 * @return void
	 */
	public function test_get_text_domain_returns_correct_value(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$plugin = Plugin::instance(
			array(
				'text_domain' => 'my-domain',
			)
		);

		$this->assertEquals( 'my-domain', $plugin->get_text_domain() );
	}

	/**
	 * Test get_plugin_name returns correct value.
	 *
	 * @return void
	 */
	public function test_get_plugin_name_returns_correct_value(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$plugin = Plugin::instance(
			array(
				'plugin_name' => 'My Plugin',
			)
		);

		$this->assertEquals( 'My Plugin', $plugin->get_plugin_name() );
	}

	/**
	 * Test get_plugin_file returns correct value.
	 *
	 * @return void
	 */
	public function test_get_plugin_file_returns_correct_value(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$plugin = Plugin::instance(
			array(
				'plugin_file' => '/path/to/plugin.php',
			)
		);

		$this->assertEquals( '/path/to/plugin.php', $plugin->get_plugin_file() );
	}

	/**
	 * Test get_plugin_path returns correct value.
	 *
	 * @return void
	 */
	public function test_get_plugin_path_returns_correct_value(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$plugin = Plugin::instance(
			array(
				'plugin_path' => '/path/to/',
			)
		);

		$this->assertEquals( '/path/to/', $plugin->get_plugin_path() );
	}

	/**
	 * Test get_plugin_url returns correct value.
	 *
	 * @return void
	 */
	public function test_get_plugin_url_returns_correct_value(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$plugin = Plugin::instance(
			array(
				'plugin_url' => 'http://example.com/plugin/',
			)
		);

		$this->assertEquals( 'http://example.com/plugin/', $plugin->get_plugin_url() );
	}

	/**
	 * Test init creates settings handler.
	 *
	 * @return void
	 */
	public function test_init_creates_settings_handler(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$plugin = Plugin::instance( array() );
		$plugin->init();

		$this->assertInstanceOf( Settings_Handler::class, $plugin->get_settings_handler() );
	}

	/**
	 * Test init creates MCP server.
	 *
	 * @return void
	 */
	public function test_init_creates_mcp_server(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$plugin = Plugin::instance( array() );
		$plugin->init();

		$this->assertInstanceOf( MCP_Server::class, $plugin->get_mcp_server() );
	}

	/**
	 * Test init registers rest_api_init action.
	 *
	 * @return void
	 */
	public function test_init_registers_rest_api_init(): void {
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		Monkey\Actions\expectAdded( 'rest_api_init' )->once();

		$plugin = Plugin::instance( array() );
		$plugin->init();

		$this->assertTrue( true ); // Expectation assertion.
	}

	/**
	 * Test init registers before_woocommerce_init action.
	 *
	 * @return void
	 */
	public function test_init_registers_before_woocommerce_init(): void {
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		Monkey\Actions\expectAdded( 'before_woocommerce_init' )->once();

		$plugin = Plugin::instance( array() );
		$plugin->init();

		$this->assertTrue( true ); // Expectation assertion.
	}

	/**
	 * Test activate flushes rewrite rules.
	 *
	 * @return void
	 */
	public function test_activate_flushes_rewrite_rules(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		Functions\expect( 'flush_rewrite_rules' )->once();

		$plugin = Plugin::instance( array() );
		$plugin->init();
		$plugin->activate();

		$this->assertTrue( true ); // Expectation assertion.
	}

	/**
	 * Test deactivate flushes rewrite rules.
	 *
	 * @return void
	 */
	public function test_deactivate_flushes_rewrite_rules(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		Functions\expect( 'flush_rewrite_rules' )->once();

		$plugin = Plugin::instance( array() );
		$plugin->init();
		$plugin->deactivate();

		$this->assertTrue( true ); // Expectation assertion.
	}

	/**
	 * Test default values are used when params not provided.
	 *
	 * @return void
	 */
	public function test_default_values_used_when_params_not_provided(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$plugin = Plugin::instance( array() );

		$this->assertEquals( '1.0.0', $plugin->get_version() );
		$this->assertEquals( 'woo-settings-mcp', $plugin->get_text_domain() );
		$this->assertEquals( 'WooCommerce Settings MCP', $plugin->get_plugin_name() );
	}

	/**
	 * Test __wakeup throws exception.
	 *
	 * @return void
	 */
	public function test_wakeup_throws_exception(): void {
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'register_activation_hook' )->justReturn( true );
		Functions\when( 'register_deactivation_hook' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( '' );

		$plugin = Plugin::instance( array() );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Cannot unserialize singleton' );

		$plugin->__wakeup();
	}
}

