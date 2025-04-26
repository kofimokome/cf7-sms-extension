<?php

/**
 * Created by PhpStorm.
 * User: kofi
 * Date: 18/8/20
 * Time: 08:10 PM
 */
namespace kmcf7_sms_extension;

use KMMenuPage;
use KMSetting;
use KMSubMenuPage;
use KMValidator;
use WPTools;
if ( !class_exists( 'CF7SmsExtension' ) ) {
    class CF7SmsExtension {
        private static $instance;

        private $version;

        private $default_properties;

        private $cf7_version = '5.8';

        private $word_press_tools;

        public function __construct() {
            // our constructor
            $this->version = '1.3.6';
            $this->word_press_tools = WPTools::getInstance( __FILE__ );
            self::$instance = $this;
        }

        public static function getInstance() : CF7SmsExtension {
            return self::$instance;
        }

        /**
         * Returns the version of this plugin
         * @since 1.1.0
         */
        public function getVersion() {
            return $this->version;
        }

        /**
         *
         * @since 1.0.0
         */
        public function run() {
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
        private function addActions() {
            // add actions here
            add_action( 'wpcf7_save_contact_form', [$this, 'saveContactForm'] );
            add_action(
                'wpcf7_before_send_mail',
                [$this, 'beforeSendMail'],
                15,
                3
            );
            //		add_action( 'admin_notices', [ $this, 'cf7_version_notice' ] );
            add_action( 'admin_enqueue_scripts', [$this, 'addScripts'] );
        }

        /**
         *
         * @since 1.0.0
         */
        public function addFilters() {
            add_filter(
                'wpcf7_editor_panels',
                [$this, 'addSMSPanel'],
                10,
                1
            );
            add_filter(
                'wpcf7_ajax_json_echo',
                [$this, 'ajaxJsonEcho'],
                10,
                2
            );
        }

        /**
         *
         * @since 1.0.0
         */
        public function addMainMenu() {
            // Create the menu page
            $menu_page = new KMMenuPage([
                'page_title' => 'CF7 SMS Extension',
                'menu_title' => 'CF7 SMS Extension',
                'capability' => 'read',
                'menu_slug'  => 'kmcf7se-sms-extension-options',
                'icon_url'   => 'dashicons-tickets-alt',
                'position'   => 100,
                'function'   => [$this, 'dashboardView'],
            ]);
            $settings_page = new KMSubMenuPage([
                'parent_slug' => $menu_page->get_menu_slug(),
                'page_title'  => 'Options',
                'menu_title'  => 'Options',
                'capability'  => 'manage_options',
                'menu_slug'   => 'kmcf7se-sms-extension-options',
                'position'    => 0,
                'function'    => [$this, 'settings_view'],
                'use_tabs'    => true,
            ]);
            $settings_page->add_tab(
                'settings',
                __( 'SMS Settings', KMCF7SE_TEXT_DOMAIN ),
                array($this, 'statusTabView'),
                array(
                    'tab' => 'settings',
                )
            );
            $settings_page->add_tab(
                'whatsapp',
                __( 'WhatsApp Settings', KMCF7SE_TEXT_DOMAIN ),
                array($this, 'statusTabView'),
                array(
                    'tab' => 'whatsapp',
                )
            );
            $settings_page->add_tab(
                'test',
                __( 'SMS Test', KMCF7SE_TEXT_DOMAIN ),
                array($this, 'statusTabView'),
                array(
                    'tab' => 'test',
                )
            );
            $settings_page->add_tab(
                'history',
                __( 'SMS History', KMCF7SE_TEXT_DOMAIN ),
                array($this, 'statusTabView'),
                array(
                    'tab' => 'history',
                )
            );
            if ( kmcf7se_fs()->can_use_premium_code() && !kmcf7se_fs()->is_premium() ) {
                $settings_page->add_tab(
                    'upgrade',
                    __( 'Upgrade Guide', KMCF7SE_TEXT_DOMAIN ),
                    array($this, 'statusTabView'),
                    array(
                        'tab' => 'upgrade',
                    )
                );
            }
            $settings_page->add_tab(
                'plugins',
                __( 'More Plugins', KMCF7SE_TEXT_DOMAIN ),
                array($this, 'statusTabView'),
                array(
                    'tab' => 'plugins',
                )
            );
            $menu_page->add_sub_menu_page( $settings_page );
            $menu_page->run();
        }

        private function getSenderIdFieldName( $provider ) {
            switch ( $provider ) {
                case 'twilio':
                    return 'Sender ID ';
                case 'messagebird':
                    return 'Originator:  ';
                case 'clicksend':
                    return 'Sender ID (optional) ';
                case 'textlocal':
                    return 'Sender ';
                case 'telnyx':
                    return "From ";
                default:
                    return 'Sender Name:  ';
            }
        }

        private function getAppIdFieldName( $provider ) {
            switch ( $provider ) {
                case 'twilio':
                    return 'Account SID ';
                case 'messagebird':
                    return 'API Key ';
                case 'clicksend':
                    return 'Username ';
                default:
                    return 'API Key ';
            }
        }

        private function getTokenFieldName( $provider ) {
            switch ( $provider ) {
                case 'twilio':
                    return 'Auth Token ';
                case 'messagebird':
                    return 'API Secret ';
                case 'clicksend':
                    return 'API Key ';
                default:
                    return 'API Secret ';
            }
        }

        private function hideSenderIDField( $provider ) {
            switch ( $provider ) {
                case 'clicksend':
                    return true;
                default:
                    return false;
            }
        }

        private function hideTokenField( $provider ) {
            switch ( $provider ) {
                case 'messagebird':
                    return true;
                case 'textlocal':
                    return true;
                case 'telnyx':
                    return true;
                default:
                    return false;
            }
        }

        /**
         * Adds Settings
         * @since 1.1.0
         */
        private function addSettings() {
            $provider = get_option( 'kmcf7se_provider', 'twilio' );
            $is_provider_twilio = $provider == 'twilio';
            $settings = new KMSetting('kmcf7se-sms-extension-options');
            $settings->add_section( 'kmcf7se_option' );
            $settings->add_field( array(
                'type'           => 'select',
                'id'             => 'kmcf7se_provider',
                'label'          => 'SMS Provider',
                'tip'            => 'Save changes to change the names of the fields below.',
                'placeholder'    => '',
                'options'        => [
                    'nexmo'       => 'Nexmo',
                    'twilio'      => 'Twilio',
                    'clicksend'   => 'ClickSend',
                    'messagebird' => 'MessageBird',
                    'textlocal'   => 'TextLocal',
                    'telnyx'      => 'Telnyx',
                ],
                'default_option' => 'nexmo',
            ) );
            $settings->add_field( array(
                'type'        => 'textarea',
                'id'          => 'kmcf7se_api_sid',
                'label'       => $this->getAppIdFieldName( $provider ),
                'tip'         => '',
                'placeholder' => '',
            ) );
            if ( !$this->hideTokenField( $provider ) ) {
                $settings->add_field( array(
                    'type'        => 'textarea',
                    'id'          => 'kmcf7se_api_token',
                    'label'       => $this->getTokenFieldName( $provider ),
                    'tip'         => '',
                    'placeholder' => '',
                ) );
            }
            if ( !$this->hideSenderIDField( $provider ) ) {
                $settings->add_field( array(
                    'type'        => 'text',
                    'id'          => 'kmcf7se_senderid',
                    'label'       => $this->getSenderIdFieldName( $provider ),
                    'tip'         => '',
                    'placeholder' => '',
                ) );
            }
            $settings->add_field( array(
                'type'        => 'checkbox',
                'id'          => 'kmcf7se_show_errors',
                'label'       => 'Show Error Message: ',
                'tip'         => __( 'This will prevent the contact form from submitting if an error occurs while sending the sms to your client', KMCF7SE_TEXT_DOMAIN ),
                'placeholder' => '',
            ) );
            $settings->add_field( array(
                'type'  => 'checkbox',
                'id'    => 'kmcf7se_message_delete_data',
                'label' => __( 'Delete my data when uninstalling this plugin: ', KMCF7SE_TEXT_DOMAIN ),
                'tip'   => '',
            ) );
            $settings->save();
            $settings = new KMSetting('kmcf7se-sms-extension-options&tab=whatsapp');
            $settings->add_section( 'kmcf7se_whatsapp' );
            $settings->add_field( array(
                'type'        => 'text',
                'id'          => 'kmcf7se_whatsapp_number',
                'label'       => 'Phone Number ID',
                'tip'         => '',
                'placeholder' => '123667227490990',
            ) );
            $settings->add_field( array(
                'type'        => 'text',
                'id'          => 'kmcf7se_whatsapp_language',
                'label'       => 'Language',
                'tip'         => 'Language of your templates',
                'placeholder' => 'en_us',
            ) );
            $settings->add_field( array(
                'type'        => 'textarea',
                'id'          => 'kmcf7se_whatsapp_key',
                'label'       => 'Auth Key',
                'tip'         => '',
                'placeholder' => 'AAALba3MGGZAkBO6cJmUiWGZB1oBor1wo6QGymPH6QDzDcXZB9It9txjcPMD0FVme6wHuLhxijR2ZCKZAHbXfFoqtvJg9LDypEnw9ZAcps7YXVJBuvYrwRxQ6J3Oo1dLXKU3Rt2UY6yNPxC9YxnHqZB5OsfSGRtFLsxQZB8dmaYm5Gg9bJQ5PH6nCJ3sNLSmElrxAshSV05uj4KrsmF0qu8oZD',
            ) );
            $settings->add_field( array(
                'type'        => 'checkbox',
                'id'          => 'kmcf7se_show_whatsapp_errors',
                'label'       => 'Show Error Message: ',
                'tip'         => 'This will prevent the contact form from submitting if an error occurs while sending the WhatsApp message to your client',
                'placeholder' => '',
            ) );
            $settings->save();
        }

        /**
         *
         * @since 1.0.0
         */
        private function setDefaultProperties() {
            $this->default_properties = [
                'active'          => false,
                'visitor_phone'   => '',
                'visitor_message' => __( 'Thank you for your message. We will get back to you as soon as possible.', KMCF7SE_TEXT_DOMAIN ),
                'your_phone'      => '',
                'your_message'    => __( 'A contact form submission has been made.', KMCF7SE_TEXT_DOMAIN ),
            ];
        }

        public function addScripts() {
        }

        /**
         *
         * @since 1.0.0
         */
        public function error_notice( $message = '' ) {
            if ( trim( $message ) != '' ) {
                ?>
                <div class="error notice is-dismissible">
                    <p><b>CF7 SMS Extension: </b><?php 
                echo $message;
                ?></p>
                </div>
			<?php 
            }
        }

        /**
         * @since 1.2.2
         */
        function cf7_version_notice() {
            //		if ( ! defined( 'WPCF7_VERSION' ) ) :
            if ( version_compare( WPCF7_VERSION, $this->cf7_version, '!=' ) ) {
                ?>
                <div class="notice notice-warning is-dismissible"
                     data-dismissible="kmcf7se-notice-<?php 
                echo $this->cf7_version;
                ?>">
                    <p>
                        The current version of the <strong>CF7 SMS Extension</strong> is not yet tested with your
                        current
                        version of Contact Form 7.
                        <br>If you notice any problems with your forms, please install Contact Form 7 <strong>version
							<?php 
                echo $this->cf7_version;
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
        public function statusTabView( $args ) {
            switch ( $args['tab'] ) {
                case 'whatsapp':
                    $this->word_press_tools->renderView( "whatsapp" );
                    break;
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
                    if ( kmcf7se_fs()->is_free_plan() || kmcf7se_fs()->can_use_premium_code() && !kmcf7se_fs()->is_premium() ) {
                        $instance = WPTools::getInstance( __FILE__ );
                        $dir = $instance->getPluginURL() . '/assets/images';
                        $text = "<div style='margin-top:20px'><strong>" . __( "This feature is available only in the premium version", KMCF7SE_TEXT_DOMAIN ) . "</strong></div>";
                        if ( !kmcf7se_fs()->is_trial() ) {
                            $text .= "<img src='" . $dir . "/messages.png' alt='' style='margin-top: 20px; max-width: 100%'>";
                        }
                        echo $text;
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
        public function beforeSendMail( $form, &$abort, $submission ) {
            $options_name = 'kmcf7se-tab-settings-' . $form->id();
            $options = get_option( $options_name );
            $props = $form->get_properties();
            $visitor_number = trim( wpcf7_mail_replace_tags( $options['visitor_phone'] ) );
            $visitor_whatsapp_number = trim( wpcf7_mail_replace_tags( $options['visitor_whatsapp_phone'] ) );
            $visitor_message = trim( wpcf7_mail_replace_tags( $options['visitor_message'] ) );
            $your_message = trim( wpcf7_mail_replace_tags( $options['your_message'] ) );
            $your_numbers = trim( wpcf7_mail_replace_tags( $options['your_phone'] ) );
            $your_whatsapp_numbers = trim( wpcf7_mail_replace_tags( $options['your_whatsapp_phone'] ) );
            $visitor_whatsapp_template = trim( $options['visitor_whatsapp_template'] );
            $visitor_whatsapp_parameters = [];
            $your_whatsapp_template = trim( $options['your_whatsapp_template'] );
            $your_whatsapp_parameters = [];
            $show_sms_errors = get_option( 'kmcf7se_show_errors' ) == 'on';
            $show_whatsapp_errors = get_option( 'kmcf7se_show_whatsapp_errors' ) == 'on';
            $is_sms_sent = true;
            //todo: enable debug mode
            if ( strlen( $visitor_number ) > 0 ) {
                if ( !$this->sendSMS( $visitor_number, $visitor_message, !$show_sms_errors ) ) {
                    $abort = $show_sms_errors;
                    $is_sms_sent = false;
                }
            }
            //todo add show_sms_errors to condition
            if ( $is_sms_sent && strlen( $your_numbers ) > 0 ) {
                $numbers = explode( ',', $your_numbers );
                foreach ( $numbers as $your_number ) {
                    $your_number = trim( $your_number );
                    if ( !$this->sendSMS( $your_number, $your_message, true ) ) {
                        // $abort = true;
                    }
                }
            }
            // avoid sending a whatsapp message if the sms was not sent and sms errors are shown
            if ( !$show_sms_errors || $is_sms_sent ) {
                if ( strlen( $visitor_whatsapp_template ) > 0 && strlen( $visitor_whatsapp_number ) > 0 ) {
                    if ( !$this->sendWhatsAppMessage(
                        $visitor_whatsapp_number,
                        $visitor_whatsapp_template,
                        $visitor_whatsapp_parameters,
                        !$show_whatsapp_errors,
                        false
                    ) ) {
                        $abort = $show_whatsapp_errors;
                    }
                }
                if ( strlen( $your_whatsapp_template ) > 0 && strlen( $your_whatsapp_numbers ) > 0 ) {
                    $numbers = explode( ',', $your_whatsapp_numbers );
                    foreach ( $numbers as $your_number ) {
                        $your_number = trim( $your_number );
                        $this->sendWhatsAppMessage(
                            $your_number,
                            $your_whatsapp_template,
                            $your_whatsapp_parameters,
                            true,
                            false
                        );
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
        ) : bool {
            $provider = get_option( 'kmcf7se_provider', 'nexmo' );
            switch ( $provider ) {
                case 'twilio':
                    return $this->sendTwilioSMS(
                        $to,
                        $message,
                        $skip_error,
                        $log_message
                    );
                    break;
                case 'clicksend':
                    return $this->sendClickSendSMS(
                        $to,
                        $message,
                        $skip_error,
                        $log_message
                    );
                case 'messagebird':
                    return $this->sendMessageBirdSMS(
                        $to,
                        $message,
                        $skip_error,
                        $log_message
                    );
                case 'telnyx':
                    return $this->sendTelnyxSMS(
                        $to,
                        $message,
                        $skip_error,
                        $log_message
                    );
                case 'textlocal':
                    return $this->sendTextLocalSMS(
                        $to,
                        $message,
                        $skip_error,
                        $log_message
                    );
                    break;
                default:
                    return $this->sendNexmoSMS(
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
        ) : bool {
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
            // -------
            add_filter(
                'https_ssl_verify',
                function ( $ssl_verify, $url_to_call ) use($url) {
                    return ( $url == $url_to_call ? false : $ssl_verify );
                },
                10,
                2
            );
            $response = wp_remote_request( $url, [
                'method' => 'POST',
                'body'   => $data,
            ] );
            if ( is_wp_error( $response ) ) {
                if ( !$skip_error ) {
                    update_option( 'km_error', 'mail' );
                    update_option( 'km_error_message', $response->get_error_message() );
                }
                return false;
            }
            $messages = json_decode( $response['body'], true )['messages'];
            if ( $messages[0]['status'] != 0 ) {
                if ( !$skip_error ) {
                    update_option( 'km_error', 'mail' );
                    update_option( 'km_error_message', $messages[0]['error-text'] );
                }
                return false;
            }
            return true;
        }

        /**
         * @since v1.3.2
         */
        public function sendWhatsAppMessage(
            string $to,
            string $template,
            array $params,
            bool $skip_error = false,
            bool $log_message = true
        ) : bool {
            $number = get_option( 'kmcf7se_whatsapp_number' );
            $key = get_option( "kmcf7se_whatsapp_key" );
            $language = get_option( "kmcf7se_whatsapp_language" );
            $template = [
                'name'     => $template,
                'language' => [
                    'code' => $language,
                ],
            ];
            if ( sizeof( $params ) > 0 ) {
                $template["components"] = [[
                    "type"       => "body",
                    "parameters" => array_map( function ( $param ) {
                        return [
                            "type" => "text",
                            "text" => $param,
                        ];
                    }, $params ),
                ]];
            }
            $url = "https://graph.facebook.com/v17.0/{$number}/messages";
            $data = [
                'messaging_product' => 'whatsapp',
                'to'                => $to,
                'type'              => 'template',
                'access_token'      => $key,
                'template'          => wp_json_encode( $template ),
            ];
            $headers = [
                'Authorization' => $key,
                'Content-Type'  => 'application/json',
            ];
            //---
            $response = wp_remote_request( $url, [
                'method'  => 'POST',
                'headers' => $headers,
                'body'    => $data,
            ] );
            if ( is_wp_error( $response ) ) {
                if ( !$skip_error ) {
                    update_option( 'km_error', 'mail' );
                    update_option( 'km_error_message', $response->get_error_message() );
                }
                return false;
            } else {
                $http_code = $response['response']['code'] ?? 400;
                $body = $response['body'];
                if ( $http_code >= 400 ) {
                    if ( !$skip_error ) {
                        update_option( 'km_error', 'mail' );
                        update_option( 'km_error_message', $body ?? $response['response']['message'] );
                    }
                    return false;
                }
            }
            //---
            return true;
        }

        private function sendMessageBirdSMS(
            $to,
            $message,
            $skip_error = false,
            bool $log_message = true
        ) {
            $access_key = get_option( 'kmcf7se_api_sid' );
            $originator = get_option( 'kmcf7se_senderid' );
            $url = "https://rest.messagebird.com/messages";
            $data = [
                'body'       => $message,
                'originator' => $originator,
                'recipients' => [$to],
            ];
            $headers = [
                'Authorization' => 'AccessKey  ' . $access_key,
                'Content-Type'  => 'application/json',
            ];
            $response = wp_remote_request( $url, [
                'method'  => 'POST',
                'headers' => $headers,
                'body'    => wp_json_encode( $data ),
            ] );
            if ( is_wp_error( $response ) ) {
                if ( !$skip_error ) {
                    update_option( 'km_error', 'mail' );
                    update_option( 'km_error_message', $response->get_error_message() );
                }
                return false;
            } else {
                $http_code = $response['response']['code'] ?? 400;
                $body = $response['body'];
                if ( $http_code >= 400 ) {
                    if ( !$skip_error ) {
                        update_option( 'km_error', 'mail' );
                        update_option( 'km_error_message', $body ?? $response['response']['message'] );
                    }
                    return false;
                }
            }
            return true;
        }

        private function sendTextLocalSMS(
            $to,
            $message,
            $skip_error = false,
            bool $log_message = true
        ) {
            $api_key = urlencode( get_option( 'kmcf7se_api_sid' ) );
            $sender = get_option( 'kmcf7se_senderid' );
            $url = "https://api.txtlocal.com/send/";
            $data = [
                'apikey'  => $api_key,
                'message' => urlencode( $message ),
                'sender'  => urlencode( $sender ),
                'numbers' => $to,
            ];
            $response = wp_remote_request( $url, [
                'method' => 'POST',
                'body'   => $data,
            ] );
            if ( is_wp_error( $response ) ) {
                if ( !$skip_error ) {
                    update_option( 'km_error', 'mail' );
                    update_option( 'km_error_message', $response->get_error_message() );
                }
                return false;
            } else {
                $body = $response['body'];
                $decoded_body = json_decode( $body, true );
                if ( $decoded_body['status'] == 'failure' ) {
                    if ( !$skip_error ) {
                        update_option( 'km_error', 'mail' );
                        update_option( 'km_error_message', $body ?? $response['response']['message'] );
                    }
                    return false;
                }
            }
            return true;
        }

        private function sendTelnyxSMS(
            $to,
            $message,
            $skip_error = false,
            bool $log_message = true
        ) {
            $api_key = urlencode( get_option( 'kmcf7se_api_sid' ) );
            $from = get_option( 'kmcf7se_senderid' );
            $url = "https://api.telnyx.com/v2/messages";
            $data = [
                'text' => $message,
                'from' => $from,
                'to'   => $to,
            ];
            $headers = [
                'Authorization' => 'Bearer ' . $api_key,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ];
            $response = wp_remote_request( $url, [
                'method'  => 'POST',
                'headers' => $headers,
                'body'    => wp_json_encode( $data ),
                'type'    => 'SMS',
            ] );
            if ( is_wp_error( $response ) ) {
                if ( !$skip_error ) {
                    update_option( 'km_error', 'mail' );
                    update_option( 'km_error_message', $response->get_error_message() );
                }
                return false;
            } else {
                $body = $response['body'];
                $decoded_body = json_decode( $body, true );
                if ( isset( $decoded_body['errors'] ) ) {
                    if ( !$skip_error ) {
                        update_option( 'km_error', 'mail' );
                        update_option( 'km_error_message', $body ?? $response['response']['message'] );
                    }
                    return false;
                }
            }
            return true;
        }

        private function sendTwilioSMS(
            $to,
            $message,
            $skip_error = false,
            bool $log_message = true
        ) {
            $TWILIO_SID = get_option( 'kmcf7se_api_sid' );
            $TWILIO_TOKEN = get_option( "kmcf7se_api_token" );
            $from = get_option( 'kmcf7se_senderid' );
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$TWILIO_SID}/Messages.json";
            $data = [
                'Body' => $message,
                'From' => $from,
                'To'   => $to,
            ];
            $headers = [
                'Authorization' => 'Basic ' . base64_encode( "{$TWILIO_SID}:{$TWILIO_TOKEN}" ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ];
            $response = wp_remote_request( $url, [
                'method'  => 'POST',
                'headers' => $headers,
                'body'    => $data,
            ] );
            if ( is_wp_error( $response ) ) {
                if ( !$skip_error ) {
                    update_option( 'km_error', 'mail' );
                    update_option( 'km_error_message', $response->get_error_message() );
                }
                return false;
            } else {
                $http_code = $response['response']['code'] ?? 400;
                $body = $response['body'];
                if ( $http_code >= 400 ) {
                    if ( !$skip_error ) {
                        update_option( 'km_error', 'mail' );
                        update_option( 'km_error_message', $body ?? $response['response']['message'] );
                    }
                    return false;
                }
            }
            return true;
        }

        private function sendClickSendSMS(
            $to,
            $message,
            $skip_error = false,
            bool $log_message = true
        ) {
            $username = get_option( 'kmcf7se_api_sid' );
            $password = get_option( "kmcf7se_api_token" );
            $from = get_option( 'kmcf7se_senderid' );
            $url = "https://rest.clicksend.com/v3/sms/send";
            $data = [
                'messages' => [[
                    'body' => $message,
                    'to'   => $to,
                ]],
            ];
            $headers = [
                'Authorization' => 'Basic ' . base64_encode( "{$username}:{$password}" ),
                'Content-Type'  => 'application/json',
            ];
            $response = wp_remote_request( $url, [
                'method'  => 'POST',
                'headers' => $headers,
                'body'    => wp_json_encode( $data ),
            ] );
            if ( is_wp_error( $response ) ) {
                if ( !$skip_error ) {
                    update_option( 'km_error', 'mail' );
                    update_option( 'km_error_message', $response->get_error_message() );
                }
                return false;
            } else {
                $http_code = $response['response']['code'] ?? 400;
                $body = $response['body'];
                if ( $http_code >= 400 ) {
                    if ( !$skip_error ) {
                        update_option( 'km_error', 'mail' );
                        update_option( 'km_error_message', $body ?? $response['response']['message'] );
                    }
                    return false;
                }
            }
            return true;
        }

        /**
         *
         * @since 1.0.0
         */
        public function ajaxJsonEcho( $response, $result ) {
            if ( get_option( 'kmcf7se_show_errors' ) == 'on' || get_option( 'kmcf7se_show_whatsapp_errors' ) == 'on' ) {
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
        public function saveContactForm( $form ) {
            $options_name = 'kmcf7se-tab-settings-' . $form->id();
            if ( isset( $_POST[$options_name] ) ) {
                $options = get_option( $options_name );
                $options_to_add = [
                    'your-phone',
                    'visitor-message',
                    'visitor-phone',
                    'your-message',
                    'visitor-whatsapp-phone',
                    'visitor-whatsapp-template',
                    'your-whatsapp-phone',
                    'your-whatsapp-template'
                ];
                foreach ( $options_to_add as $option_to_add ) {
                    if ( isset( $_POST[$options_name][$option_to_add] ) ) {
                        $options[str_replace( '-', '_', $option_to_add )] = trim( sanitize_text_field( wp_unslash( $_POST[$options_name][$option_to_add] ) ) );
                    }
                }
                update_option( $options_name, $options );
            }
        }

        /**
         *
         * @since 1.0.0
         */
        public function addSMSPanel( $panels ) {
            $panels = array_merge( $panels, [
                'sms-panel' => array(
                    'title'    => __( 'SMS Settings', 'contact-form-7' ),
                    'callback' => [$this, 'editorSMSPanel'],
                ),
            ] );
            return $panels;
        }

        /**
         *
         * @since 1.0.0
         */
        public function editorSMSPanel( $post, $args = '' ) {
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
            echo esc_html( __( 'SMS Settings', KMCF7SE_TEXT_DOMAIN ) );
            ?></h1>
			<?php 
            _e( "You can use the following tags", KMCF7SE_TEXT_DOMAIN );
            $post->suggest_mail_tags();
            ?>
            <br><br>


            <h2><?php 
            echo esc_html( __( 'Text To Send  ( Auto reply, Visitor SMS )', KMCF7SE_TEXT_DOMAIN ) );
            ?></h2>
            <h3>SMS Settings:</h3>
            <fieldset>
                <legend><?php 
            _e( "Visitor Phone Number (<strong>leave blank if you do not want to send an SMS</strong>)", KMCF7SE_TEXT_DOMAIN );
            ?></legend>
                <input type="text" id="kmcf7se-visitor-phone" name="<?php 
            echo $options_name;
            ?>[visitor-phone]"
                       class="large-text"
                       value="<?php 
            echo esc_attr( $sms['visitor_phone'] );
            ?>"
                       placeholder="[your-phone-number]"/>
            </fieldset>
            <br>
            <fieldset>
                <legend><?php 
            _e( "Visitor Auto Response Message", KMCF7SE_TEXT_DOMAIN );
            ?>:</legend>
                <textarea id="kmcf7se-visitor-message" name="<?php 
            echo $options_name;
            ?>[visitor-message]" cols="100"
                          rows="8"
                          class="large-text"
                          placeholder="Your message has been received. We will get back to you shortly"><?php 
            echo esc_textarea( $sms['visitor_message'] );
            ?></textarea>
            </fieldset>
            <h3>WhatsApp Settings:</h3>
            <fieldset>
                <legend><?php 
            _e( "Visitor WhatsApp Number (<strong>leave blank if you do not want to send a WhatsApp message</strong>)", KMCF7SE_TEXT_DOMAIN );
            ?></legend>
                <input type="text" id="kmcf7se-visitor-whatsapp-phone"
                       name="<?php 
            echo $options_name;
            ?>[visitor-whatsapp-phone]"
                       class="large-text"
                       value="<?php 
            echo esc_attr( $sms['visitor_whatsapp_phone'] );
            ?>"
                       placeholder="[your-number]"/>
            </fieldset>
            <br>
            <fieldset>
                <legend><?php 
            _e( "WhatsApp Template", KMCF7SE_TEXT_DOMAIN );
            ?>:</legend>
                <input type="text" id="kmcf7se-visitor-whatsapp-template"
                       name="<?php 
            echo $options_name;
            ?>[visitor-whatsapp-template]"
                       class="large-text"
                       placeholder="hello_world"
                       value="<?php 
            echo esc_textarea( $sms['visitor_whatsapp_template'] );
            ?>"/>
            </fieldset>
            <br>
            <fieldset>
                <legend><?php 
            _e( "WhatsApp Template Parameters", KMCF7SE_TEXT_DOMAIN );
            ?>
                    <strong>(<?php 
            _e( "leave this blank if your WhatsApp template does not have parameters <br/>Parameters should be separated by a comma.", KMCF7SE_TEXT_DOMAIN );
            ?>
                        )</strong>:
                </legend>
				<?php 
            ?>

				<?php 
            if ( kmcf7se_fs()->is_free_plan() || kmcf7se_fs()->can_use_premium_code() && !kmcf7se_fs()->is_premium() ) {
                ?>

                    <input type="text"
                           name=""
                           class="large-text"
                           placeholder="[your-name], [your-phone]"
                           value="" disabled readonly/>

                    <strong style="color: red">Upgrade to the paid version to send a WhatsApp template with
                        parameters</strong>

				<?php 
            }
            ?>
            </fieldset>

            <h2 style="margin-top: 40px"><?php 
            echo esc_html( __( 'Text To Receive ( From Form , Your SMS )', KMCF7SE_TEXT_DOMAIN ) );
            ?></h2>
            <h3>SMS Settings:</h3>
            <fieldset>
                <legend>
					<?php 
            _e( "Your Phone Number: (<strong>leave blank if you do not want to receive an SMS</strong>) <br>\n                <b>You can add more numbers, separated by a comma (,). Example: [your-number], +237670223029,\n                    +12345678901 </b>", KMCF7SE_TEXT_DOMAIN );
            ?>
                </legend>
                <input type="text" id="kmcf7se-your-phone" name="<?php 
            echo $options_name;
            ?>[your-phone]"
                       class="large-text"
                       placeholder="[your-number], +237670223029, +12345678901"
                       value="<?php 
            echo esc_attr( $sms['your_phone'] );
            ?>"/>
            </fieldset>
            <br>
            <fieldset>
                <legend><?php 
            _e( "Your Response Message", KMCF7SE_TEXT_DOMAIN );
            ?>:</legend>
                <textarea id="<kmcf7se-your-message" name="<?php 
            echo $options_name;
            ?>[your-message]" cols="100"
                          rows="8"
                          class="large-text"
                          placeholder="A contact form submission has been made from [your-name]"><?php 
            echo esc_textarea( $sms['your_message'] );
            ?></textarea>
            </fieldset>
            <h3>WhatsApp Settings:</h3>
            <fieldset>
                <legend>
                    <strong><?php 
            _e( "Your WhatsApp Number: (leave blank if you do not want to receive a WhatsApp message) <br>\n                You can add more numbers, separated by a comma (,). Example: [your-number], +237670223029,\n                    +12345678901", KMCF7SE_TEXT_DOMAIN );
            ?></strong>
                </legend>
                <input type="text" id="kmcf7se-your-whatsapp-phone"
                       name="<?php 
            echo $options_name;
            ?>[your-whatsapp-phone]"
                       class="large-text"
                       placeholder="[your-number], +237670223029, +12345678901"
                       value="<?php 
            echo esc_attr( $sms['your_whatsapp_phone'] );
            ?>"/>
            </fieldset>
            <br>
            <fieldset>
                <legend><?php 
            _e( "WhatsApp Template", KMCF7SE_TEXT_DOMAIN );
            ?>:</legend>
                <input type="text" id="kmcf7se-visitor-whatsapp-template"
                       name="<?php 
            echo $options_name;
            ?>[your-whatsapp-template]"
                       class="large-text"
                       placeholder="hello_world"
                       value="<?php 
            echo esc_textarea( $sms['your_whatsapp_template'] );
            ?>"/>
            </fieldset>
            <br>
            <fieldset>
                <legend><?php 
            _e( "WhatsApp Template Parameters", KMCF7SE_TEXT_DOMAIN );
            ?>
                    <strong>(<?php 
            _e( "leave this blank if your WhatsApp template does not have parameters <br/>Parameters should be separated by a comma.", KMCF7SE_TEXT_DOMAIN );
            ?>
                        )</strong>:
                </legend>
				<?php 
            ?>

				<?php 
            if ( kmcf7se_fs()->is_free_plan() || kmcf7se_fs()->can_use_premium_code() && !kmcf7se_fs()->is_premium() ) {
                ?>
                    <input type="text"
                           name=""
                           class="large-text"
                           placeholder="[your-name], [your-phone]"
                           value="" disabled readonly/>

                    <strong style="color: red">Upgrade to the paid version to send a WhatsApp template with
                        parameters</strong>

				<?php 
            }
            ?>
            </fieldset>
			<?php 
        }

        /**
         * Displays Dashboard page
         * @since 1.0.0
         */
        public function dashboardView() {
        }

        /**
         * @since 1.2.2
         * Decode unicode variables in string
         */
        static function decodeUnicodeVars( $message ) {
            $message = ( is_array( $message ) ? implode( " ", $message ) : $message );
            return mb_convert_encoding( $message, 'UTF-8', mb_detect_encoding( $message, 'UTF-8, ISO-8859-1', true ) );
        }

    }

}