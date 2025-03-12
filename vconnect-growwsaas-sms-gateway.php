<?php
/**
 * Plugin Name: WooCommerce SMS OTP Login - Vouch GrowwSaaS
 * Plugin URI: https://codenskills.com/
 * Description: Enables SMS OTP login for WooCommerce customers using Vouch GrowwSaaS API.
 * Version: 1.0
 * Author: Yashvir Pal
 * Author URI: https://yashvirpal.com/
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woocommerce-sms-otp-login
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

//  Add Admin Settings Page for Vouch API
function vouch_sms_settings_page() {
    add_menu_page(
        'Vouch SMS Settings',
        'Vouch SMS',
        'manage_options',
        'vouch-sms-settings',
        'vouch_sms_settings_callback'
    );
}
add_action('admin_menu', 'vouch_sms_settings_page');

function vouch_sms_settings_callback() {
    ?>
    <div class="wrap">
        <h2>Vouch SMS API Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('vouch_sms_settings_group');
            do_settings_sections('vouch_sms_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function vouch_sms_register_settings() {
    register_setting('vouch_sms_settings_group', 'vouch_sender');
    register_setting('vouch_sms_settings_group', 'vouch_user_id');
    register_setting('vouch_sms_settings_group', 'vouch_password');
    register_setting('vouch_sms_settings_group', 'vouch_sender_template_id');

    add_settings_section('vouch_sms_main_section', '', null, 'vouch_sms_settings');

    add_settings_field('vouch_sender', 'Vouch API Key', 'vouch_sender_callback', 'vouch_sms_settings', 'vouch_sms_main_section');
    add_settings_field('vouch_user_id', 'User ID', 'vouch_user_id_callback', 'vouch_sms_settings', 'vouch_sms_main_section');
    add_settings_field('vouch_password', 'Password', 'vouch_password_callback', 'vouch_sms_settings', 'vouch_sms_main_section');
    add_settings_field('vouch_sender_template_id', 'Sender Template ID', 'vouch_sender_template_id_callback', 'vouch_sms_settings', 'vouch_sms_main_section');
}

// Callbacks to render input fields
function vouch_sender_callback() {
    $value = get_option('vouch_sender');
    echo '<input type="text" name="vouch_sender" value="' . esc_attr($value) . '" />';
}

function vouch_user_id_callback() {
    $value = get_option('vouch_user_id');
    echo '<input type="text" name="vouch_user_id" value="' . esc_attr($value) . '" />';
}

function vouch_password_callback() {
    $value = get_option('vouch_password');
    echo '<input type="password" name="vouch_password" value="' . esc_attr($value) . '" />';
}

function vouch_sender_template_id_callback() {
    $value = get_option('vouch_sender_template_id');
    echo '<input type="text" name="vouch_sender_template_id" value="' . esc_attr($value) . '" />';
}

add_action('admin_init', 'vouch_sms_register_settings');

// Add OTP Login Form in WooCommerce
function vouch_sms_login_form() {
    if (is_user_logged_in()) return;
    ?>
    <style>
        .otp-section{
            display: flex;
            justify-content: space-between;
        }
        @media (max-width: 900px) {
            .otp_btn {
                font-size: 12px;
                padding: 0.35em 1.0em;
            }
        }
    </style>
    <div style="text-align: center; margin-top: 10px;">
        <p><strong><?php esc_html_e('Or login with OTP', 'woocommerce'); ?></strong></p>
    </div>

    <!-- OTP Login Fields -->
    <!--<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">-->
        <label for="vouch_phone"><?php esc_html_e('Phone Number (for OTP login)', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
        <div class="otp-section">
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" id="vouch_phone" name="phone" placeholder="phone" style="width:70%" required />
        <button type="button" class="button otp_btn" id="send_otp"><?php esc_html_e('Send OTP', 'woocommerce'); ?></button>
        </div>
    <!--</p>-->

    <!--<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">-->
        <div class="otp-section">
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" id="vouch_otp" name="otp" placeholder="Enter OTP" required style="width:70%;display: none;" />
        <button type="button" class="button otp_btn" id="verify_otp" style="display: none;"><?php esc_html_e('Verify OTP', 'woocommerce'); ?></button>
        </div>
        <p id="otp_message">&nbsp;</p>
    <!--</p>-->

    <script>
    jQuery(document).ready(function($) {
        $('#send_otp').click(function() {
            var phone = $('#vouch_phone').val();
            if (phone === '') {
                $('#otp_message').text('Please enter your phone number.');
                return;
            }

            $.post('<?php echo admin_url("admin-ajax.php"); ?>', 
                { action: 'vouch_send_otp', phone: phone }, 
                function(response) {
                    $('#otp_message').text(response.message);
                    if(response.success) {
                        $('#vouch_otp, #verify_otp').show();
                    }
                }
            );
        });

        $('#verify_otp').click(function() {
            var phone = $('#vouch_phone').val();
            var otp = $('#vouch_otp').val();
            if (otp === '') {
                $('#otp_message').text('Please enter the OTP.');
                return;
            }

            $.post('<?php echo admin_url("admin-ajax.php"); ?>', 
                { action: 'vouch_verify_otp', phone: phone, otp: otp }, 
                function(response) {
                    if(response.success) {
                        window.location.reload();
                    } else {
                        $('#otp_message').text(response.message);
                    }
                }
            );
        });
    });
    </script>
    <?php
}
add_action('woocommerce_login_form_end', 'vouch_sms_login_form');


// Send OTP via Vouch API
function vouch_send_otp() {
    // Validate Phone Number
    if (!isset($_POST['phone']) || empty($_POST['phone'])) {
        wp_send_json(['success' => false, 'message' => 'Phone number is required.']);
    }

    $phone = sanitize_text_field($_POST['phone']);

    // Check if phone number is registered in WooCommerce
    $users = get_users([
        'meta_key'   => 'billing_phone',
        'meta_value' => $phone,
        'number'     => 1,
    ]);

    if (empty($users)) {
        wp_send_json(['success' => false, 'message' => 'This phone number is not registered.']);
    }

    // Get User ID (first matching user)
    $user = $users[0];
    $user_id = $user->ID;

    // Verify if the user is active and has an email (optional)
    if (!get_user_by('ID', $user_id)) {
        wp_send_json(['success' => false, 'message' => 'User account not found.']);
    }

    // Call the function to send OTP using the user's phone number
    $response = send_update_otp_via_curl($phone, "sent");

    // Handle API Response
    if (!$response['success']) {
        wp_send_json(['success' => false, 'message' => $response['message']]);
    }
    wp_send_json(['success' => true, 'message' => 'OTP sent successfully!']);
}
add_action('wp_ajax_nopriv_vouch_send_otp', 'vouch_send_otp');


//  Verify OTP and Log In
function vouch_verify_otp() {
    // Validate Input Fields
    if (!isset($_POST['phone']) || !isset($_POST['otp']) || empty($_POST['phone']) || empty($_POST['otp'])) {
        wp_send_json(['success' => false, 'message' => 'Phone number and OTP are required.']);
    }

    $phone = sanitize_text_field($_POST['phone']);
    $entered_otp = sanitize_text_field($_POST['otp']);

    // Call OTP verification API
    $response = send_update_otp_via_curl($phone, 'verify', $entered_otp);

    //$response = json_decode($response, true);
    // Check API response
   if (!$response['success'] || !isset($response['response']['stcode']) || $response['response']['stcode'] !== "200") {
        wp_send_json(['success' => false, 'message' => 'Invalid OTP. Please try again.', 'response' => $response]);
    }
    
    

    // OTP Verified - Check if user exists
    $users = get_users([
        'meta_key'   => 'billing_phone',
        'meta_value' => $phone,
        'number'     => 1,
    ]);

    if (empty($users)) {
        // Create new user if not registered
        $user_email = $phone . "@example.com"; // Placeholder email for registration
        if (email_exists($user_email)) {
            wp_send_json(['success' => false, 'message' => 'A user with this email already exists.']);
        }

        $user_id = wp_create_user($phone, wp_generate_password(), $user_email);
        if (is_wp_error($user_id)) {
            wp_send_json(['success' => false, 'message' => 'Error creating user.']);
        }

        update_user_meta($user_id, 'billing_phone', $phone);
        $user = get_user_by('ID', $user_id);
    } else {
        // Use existing user
        $user = $users[0];
    }

    // Log in user
    wp_set_auth_cookie($user->ID);
    wp_send_json(['success' => true, 'message' => 'Login successful!']);
}
add_action('wp_ajax_nopriv_vouch_verify_otp', 'vouch_verify_otp');


/* Registration  */

function custom_woocommerce_registration_fields() {
    ?>
     <style>
        .otp-section{
            display: flex;
            justify-content: space-between;
        }
        @media (max-width: 768px) {
            .otp_btn {
                font-size: 12px;
                padding: 0.35em 1.0em;
            }
        }
    </style>
    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_first_name"><?php esc_html_e('First Name', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="first_name" id="reg_first_name" value="<?php echo esc_attr($_POST['first_name'] ?? ''); ?>" required />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_last_name"><?php esc_html_e('Last Name', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="last_name" id="reg_last_name" value="<?php echo esc_attr($_POST['last_name'] ?? ''); ?>" required />
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <label for="reg_phone"><?php esc_html_e('Mobile Number', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
        <div class="otp-section">
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="billing_phone" id="reg_phone" style="width:70%" value="<?php echo esc_attr($_POST['billing_phone'] ?? ''); ?>" required />
        <button type="button" id="send_otp_reg" class="button otp_btn"><?php esc_html_e('Send OTP', 'woocommerce'); ?></button>
        </div>
        <p id="otp_message_reg"></p>
    </p>

    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
        <div class="otp-section">
        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="reg_otp" id="reg_otp" style="width:70%;display: none" placeholder="Enter OTP" required  />
        <button type="button" id="verify_otp_reg" class="button otp_btn" style="display: none;"><?php esc_html_e('Verify OTP', 'woocommerce'); ?></button>
        </div>
    </p>

    <input type="hidden" name="otp_verified" id="otp_verified" value="0">

    <script>
    jQuery(document).ready(function($) {
        $('.woocommerce-form-register__submit').css('display','none');
        $('#send_otp_reg').click(function() {
            var phone = $('#reg_phone').val();
            $.post('<?php echo admin_url("admin-ajax.php"); ?>', { action: 'send_otp', phone: phone }, function(response) {
                $('#otp_message_reg').text(response.message);
                if(response.success) {
                    $('#reg_otp, #verify_otp_reg').show();
                }
            });
        });

        $('#verify_otp_reg').click(function() {
            var phone = $('#reg_phone').val();
            var otp = $('#reg_otp').val();
            $.post('<?php echo admin_url("admin-ajax.php"); ?>', { action: 'verify_otp', phone: phone, otp: otp }, function(response) {
                $('.woocommerce-form-register__submit').css('display','none');
                if(response.success) {
                    $('.woocommerce-form-register__submit').css('display','block');
                    $('#otp_message_reg').text(response.message);
                    $('#otp_verified').val('1');
                } else {
                    $('#otp_message_reg').text(response.message);
                }
            });
        });
    });
    </script>
    <?php
}
add_action('woocommerce_register_form_start', 'custom_woocommerce_registration_fields');
function validate_otp_during_registration($errors, $username, $email) {
    if (empty($_POST['otp_verified']) || $_POST['otp_verified'] !== '1') {
        $errors->add('registration_error', __('Please verify your mobile number before registering.', 'woocommerce'));
    }
    return $errors;
}
add_filter('woocommerce_registration_errors', 'validate_otp_during_registration', 10, 3);
function save_mobile_number_after_registration($customer_id) {
    if (isset($_POST['billing_phone'])) {
        update_user_meta($customer_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
    }
}
add_action('woocommerce_created_customer', 'save_mobile_number_after_registration');

function send_otp_via_ajax() {
    if (!isset($_POST['phone'])) {
        wp_send_json(['success' => false, 'message' => 'Phone number is required.']);
    }

    $phone = sanitize_text_field($_POST['phone']);
   
    // Call your API to send OTP (replace this with actual API integration)
    $response = send_update_otp_via_curl($phone, 'sent');

     // Handle API Response
    if (!$response['success']) {
        wp_send_json(['success' => false, 'message' => $response['message']]);
    }
    wp_send_json(['success' => true, 'message' => 'OTP sent successfully!','response'=>$response]);
}
add_action('wp_ajax_send_otp', 'send_otp_via_ajax');
add_action('wp_ajax_nopriv_send_otp', 'send_otp_via_ajax');
function verify_otp_via_ajax() {
    if (!isset($_POST['phone']) || !isset($_POST['otp'])) {
        wp_send_json(['success' => false, 'message' => 'Phone and OTP are required.']);
    }

    $phone = sanitize_text_field($_POST['phone']);
    $entered_otp = sanitize_text_field($_POST['otp']);
    // Call your API to send OTP (replace this with actual API integration)
     $response = send_update_otp_via_curl($phone, 'verify', $entered_otp);

    //$response = json_decode($response, true);
    // Check API response
   if (!$response['success'] || !isset($response['response']['stcode']) || $response['response']['stcode'] !== "200") {
        wp_send_json(['success' => false, 'message' => 'Invalid OTP. Please try again.', 'response' => $response]);
    }
    
    // OTP is verified successfully
    wp_send_json(['success' => true, 'message' => 'OTP Verified!', 'response' => $response]);
}
add_action('wp_ajax_verify_otp', 'verify_otp_via_ajax');
add_action('wp_ajax_nopriv_verify_otp', 'verify_otp_via_ajax');






function send_update_otp_via_curl($mobile, $type, $otp = "") {
    // Validate API Credentials
    $sender       = get_option('vouch_sender');
    $user_id      = get_option('vouch_user_id');
    $password     = get_option('vouch_password');
    $template_id  = get_option('vouch_sender_template_id');

    if (empty($sender) || empty($user_id) || empty($password) || empty($template_id)) {
        return ['success' => false, 'message' => 'API credentials are missing. Please check settings.'];
    }

    // Set API URL based on type
    if ($type === 'sent') {
        $url = "https://vouch.growwsaas.com/api/genOTP";
    } elseif ($type === 'verify') {
        $url = "https://vouch.growwsaas.com/api/vldtOTP";
    } else {
        return ['success' => false, 'message' => 'Invalid OTP request type. Use "sent" or "verify".'];
    }

    // Prepare API payload
    $data = [
        "uid"  => $user_id,
        "pwd"  => $password,
        "sn"   => $sender,
        "mno"  => (string) $mobile, 
        "tid"  => (string) $template_id 
    ];

    // If type is "verify", include the OTP parameter
    if ($type === 'verify' && !empty($otp)) {
        $data['otp'] = $otp;
    }

    // Initialize cURL request
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Execute request and get response
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Handle cURL errors
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['success' => false, 'message' => "cURL Error: $error_msg"];
    }

    curl_close($ch);

    return [
        'success'   => $http_code === 200, // Check if request was successful
        'http_code' => $http_code,
        'response'  => json_decode($response, true) // Decode JSON response
    ];
}


// $url = "https://vouch.growwsaas.com/api/genOTP";
// $data = [
//     "uid"  => "Ginnora.otp",
//     "pwd"  => "xO3hHpN6",
//     "sn"   => "ZUPEIN",
//     "mno"  => "8756672297", // Ensure this is a string
//     "tid"  => "1707174072369867944" 
//     //"tid"  => "1701173495020546215" // Ensure this is a string if required
// ];

// $ch = curl_init($url);

// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);
// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     "Content-Type: application/json"
// ]);
// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// $response = curl_exec($ch);
// $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// if (curl_errno($ch)) {
//     echo "cURL Error: " . curl_error($ch);
// }

// curl_close($ch);

// echo "HTTP Code: " . $http_code . "\n";
// print_r($response);
