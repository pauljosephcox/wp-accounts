<?php
/**
 * WPMember User
 * ------------------------------
 * All user interaction related methods.
 * - Account Creation Pages
 * - Account Edit Pages
 * - Password Reset
 * - Activation
 */

namespace WPMember_Accounts;

class User {

	/**
	 * Constructor
	 */

	function __construct(){

		// Authenticate Users
		add_filter('authenticate', array($this,'validate_active_users'), 99);
		add_action('after_setup_theme', array($this,'remove_admin_bar'));

		// Default User Interaction Templates

		// Create Account Page
		add_action('accounts_the_content_create', function(){ Utils::template_include('new-account.php'); });

		// Activate Account Page
		add_action('accounts_the_content_activate', function(){ Utils::template_include('activate.php'); });

		// Password Reset Page
		add_action('accounts_the_content_lostpassword', function(){ Utils::template_include('lost-password.php'); }, 4);
		add_action('accounts_the_content_passwordreset', function(){ Utils::template_include('password-reset.php'); });


	}

	/**
	 * Remove Admin Bar
	 * Remove the ability for a user with the type Member to see anything related to wordpress.
	 * @return null
	 */

	public function remove_admin_bar() {

		if (current_user_can('member')) { show_admin_bar(false); }

	}


	/**
	 * Validate Active Users on Login
	 * @param object $user
	 * @param string $username
	 * @param string $password
	 * @return object|null
	 */

	public function validate_active_users( $user, $username = null, $password = null){

		if($_POST['accounts_action'] == 'accounts_activate_account'){ return $user; }

		if(is_wp_error($user)) return false;
		
		if(array_intersect(array('administrator','editor','author','contributor'), $user->roles )) return $user;

		$status = get_user_meta($user->ID,'activated',true);

		if($status != 'active'){
			Utils::redirect('/account/?msg=notactive');
			return false;
		} else {
			return $user;
		}

	}

	/**
	 * Create Account
	 * Create a new user account
	 * @param array $vars $_POST variables
	 * @return null
	 */

   	public function create($vars) {

   		$userdata = array(
		    'user_login'  => $vars['account']['email'],
		    'user_pass'   => $vars['account']['password'],
		    'first_name'  => $vars['account']['first_name'],
		    'last_name'   => $vars['account']['last_name'],
		    'user_email'  => $vars['account']['email'],
		    'role'        => 'member'
		);

		$current_url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

		if(email_exists($vars['account']['email']) != true){

			$user_id = wp_insert_user( $userdata ) ;

		} else {

			// User Exists Check if Activated
			$user_id = email_exists($vars['account']['email']);

			if (get_user_meta($user_id, 'activated', true) != 'active') {

				// Reset Their Password to the one they have just enter.
				if($vars['account']['password']) wp_set_password($vars['account']['password'],$user_id);

				// Send Activation Email
				$this->send_activation_email($user_id,$vars['account']['email']);

				// Redirect To Success
				Utils::redirect('/account?msg=newaccountsuccess');

			} else {

				Utils::redirect($current_url.'?msg=newaccountexists');

			}
		}

		if(!is_wp_error($user_id)){

			// Set Status
			update_user_meta($user_id,'activated','not-active');

			// Send Activation Email
			$this->send_activation_email($user_id,$vars['account']['email']);

			// Run Actions
			$user_id = apply_filters('accounts_create_account_success',$user_id);

			// Redirect To Success
			Utils::redirect('/account?msg=newaccountsuccess');

		} else {

			// Run Actions
			$vars = apply_filters('accounts_create_account_fail',$vars);
			Utils::redirect($current_url.'?msg=newaccounterror');

		}

	}

	/**
	 * Edit Account Save
	 * Save the default fields
	 * @param array $vars  $_POST vars
	 * @return null
	 */

	public function save($vars) {

		$user_id = get_current_user_id();

		// Data Mapping
		$args = array();
		$args['ID'] = $user_id;
		$args['first_name'] = $vars['account']['first_name'];
		$args['last_name']  = $vars['account']['last_name'];
		$args['user_email'] = $vars['account']['email'];

		// Validate Email Against Other Wordpress Users
		$valid_email = Utils::validate_email_update($vars['account']['email']);

		if ($valid_email != true) {

			$message['status'] = 'bad';
			$message['message'] = "This email address is already taken.";
			return $message;
		}

		// Update User
		$user_id = wp_update_user( $args );
		if(is_wp_error($user_id)){

			$vars = apply_filters('accounts_edit_account_save_fail',$vars);
			Utils::redirect('/account?msg=editaccounterror');

		} else {

			$vars = apply_filters('accounts_edit_account_save_success',$vars);
			Utils::redirect('/account?msg=editaccountsuccess');
		}

	}

	/**
	 * Send Activation EMail
	 * @param int $user_id 
	 * @param string $user_email 
	 * @return null
	 */

	public function send_activation_email($user_id, $user_email){

		$email = array();
		$email['subject'] = "Active your account";
		$email['body']    = "Activate your account by clicking the link below.<br>[activationlink]";
		$email['to']      = $user_email;

		// Allow Overrides
		$email['subject'] = apply_filters('accounts_activation_email_subject',$email['subject']);
		$email['body']    = apply_filters('accounts_activation_email_body',$email['body']);

		$replacements = array();
		$replacements['[activationlink]'] = $this->create_activation_link($user_id);

		return Utils::email($email['to'], $email['subject'], $email['body'], $replacements);


	}

	/**
	 * Create an Activation Link
	 * @param int $user_id 
	 * @return string
	 */

	public function create_activation_link($user_id){

		$key = uniqid();

		$current_key = get_user_meta($user_id,'activation_key',true);

		if(!$current_key) update_user_meta($user_id,'activation_key',$key);
		else $key = $current_key;

		$url = site_url().'/account/activate/?key='.$key;
		return $url;

	}

	/**
	 * Activate
	 * Activate the user account
	 * @param array $vars $_POST
	 * @return null
	 */

	public function activate($vars){

		$creds = array();
		$creds['user_login'] = $vars['account']['email'];
		$creds['user_password'] = $vars['account']['password'];
		$creds['remember'] = true;
		$user = wp_signon( $creds, false );
		if ( is_wp_error($user) ){

			if(strstr($user->get_error_message(), 'Lost your password')){
				$e = 'invalidpassword';
			} else {
				$e = 'activationfail';
			}

			// Run Actions
			$vars = apply_filters('accounts_active_account_fail',$vars);

			Utils::redirect('/account/activate/?key='.$vars['account']['key'].'&msg='.$e);

		} else {

			// Update Activated
			update_user_meta($user->ID,'activated','active');

			// Run Actions
			$vars = apply_filters('accounts_active_account_success',$vars);

			// Redirect to account
			Utils::redirect('/account');

		}

	}

	/**
	 * Request A Password Reset
	 * @param array $vars $_POST
	 * @return null
	 */

	public function request_password_reset($vars){

   		// Get User By Email
   		$user = get_user_by('email',$vars['account']['email']);

   		// Create A Reset Link
   		$key = uniqid();
   		$link = site_url().'/account/passwordreset?key='.$key;
   		update_user_meta($user->ID,'password_reset_token',$key);

   		// Email Token
   		$email = array();
		$email['subject'] = "Reset Password Link";
		$email['body']    = "<p>Hi there,</p>

		<p>You have requested to reset the password to your ". get_bloginfo('name') ." account. To reset your password, please click on the link below.</p>

		<p><strong><a href='$link'>Reset Your Password</a></strong></p>

		<p>Thank you.</p>";
		$email['to']      = $user->data->user_email;
		Utils::email($email['to'], $email['subject'], $email['body'], $replacements);

		// Run Actions
		$vars = apply_filters('accounts_request_lost_password',$vars);

		// Redirect
		Utils::redirect('/account?msg=passwordresetsent');

   	}

   	/**
   	 * Reset Password
   	 * @param array $vars 
   	 * @return null
   	 */

	public function reset_password($vars){

   		// Get User
   		$args = array();
   		$args['meta_key'] = 'password_reset_token';
   		$args['meta_value'] = $vars['token'];
   		$users = get_users($args);

   		// Guard
   		if(!$users[0]) Utils::redirect('/account/passwordreset/?key='.$vars['token'].'&msg=passwordresetfail');
   		
   		// Set The User
   		$user = $users[0];

   		if($vars['account']['password'] == $vars['account']['confirmpassword']){

			wp_set_password( $vars['account']['password'], $user->ID );

			delete_user_meta($user->ID,'password_reset_token');

			// Run Actions
			$vars = apply_filters('accounts_reset_account_password_success',$vars);

			// Redirect
			Utils::redirect('/account/?msg=passwordresetsuccess');

		} else {

			// Run Actions
			$vars = apply_filters('accounts_reset_account_password_fail',$vars);

			// Redirect
			Utils::redirect('/account/passwordreset/?key='.$vars['token'].'&msg=passwordresetfail1');

		}

   	}
}
?>