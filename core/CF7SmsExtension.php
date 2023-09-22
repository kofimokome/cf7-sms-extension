<?php

/**
 * Created by PhpStorm.
 * User: kofi
 * Date: 18/8/20
 * Time: 08:10 PM
 */
namespace kmcf7_sms_extension;

use  KMMenuPage ;
use  KMSetting ;
use  KMSubMenuPage ;
use  KMValidator ;
use  WordPressTools ;
if ( !class_exists( 'CF7SmsExtension' ) ) {
    class CF7SmsExtension
    {
        private static  $instance ;
        private  $version ;
        private  $default_properties ;
        private  $cf7_version = '5.8' ;
        private  $word_press_tools ;
        public function __construct()
        {
            // our constructor
            $this->version = '1.2.2';
            $this->word_press_tools = WordPressTools::getInstance( __FILE__ );
            self::$instance = $this;
        }
        
        public static function getInstance() : CF7SmsExtension
        {
            return self::$instance;
        }
        
        /**
         * Returns the version of this plugin
         * @since 1.1.0
         */
        public function getVersion()
        {
            return $this->version;
        }
        
        /**
         *
         * @since 1.0.0
         */
        public function run()
        {
            $this->addActions();
            $this->addFilters();
            $this->addMainMenu();
            $this->addSettings();
            $this->setDefaultProperties();
        }
        
        /**
         *
         * @since 1.0.0
         */
        private function addActions()
        {
            // add actions here
            add_action( 'wpcf7_save_contact_form', [ $this, 'saveContactForm' ] );
            add_action(
                'wpcf7_before_send_mail',
                [ $this, 'beforeSendMail' ],
                15,
                3
            );
            //		add_action( 'admin_notices', [ $this, 'cf7_version_notice' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'addScripts' ] );
        }
        
        /**
         *
         * @since 1.0.0
         */
        public function addFilters()
        {
            add_filter(
                'wpcf7_editor_panels',
                [ $this, 'addSMSPanel' ],
                10,
                1
            );
            add_filter(
                'wpcf7_ajax_json_echo',
                [ $this, 'ajaxJsonEcho' ],
                10,
                2
            );
        }
        
        /**
         *
         * @since 1.0.0
         */
        public function addMainMenu()
        {
            // Create the menu page
            $menu_page = new KMMenuPage( [
                'page_title' => 'CF7 SMS Extension',
                'menu_title' => 'CF7 SMS Extension',
                'capability' => 'read',
                'menu_slug'  => 'kmcf7se-sms-extension-options',
                'icon_url'   => 'dashicons-tickets-alt',
                'position'   => 100,
                'function'   => [ $this, 'dashboardView' ],
            ] );
            $settings_page = new KMSubMenuPage( [
                'parent_slug' => $menu_page->get_menu_slug(),
                'page_title'  => 'Options',
                'menu_title'  => 'Options',
                'capability'  => 'manage_options',
                'menu_slug'   => 'kmcf7se-sms-extension-options',
                'position'    => 0,
                'function'    => [ $this, 'settings_view' ],
                'use_tabs'    => true,
            ] );
            $settings_page->add_tab(
                'settings',
                __( 'Basic Settings', KMCF7SE_TEXT_DOMAIN ),
                array( $this, 'statusTabView' ),
                array(
                'tab' => 'settings',
            )
            );
            $settings_page->add_tab(
                'test',
                __( 'SMS Test', KMCF7SE_TEXT_DOMAIN ),
                array( $this, 'statusTabView' ),
                array(
                'tab' => 'test',
            )
            );
            $settings_page->add_tab(
                'history',
                __( 'SMS History', KMCF7SE_TEXT_DOMAIN ),
                array( $this, 'statusTabView' ),
                array(
                'tab' => 'history',
            )
            );
            if ( kmcf7se_fs()->can_use_premium_code() && !kmcf7se_fs()->is_premium() ) {
                $settings_page->add_tab(
                    'upgrade',
                    __( 'Upgrade Guide', KMCF7MS_TEXT_DOMAIN ),
                    array( $this, 'statusTabView' ),
                    array(
                    'tab' => 'upgrade',
                )
                );
            }
            $settings_page->add_tab(
                'plugins',
                __( 'More Plugins', KMCF7SE_TEXT_DOMAIN ),
                array( $this, 'statusTabView' ),
                array(
                'tab' => 'plugins',
            )
            );
            $menu_page->add_sub_menu_page( $settings_page );
            $menu_page->run();
        }
        
        /**
         * Adds Settings
         * @since 1.1.0
         */
        private function addSettings()
        {
            $provider = get_option( 'kmcf7se_provider', 'twilio' );
            $is_provider_twilio = $provider == 'twilio';
            $settings = new KMSetting( 'kmcf7se-sms-extension-options' );
            $settings->add_section( 'kmcf7se_option' );
            $settings->add_field( array(
                'type'           => 'select',
                'id'             => 'kmcf7se_provider',
                'label'          => 'SMS Provider',
                'tip'            => 'Save changes to change the names of the fields below.',
                'placeholder'    => '',
                'options'        => [
                'twilio' => 'Twilio',
                'nexmo'  => 'Nexmo',
            ],
                'default_option' => 'nexmo',
            ) );
            $settings->add_field( array(
                'type'        => 'text',
                'id'          => 'kmcf7se_api_sid',
                'label'       => ( $is_provider_twilio ? 'API SID: ' : 'API Key: ' ),
                'tip'         => '',
                'placeholder' => '',
            ) );
            $settings->add_field( array(
                'type'        => 'text',
                'id'          => 'kmcf7se_api_token',
                'label'       => ( $is_provider_twilio ? 'API Token: ' : 'API Secret: ' ),
                'tip'         => '',
                'placeholder' => '',
            ) );
            $settings->add_field( array(
                'type'        => 'text',
                'id'          => 'kmcf7se_senderid',
                'label'       => ( $is_provider_twilio ? 'SenderID: ' : 'Sender Name: ' ),
                'tip'         => '',
                'placeholder' => '',
            ) );
            $settings->add_field( array(
                'type'        => 'checkbox',
                'id'          => 'kmcf7se_show_errors',
                'label'       => 'Show Error Message: ',
                'tip'         => 'This will prevent the contact form from submitting if an error occurs while sending the sms',
                'placeholder' => '',
            ) );
            $settings->add_field( array(
                'type'  => 'checkbox',
                'id'    => 'kmcf7se_message_delete_data',
                'label' => __( 'Delete my data when uninstalling this plugin: ', KMCF7SE_TEXT_DOMAIN ),
                'tip'   => '',
            ) );
            $settings->save();
        }
        
        /**
         *
         * @since 1.0.0
         */
        private function setDefaultProperties()
        {
            $this->default_properties = [
                'active'          => false,
                'visitor_phone'   => '',
                'visitor_message' => __( 'Thank you for your message. We will get back to you as soon as possible.', KMCF7SE_TEXT_DOMAIN ),
                'your_phone'      => '',
                'your_message'    => __( 'A contact form submission has been made.', KMCF7SE_TEXT_DOMAIN ),
            ];
        }
        
        public function addScripts()
        {
        }
        
        /**
         *
         * @since 1.0.0
         */
        public function error_notice( $message = '' )
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
        
        /**
         * @since 1.2.2
         */
        function cf7_version_notice()
        {
            //		if ( ! defined( 'WPCF7_VERSION' ) ) :
            
            if ( version_compare( WPCF7_VERSION, $this->cf7_version, '!=' ) ) {
                ?>
                <div class="notice notice-warning is-dismissible"
                     data-dismissible="kmcf7se-notice-<?php 
                echo  $this->cf7_version ;
                ?>">
                    <p>
                        The current version of the <strong>CF7 SMS Extension</strong> is not yet tested with your
                        current
                        version of Contact Form 7.
                        <br>If you notice any problems with your forms, please install Contact Form 7 <strong>version
							<?php 
                echo  $this->cf7_version ;
                ?></strong>.
                    </p>
                </div>
			<?php 
            }
        
        }
        
        /**
         * Displays settings page
         * @since 1.1.0
         */
        public function statusTabView( $args )
        {
            switch ( $args['tab'] ) {
                case 'plugins':
                    $this->word_press_tools->renderView( "plugins" );
                    break;
                case 'upgrade':
                    $this->word_press_tools->renderView( "upgrade" );
                    break;
                case 'test':
                    $this->word_press_tools->renderView( "test" );
                    break;
                case 'history':
                    if ( kmcf7se_fs()->is_free_plan() ) {
                        echo  "<div style='margin-top:20px'>" . __( "This feature is available only in the premium version", KMCF7SE_TEXT_DOMAIN ) . "</div?" ;
                    }
                    break;
                default:
                    $this->word_press_tools->renderView( "settings" );
                    break;
            }
        }
        
        /**
         * @since 1.0.1
         */
        // todo: review naming of variables of this function
        /**
         *
         * @since 1.0.0
         */
        public function beforeSendMail( $form, &$abort, $submission )
        {
            $options_name = 'kmcf7se-tab-settings-' . $form->id();
            $options = get_option( $options_name );
            $props = $form->get_properties();
            $visitor_number = trim( wpcf7_mail_replace_tags( $options['visitor_phone'] ) );
            $visitor_message = trim( wpcf7_mail_replace_tags( $options['visitor_message'] ) );
            $your_message = trim( wpcf7_mail_replace_tags( $options['your_message'] ) );
            $your_numbers = trim( wpcf7_mail_replace_tags( $options['your_phone'] ) );
            $send_sms_to_me = true;
            //todo: enable debug mode
            if ( strlen( $visitor_number ) > 0 ) {
                if ( !$this->sendSMS( $visitor_number, $visitor_message ) ) {
                    // $abort = true;
                    $send_sms_to_me = false;
                }
            }
            
            if ( $send_sms_to_me && strlen( $your_numbers ) > 0 ) {
                $your_numbers = explode( ',', $your_numbers );
                foreach ( $your_numbers as $your_number ) {
                    $your_number = trim( $your_number );
                    if ( !$this->sendSMS( $your_number, $your_message, true ) ) {
                        // $abort = true;
                    }
                }
            }
            
            if ( $props['mail']['recipient'] == '' ) {
                // $abort = true;
            }
        }
        
        public function sendSMS(
            string $to,
            string $message,
            bool $skip_error = false,
            bool $log_message = true
        ) : bool
        {
            $provider = get_option( 'kmcf7se_provider', 'twilio' );
            switch ( $provider ) {
                case 'nexmo':
                    return $this->sendNexmoSMS(
                        $to,
                        $message,
                        $skip_error,
                        $log_message
                    );
                    break;
                default:
                    return $this->sendTwilioSMS(
                        $to,
                        $message,
                        $skip_error,
                        $log_message
                    );
            }
        }
        
        private function sendNexmoSMS(
            string $to,
            string $message,
            bool $skip_error = false,
            bool $log_message = true
        ) : bool
        {
            $NEXMO_KEY = get_option( 'kmcf7se_api_sid' );
            $NEXMO_SECRET = get_option( "kmcf7se_api_token" );
            $from = get_option( 'kmcf7se_senderid', false );
            $url = "https://rest.nexmo.com/sms/json";
            $data = [
                'api_key'    => $NEXMO_KEY,
                'api_secret' => $NEXMO_SECRET,
                'text'       => $message,
                'from'       => $from,
                'to'         => $to,
            ];
            $post = http_build_query( $data );
            $x = curl_init( $url );
            curl_setopt( $x, CURLOPT_POST, true );
            curl_setopt( $x, CURLOPT_FAILONERROR, true );
            curl_setopt( $x, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $x, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
            curl_setopt( $x, CURLOPT_POSTFIELDS, $post );
            $y = curl_exec( $x );
            $httpcode = curl_getinfo( $x, CURLINFO_HTTP_CODE );
            
            if ( curl_errno( $x ) ) {
                
                if ( !$skip_error ) {
                    update_option( 'km_error', 'mail' );
                    update_option( 'km_error_message', curl_error( $x ) );
                }
                
                return false;
            } else {
                
                if ( $httpcode >= 400 ) {
                    
                    if ( !$skip_error ) {
                        update_option( 'km_error', 'mail' );
                        update_option( 'km_error_message', $y );
                    }
                    
                    return false;
                }
            
            }
            
            curl_close( $x );
            return true;
        }
        
        private function sendTwilioSMS(
            $to,
            $message,
            $skip_error = false,
            bool $log_message = true
        )
        {
            $TWILIO_SID = get_option( 'kmcf7se_api_sid' );
            $TWILIO_TOKEN = get_option( "kmcf7se_api_token" );
            $from = get_option( 'kmcf7se_senderid' );
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$TWILIO_SID}/Messages.json";
            $data = [
                'Body' => $message,
                'From' => $from,
                'To'   => $to,
            ];
            $post = http_build_query( $data );
            $x = curl_init( $url );
            curl_setopt( $x, CURLOPT_POST, true );
            //		 curl_setopt($x, CURLOPT_FAILONERROR, true);
            curl_setopt( $x, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $x, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
            curl_setopt( $x, CURLOPT_USERPWD, "{$TWILIO_SID}:{$TWILIO_TOKEN}" );
            curl_setopt( $x, CURLOPT_POSTFIELDS, $post );
            $y = curl_exec( $x );
            $httpcode = curl_getinfo( $x, CURLINFO_HTTP_CODE );
            
            if ( curl_errno( $x ) ) {
                
                if ( !$skip_error ) {
                    update_option( 'km_error', 'mail' );
                    update_option( 'km_error_message', curl_error( $x ) );
                }
                
                return false;
            } else {
                
                if ( $httpcode >= 400 ) {
                    
                    if ( !$skip_error ) {
                        update_option( 'km_error', 'mail' );
                        update_option( 'km_error_message', $y );
                    }
                    
                    return false;
                }
            
            }
            
            curl_close( $x );
            return true;
        }
        
        /**
         *
         * @since 1.0.0
         */
        public function ajaxJsonEcho( $response, $result )
        {
            
            if ( get_option( 'kmcf7se_show_errors' ) == 'on' ) {
                
                if ( get_option( 'km_error' ) == 'mail' ) {
                    $response['status'] = 'mail_failed';
                    $error = json_decode( get_option( 'km_error_message' ) );
                    $response['message'] = $error->message ?? get_option( 'km_error_message' );
                }
                
                delete_option( 'km_error' );
                delete_option( 'km_error_message' );
            }
            
            return $response;
        }
        
        /**
         *
         * @since 1.0.0
         */
        public function saveContactForm( $form )
        {
            $options_name = 'kmcf7se-tab-settings-' . $form->id();
            
            if ( isset( $_POST[$options_name] ) ) {
                $options = get_option( $options_name );
                $options['your_phone'] = trim( sanitize_text_field( $_POST[$options_name]['your-phone'] ) );
                $options['visitor_message'] = trim( sanitize_textarea_field( $_POST[$options_name]['visitor-message'] ) );
                $options['visitor_phone'] = trim( sanitize_text_field( $_POST[$options_name]['visitor-phone'] ) );
                $options['your_message'] = trim( sanitize_textarea_field( $_POST[$options_name]['your-message'] ) );
                update_option( $options_name, $options );
            }
        
        }
        
        /**
         *
         * @since 1.0.0
         */
        public function addSMSPanel( $panels )
        {
            $panels = array_merge( $panels, [
                'sms-panel' => array(
                'title'    => __( 'SMS Settings', 'contact-form-7' ),
                'callback' => [ $this, 'editorSMSPanel' ],
            ),
            ] );
            return $panels;
        }
        
        /**
         *
         * @since 1.0.0
         */
        public function editorSMSPanel( $post, $args = '' )
        {
            $options_name = 'kmcf7se-tab-settings-' . $post->id();
            $options = get_option( $options_name );
            
            if ( $options == false ) {
                add_option( $options_name );
                $options = $this->default_properties;
                update_option( $options_name, $options );
            }
            
            $sms = wp_parse_args( $options, $this->default_properties );
            ?>
            <h1><?php 
            echo  esc_html( __( 'SMS Settings', KMCF7SE_TEXT_DOMAIN ) ) ;
            ?></h1>
			<?php 
            _e( "You can use the following tags", KMCF7SE_TEXT_DOMAIN );
            $post->suggest_mail_tags();
            ?>
            <br><br>


            <h2><?php 
            echo  esc_html( __( 'Text To Send  ( Auto reply, Visitor SMS )', KMCF7SE_TEXT_DOMAIN ) ) ;
            ?></h2>
            <fieldset>
                <legend><?php 
            _e( "Visitor Phone Number (<strong>leave blank if you do not want to send a message</strong>)", KMCF7SE_TEXT_DOMAIN );
            ?></legend>
                <input type="text" id="kmcf7se-visitor-phone" name="<?php 
            echo  $options_name ;
            ?>[visitor-phone]"
                       class="large-text"
                       value="<?php 
            echo  esc_attr( $sms['visitor_phone'] ) ;
            ?>"
                       placeholder="[your-phone-number]"/>
            </fieldset>
            <br>
            <fieldset>
                <legend><?php 
            _e( "Visitor Auto Response Message", KMCF7SE_TEXT_DOMAIN );
            ?>:</legend>
                <textarea id="kmcf7se-visitor-message" name="<?php 
            echo  $options_name ;
            ?>[visitor-message]" cols="100"
                          rows="8"
                          class="large-text"
                          placeholder="Your message has been received. We will get back to you shortly"><?php 
            echo  esc_textarea( $sms['visitor_message'] ) ;
            ?></textarea>
            </fieldset>
            <h2><?php 
            echo  esc_html( __( 'Text To Receive ( From Form , Your SMS )', KMCF7SE_TEXT_DOMAIN ) ) ;
            ?></h2>
            <fieldset>
                <legend>
					<?php 
            _e( "Your Phone Number: (<strong>leave blank if you do not want to receive a message</strong>) <br>\n                <b>You can add more numbers, separated by a comma (,). Example: [your-number], +237670223029,\n                    +12345678901 </b>", KMCF7SE_TEXT_DOMAIN );
            ?>
                </legend>
                <input type="text" id="kmcf7se-visitor-name" name="<?php 
            echo  $options_name ;
            ?>[your-phone]"
                       class="large-text"
                       placeholder="[your-number], +237670223029, +12345678901"
                       value="<?php 
            echo  esc_attr( $sms['your_phone'] ) ;
            ?>"/>
            </fieldset>
            <br>
            <fieldset>
                <legend><?php 
            _e( "Your Response Message", KMCF7SE_TEXT_DOMAIN );
            ?>:</legend>
                <textarea id="<kmcf7se-your-message" name="<?php 
            echo  $options_name ;
            ?>[your-message]" cols="100"
                          rows="8"
                          class="large-text"
                          placeholder="A contact form submission has been made from [your-name]"><?php 
            echo  esc_textarea( $sms['your_message'] ) ;
            ?></textarea>
            </fieldset>
			<?php 
        }
        
        /**
         * Displays Dashboard page
         * @since 1.0.0
         */
        public function dashboardView()
        {
        }
        
        /**
         * @since 1.2.2
         * Decode unicode variables in string
         */
        static function decodeUnicodeVars( $message )
        {
            $message = ( is_array( $message ) ? implode( " ", $message ) : $message );
            return mb_convert_encoding( $message, 'UTF-8', mb_detect_encoding( $message, 'UTF-8, ISO-8859-1', true ) );
        }
    
    }
}