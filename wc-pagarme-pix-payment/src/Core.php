<?php

namespace WCPagarmePixPayment;

use WCPagarmePixPayment\Emails\PaymentEmail;
use WCPagarmePixPayment\WP\Helper as WP;
use WCPagarmePixPayment\Gateway\BaseGateway;
use WCPagarmePixPayment\Gateway\PagarmePixGatewayBlocksSupport;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

//Prevent direct file call
defined( 'ABSPATH' ) || exit;

/**
 * WC Pagarme Pix Payment
 *
 * @package WCPagarmePixPayment
 * @since   1.0.0
 * @version 1.0.0
 */
class Core {
	/**
	 * The unique identifier of this plugin.
	 *
	 * @since 1.0.0
	 * @var string $pluginName
	 */
	public $pluginName;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.1.0
	 * @var string $pluginVersion
	 */
	public $pluginVersion;

	/**
	 * Path to plugin directory.
	 * 
	 * @since 1.1.0
	 * @var string $pluginPath Without trailing slash.
	 */
	public $pluginPath;

	/**
	 * URL to plugin directory.
	 * 
	 * @since 1.1.0
	 * @var string $pluginUrl Without trailing slash.
	 */
	public $pluginUrl;

	/**
	 * URL to plugin assets directory.
	 * 
	 * @since 1.1.0
	 * @var string $assetsUrl Without trailing slash.
	 */
	public $assetsUrl;

	/**
	 * Plugin settings.
	 * 
	 * @since 1.1.0
	 * @var array
	 */
	protected $settings;

	/**
	 * Startup plugin.
	 * 
	 * @since 1.1.0
	 * @return void
	 */

	/**
	 * Initialize the plugin public actions.
	 */
	public function __construct() {
		$this->pluginUrl = \WC_PAGARME_PIX_PAYMENT_PLUGIN_URL;
		$this->pluginPath = \WC_PAGARME_PIX_PAYMENT_PLUGIN_PATH;
		$this->assetsUrl = $this->pluginUrl . '/assets';

		$this->pluginName = \WC_PAGARME_PIX_PAYMENT_PLUGIN_NAME;
		$this->pluginVersion = \WC_PAGARME_PIX_PAYMENT_PLUGIN_VERSION;

		WP::add_action( 'plugins_loaded', $this, 'after_load' );
	}

	/**
	 * Plugin loaded method.
	 * 
	 * @since 1.1.0
	 * @return void
	 */
	public function after_load() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			// Cannot start plugin
			return;
		}

		// Startup gateway
		BaseGateway::init();

		WP::add_action( 'wp_enqueue_scripts', $this, 'enqueue_scripts' );
		WP::add_action( 'admin_enqueue_scripts', $this, 'admin_enqueue_scripts' );
		WP::add_action( 'wp_head', $this, 'head', 2 );
		WP::add_action( 'woocommerce_view_order', $this, 'woocommerce_view_order_page', 2 );
		WP::add_action( 'before_woocommerce_init', $this, 'woocommerce_declare_compatibility' );
		WP::add_action( 'woocommerce_blocks_loaded', $this, 'woocommerce_gateway_woocommerce_block_support' );
		WP::add_action( 'admin_init', $this, 'hide_notices' );
		WP::add_action( 'admin_notices', $this, 'sugestion_admin_notice' );

		WP::add_filter( 'woocommerce_email_classes', $this, 'include_emails' );

		add_action( 'woocommerce_checkout_process', function () {
			$opa = "";
		} );
	}

	public function head() {
		if ( $this->is_pix_payment_page() ) {
			$interval = 5;
			$reload = 'false';
			$plugin_options = maybe_unserialize( get_option( 'woocommerce_wc_pagarme_pix_payment_geteway_settings', false ) );

			if ( $plugin_options && isset( $plugin_options['check_payment_interval'] ) )
				$interval = $plugin_options['check_payment_interval'];

			if ( $plugin_options && isset( $plugin_options['page_refresh'] ) )
				$reload = $plugin_options['page_refresh'] == 'yes' ? 'true' : 'false';

			printf( "<script>window.wc_pagarme_pix_payment_geteway = {'checkInterval': %d, 'reload': %s};</script>", $interval * 1000, $reload );
		}
	}

	public function is_pix_payment_page() {
		global $wp;

		//Page is view order or order received?
		if ( ! is_wc_endpoint_url( 'order-received' ) && ! isset( $wp->query_vars['view-order'] ) )
			return false;

		$query_var = isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : $wp->query_vars['view-order'];

		$order_id = absint( $query_var );

		if ( empty( $order_id ) || $order_id == 0 )
			return false;

		$order = wc_get_order( $order_id );

		if ( ! $order )
			return false;

		if (
			( ( is_wc_endpoint_url( 'order-received' ) && is_checkout() ) ||
				is_wc_endpoint_url( 'view-order' ) ) &&
			'wc_pagarme_pix_payment_geteway' == $order->get_payment_method()
		)
			return true;

		return false;
	}

	public function enqueue_scripts() {
		if ( $this->is_pix_payment_page() ) {
			wp_enqueue_script( \WC_PAGARME_PIX_PAYMENT_PLUGIN_NAME . '-checkout', \WC_PAGARME_PIX_PAYMENT_PLUGIN_URL . 'assets/js/public/checkout.js', array( 'jquery' ), \WC_PAGARME_PIX_PAYMENT_PLUGIN_VERSION );
		}

		if ( is_checkout() ) {
			wp_enqueue_script( \WC_PAGARME_PIX_PAYMENT_PLUGIN_NAME . '-before-checkout', \WC_PAGARME_PIX_PAYMENT_PLUGIN_URL . 'assets/js/public/before-checkout.js', array( 'jquery' ), \WC_PAGARME_PIX_PAYMENT_PLUGIN_VERSION );
		}
	}


	function woocommerce_view_order_page( $order_id ) {
		$order = wc_get_order( $order_id );
		$qr_code = $order->get_meta( '_wc_pagarme_pix_payment_qr_code' );
		$qr_code_image = $order->get_meta( '_wc_pagarme_pix_payment_qr_code_image' );
		$status = $order->get_status();
		$payment_method = $order->get_payment_method();

		if ( $payment_method != 'wc_pagarme_pix_payment_geteway' || $status != 'pending' )
			return;

		wc_get_template(
			'html-woocommerce-thank-you-page.php',
			[ 
				'qr_code' => $qr_code,
				'thank_you_message' => '',
				'order_recived_message' => '',
				'order' => $order,
				'qr_code_image' => $qr_code_image,
				'order_key' => $order->get_order_key(),
				'expiration_date' => '2'
			],
			WC()->template_path() . \WC_PAGARME_PIX_PAYMENT_DIR_NAME . '/',
			WC_PAGARME_PIX_PAYMENT_PLUGIN_PATH . 'templates/'
		);
	}

	public function admin_enqueue_scripts( $hook ) {
		if ( $hook != 'woocommerce_page_wc-settings' || ! ( isset( $_GET['section'] ) && $_GET['section'] == 'wc_pagarme_pix_payment_geteway' ) )
			return;

		wp_enqueue_script(
			'colpick',
			\WC_PAGARME_PIX_PAYMENT_PLUGIN_URL . 'assets/js/admin/colpick/colpick.js',
			array( 'jquery' ),
			\WC_PAGARME_PIX_PAYMENT_PLUGIN_VERSION
		);

		wp_enqueue_script(
			\WC_PAGARME_PIX_PAYMENT_PLUGIN_NAME . '-settings',
			\WC_PAGARME_PIX_PAYMENT_PLUGIN_URL . 'assets/js/admin/settings.js',
			array( 'jquery' ),
			\WC_PAGARME_PIX_PAYMENT_PLUGIN_VERSION
		);

		wp_enqueue_style(
			'colpick',
			\WC_PAGARME_PIX_PAYMENT_PLUGIN_URL . 'assets/js/admin/colpick/colpick.css'
		);
	}

	public function woocommerce_declare_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', \WC_PAGARME_PIX_PAYMENT_FILE_NAME, true );
		}
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 */
	public static function woocommerce_gateway_woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once \WC_PAGARME_PIX_PAYMENT_PLUGIN_PATH . 'src/Gateway/PagarmePixGatewayBlocksSupport.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function (PaymentMethodRegistry $payment_method_registry) {
					$payment_method_registry->register( new PagarmePixGatewayBlocksSupport() );
				}
			);
		}
	}

	public function sugestion_admin_notice() {
		if ( ( ! class_exists( 'WC_Correios' ) && ! class_exists( 'Virtuaria_Correios' ) && ! class_exists( 'Melhor_Envio_Plugin' ) ) || class_exists( 'Infixs\CorreiosAutomatico\Container' ) ) {
			return;
		}

		$dismissTime = get_user_meta( get_current_user_id(), '_wc_pagarme_pix_payment_dismissed_notice_plugin_sugestion', true );

		if ( $dismissTime ) {
			$now = time();
			$diff = $now - $dismissTime;
			if ( $diff < MONTH_IN_SECONDS ) {
				return;
			}
		}

		include \WC_PAGARME_PIX_PAYMENT_PLUGIN_PATH . 'src/Presentation/admin/notices/plugin-sugestion.php';
	}


	/**
	 * Include custom emails.
	 *
	 * @param array $emails
	 * @return array
	 */
	public function include_emails( $emails ) {
		if ( ! isset( $emails['WC_Pagarme_Pix_Payment_Email'] ) ) {
			$emails['WC_Pagarme_Pix_Payment_Email'] = new PaymentEmail();
		}
		return $emails;
	}

	/**
	 * Hide a notice if the GET variable is set.
	 */
	public static function hide_notices() {
		if ( isset( $_GET['pppinf-hide-notice'] ) && isset( $_GET['_pppinf_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_pppinf_notice_nonce'] ) ), 'pppinf_hide_notices_nonce' ) ) {
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'wc-pagarme-pix-payment' ) );
			}

			$notice_name = sanitize_text_field( wp_unslash( $_GET['pppinf-hide-notice'] ) );

			update_user_meta( get_current_user_id(), '_wc_pagarme_pix_payment_dismissed_notice_' . $notice_name, time() );
		}
	}
}
