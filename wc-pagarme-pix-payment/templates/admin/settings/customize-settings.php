<?php
/**
 * @var WCPagarmePixPayment\Gateway\PagarmePixGateway $this
 */

defined( 'ABSPATH' ) || exit;
?>
<style>
	.mgn-editor {
		height: 200px;
	}

	.mgn-info {
		font-weight: normal;
		color: #646970;
	}

	.mgn-flex {
		display: flex;
		height: 35px;
		align-items: center;
	}

	.colpick.colpick_full {
		z-index: 999;
	}
</style>
<h2><?php echo esc_html( $current_tab_name ); ?></h2>
<table class="form-table">
	<tbody>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="custom_email">E-mail de pagamento </label>
				<p class="mgn-info">
					<?php echo __( 'Customize o e-mail de pagamento que vai para o cliente com o QR Code', 'wc-pagarme-pix-payment' ); ?>
				</p>
			</th>
			<td class="forminp">
				<fieldset>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_pagarme_pix_payment_email' ) ); ?>"
						class="button button-primary">
						<?php esc_html_e( 'Ir para configurações de email do WooCommerce', 'wc-pagarme-pix-payment' ); ?>
					</a>

				</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_html( $this->get_field_name( 'checkout_message' ) ); ?>">Mensagem nas opções
					de
					pagamento </label>
				<p class="mgn-info">
					<?php echo __( 'Quando é selecionado o PIX como forma de pagamento antes de finalizar a compra.', 'wc-pagarme-pix-payment' ); ?>
				</p>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span>Mensagem nas opções de pagamento</span></legend>
					<?php
					wp_editor(
						$this->checkout_message,
						"checkout_message",
						[ 
							'editor_class' => 'mgn-editor',
							'textarea_name' => esc_html( $this->get_field_name( 'checkout_message' ) )
						]
					);
					?>
				</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_html( $this->get_field_name( 'order_recived_message' ) ); ?>">
					<?php echo esc_html( __( 'Mensagem na tela do QR Code', 'wc-pagarme-pix-payment' ) ); ?>
				</label>
				<p class="mgn-info">
					<?php echo __( 'Essa mensagem aparece na tela do QR Code, depois que o cliente finaliza o pedido.<br><br><code>[qr_code]</code> para definir o local do código QR.<br><code>[copy_button]</code> para definir o local do botão para copiar o código.<br><code>[text_code]</code> para definir o local do código pix em texto corrido<br><code>[expiration_date]</code> para inserir a data que o código expira', 'wc-pagarme-pix-payment' ); ?>
				</p>
			</th>
			<td class="forminp">
				<fieldset>
					<?php
					wp_editor(
						$this->order_recived_message,
						"order_recived_message",
						[ 
							'editor_class' => 'mgn-editor',
							'textarea_name' => esc_html( $this->get_field_name( 'order_recived_message' ) )
						]
					);
					?>
				</fieldset>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="thank_you_message">
					<?php echo __( 'Mensagem de agradecimento pelo pagamento', 'wc-pagarme-pix-payment' ); ?>
				</label>
				<p class="mgn-info">
					<?php echo __( 'Essa mensagem aparece quando o pagamento PIX é confirmado.', 'wc-pagarme-pix-payment' ); ?>
				</p>
			</th>
			<td class="forminp">
				<fieldset>
					<?php
					wp_editor(
						$this->thank_you_message,
						esc_html( $this->get_field_name( 'thank_you_message' ) ),
						[ 
							'editor_class' => 'mgn-editor',
							'textarea_name' => esc_html( $this->get_field_name( 'thank_you_message' ) )
						]
					);
					?>
				</fieldset>
			</td>
		</tr>
	</tbody>
</table>