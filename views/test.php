<?php

namespace kmcf7_sms_extension;
?>
    <h1>Send a test SMS</h1>
    <hr>

    <form action="" method="post">
        <label for="">
            Send SMS to: <input type="tel" name="smsto" placeholder="+237670224092" required autocomplete="off">
        </label>
        <button style="background:green; color:white; padding: 7px 10px; border:none;border-radius:5px;">Send SMS
        </button>
    </form>

    <?php
if (isset($_POST['smsto'])) {
    $instance = CF7SmsExtension::getInstance();
    if (!$instance->sendSMS($_POST['smsto'], 'This is a test message from the Contact Form 7 SMS Extension Plugin')) {
        ?>
        <div style="border:solid red 2px; width: 400px; padding:7px 10px;margin-top:10px;">
            <h3>Your message was not sent !!! </h3>
            <?php $error = json_decode(get_option('km_error_message')); ?>
            <table>
                <tr>
                    <td><b>Error Code:</b></td>
                    <td><?php echo $error->code ?? '' ?></td>
                </tr>
                <tr>
                    <td><b>Message:</b></td>
                    <td><?php echo $error->message ?? get_option('km_error_message') ?> </td>
                </tr>
                <tr>
                    <td><b>More Info:</b></td>
                    <td><?php echo $error->more_info ?? '' ?></td>
                </tr>
            </table>
        </div>
        <?php
        delete_option('km_error');
        delete_option('km_error_message');

    } else {
        ?>
        <div style="border:solid green 2px; width: 340px; padding:7px 10px;margin-top:10px;text-align:center;">
            Your message has been sent
        </div>
    <?php }
}
?>