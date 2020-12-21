<?php
/**
 * Created by PhpStorm.
 * User: kofi
 * Date: 18/8/20
 * Time: 08:10 PM
 * Added by UnderWordPressure: [text <name> ...] filter.
 */

namespace kmcf7_sms_extension;
require_once(plugin_dir_path(__DIR__) . 'providers/Twilio/autoload.php');

use Twilio\Rest\Client;

class CF7SmsExtension
{

    private $temp_email;
    private $temp_message;
    private $count_updated = false;
    private $blocked;
    private $log_file;
    private $version;

    public function __construct()
    {
        // our constructor
        $this->blocked = get_option("kmcfmf_messages_blocked_today");
        //  $this->error_notice("hi there");
        $logs_root = plugin_dir_path(dirname(__FILE__)) . 'logs/';
        $this->log_file = $logs_root . 'messages.txt';
        $this->version = '1.0.0';


        $to = +237670224092;//(isset($_POST["numbers"])) ? $_POST["numbers"] : "";
        $sender_id = +15005550006; //(isset($_POST["sender"]))  ? $_POST["sender"]  : "";
        $message = "test";// (isset($_POST["message"])) ? $_POST["message"] : "";

        //gets our api details from the database.

        $TWILIO_SID = get_option('kmcf7se_api_sid');
        $TWILIO_TOKEN = get_option("kmcf7se_api_token");


        /*try {
            $client = new Client($TWILIO_SID, $TWILIO_TOKEN);
            $response = $client->messages->create(
                $to,
                array(
                    "from" => $sender_id,
                    "body" => $message
                )
            );
            var_dump($response->sid);
            die("done");
        } catch (\Exception $e) {
            // self::DisplayError($e->getMessage());
            die($e->getMessage());
        }*/
    }

    public function run()
    {
        // $this->add_actions();
        // $this->add_options();
        $this->add_filters();
        $this->add_main_menu();
        // $this->transfer_old_data();
    }


    private function add_actions()
    {

        // add actions here
        add_action('admin_enqueue_scripts', array($this, 'add_scripts'));

    }

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

    public function add_panels()
    {

    }

    public function add_scripts($hook)
    {
        global $wp;
        $url = add_query_arg(array($_GET), $wp->request);
        $url = substr($url, 0, 29);
        // echo "<script> alert('$url');</script>";
        //wp_enqueue_style( 'style-name', get_stylesheet_uri() );
        if ($hook == 'toplevel_page_kmcf7-message-filter' || $url == '?page=kmcf7-filtered-messages') {

            wp_enqueue_script('vendor', plugins_url('assets/js/vendor.min.js', dirname(__FILE__)), array('jquery'), '1.0.0', true);
            wp_enqueue_script('moment', plugins_url('assets/libs/moment/moment.min.js', dirname(__FILE__)), array('jquery'), '1.0.0', true);
            wp_enqueue_script('apex', plugins_url('assets/libs/apexcharts/apexcharts.min.js', dirname(__FILE__)), array('jquery'), '1.0.0', false);
            wp_enqueue_script('flat', plugins_url('assets/libs/flatpickr/flatpickr.min.js', dirname(__FILE__)), array('jquery'), '1.0.0', true);
            wp_enqueue_script('dash', plugins_url('assets/js/pages/dashboard.init.js', dirname(__FILE__)), array('jquery'), '1.0.0', true);
            wp_enqueue_script('app', plugins_url('assets/js/app.min.js', dirname(__FILE__)), array('jquery'), '1.0.0', true);


            wp_enqueue_style('bootstrap', plugins_url('/assets/css/bootstrap.min.css', dirname(__FILE__)), '', '4.3.1');
            wp_enqueue_style('app', plugins_url('/assets/css/app.min.css', dirname(__FILE__)), '', '4.3.1');
            wp_enqueue_style('icons', plugins_url('/assets/css/icons.min.css', dirname(__FILE__)), '', '4.3.1');
        }
    }

    public function add_main_menu()
    {
        // Create the menu page

        $menu_page = new KmMenuPage('CF7 SMS Extension', 'CF7 SMS Extension', 'read', 'kmcf7se-sms-extension', 'dashicons-filter', null, array($this, 'dashboard_view'));

        $settings_page = new KmSubMenuPage($menu_page->get_menu_slug(), 'Options', 'Options', 'manage_options', 'kmcf7se-sms-extension-options', null, true);
        $settings_page->add_section('kmcf7se_option');
        $settings_page->add_field(
            array(
                'type' => 'text',
                'id' => 'kmcf7se_api_sid',
                'label' => 'API SID: ',
                'tip' => 'type <code>[link]</code> to filter messages containing links, type <code>[russian]</code> to filter messages contains russian characters',
                'placeholder' => 'eg john, doe, baby, man, [link], [russian]'
            )
        );
        $settings_page->add_field(
            array(
                'type' => 'text',
                'id' => 'kmcf7se_api_token',
                'label' => 'API Token: ',
                'tip' => '',
                'placeholder' => 'You have entered a word marked as spam'
            )
        );

        $menu_page->add_sub_menu_page($settings_page);

        $menu_page->run();

    }


    public function add_filters()
    {
        add_filter('wpcf7_editor_panels', [$this, 'add_sms_panel'], 10, 1);
        add_filter('wpcf7_contact_form_properties', [$this, 'add_sms_property'], 10, 1);
    }

    public function add_sms_property($properties)
    {
        $properties = array_merge($properties, [
            'kmsms' => array('active' => false,
                'visitor_phone' => '',
                'visitor_message' => 'Thank you for your message. We will get back to you as soon as possible.',
                'visitor_name' => '',
                'your_message' => 'A contact form submission has been made.')
        ]);
        return $properties;
    }

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

    public function editor_panel_sms($post, $args = '')
    {
        $args = wp_parse_args($args, array(
            'id' => 'wpcf7-kmsms',
            'name' => 'kmsms',
            'title' => __('Mail', 'contact-form-7'),
            'use' => null,
        ));
        $id = esc_attr( $args['id'] );
        var_dump($post);
        $sms = wp_parse_args($post->prop($args['name']), array(
            'active' => false,
            'visitor_phone' => '',
            'visitor_message' => '',
            'visitor_name' => '',
            'your_message' => '',
        ));
        $desc_link = wpcf7_link(
            __('https://contactform7.com/additional-settings/', 'contact-form-7'),
            __('Additional settings', 'contact-form-7'));
        $description = __("You can add customization code snippets here. For details, see %s.", 'contact-form-7');
        $description = sprintf(esc_html($description), $desc_link);

        ?>
        <h1><?php echo esc_html(__('SMS Settings', 'cf7-sms-extension')); ?></h1>
        You can use the following tags <?php $post->suggest_mail_tags(); ?>etc
        <br><br>


        <h2><?php echo esc_html(__('Text To Send  ( Auto reply, Visitor SMS )', 'cf7-sms-extension')); ?></h2>
        <fieldset>
            <legend>Visitor Phone Number</legend>
            <input type="text" id="<?php echo $id ?>-visitor-phone" name="<?php echo $id ?>[visitor_phone]"
                   class="large-text"
                   data-config-field="<?php echo sprintf('%s.visitor_phone', esc_attr($args['name'])); ?>"
                   value="<?php echo esc_attr($sms['visitor_phone']); ?>"
                   placeholder="[your-phone-number]"/>
        </fieldset>
        <fieldset>
            <legend>Visitor Auto Response Message:</legend>
            <textarea id="<?php echo $id ?>-visitor-message" name="<?php echo $id ?>['visitor_message']" cols="100"
                      rows="8"
                      class="large-text"
                      data-config-field="<?php echo sprintf('%s.visitor_message', esc_attr($args['name'])); ?>"
                      placeholder="Your message has been received. We will get back to you shortly"><?php echo esc_textarea($post->prop('kmsms_extension')); ?></textarea>
        </fieldset>
        <h2><?php echo esc_html(__('Text To Receive ( From Form , Your SMS )', 'cf7-sms-extension')); ?></h2>
        <fieldset>
            <legend>Visitor Nick Name</legend>
            <input type="text" id="<?php echo $id ?>-visitor-name" name="<?php echo $id ?>['visitor_name']"
                   class="large-text"
                   data-config-field="<?php echo sprintf('%s.visitor_name', esc_attr($args['name'])); ?>"
                   placeholder="[your-name]"
                   value="<?php echo esc_attr($sms['visitor_name']); ?>"/>
        </fieldset>
        <fieldset>
            <legend>Your Response Message:</legend>
            <textarea id="<?php echo $id ?>-your-message" name="<?php echo $id ?>['your-message']" cols="100" rows="8"
                      class="large-text"
                      data-config-field="<?php echo sprintf('%s.your_name', esc_attr($args['name'])); ?>"
                      placeholder="A contact form submission has been made from [your-name]"><?php echo esc_textarea($sms['your-message']); ?></textarea>
        </fieldset>
        <br>
        <fieldset>
            <input type="checkbox" checked="checked"> Activate SMS Notification
        </fieldset>
        <?php
    }

    /**
     * Adds a custom message for messages flagged as spam
     * @since 1.2.2
     */
    public function add_custom_messages($messages)
    {
        $spam_word_eror = get_option('kmcfmf_spam_word_error') ? get_option('kmcfmf_spam_word_error') : 'One or more fields have an error. Please check and try again.';
        $spam_email_error = get_option('kmcfmf_spam_email_error') ? get_option('kmcfmf_spam_email_error') : 'The e-mail address entered is invalid.';
        $messages = array_merge($messages, array(
            'spam_word_error' => array(
                'description' =>
                    __("Message contains a word marked as spam", 'contact-form-7'),
                'default' =>
                    __($spam_word_eror, 'contact-form-7'),
            ),
            'spam_email_error' => array(
                'description' =>
                    __("Email is an email marked as spam", 'contact-form-7'),
                'default' =>
                    __($spam_email_error, 'contact-form-7'),
            ),
        ));

        return $messages;
    }

    /**
     * Displays Dashboard page
     * @since 1.2.0
     */
    public function dashboard_view()
    {
        include "partials/dashboard.php";
    }

    /**
     * Logs messages blockded to the log file
     * @since 1.2.0
     */
    private function update_log($email, $message)
    {
        update_option('kmcfmf_last_message_blocked', '<td>' . Date('d-m-y h:ia') . ' </td><td>' . $email . '</td><td>' . $message . ' </td>');
        //update_option("kmcfmf_messages", get_option("kmcfmf_messages") . "]kmcfmf_message[ kmcfmf_data=" . $message . " kmcfmf_data=" . $this->temp_email . " kmcfmf_data=" . Date('d-m-y  h:ia'));
        $log_messages = (array)json_decode(file_get_contents($this->log_file));
        $log_message = ['message' => $message, 'date' => Date('d-m-y  h:ia'), 'email' => $email];
        array_push($log_messages, $log_message);

        $log_messages = json_encode((object)$log_messages);
        file_put_contents($this->log_file, $log_messages);
        update_option('kmcfmf_messages_blocked', get_option('kmcfmf_messages_blocked') + 1);
        update_option("kmcfmf_messages_blocked_today", get_option("kmcfmf_messages_blocked_today") + 1);
        $today = date('N');
        $weekly_stats = json_decode(get_option('kmcfmf_weekly_stats'));
        $weekly_stats[$today - 1] = get_option("kmcfmf_messages_blocked_today");
        update_option('kmcfmf_weekly_stats', json_encode($weekly_stats));

        $this->count_updated = true;
    }

}
