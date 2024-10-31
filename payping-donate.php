<?php
/*
Plugin Name: Payping Donate
Version: 1.4.1
Description:  افزونه دریافت حمایت مالی وردپرس
Plugin URI: https://payping.io/
Author: Hadi Hosseini
Author URI: https://hosseini-dev.ir
License: GPLv3 or later
*/
if (!defined('ABSPATH'))
	exit;
define ('TABLE_DONATE'  , 'payping_donate');
define('PPDonationDIR', plugin_dir_path( __FILE__ ));
define('PPDonationDU', plugin_dir_url( __FILE__ ));
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

// Enqueue your script
function enqueue_my_script() {
    wp_enqueue_script( 'donate-ajax', PPDonationDU . 'assets/js/script.js', array('jquery'), null, true );
    wp_localize_script('donate-ajax', 'payping_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    wp_enqueue_style('default-styles', PPDonationDU . 'assets/css/styles.css');
}
add_action('wp_enqueue_scripts', 'enqueue_my_script');


// Function to conditionally add custom CSS
function payping_donation_add_custom_css() {
    // Check if custom styles are enabled
    $use_custom_style = get_option('payPingDonate_UseCustomStyle');

    if ($use_custom_style === 'true') {
        // Get the custom CSS from the database
        $custom_css = get_option('payPingDonate_CustomStyle');
        
        if (!empty($custom_css)) {
            // Add the custom CSS inline to the default stylesheet
            wp_add_inline_style('default-styles', $custom_css);
        }
    }
}
add_action('wp_enqueue_scripts', 'payping_donation_add_custom_css', 20);

function enqueue_admin_script() {
	wp_enqueue_script( 'donate-ajax', PPDonationDU . 'assets/js/admin-script.js', array('jquery'), null, true );
  }
add_action('admin_enqueue_scripts', 'enqueue_admin_script');

if ( is_admin() ) {
	add_action('admin_menu', 'payPingDonate_AdminMenuItem');
	function payPingDonate_AdminMenuItem()
	{
		add_menu_page( 
		 	'تنظیمات افزونه حمایت مالی - پی‌پینگ',
		 	'حمایت مالی', 
		 	'administrator', 
		 	'payPingDonate_MenuItem', 
		 	'payPingDonate_MainPageHTML', 
		 	'dashicons-plus-alt' 
		);
		add_submenu_page('payPingDonate_MenuItem','نمایش حامیان مالی','نمایش حامیان مالی', 'administrator','payPingDonate_Hamian','payPingDonate_HamianHTML');
	}
}

function payPingDonate_MainPageHTML() {
	include('payPingDonate_AdminPage.php');
}

function payPingDonate_HamianHTML() {
	include('payPingDonate_Hamian.php');
}


add_action( 'init', 'PayPingDonateShortcode');
function PayPingDonateShortcode(){
	add_shortcode('PayPing_Donate', 'PayPingDonateForm');
}
// AJAX Handlers
add_action('wp_ajax_nopriv_process_donation', 'process_donation');
add_action('wp_ajax_process_donation', 'process_donation');

function process_donation() {
    $Token = get_option('payPingDonate_Token');
    $payPingDonate_Unit = get_option('payPingDonate_Unit');
    
    $Amount = isset($_POST['payPingDonate_Amount']) ? sanitize_text_field($_POST['payPingDonate_Amount']) : '';
    $Description = isset($_POST['payPingDonate_Description']) ? sanitize_text_field($_POST['payPingDonate_Description']) : '';
    $Name = isset($_POST['payPingDonate_Name']) ? sanitize_text_field($_POST['payPingDonate_Name']) : '';
    $Mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
    $Email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $returnUrl = isset($_POST['page_url']) ? sanitize_text_field($_POST['page_url']) : '';
    
	function convertPrice($price) {
		$price = str_replace(',', '', $price);
		return $price;
	}
	$Amount = convertPrice($Amount);
    if ($Amount == '' || !is_numeric($Amount)) {
        wp_send_json_error('مبلغ به درستی وارد نشده است');
    }
    if ($Name == '') {
        wp_send_json_error('فیلد نام نمیتواند خالی باشد!');
    }
    if ($payPingDonate_Unit == 'ریال') {
        $SendAmount = $Amount / 10;
    } else {
        $SendAmount = $Amount;
    }

    // Insert the donation record into the database
    global $wpdb;
    $wpdb->insert($wpdb->prefix . TABLE_DONATE, array(
        'Name' => $Name,
        'AmountTomaan' => $SendAmount,
        'Mobile' => $Mobile,
        'Email' => $Email,
        'InputDate' => current_time('mysql'),
        'Description' => $Description,
        'Status' => 'SEND'
    ));
    
    $code = $wpdb->insert_id;

    $data = array(
        'Amount' => $SendAmount,
        'ReturnUrl' => $returnUrl,
        'PayerIdentity' => $Mobile,
        'PayerName' => $Name,
        'Description' => $Description,
        'ClientRefId' => "{$code}",
        'NationalCode' => ''
    );
    $response = wp_remote_post('https://api.payping.ir/v3/pay', array(
        'body' => wp_json_encode($data),
        'headers' => array(
            'X-Platform'         => 'payping-wp-donate',
        	'X-Platform-Version' => '1.4.0',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $Token,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json'
        ),
        'timeout' => 30,
        'redirection' => 10,
    ));
    if (is_wp_error($response)) {
        wp_send_json_error($response->get_error_message());
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        if ($status_code == 200) {
            $response_data = json_decode($response_body, true);
            if (isset($response_data['paymentCode']) && $response_data['paymentCode'] != '') {
                $url = sprintf('https://api.payping.ir/v3/pay/start/%s', $response_data['paymentCode']);
                wp_send_json_success($url);
            } else {
                wp_send_json_error('تراکنش ناموفق بود- شرح خطا : عدم وجود کد ارجاع');
            }
        } elseif ($status_code == 400) {
            $error_data = json_decode($response_body, true);
            wp_send_json_error('تراکنش ناموفق بود- شرح خطا : ' . implode('. ', array_values($error_data)));
        } else {
            wp_send_json_error('تراکنش ناموفق بود- شرح خطا : ' . payPingDonate_GetResaultStatusString($status_code) . '(' . $status_code . ')');
        }
    }
}


function PayPingDonateForm() {
	$out = '';
	$error = '';
	$message = '';

	$Token = get_option( 'payPingDonate_Token');
	$payPingDonate_IsOK = get_option( 'payPingDonate_IsOK');
	$payPingDonate_IsError = get_option( 'payPingDonate_IsError');
	$payPingDonate_Unit = get_option( 'payPingDonate_Unit');

	$Amount = '';
	$Description = '';
	$Name = '';
	$Mobile = '';
	$Email = '';

	


	////////////////////////////////////////////////////
	///             RESPONSE
	if (isset($_REQUEST['status'])) {
		$data = stripslashes($_REQUEST['data']);
		$newData = json_decode($data, true);
        $id = $newData['clientRefId'];
        $status = $_REQUEST['status'];
       
		if ($status == 0) {
            payPingDonate_ChangeStatus($id, 'ERROR');
			$error .= esc_html(get_option('payPingDonate_IsError')) . "<br>\r\n";
            $error .= 'پرداخت توسط کاربر لغو شد!';
			payPingDonate_SetAuthority($id, $status);
        } else {
            $refid = $newData['paymentRefId'];
            $Record = payPingDonate_GetDonate($id);
			$amount = $newData['amount'];
            if ($Record === false) {
                $error .= 'چنین تراکنشی در سایت ثبت نشده است' . "<br>\r\n";
            } else {
                $data = array('paymentRefId' => $refid, 'amount' => $amount);
                try {
                    $response = wp_remote_post('https://api.payping.ir/v3/pay/verify', array(
                        'body'        => wp_json_encode($data),
                        'headers'     => array(
                            'Accept'        => 'application/json',
                            'Authorization' => 'Bearer ' . esc_attr($Token),
                            'Cache-Control' => 'no-cache',
                            'Content-Type'  => 'application/json'
                        ),
                        'timeout'     => 30,
                        'redirection' => 10,
                    ));
                
                    if (is_wp_error($response)) {
                        $error_message = $response->get_error_message();
                        payPingDonate_ChangeStatus($id, 'ERROR');
                        $error .= esc_html(get_option('payPingDonate_IsError')) . "<br>\r\n";
                        $error .= 'خطا در ارتباط به پی‌پینگ : شرح خطا ' . esc_html($error_message) . "<br>\r\n";
                        payPingDonate_SetAuthority($id, $refid);
                    } else {
                        $status_code = wp_remote_retrieve_response_code($response);
                        $response_body = wp_remote_retrieve_body($response);
                
                        if ($status_code == 200) {
                            $response_data = json_decode($response_body, true);
                            if (!empty($refid)) {
                                payPingDonate_ChangeStatus($id, 'OK');
                                payPingDonate_SetAuthority($id, $refid);
                                $message .= esc_html(get_option('payPingDonate_IsOk')) . "<br>\r\n";
                                $message .= 'کد پیگیری تراکنش: ' . esc_html($refid) . "<br>\r\n";
                                $payPingDonate_TotalAmount = get_option("payPingDonate_TotalAmount");
                                update_option("payPingDonate_TotalAmount", $payPingDonate_TotalAmount + $amount);
                            } else {
                                payPingDonate_ChangeStatus($id, 'ERROR');
                                $error .= esc_html(get_option('payPingDonate_IsError')) . "<br>\r\n";
                                payPingDonate_SetAuthority($id, $refid);
                                $error .= 'متاسفانه سامانه قادر به دریافت کد پیگیری نمی باشد! نتیجه درخواست : ' . esc_html(payPingDonate_GetResaultStatusString($status_code)) . '(' . esc_html($status_code) . ')' . "<br>\r\n";
                            }
                        } elseif ($status_code == 400) {
                            payPingDonate_ChangeStatus($id, 'ERROR');
                            $error .= esc_html(get_option('payPingDonate_IsError')) . "<br>\r\n";
                            payPingDonate_SetAuthority($id, $refid);
                            $error .= 'تراکنش ناموفق بود- شرح خطا : ' . esc_html(implode('. ', array_values(json_decode($response_body, true)))) . "<br>\r\n";
                        } else {
                            payPingDonate_ChangeStatus($id, 'ERROR');
                            $error .= esc_html(get_option('payPingDonate_IsError')) . "<br>\r\n";
                            payPingDonate_SetAuthority($id, $refid);
                            $error .= ' تراکنش ناموفق بود- شرح خطا : ' . esc_html(payPingDonate_GetResaultStatusString($status_code)) . '(' . esc_html($status_code) . ')' . "<br>\r\n";
                        }
                    }
                } catch (Exception $e) {
                    payPingDonate_ChangeStatus($id, 'ERROR');
                    $error .= esc_html(get_option('payPingDonate_IsError')) . "<br>\r\n";
                    payPingDonate_SetAuthority($id, $refid);
                    $error .= ' تراکنش ناموفق بود- شرح خطا سمت برنامه شما : ' . esc_html($e->getMessage()) . "<br>\r\n";
                }
            }
        }
    
	
		
	}
	
	///     END RESPONSE
	
    $out = '
        <div id="payPingDonate_MainForm">
            <div id="payPingDonate_Form">';

    if ($message != '') {
        $out .= "<div id=\"payPingDonate_Message\">${message}</div>";
    }

    if ($error != '') {
        $out .= "<div id=\"payPingDonate_Error\">${error}</div>";
    }

    $out .= '<form id="payping-donate-form">
                    
                    <div class="payPingDonate_FormItem">
                        <label class="payPingDonate_FormLabel">نام و نام خانوادگی :</label>
                        <div class="payPingDonate_ItemInput"><input class="payPingDonate_input" type="text" name="payPingDonate_Name" value="' . esc_attr($Name) . '" /></div>
                    </div>
                    <div class="payPingDonate_FormItem">
                        <label class="payPingDonate_FormLabel">مبلغ را به  ' .  esc_html($payPingDonate_Unit) . ' وارد کنید :</label>
                        <div class="payPingDonate_ItemInput">
                            <input id="payPingDonate_Amount" type="text" class="payPingDonate_input" name="payPingDonate_Amount" value="' . esc_attr($Amount) . '" />
                        </div>
                    </div>
                    <div class="payPingDonate_FormItem">
                        <label class="payPingDonate_FormLabel">تلفن همراه :</label>
                        <div class="payPingDonate_ItemInput"><input type="text" class="payPingDonate_input" name="mobile" value="' . esc_attr($Mobile) . '" /></div>
                    </div>
                    <div class="payPingDonate_FormItem">
                        <label class="payPingDonate_FormLabel">ایمیل :</label>
                        <div class="payPingDonate_ItemInput"><input type="text" class="payPingDonate_input" name="email" style="direction:ltr;text-align:left;" value="' . esc_attr($Email) . '" /></div>
                    </div>
                    <div class="payPingDonate_FormItem">
                        <label class="payPingDonate_FormLabel">توضیحات :</label>
                        <div class="payPingDonate_ItemInput"><textarea type="text" class="payPingDonate_input payping_description_input" name="payPingDonate_Description" value="' . esc_attr($Description) . '" /></textarea></div>
                    </div>
                    <div class="payPingDonate_FormItem">
                        <button type="submit" name="submit" value="پرداخت" class="payPingDonate_Submit"><div class="payping-loader"></div> پرداخت</button>
                        
                    </div>
                </form>
            </div>
        </div>
    ';

    return $out;
}

/////////////////////////////////////////////////

register_activation_hook(__FILE__,'payPingDonate_install');
function payPingDonate_install() {
	payPingDonate_CreateDatabaseTables();
}
function payPingDonate_CreateDatabaseTables() {
	global $wpdb;
	$DonateTable = $wpdb->prefix . TABLE_DONATE;
	// Creat table
	$paypingDonate = "CREATE TABLE IF NOT EXISTS $DonateTable (
					  DonateID int(11) NOT NULL AUTO_INCREMENT,
					  Authority varchar(50) NOT NULL,
					  Name varchar(50) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
					  AmountTomaan int(11) NOT NULL,
					  Mobile varchar(11) ,
					  Email varchar(50),
					  InputDate varchar(20),
					  Description varchar(100) CHARACTER SET utf8 COLLATE utf8_persian_ci,
					  Status varchar(5),
					  PRIMARY KEY (DonateID),
					  KEY DonateID (DonateID)
					) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
	dbDelta($paypingDonate);
    
    $style = '';
	// Other Options
	add_option("payPingDonate_TotalAmount", 0, '', 'yes');
	add_option("payPingDonate_TotalPayment", 0, '', 'yes');
	add_option("payPingDonate_IsOK", 'با تشکر پرداخت شما به درستی انجام شد.', '', 'yes');
	add_option("payPingDonate_IsError", 'متاسفانه پرداخت انجام نشد.', '', 'yes');


	add_option("payPingDonate_CustomStyle", $style, '', 'yes');
	add_option("payPingDonate_UseCustomStyle", 'false', '', 'yes');
}

function payPingDonate_GetDonate($id)
{
    global $wpdb;
    $id = wp_strip_all_tags($id);
    if ($id == '') {
        return false;
    }
    $DonateTable = $wpdb->prefix . TABLE_DONATE;
    $query = $wpdb->prepare("SELECT * FROM {$DonateTable} WHERE DonateID = %d LIMIT 1", $id);
    $res = $wpdb->get_results($query, ARRAY_A);
    if (count($res) == 0) {
        return false;
    }

    return $res[0];
}


function payPingDonate_AddDonate($data, $format)
{
    global $wpdb;
    if (!is_array($data)) {
        return false;
    }
    $data = array_map('wp_strip_all_tags', $data);
    $DonateTable = $wpdb->prefix . TABLE_DONATE;
    $res = $wpdb->insert($DonateTable, $data, $format);
    if ($res === false) {
        return false;
    }
    $totalPay = get_option('payPingDonate_TotalPayment');
    $totalPay += 1;
    update_option('payPingDonate_TotalPayment', $totalPay);
    return $wpdb->insert_id;
}


function payPingDonate_ChangeStatus($id, $status)
{
    global $wpdb;
    $id = wp_strip_all_tags($id);
    $status = wp_strip_all_tags($status);
    if ($id == '' || $status == '') {
        return false;
    }
    $DonateTable = $wpdb->prefix . TABLE_DONATE;
    $query = $wpdb->prepare("UPDATE {$DonateTable} SET Status = %s WHERE DonateID = %d", $status, $id);
    $res = $wpdb->query($query);
    return $res;
}
function payPingDonate_SetAuthority($id, $authority)
{
    global $wpdb;
    $id = wp_strip_all_tags($id);
    $authority = wp_strip_all_tags($authority);
    if ($id == '' || $authority == '') {
        return false;
    }
    $DonateTable = $wpdb->prefix . TABLE_DONATE;
    $query = $wpdb->prepare("UPDATE {$DonateTable} SET Authority = %s WHERE DonateID = %d", $authority, $id);
    $res = $wpdb->query($query);
    return $res;
}


function payPingDonate_GetResaultStatusString($StatusNumber)
{
	switch($StatusNumber) {
		case 200 :
			return 'عملیات با موفقیت انجام شد';
			break ;
		case 400 :
			return 'مشکلی در ارسال درخواست وجود دارد';
			break ;
		case 500 :
			return 'مشکلی در سرور رخ داده است';
			break;
		case 503 :
			return 'سرور در حال حاضر قادر به پاسخگویی نمی‌باشد';
			break;
		case 401 :
			return 'عدم دسترسی';
			break;
		case 403 :
			return 'دسترسی غیر مجاز';
			break;
		case 404 :
			return 'آیتم درخواستی مورد نظر موجود نمی‌باشد';
			break;
	}

	return '';
}

function payPingDonate_GetCallBackURL()
{
    
    $https = filter_input(INPUT_SERVER, 'HTTPS', FILTER_SANITIZE_STRING);
    $serverName = htmlspecialchars(filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING), ENT_QUOTES, 'UTF-8');
    $serverPort = filter_input(INPUT_SERVER, 'SERVER_PORT', FILTER_SANITIZE_NUMBER_INT);
    $requestUri = htmlspecialchars(filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING), ENT_QUOTES, 'UTF-8');

    
    $pageURL = ($https == 'on') ? 'https://' : 'http://';

    if ($serverPort != '80' && $serverPort != '443') {
        $pageURL .= $serverName . ':' . $serverPort . $requestUri;
    } else {
        $pageURL .= $serverName . $requestUri;
    }
	
	
    return $pageURL;
	
}


?>