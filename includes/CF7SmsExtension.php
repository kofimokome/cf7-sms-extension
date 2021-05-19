<?php
/**
 * Created by PhpStorm.
 * User: kofi
 * Date: 18/8/20
 * Time: 08:10 PM
 */

namespace kmcf7_sms_extension;

class CF7SmsExtension
{
    private $default_properties;
    private static $version;


    public function __construct()
    {
        // our constructor
        self::$version = '1.1.0';
    }

    /**
     *
     * @since 1.0.0
     */
    public function run()
    {
        $this->add_actions();
        $this->add_filters();
        $this->add_main_menu();
        $this->add_settings();
        $this->set_default_properties();
    }

    /**
     * Returns the version of this plugin
     * @since 1.1.0
     */
    public static function get_version()
    {
        return self::$version;
    }

    /**
     *
     * @since 1.0.0
     */
    private function add_actions()
    {

        // add actions here
        add_action('wpcf7_save_contact_form', [$this, 'save_contact_form']);
        add_action('wpcf7_before_send_mail', [$this, 'before_send_email'], 15, 3);


    }

    /**
     *
     * @since 1.0.0
     */
    public function error_notice($message = '')
    {
        if (trim($message) != ''):
            ?>
            <div class="error notice is-dismissible">
                <p><b>CF7 SMS Extension: </b><?php echo $message ?></p>
            </div>
        <?php
        endif;
    }

    /**
     *
     * @since 1.0.0
     */
    public function add_main_menu()
    {
        // Create the menu page

        $menu_page = new MenuPage('CF7 SMS Extension', 'CF7 SMS Extension', 'read', 'kmcf7se-sms-extension-options', 'dashicons-tickets-alt', null, array($this, 'dashboard_view'));

        $settings_page = new SubMenuPage($menu_page->get_menu_slug(), 'Options', 'Options', 'manage_options', 'kmcf7se-sms-extension-options', array($this, 'settings_view'), true);
        $settings_page->add_tab('settings', 'Basic Settings', array($this, 'status_tab_view'), array('tab' => 'settings'));
        $settings_page->add_tab('test', 'SMS Test', array($this, 'status_tab_view'), array('tab' => 'test'));
        $settings_page->add_tab('plugins', 'More Plugins', array($this, 'status_tab_view'), array('tab' => 'plugins'));

        $menu_page->add_sub_menu_page($settings_page);

        $menu_page->run();

    }

    /**
     * Displays settings page
     * @since 1.1.0
     */
    public function status_tab_view($args)
    {
        switch ($args['tab']) {
            case 'plugins':
                include "views/plugins.php";
                break;
            case 'test':
                include "views/test.php";
                break;
            default:
                include "views/settings.php";
                break;
        }
    }

    /**
     * Adds Settings
     * @since 1.1.0
     */
    private function add_settings()
    {

        $settings = new Setting('kmcf7se-sms-extension-options');
        $settings->add_section('kmcf7se_option');
        $settings->add_field(
            array(
                'type' => 'text',
                'id' => 'kmcf7se_api_sid',
                'label' => 'API SID: ',
                'tip' => '',
                'placeholder' => ''
            )
        );
        $settings->add_field(
            array(
                'type' => 'text',
                'id' => 'kmcf7se_api_token',
                'label' => 'API Token: ',
                'tip' => '',
                'placeholder' => ''
            )
        );
        $settings->add_field(
            array(
                'type' => 'text',
                'id' => 'kmcf7se_senderid',
                'label' => 'SenderID: ',
                'tip' => '',
                'placeholder' => ''
            )
        );
        $settings->add_field(
            array(
                'type' => 'checkbox',
                'id' => 'kmcf7se_show_errors',
                'label' => 'Show Error Message: ',
                'tip' => 'This will prevent the contact form from submitting if an error occurs while sending the sms',
                'placeholder' => ''
            )
        );
        $settings->save();
    }

    /**
     *
     * @since 1.0.0
     */
    public function add_filters()
    {
        add_filter('wpcf7_editor_panels', [$this, 'add_sms_panel'], 10, 1);
        add_filter('wpcf7_ajax_json_echo', [$this, 'ajax_json_echo'], 10, 2);

    }

    /**
     *
     * @since 1.0.0
     */
    public function before_send_email($form, &$abort, $submission)
    {
        $options_name = 'kmcf7se-tab-settings-' . $form->id();
        $options = get_option($options_name);

        $props = $form->get_properties();

        $visitor_number = trim(wpcf7_mail_replace_tags($options['visitor_phone']));
        $visitor_message = trim(wpcf7_mail_replace_tags($options['visitor_message']));
        $your_message = trim(wpcf7_mail_replace_tags($options['your_message']));
        $your_number = trim(wpcf7_mail_replace_tags($options['your_phone']));

        //todo: enable debug mode

        if (strlen($visitor_number) > 0) {
            if (!$this->send_sms($visitor_number, $visitor_message)) {
                // $abort = true;
            }
        }
        if (strlen($your_number) > 0) {
            if (!$this->send_sms($your_number, $your_message)) {
                // $abort = true;
            }
        }


        if ($props['mail']['recipient'] == '') {
            // $abort = true;
        }
    }

    /**
     * @since 1.0.1
     */
    // todo: review naming of variables of this function
    public static function send_sms($to, $message)
    {
        $TWILIO_SID = get_option('kmcf7se_api_sid');
        $TWILIO_TOKEN = get_option("kmcf7se_api_token");
        $from = get_option('kmcf7se_senderid');

        $url = "https://api.twilio.com/2010-04-01/Accounts/${TWILIO_SID}/Messages.json";
        $data = [
            'Body' => $message,
            'From' => $from,
            'To' => $to
        ];

        $post = http_build_query($data);
        $x = curl_init($url);
        curl_setopt($x, CURLOPT_POST, true);
        // curl_setopt($x, CURLOPT_FAILONERROR, true);
        curl_setopt($x, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($x, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($x, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($x, CURLOPT_USERPWD, "$TWILIO_SID:$TWILIO_TOKEN");
        curl_setopt($x, CURLOPT_POSTFIELDS, $post);
        $y = curl_exec($x);
        $httpcode = curl_getinfo($x, CURLINFO_HTTP_CODE);

        if (curl_errno($x)) {
            update_option('km_error', 'mail');
            update_option('km_error_message', curl_error($x));
            return false;
        } else if ($httpcode >= 400) {
            update_option('km_error', 'mail');
            update_option('km_error_message', $y);
            return false;
        }

        curl_close($x);

        return true;
    }

    /**
     *
     * @since 1.0.0
     */
    public function ajax_json_echo($response, $result)
    {
        if (get_option('kmcf7se_show_errors') == 'on') {
            if (get_option('km_error') == 'mail') {
                $response['status'] = 'mail_failed';
                $response['message'] = get_option('km_error_message');
            }
            delete_option('km_error');
            delete_option('km_error_message');
        }
        return $response;
    }

    /**
     *
     * @since 1.0.0
     */
    public function save_contact_form($form)
    {
        $options_name = 'kmcf7se-tab-settings-' . $form->id();
        if (isset($_POST[$options_name])) {
            $options = get_option($options_name);
            $options['your_phone'] = trim(sanitize_text_field($_POST[$options_name]['your-phone']));
            $options['visitor_message'] = trim(sanitize_textarea_field($_POST[$options_name]['visitor-message']));
            $options['visitor_phone'] = trim(sanitize_text_field($_POST[$options_name]['visitor-phone']));
            $options['your_message'] = trim(sanitize_textarea_field($_POST[$options_name]['your-message']));

            update_option($options_name, $options);
        }
    }

    /**
     *
     * @since 1.0.0
     */
    private function set_default_properties()
    {
        $this->default_properties = [
            'active' => false,
            'visitor_phone' => '',
            'visitor_message' => 'Thank you for your message. We will get back to you as soon as possible.',
            'your_phone' => '',
            'your_message' => 'A contact form submission has been made.'
        ];
    }

    /**
     *
     * @since 1.0.0
     */
    public function add_sms_panel($panels)
    {
        $panels = array_merge($panels, [
            'sms-panel' => array(
                'title' => __('SMS Settings', 'contact-form-7'),
                'callback' => [$this, 'editor_panel_sms'],
            )
        ]);
        return $panels;
    }

    /**
     *
     * @since 1.0.0
     */
    public function editor_panel_sms($post, $args = '')
    {
        $options_name = 'kmcf7se-tab-settings-' . $post->id();
        $options = get_option($options_name);

        if ($options == false) {
            add_option($options_name);
            $options = $this->default_properties;
            update_option($options_name, $options);
        }

        $sms = wp_parse_args($options, $this->default_properties);

        ?>
        <h1><?php echo esc_html(__('SMS Settings', 'cf7-sms-extension')); ?></h1>
        You can use the following tags <?php $post->suggest_mail_tags(); ?>
        <br><br>


        <h2><?php echo esc_html(__('Text To Send  ( Auto reply, Visitor SMS )', 'cf7-sms-extension')); ?></h2>
        <fieldset>
            <legend>Visitor Phone Number (<strong>leave blank if you do not want to send a message</strong>)</legend>
            <input type="text" id="kmcf7se-visitor-phone" name="<?php echo $options_name ?>[visitor-phone]"
                   class="large-text"
                   value="<?php echo esc_attr($sms['visitor_phone']); ?>"
                   placeholder="[your-phone-number]"/>
        </fieldset>
        <br>
        <fieldset>
            <legend>Visitor Auto Response Message:</legend>
            <textarea id="kmcf7se-visitor-message" name="<?php echo $options_name ?>[visitor-message]" cols="100"
                      rows="8"
                      class="large-text"
                      placeholder="Your message has been received. We will get back to you shortly"><?php echo esc_textarea($sms['visitor_message']); ?></textarea>
        </fieldset>
        <h2><?php echo esc_html(__('Text To Receive ( From Form , Your SMS )', 'cf7-sms-extension')); ?></h2>
        <fieldset>
            <legend>Your Phone Number: (<strong>leave blank if you do not want to receive a message</strong>)</legend>
            <input type="text" id="kmcf7se-visitor-name" name="<?php echo $options_name ?>[your-phone]"
                   class="large-text"
                   placeholder="[your-number]"
                   value="<?php echo esc_attr($sms['your_phone']); ?>"/>
        </fieldset>
        <br>
        <fieldset>
            <legend>Your Response Message:</legend>
            <textarea id="<kmcf7se-your-message" name="<?php echo $options_name ?>[your-message]" cols="100" rows="8"
                      class="large-text"
                      placeholder="A contact form submission has been made from [your-name]"><?php echo esc_textarea($sms['your_message']); ?></textarea>
        </fieldset>
        <?php
    }


    /**
     * Displays Dashboard page
     * @since 1.0.0
     */
    public function dashboard_view()
    {

    }

}
