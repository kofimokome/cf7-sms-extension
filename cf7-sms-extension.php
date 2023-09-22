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
 * Version: 1.2.2
 * Author: Kofi Mokome
 * Author URI: www.kofimokome.stream
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: cf7-sms-extension
 * Domain Path: /languages
 *
 */
namespace kmcf7_sms_extension;

use  KMEnv ;
use  WordPressTools ;
defined( 'ABSPATH' ) or die( 'Giving To Cesar What Belongs To Caesar' );
if ( !defined( 'KMCF7SE_TEXT_DOMAIN' ) ) {
    define( 'KMCF7SE_TEXT_DOMAIN', 'cf7-sms-extension' );
}
$error = false;

if ( function_exists( 'kmcf7_sms_extension\\kmcf7se_fs' ) ) {
    kmcf7se_fs()->set_basename( false, __FILE__ );
} else {
    
    if ( !function_exists( 'kmcf7_sms_extension\\kmcf7se_fs' ) ) {
        // Create a helper function for easy SDK access.
        function kmcf7se_fs()
        {
            global  $kmcf7se_fs ;
            
            if ( !isset( $kmcf7se_fs ) ) {
                // Activate multisite network integration.
                if ( !defined( 'WP_FS__PRODUCT_13504_MULTISITE' ) ) {
                    define( 'WP_FS__PRODUCT_13504_MULTISITE', true );
                }
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $kmcf7se_fs = fs_dynamic_init( array(
                    'id'             => '13504',
                    'slug'           => 'cf7-sms-extension',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_2bb50bfd24d147612e9e0c4fa3de2',
                    'is_premium'     => false,
                    'premium_suffix' => 'Basic',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'trial'          => array(
                    'days'               => 7,
                    'is_require_payment' => false,
                ),
                    'menu'           => array(
                    'slug'    => 'kmcf7se-sms-extension-options',
                    'contact' => false,
                ),
                    'is_live'        => true,
                ) );
            }
            
            return $kmcf7se_fs;
        }
        
        // Init Freemius.
        kmcf7se_fs();
        // Signal that SDK was initiated.
        do_action( 'kmcf7se_fs_loaded' );
    }
    
    kmcf7se_fs()->add_action( 'after_uninstall', 'kmcf7_sms_extension\\KMCF7SEUninstall' );
    function KMCF7SEErrorNotice( $message = '' )
    {
        
        if ( trim( $message ) != '' ) {
            ?>
            <div class="error notice is-dismissible">
                <p><b>CF7 SMS Extension: </b><?php 
            echo  $message ;
            ?></p>
            </div>
		<?php 
        }
    
    }
    
    add_action(
        'admin_notices',
        'kmcf7_sms_extension\\KMCF7SEErrorNotice',
        10,
        1
    );
    // loads classes / files
    function KMCF7SELoader()
    {
        global  $error ;
        // scan directories for requires.php files
        foreach ( scandir( __DIR__ ) as $dir ) {
            if ( strpos( $dir, '.' ) === false && is_dir( __DIR__ . '/' . $dir ) && is_file( __DIR__ . '/' . $dir . '/requires.php' ) ) {
                require_once __DIR__ . '/' . $dir . '/requires.php';
            }
        }
        $requires = apply_filters( 'kmcf7se_requires_filter', [] );
        foreach ( $requires as $file ) {
            
            if ( !($filepath = file_exists( $file )) ) {
                KMCF7SEErrorNotice( sprintf( __( 'Error locating <b>%s</b> for inclusion', KMCF7SE_TEXT_DOMAIN ), $file ) );
                $error = true;
            } else {
                require_once $file;
            }
        
        }
        // scan directories for includes.php files
        foreach ( scandir( __DIR__ ) as $dir ) {
            if ( strpos( $dir, '.' ) === false && is_dir( __DIR__ . '/' . $dir ) && is_file( __DIR__ . '/' . $dir . '/includes.php' ) ) {
                require_once __DIR__ . '/' . $dir . '/includes.php';
            }
        }
        $includes = apply_filters( 'kmcf7se_includes_filter', [] );
        foreach ( $includes as $file ) {
            
            if ( !($filepath = file_exists( $file )) ) {
                KMCF7SEErrorNotice( sprintf( __( 'Error locating <b>%s</b> for inclusion', KMCF7SE_TEXT_DOMAIN ), $file ) );
                $error = true;
            } else {
                include_once $file;
            }
        
        }
        return $error;
    }
    
    function KMCF7SEStart()
    {
        $wordpress_tools = new WordPressTools( __FILE__ );
        $message_extension = new CF7SmsExtension();
        $message_extension->run();
    }
    
    if ( !KMCF7SELoader() ) {
        KMCF7SEStart();
    }
    // remove options upon deactivation
    register_deactivation_hook( __FILE__, 'kmcf7_sms_extension\\KMCF7SEDeactivation' );
    function KMCF7SEDeactivation()
    {
        // set options to remove here
    }
    
    function KMCF7SEUninstall()
    {
        global  $wpdb ;
        //query the wp options table and delete all options that start with kmcfmf_
        $query = $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'kmcf7se_%'" );
        $wpdb->query( $query );
        if ( get_option( 'kmcf7se_message_delete_data', 'off' ) == 'on' ) {
        }
        // set options to remove here
    }
    
    // todo: for future use
    load_plugin_textdomain( 'cf7-sms-extension', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
