<?php
// ------------------------------------
//
// Accounts Template Tags
//
// ------------------------------------


// ------------------------------------
// Account Content
// ------------------------------------

function the_account(){

	global $wpmember_accounts;
	global $current_user;

    get_currentuserinfo();

	$wpmember_accounts->render_content();

}

// ------------------------------------
// The Navigation
// ------------------------------------

function the_account_navigation(){

	global $wpmember_accounts;
	$wpmember_accounts->render_navigation();

}

// ------------------------------------
// Messages
// ------------------------------------

function get_accounts_message(){

	global $wpmember_accounts;
	if(!empty($wpmember_accounts->message)){
		return $wpmember_accounts->message;
	}
	else {
		return false;
	}
}

function the_accounts_message(){
	$message = get_accounts_message();
	if(!empty($message)){
		echo "<div class=\"accoutns-message message {$message['status']}\">{$message['message']}</div>";
	}
}

// ------------------------------------
// Notifications
// ------------------------------------

function the_accounts_notifications(){

	global $wpmember_accounts;
	$notifications = $wpmember_accounts->get_notifications(array());
	$wpmember_accounts->template_include('inline-notifications.php',$notifications,'notifications');

}

// ------------------------------------
// Get Email From Key
// ------------------------------------

function get_account_from_activation_key($key){

	$user = get_users(array(
		'meta_key' => 'activation_key',
		'meta_value' => $key,
		'number' => 1,
		'count_total' => false
	));

	return $user[0];

}

?>