<?php
defined( 'ABSPATH' ) || exit;

ob_start();

$allowed_html = array(
	'a' => array(
		'href' => array(),
		'title' => array(),
		'target' => array(),
		'style' => array(),
		'class' => array(),
	),
	'br' => array(),
	'em' => array(),
	'strong' => array(),
	'p' => array(),
	'span' => array(),
	'div' => array(),
	'img' => array(
		'src' => array(),
		'alt' => array(),
		'title' => array(),
		'width' => array(),
		'height' => array(),
		'style' => array(),
		'class' => array(),
	),
);

?>
<img src="<?php echo $qr_code_image; ?>" />
<?php
$qr_code_html = ob_get_clean();

$email_instruction = wp_kses( $email_instruction, $allowed_html );

if ( preg_match( '/\[qr_code\]/i', $email_instruction ) ) {
	$email_instruction = preg_replace( '/\[qr_code\]/i', $qr_code_html, $email_instruction, 1 );
}

if ( preg_match( '/\[(link)\s{0,}(text=[\"\”](.+)[\"\”])?\s{0,}\]/i', $email_instruction, $matches ) ) {
	$email_instruction = preg_replace( '/\[link.+\]/i', '<a href="' . $order_url . '">' . ( isset( $matches[3] ) ? $matches[3] : 'Clique aqui' ) . '</a>', $email_instruction, 1 );
}

if ( preg_match( '/\[text_code\]/i', $email_instruction ) ) {
	$email_instruction = preg_replace( '/\[text_code\]/i', $qr_code, $email_instruction, 1 );
}

if ( preg_match( '/\[expiration_date\]/i', $email_instruction ) ) {
	$email_instruction = preg_replace( '/\[expiration_date\]/i', date( 'd/m/Y H:i:s', strtotime( $expiration_date ) ), $email_instruction );
}

echo $email_instruction;

?>