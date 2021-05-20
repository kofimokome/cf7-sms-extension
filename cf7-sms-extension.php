<?php
/**
 * @link              www.kofimokome.stream
 * @since             1.0.0
 * @package           kmcf7_sms_extension
 *
 * @wordpress-plugin
 * Plugin Name: SMS Extension for Contact Form 7
 * Plugin URI: https://github.com/kofimokome/cf7-sms-extension
 * Description: Receive text message notifications when a form is submitted.
 * Version: 1.1.0
 * Author: Kofi Mokome
 * Author URI: www.kofimokome.stream
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: cf7-sms-extension
 * Domain Path: /languages
 */

namespace kmcf7_sms_extension;

defined('ABSPATH') or die('Giving To Cesar What Belongs To Caesar');

$error = false;

function kmcf7se_error_notice($message = '')
{
    if (trim($message) != ''):
        ?>
        <div class="error notice is-dismissible">
            <p><b>CF7 SMS Extension: </b><?php echo $message ?></p>
        </div>
    <?php
    endif;
}

add_action('admin_notices', 'kmcf7_sms_extension\\kmcf7se_error_notice', 10, 1);

// loads classes / files
function kmcf7se_loader()
{
    global $error;
    $classes = array(
        'CF7SmsExtension.php', //
        'MenuPage.php', //
        'SubMenuPage.php', //
        'Setting.php', //
        // 'admin_menu.php', //

    );

    foreach ($classes as $file) {
        if (!$filepath = file_exists(plugin_dir_path(__FILE__) . "includes/" . $file)) {
            kmcf7se_error_notice(sprintf(__('Error locating <b>%s</b> for inclusion', 'kmcf7se'), $file));
            $error = true;
        } else {
            include_once plugin_dir_path(__FILE__) . "includes/" . $file;
        }
    }
}

function kmcf7se_start()
{
    $message_extension = new CF7SmsExtension();
    $message_extension->run();
}


kmcf7se_loader();
if (!$error) {
    kmcf7se_start();
}


// remove options upon deactivation

register_deactivation_hook(__FILE__, 'kmcf7se_deactivation');

function kmcf7se_deactivation()
{
    // set options to remove here
}

// todo: for future use
load_plugin_textdomain('cf7-sms-extension', false, basename(dirname(__FILE__)) . '/languages');