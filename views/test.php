<?php

namespace kmcf7_sms_extension;
?>
<h1><?php _e( "Send a test Message", KMCF7SE_TEXT_DOMAIN ) ?></h1>
<hr>

<form action="" method="post">
    <table>
        <tr>
            <td>
                <label for="km_to">
					<?php _e( "Phone number", KMCF7SE_TEXT_DOMAIN ) ?>:
                </label>
            </td>
            <td>
                <input type="tel" name="km_to"
                       placeholder="+237670224092" required
                       autocomplete="off">
            </td>
        </tr>
        <tr style="display: none" class="km-whatsapp-fields">
            <td>
                <label for="km_whatsapp_template">
					<?php _e( "WhatsApp Template", KMCF7SE_TEXT_DOMAIN ) ?>:
                </label>
            </td>
            <td>
                <input type="tel" name="km_whatsapp_template" value="hello_world"
                       placeholder="hello_world" required
                       autocomplete="off">
            </td>
        </tr>
        <tr style="display: none" class="km-whatsapp-fields">
            <td>
                <label for="km_whatsapp_template_parameters">
					<?php _e( "WhatsApp Template Parameters", KMCF7SE_TEXT_DOMAIN ) ?>:
                </label>
            </td>
            <td>
                <input type="tel" name="km_whatsapp_template_parameters"
                       placeholder="param1, param2"
                       autocomplete="off"><br>
                <small><?php _e( "Leave blank if your template does not have parameters. The parameters should be separated by a comma" ) ?></small>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <label>
                    <input type="checkbox" name="km_send_sms" checked> SMS
                    <input type="checkbox" name="km_send_whatsapp" id="km-whatsapp-checkbox"
                           style="margin-left: 30px"> WhatsApp
                </label>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <button style="background:green; color:white; padding: 7px 10px; border:none;border-radius:5px;"><?php _e( "Send Message", KMCF7SE_TEXT_DOMAIN ) ?></button>
            </td>
        </tr>
    </table>
</form>

<?php
if ( isset( $_POST['km_to'] ) ) :
	$instance = CF7SmsExtension::getInstance();
	if ( isset( $_POST['km_send_sms'] ) ) {
		if ( $instance->sendSms( $_POST['km_to'], 'This is a test message from the Contact Form 7 SMS Extension Plugin', false, false ) ):?>
            <div style="border:solid green 2px; width: 340px; padding:7px 10px;margin-top:10px;text-align:center;">
				<?php _e( "Your SMS has been sent", KMCF7SE_TEXT_DOMAIN ) ?>
            </div>
		<?php else: ?>
            <div style="border:solid red 2px; width: 400px; padding:7px 10px;margin-top:10px;">
				<?php _e( "Your SMS was not sent", KMCF7SE_TEXT_DOMAIN ) ?>
            </div>
		<?php endif;
	}
	if ( isset( $_POST['km_send_whatsapp'] ) ) {
		$template = sanitize_text_field( $_POST['km_whatsapp_template'] );
		$params   = sanitize_text_field( $_POST['km_whatsapp_template_parameters'] );
		$params   = explode( ',', $params );
		$params   = array_filter( $params, function ( $param ) {
			return trim( $param ) !== '';
		} );
		if ( $instance->sendWhatsAppMessage( $_POST['km_to'], $template, $params ) ):?>
            <div style="border:solid green 2px; width: 340px; padding:7px 10px;margin-top:10px;text-align:center;">
				<?php _e( "Your WhatsApp message has been sent", KMCF7SE_TEXT_DOMAIN ) ?>
            </div>
		<?php else: ?>
            <div style="border:solid red 2px; width: 400px; padding:7px 10px;margin-top:10px;">
				<?php _e( "Your WhatsApp message was not sent", KMCF7SE_TEXT_DOMAIN ) ?>
            </div>
		<?php endif;
	}
	if ( get_option( 'km_error_message', '' ) != '' ) : ?>
        <div style="border:solid red 2px; width: 400px; padding:7px 10px;margin-top:10px;">
            <h3><?php _e( "An error occurred !!!", KMCF7SE_TEXT_DOMAIN ) ?></h3>
			<?php $error = json_decode( get_option( 'km_error_message' ) ); ?>
            <table>
                <tr>
                    <td><b><?php _e( "Error Code", KMCF7SE_TEXT_DOMAIN ) ?>:</b></td>
                    <td><?php echo $error->code ?? '' ?></td>
                </tr>
                <tr>
                    <td><b><?php _e( "Message", KMCF7SE_TEXT_DOMAIN ) ?>:</b></td>
                    <td><?php echo $error->message ?? get_option( 'km_error_message' ) ?> </td>
                </tr>
                <tr>
                    <td><b><?php _e( "More Info", KMCF7SE_TEXT_DOMAIN ) ?>:</b></td>
                    <td><?php echo $error->more_info ?? '' ?></td>
                </tr>
            </table>
        </div>
		<?php
		delete_option( 'km_error' );
		delete_option( 'km_error_message' );

	else:
		?>
        <div style="border:solid green 2px; width: 340px; padding:7px 10px;margin-top:10px;text-align:center;">
			<?php _e( "Your message has been sent", KMCF7SE_TEXT_DOMAIN ) ?>
        </div>
	<?php endif; endif; ?>

<script>
    jQuery(function ($) {
        $(document).ready(function () {
            $("#km-whatsapp-checkbox").change(function () {
                if (this.checked) {
                    $(".km-whatsapp-fields").show();
                } else {
                    $(".km-whatsapp-fields").hide();
                }
            })
        })
    })

</script>
