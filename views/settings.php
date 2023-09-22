<?php

namespace kmcf7_sms_extension;

$provider = get_option( 'kmcf7se_provider', 'twilio' );

$link     = $provider == 'twilio' ? "https://twilio.com" : 'https://ui.idp.vonage.com/ui/auth/registration';
$provider = $provider == 'twilio' ? 'Twilio' : 'Nexmo';

?>
    <h1><?php echo $provider ?> <?php _e( 'Account Configuration', KMCF7SE_TEXT_DOMAIN ) ?></h1>
	<?php _e( "You will need to create a {$provider} Account. If you don't have one, you can create it <a href='{$link}'
                                                                                          target='_blank'>here</a>", KMCF7SE_TEXT_DOMAIN ) ?>
	<?php settings_errors(); ?>
    <form method="post" action="options.php" id="basic_settings_form">
		<?php

		settings_fields( 'kmcf7se_option' );
		do_settings_sections( 'kmcf7se-sms-extension-options' );

		submit_button();
		?>
    </form>
	<?php if ( get_option( 'kmcf7se_version', '0' ) !== CF7SmsExtension::getInstance()->getVersion() ): ?>

    <!-- The Modal -->
    <div id="myModal" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2> Thank You For Using CF7 SMS Extension</h2>
            <div style="border:solid green 2px; border-radius: 5px;padding:4px 10px;">
                It will be great if you can take just 5 minutes of your
                time to leave a review, if this plugin has been useful to you<br>
                <a href="https://wordpress.org/plugins/cf7-sms-extension/reviews/#new-post"
                   class="btn btn-success" target="_blank" rel="noopener noreferrer">Submit Review</a>

            </div>
            <h2>Here are the major changes in this version</h2>
            <ol>
                <li>Special thanks to <a href="https://profiles.wordpress.org/jdy68/">Jenny Dupuy (jdy68)</a> and <a
                            href="https://profiles.wordpress.org/wplmillet/">Laurent MILLET (wplmillet)</a> for
                    translating the plugin to French
                </li>
                <li>Added a new SMS provider, Nexmo</li>
                <li>Added the SMS history tab</li>
                <li>Other bug fixes...</li>
                <li>I will be grateful if you can buy me a cup of coffee <br> ( More Plugins Tab )</li>
            </ol>
        </div>

    </div>

    <style>
        /* The Modal (background) */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0, 0, 0); /* Fallback color */
            background-color: rgba(0, 0, 0, 0.4); /* Black w/ opacity */
        }

        /* Modal Content/Box */
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 30%; /* Could be more or less, depending on screen size */
        }

        /* The Close Button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
    <script>
        // Get the modal
        const modal = document.getElementById("myModal");


        // Get the <span> element that closes the modal
        const span = document.getElementsByClassName("close")[0];

        modal.style.display = "block";


        // When the user clicks on <span> (x), close the modal
        span.onclick = function () {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function (event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
	<?php update_option( 'kmcf7se_version', CF7SmsExtension::getInstance()->getVersion() );
endif;
// $settings->run();
?>