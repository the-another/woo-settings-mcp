<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Another\Plugin\Woo_Settings_MCP\Tests
 */

declare( strict_types = 1 );

// Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Brain Monkey setup.
require_once dirname( __DIR__ ) . '/vendor/antecedent/patchwork/Patchwork.php';

use Brain\Monkey;

// Mock WordPress core classes.
if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Mock WP_Error class.
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		private string $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private string $message;

		/**
		 * Error data.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param mixed  $data    Error data.
		 */
		public function __construct( string $code = '', string $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * Get error code.
		 *
		 * @return string
		 */
		public function get_error_code(): string {
			return $this->code;
		}

		/**
		 * Get error message.
		 *
		 * @return string
		 */
		public function get_error_message(): string {
			return $this->message;
		}

		/**
		 * Get error data.
		 *
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * Mock WP_REST_Response class.
	 */
	class WP_REST_Response {
		/**
		 * Response data.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Response status.
		 *
		 * @var int
		 */
		private int $status;

		/**
		 * Constructor.
		 *
		 * @param mixed $data   Response data.
		 * @param int   $status Response status code.
		 */
		public function __construct( $data = null, int $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		/**
		 * Get response data.
		 *
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * Get response status.
		 *
		 * @return int
		 */
		public function get_status(): int {
			return $this->status;
		}
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	/**
	 * Mock WP_REST_Server class.
	 */
	class WP_REST_Server {
		public const READABLE   = 'GET';
		public const CREATABLE  = 'POST';
		public const EDITABLE   = 'POST, PUT, PATCH';
		public const DELETABLE  = 'DELETE';
		public const ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
	}
}

/**
 * Base test case class for Brain Monkey tests.
 */
abstract class WooSettingsMCP_TestCase extends \PHPUnit\Framework\TestCase {

	/**
	 * Set up Brain Monkey before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Define common WordPress constants if not defined.
		if ( ! defined( 'WPINC' ) ) {
			define( 'WPINC', 'wp-includes' );
		}

		// Mock common WordPress functions.
		$this->mock_wordpress_functions();
	}

	/**
	 * Tear down Brain Monkey after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Mock common WordPress functions.
	 *
	 * @return void
	 */
	protected function mock_wordpress_functions(): void {
		// Mock translation functions.
		Monkey\Functions\stubs(
			array(
				'__'            => static fn( string $text, string $domain = 'default' ): string => $text,
				'esc_html__'    => static fn( string $text, string $domain = 'default' ): string => $text,
				'esc_attr__'    => static fn( string $text, string $domain = 'default' ): string => $text,
				'_e'            => static function ( string $text, string $domain = 'default' ): void {
					echo $text;
				},
				'esc_html_e'    => static function ( string $text, string $domain = 'default' ): void {
					echo $text;
				},
			)
		);

		// Mock sanitization functions.
		Monkey\Functions\stubs(
			array(
				'sanitize_text_field' => static fn( string $str ): string => trim( strip_tags( $str ) ),
				'wp_kses_post'        => static fn( string $str ): string => $str,
				'absint'              => static fn( $value ): int => abs( (int) $value ),
				'esc_html'            => static fn( string $text ): string => htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ),
			)
		);

		// Mock JSON functions.
		Monkey\Functions\stubs(
			array(
				'wp_json_encode' => static fn( $data, int $options = 0 ): string|false => json_encode( $data, $options ),
			)
		);

		// Mock plugin functions.
		Monkey\Functions\stubs(
			array(
				'plugin_dir_path' => static fn( string $file ): string => dirname( $file ) . '/',
				'plugin_dir_url'  => static fn( string $file ): string => 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/',
			)
		);
	}

	/**
	 * Mock WooCommerce functions and classes.
	 *
	 * @return void
	 */
	protected function mock_woocommerce(): void {
		// Mock get_woocommerce_currencies.
		Monkey\Functions\when( 'get_woocommerce_currencies' )
			->justReturn(
				array(
					'USD' => 'United States (US) dollar',
					'EUR' => 'Euro',
					'GBP' => 'Pound sterling',
					'CAD' => 'Canadian dollar',
					'AUD' => 'Australian dollar',
				)
			);
	}

	/**
	 * Create a mock WC_Countries instance.
	 *
	 * @return \Mockery\MockInterface
	 */
	protected function create_mock_wc_countries(): \Mockery\MockInterface {
		$countries = array(
			'US' => 'United States',
			'CA' => 'Canada',
			'GB' => 'United Kingdom',
			'DE' => 'Germany',
			'FR' => 'France',
			'AU' => 'Australia',
		);

		$states = array(
			'US' => array(
				'CA' => 'California',
				'NY' => 'New York',
				'TX' => 'Texas',
			),
			'CA' => array(
				'ON' => 'Ontario',
				'BC' => 'British Columbia',
				'QC' => 'Quebec',
			),
		);

		$mock = \Mockery::mock( 'WC_Countries' );
		$mock->shouldReceive( 'get_countries' )->andReturn( $countries );
		$mock->shouldReceive( 'get_states' )->andReturnUsing(
			static function ( string $country_code ) use ( $states ): array {
				return $states[ $country_code ] ?? array();
			}
		);

		return $mock;
	}
}

