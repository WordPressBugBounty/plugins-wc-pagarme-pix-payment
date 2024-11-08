<?php


defined( 'ABSPATH' ) || exit;

$correios = class_exists( 'WC_Correios' );
$melhor_envio = class_exists( 'Melhor_Envio_Plugin' );
$install_url = wp_nonce_url( network_admin_url( 'plugin-install.php?tab=search&type=term&s=infixs%2520correios%2520automático' ), 'install-plugin_nome-do-plugin' );

?>

<div id="message" class="updated woocommerce-message" style="position: relative;">
	<a class="woocommerce-message-close notice-dismiss"
		href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'pppinf-hide-notice', 'plugin_sugestion' ), 'pppinf_hide_notices_nonce', '_pppinf_notice_nonce' ) ); ?>">
		<?php esc_html_e( 'Dispensar', 'wc-pagarme-pix-payment' ); ?>
	</a>
	<p>
		<strong>Instale o Novo Plugin dos Correios</strong>
	</p>
	<p>
		Foi lançado o Novo Plugin dos Correios <strong>Correios Automático - Rastreio, Etiqueta e
			Frete</strong>,
		<?php echo wp_kses( $melhor_envio ? "Você pode usar ele em conjunto com o <strong>Melhor Envio</strong>. " : ( $correios ? "Instale agora mesmo e substitua o plugin antigo do Cláudio Sanches (Que não atualiza mais e está depreciado). " : "instale agora mesmo e substitua o seu plugin atual dos correios. " ), [ 'strong' => [] ] ); ?>
		O plugin novo tem mais
		recursos para integrar com os <strong>Correios</strong> de forma rápida e pratica.
	</p>

	<p class="submit">
		<a href="<?php echo esc_url( $install_url ); ?>" class="button-primary">
			Instalar Agora
		</a>
		<a href="https://wordpress.org/plugins/infixs-correios-automatico/" target="_blank" class="button-secondary">
			Saiba mais
		</a>
	</p>
</div>