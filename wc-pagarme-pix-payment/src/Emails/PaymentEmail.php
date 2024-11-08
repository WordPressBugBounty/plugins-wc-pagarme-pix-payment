<?php

namespace WCPagarmePixPayment\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Payment email class.
 */
class PaymentEmail extends \WC_Email {
	/**
	 * Initialize tracking template.
	 */
	public function __construct() {
		$this->id = 'wc_pagarme_pix_payment_email';
		$this->title = __( 'PIX Automático - Email para Pagamento PIX', 'wc-pagarme-pix-payment' );
		$this->customer_email = true;
		$this->description = __( 'Esse email é enviado quando o cliente finaliza a compra para que ele faça o pagamento do PIX com QR Code ou Copia e Cola.', 'wc-pagarme-pix-payment' );
		$this->template_html = 'emails/email-payment-instructions.php';
		$this->template_plain = 'emails/email-payment-instructions-plain.php';
		$this->placeholders = [ 
			'{qr_code_image}' => '',
			'{text_code}' => '',
			'{link_text}' => '',
			'{expiration_date}' => '',
		];

		// Call parent constructor.
		parent::__construct();

		$this->template_base = \WC_PAGARME_PIX_PAYMENT_PLUGIN_PATH . 'templates/';
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Faça o pagamento via PIX da sua compra', 'wc-pagarme-pix-payment' );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Instruções de pagamento', 'wc-pagarme-pix-payment' );
	}

	/**
	 * Default payment message content.
	 *
	 * @return string
	 */
	public function get_default_payment_message() {
		return __( 'Olá,', 'wc-pagarme-pix-payment' )
			. PHP_EOL . ' ' . PHP_EOL
			. __( 'Faça o pagamento do seu pedido para concluir a compra, copie o código copia e cola a baixo ou então use o QR Code:', 'wc-pagarme-pix-payment' )
			. PHP_EOL . ' ' . PHP_EOL
			. __( 'Código PIX:', 'wc-pagarme-pix-payment' )
			. PHP_EOL . ' {text_code}'
			. PHP_EOL . ' ' . PHP_EOL
			. __( 'Data de expiração:', 'wc-pagarme-pix-payment' )
			. PHP_EOL . ' {expiration_date}'
			. PHP_EOL . ' ' . PHP_EOL
			. __( 'QR Code:', 'wc-pagarme-pix-payment' )
			. PHP_EOL . ' {qr_code_image}'
			. PHP_EOL . ' ' . PHP_EOL
			. __( 'Atenciosamente,', 'wc-pagarme-pix-payment' );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		/* translators: %s: list of placeholders */
		$placeholder_text = sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );

		$this->form_fields = [ 
			'enabled' => [ 
				'title' => __( 'Enable/Disable', 'woocommerce' ),
				'type' => 'checkbox',
				'label' => __( 'Enable this email notification', 'woocommerce' ),
				'default' => 'yes',
			],
			'subject' => [ 
				'title' => __( 'Subject', 'woocommerce' ),
				'type' => 'text',
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_subject(),
				'default' => $this->get_default_subject(),
				'desc_tip' => true,
			],
			'heading' => [ 
				'title' => __( 'Email heading', 'woocommerce' ),
				'type' => 'text',
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_heading(),
				'default' => $this->get_default_heading(),
				'desc_tip' => true,
			],
			'payment_message' => [ 
				'title' => __( 'Email content', 'woocommerce' ),
				'type' => 'textarea',
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_payment_message(),
				'default' => $this->get_default_payment_message(),
				'desc_tip' => true,
			],
			'email_type' => [ 
				'title' => __( 'Email type', 'woocommerce' ),
				'type' => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default' => 'html',
				'class' => 'email_type wc-enhanced-select',
				'options' => $this->get_email_type_options(),
				'desc_tip' => true,
			],
		];
	}

	/**
	 * Get email payment message.
	 *
	 * @return string
	 */
	public function get_payment_message() {
		$message = $this->get_option( 'payment_message', $this->get_default_payment_message() );

		return apply_filters( 'wc_pagarme_pix_payment_email_payment_message', $this->format_string( $message ), $this->object );
	}

	/**
	 * Get QR Code Image
	 *
	 * @return string
	 */
	public function get_qr_code_image( $url ) {
		$html = sprintf( '<img src="%s"/>', esc_attr( $url ) );

		return apply_filters( 'wc_pagarme_pix_payment_email_qr_code_image', $html, $url, $this->object );
	}

	/**
	 * Trigger email.
	 *
	 * @param int $order_id Order ID.
	 */
	public function trigger( $order_id ) {

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		if ( is_object( $order ) ) {
			$this->object = $order;

			$qr_code_image_url = $order->get_meta( '_wc_pagarme_pix_payment_qr_code_image' );
			$text_code = $order->get_meta( '_wc_pagarme_pix_payment_qr_code' );
			$expiration_date = $order->get_meta( '_wc_pagarme_pix_payment_expiration_date' );

			$this->recipient = $this->object->get_billing_email();

			$this->placeholders['{text_code}'] = $text_code;
			$this->placeholders['{expiration_date}'] = wp_date( 'd/m/Y H:i:s', strtotime( $expiration_date ) );
			$this->placeholders['{qr_code_image}'] = $this->get_qr_code_image( $qr_code_image_url );
		}

		if ( ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get content HTML.
	 *
	 * @return string
	 */
	public function get_content_html() {
		ob_start();

		wc_get_template(
			$this->template_html,
			[ 
				'order' => $this->object,
				'email_heading' => $this->get_heading(),
				'payment_message' => $this->get_payment_message(),
				'sent_to_admin' => false,
				'plain_text' => false,
				'email' => $this,
			],
			'',
			$this->template_base
		);

		return ob_get_clean();
	}

	/**
	 * Get content plain text.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();

		// Format list.
		$message = $this->get_payment_message();
		$message = str_replace( '<ul>', "\n", $message );
		$message = str_replace( '<li>', "\n - ", $message );
		$message = str_replace( [ '</ul>', '</li>' ], '', $message );

		wc_get_template(
			$this->template_plain,
			[ 
				'order' => $this->object,
				'email_heading' => $this->get_heading(),
				'payment_message' => $this->get_payment_message(),
				'sent_to_admin' => false,
				'plain_text' => true,
				'email' => $this,
			],
			'',
			$this->template_base
		);

		return ob_get_clean();
	}
}
