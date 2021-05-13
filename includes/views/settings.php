<?php

namespace kmcf7_sms_extension;
?>
    <h1>Twilio Account Configuration</h1>
    <?php settings_errors(); ?>
    <form method="post" action="options.php" id="basic_settings_form">
        <?php

        settings_fields('kmcf7se_option');
        do_settings_sections('kmcf7se-sms-extension-options');

        submit_button();
        ?>
    </form>
<?php
// $settings->run();