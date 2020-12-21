<?php
/**
 * Created by PhpStorm.
 * User: kofi
 * Date: 18/8/20
 * Time: 08:10 PM
 * Added by UnderWordPressure: [text <name> ...] filter.
 */

namespace kmcf7_sms_extension;


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

    }

    public function run()
    {
        // $this->add_actions();
        // $this->add_options();
        // $this->add_filters();
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
                <p><b>Gifted Mom Comment: </b><?php echo $message ?></p>
            </div>
        <?php
        endif;
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
                'id' => 'kmcf7se_restricted_words',
                'label' => 'API Key: ',
                'tip' => 'type <code>[link]</code> to filter messages containing links, type <code>[russian]</code> to filter messages contains russian characters',
                'placeholder' => 'eg john, doe, baby, man, [link], [russian]'
            )
        );
        $settings_page->add_field(
            array(
                'type' => 'text',
                'id' => 'kmcf7se_spam_word_error',
                'label' => 'API Key 2: ',
                'tip' => '',
                'placeholder' => 'You have entered a word marked as spam'
            )
        );

        $menu_page->add_sub_menu_page($settings_page);

        $menu_page->run();

    }

    /**
     * Todo: Add Description
     *
     * @since    2.0.0
     * @access   public
     */
    private function add_options()
    {

        //
        $reset_message_filter_counter = get_option('kmcfmf_message_filter_reset') == 'on' ? true : false;

        $option_names = array(
            'kmcfmf_messages_blocked',
            'kmcfmf_last_message_blocked',
            'kmcfmf_message_filter_reset',
            'kmcfmf_date_of_today',
            'kmcfmf_messages_blocked_today',
            'kmcfmf_messages',
            'kmcfmf_weekly_stats',
            'kmcfmf_weekend',
        );

        foreach ($option_names as $option_name) {
            if (get_option($option_name) == false) {
                // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
                $deprecated = null;
                $autoload = 'no';
                add_option($option_name, 0, $deprecated, $autoload);
            }

            if ($reset_message_filter_counter) {
                update_option($option_name, 0);
            }

        }
//        if ($reset_message_filter_counter || file_get_contents($this->log_file) == '') {
//            $content = "{}";
//            file_put_contents($this->log_file, $content);
//        }
        update_option('kmcfmf_message_filter_reset', 'off');
        update_option('kmcfmf_weekly_stats', get_option('kmcfmf_weekly_stats') == '0' ? '[0,0,0,0,0,0,0]' : get_option('kmcfmf_weekly_stats'));

        $date = get_option('kmcfmf_date_of_today');
        $now = strtotime(Date("d F Y"));
        $today = date("N", $now);
        if ((int)get_option('kmcfmf_weekend') == 0 || (int)get_option('kmcfmf_weekend') < (int)$now) {
            $sunday = strtotime("+" . (7 - $today) . "day");
            update_option('kmcfmf_weekend', $sunday);
            update_option('kmcfmf_weekly_stats', '[0,0,0,0,0,0,0]');
        }
        if ((int)$date < (int)$now) {
            $weekly_stats = json_decode(get_option('kmcfmf_weekly_stats'));
            $weekly_stats[date('N', $date) - 1] = get_option("kmcfmf_messages_blocked_today");
            update_option('kmcfmf_weekly_stats', json_encode($weekly_stats));
            update_option("kmcfmf_date_of_today", $now);
            update_option("kmcfmf_messages_blocked_today", 0);
            update_option("kmcfmf_emails_blocked_today", 0);
        }
    }

    public function add_filters()
    {
        add_filter('wpcf7_messages', array($this, 'add_custom_messages'), 10, 1);

        $enable_message_filter = get_option('kmcfmf_message_filter_toggle') == 'on' ? true : false;
        $enable_email_filter = get_option('kmcfmf_email_filter_toggle') == 'on' ? true : false;
        $enable_tags_by_names_filter = get_option('kmcfmf_tags_by_name_filter_toggle') == 'on' ? true : false;

        if ($enable_email_filter) {
            add_filter('wpcf7_validate_email', array($this, 'text_validation_filter'), 12, 2);
            add_filter('wpcf7_validate_email*', array($this, 'text_validation_filter'), 12, 2);
        }

        if ($enable_message_filter) {
            add_filter('wpcf7_validate_textarea', array($this, 'textarea_validation_filter'), 12, 2);
            add_filter('wpcf7_validate_textarea*', array($this, 'textarea_validation_filter'), 12, 2);
        }

        if ($enable_tags_by_names_filter) {
            add_filter('wpcf7_validate_text', array($this, 'text_tags_by_name_validation_filter'), 12, 2);
            add_filter('wpcf7_validate_text*', array($this, 'text_tags_by_name_validation_filter'), 12, 2);
        }

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
     * Filters text from form text elements from elems_names List
     * @author: UnderWordPressure
     * @since 1.2.3
     */
    function text_tags_by_name_validation_filter($result, $tag)
    {

        $name = $tag->name;
        $names = preg_split('/[\s,]+/', get_option('kmcfmf_tags_by_name'));
        if (in_array($name, $names)) {
            $result = $this->textarea_validation_filter($result, $tag);
        }

        return $result;

    }

    /**
     * Filters text from textarea
     * @since 1.0.0
     */
    function textarea_validation_filter($result, $tag)
    {
        $name = $tag->name;

        $found = false;

        // UnderWordPressue: Change explode(" ", $values) to preg_split reason: whole whitespace range AND comma are valid separators
        $check_words = preg_split('/[\s,]+/', get_option('kmcfmf_restricted_words'));

        $message = isset($_POST[$name]) ? trim((string)$_POST[$name]) : '';

        // UnderWordPressue: make all lowercase - safe is safe
        $values = strtolower($message);
        //$value = '';

        // UnderWordPressue: Change explode(" ", $values) to preg_split([white-space]) -  reason: whole whitespace range are valid separators
        //                   and rewrite the foreach loops
        $values = preg_split('/\s+/', $values);
        foreach ($values as $value) {
            $value = trim($value);

            foreach ($check_words as $check_word) {

                /*if (preg_match("/^\.\w+/miu", $value) > 0) {
                    $found = true;
                }else if (preg_match("/\b" . $check_word . "\b/miu", $value) > 0) {
                    $found = true;
                }*/

                $check_word = strtolower(trim($check_word));
                switch ($check_word) {
                    case '':
                        break;
                    case '[russian]':
                        $found = preg_match('/[а-яА-Я]/miu', $value);
                        break;
                    case '[link]':
                        $pattern = '/((ftp|http|https):\/\/\w+)|(www\.\w+\.\w+)/ium'; // filters http://google.com and http://www.google.com and www.google.com
                        $found = preg_match($pattern, $value);
                        break;
                    default:

                        $like_start = (preg_match('/^\*/', $check_word));
                        $like_end = (preg_match('/\*$/', $check_word));

                        # Remove leading and trailing asterisks from $check_word
                        $regex_pattern = preg_quote(trim($check_word, '*'));

                        if ($like_start) {
                            $regex_pattern = '.*' . $regex_pattern;
                        }
                        if ($like_end) {
                            $regex_pattern = $regex_pattern . '+.*';
                        }

                        $found = preg_match('/^' . $regex_pattern . '$/miu', $value);

                        break;
                }

                if ($found) {
                    break 2; // stops the first foreach loop since we have already identified a spam word
                }
            }

        } // end of foreach($values...)


        #####################
        # Final evaluation. #
        #####################

        // Spam word is recognized
        if ($found) {
            $result->invalidate($tag, wpcf7_get_message('spam_word_error'));

            $this->temp_email = $_POST['your-email'];

            if (!$this->count_updated && $this->temp_email != '') {
                $this->update_log($this->temp_email, $message);
            }
        } else {

            // Check additional conditions on $message
            if (empty($message)) {
                // No content ($message) in a required Tag
                if ($tag->is_required()) {
                    $result->invalidate($tag, wpcf7_get_message('invalid_required'));
                }
            } else {

                $maxlength = $tag->get_maxlength_option();
                $minlength = $tag->get_minlength_option();

                if ($maxlength && $minlength && $maxlength < $minlength) {
                    $maxlength = $minlength = null;
                }

                $code_units = wpcf7_count_code_units(stripslashes($message));

                if ($code_units) {
                    if ($maxlength && $maxlength < $code_units) {
                        $result->invalidate($tag, wpcf7_get_message('invalid_too_long'));
                    } elseif ($minlength && $code_units < $minlength) {
                        $result->invalidate($tag, wpcf7_get_message('invalid_too_short'));
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Filters text from text input fields
     * @since 1.0.0
     */
    function text_validation_filter($result, $tag)
    {
        $name = $tag->name;
        $check_words = explode(" ", get_option('kmcfmf_restricted_emails'));

        $value = isset($_POST[$name])
            ? trim(wp_unslash(strtr((string)$_POST[$name], "\n", " ")))
            : '';

        if ('text' == $tag->basetype) {
            if ($tag->is_required() && '' == $value) {
                $result->invalidate($tag, wpcf7_get_message('invalid_required'));
            }
        }

        if ('email' == $tag->basetype) {
            if ($tag->is_required() && '' == $value) {
                $result->invalidate($tag, wpcf7_get_message('invalid_required'));
            } elseif ('' != $value && !wpcf7_is_email($value)) {
                $result->invalidate($tag, wpcf7_get_message('invalid_email'));
            } else {
                foreach ($check_words as $check_word) {
                    if (strpos($value, $check_word) !== false) {
                        $this->temp_message = $_POST['your-message'];
                        $result->invalidate($tag, wpcf7_get_message('spam_email_error'));

                        if (!$this->count_updated && $this->temp_message != '') {
                            $this->update_log($value, $this->temp_message);
                        }
                    }
                }
            }
        }

        if ('' !== $value) {
            $maxlength = $tag->get_maxlength_option();
            $minlength = $tag->get_minlength_option();

            if ($maxlength && $minlength && $maxlength < $minlength) {
                $maxlength = $minlength = null;
            }

            $code_units = wpcf7_count_code_units(stripslashes($value));

            if (false !== $code_units) {
                if ($maxlength && $maxlength < $code_units) {
                    $result->invalidate($tag, wpcf7_get_message('invalid_too_long'));
                } elseif ($minlength && $code_units < $minlength) {
                    $result->invalidate($tag, wpcf7_get_message('invalid_too_short'));
                }
            }
        }

        return $result;
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

    /**
     * Transfer data in old format to new format, when plugin is updated to from an older version to this version
     * @since 1.2.0
     */
    private function transfer_old_data()
    {
        if (get_option('kmcfmf_messages') != '0') {
            $messages = explode("]kmcfmf_message[", get_option('kmcfmf_messages'));
            $log_messages = [];
            for ($i = 0; $i < sizeof($messages); $i++) {
                $data = explode("kmcfmf_data=", $messages[$i]);
                if ($data[1] != '' && $data[2] != '' && $data[3] != '') {
                    $log_message = ['message' => $data[1], 'date' => $data[3], 'email' => $data[2]];
                    array_push($log_messages, $log_message);

                }
            }
            $log_messages = json_encode((object)$log_messages);
            file_put_contents($this->log_file, $log_messages);

            update_option('kmcfmf_messages', 0);
        }
    }

}
