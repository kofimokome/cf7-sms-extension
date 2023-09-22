<?php

namespace kmcf7_sms_extension;
use WordPressTools;

$instance = WordPressTools::getInstance( __FILE__ );
$dir = $instance->getPluginURL().'/assets/images';
?>
<h1><?php _e( "How to Upgrade", KMCF7SE_TEXT_DOMAIN ) ?></h1>

<div>
    <h2>
        1. <?php _e( "Download the pro version", KMCF7SE_TEXT_DOMAIN ) ?>
    </h2>
    <img src="<?php echo $dir . '/upgrade1.png' ?>" alt="" style="max-width: 1000px;">
</div>
<hr>
<div>
    <h2>
        2. <?php _e( "Click on the <kbd>Plugins</kbd> menu item", KMCF7SE_TEXT_DOMAIN ) ?>
    </h2>
    <h2>
        3. <?php _e( "Click on the<kbd>Add New</kbd> button", KMCF7SE_TEXT_DOMAIN ) ?>
    </h2>
    <img src="<?php echo $dir . '/upgrade2.png' ?>" alt="" style="max-width: 1000px;">
</div>
<hr>
<div>
    <h2>
        4. <?php _e( "Click on the <kbd>Upload Plugin</kbd> button", KMCF7SE_TEXT_DOMAIN ) ?>
    </h2>
    <h2>
        5. <?php _e( "Click on the <kbd>Choose File</kbd> and select the downloaded file", KMCF7SE_TEXT_DOMAIN ) ?>
    </h2>
    <h2>
        6. <?php _e( "Click on the <kbd>Install Now</kbd> button", KMCF7SE_TEXT_DOMAIN ) ?>
    </h2>
    <img src="<?php echo $dir . '/upgrade3.png' ?>" alt="" style="max-width: 1000px;">
</div>
<hr>
<div>
    <h2>
        7. <?php _e( "Click on the <kbd>Go to Plugin Installer</kbd> link", KMCF7SE_TEXT_DOMAIN ) ?>
    </h2>
    <img src="<?php echo $dir . '/upgrade4.png' ?>" alt="" style="max-width: 1000px;">
</div>
<hr>
<div>
    <h2>
        8. <?php _e( "Deactivate the  <kbd>Message Filter for Contact Form 7</kbd> plugin", KMCF7SE_TEXT_DOMAIN ) ?>
    </h2>
    <img src="<?php echo $dir . '/upgrade5.png' ?>" alt="" style="max-width: 1000px;">
</div>
<div>
    <h2>
        9. <?php _e( "Activate the  <kbd>Message Filter for Contact Form 7 Pro</kbd> plugin", KMCF7SE_TEXT_DOMAIN ) ?>
    </h2>
    <img src="<?php echo $dir . '/upgrade6.png' ?>" alt="" style="max-width: 1000px;">
</div>
<div>
    <h2>
        10. <?php _e( "You can delete the free plugin if you wish", KMCF7SE_TEXT_DOMAIN ) ?>
    </h2>
    <img src="<?php echo $dir . '/upgrade7.png' ?>" alt="" style="max-width: 1000px;">
</div>
