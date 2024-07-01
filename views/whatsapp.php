<?php

namespace kmcf7_sms_extension;

$link = "https://developers.facebook.com/docs/whatsapp/cloud-api/get-started/";
?>
<h1><?php _e( 'WhatsApp Configuration', KMCF7SE_TEXT_DOMAIN ) ?></h1>
<?php _e( "You will need to configure cloud api on Meta. You can configure it <a href='{$link}'
                                                                                          target='_blank'>here</a>", KMCF7SE_TEXT_DOMAIN ) ?>
<?php settings_errors(); ?>
<form method="post" action="options.php" id="basic_settings_form">
	<?php

	settings_fields( 'kmcf7se_whatsapp' );
	do_settings_sections( 'kmcf7se-sms-extension-options&tab=whatsapp' );

	submit_button();
	?>
</form>
