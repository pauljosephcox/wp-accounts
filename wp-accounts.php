<?php
/**
 * @wordpress-plugin
 * Plugin Name: WP Member Accounts
 * Description: Basic User Accounts Management Plugin
 * Author: Paul Joseph Cox
 * Version: 2.0
 * Author URI: http://pauljosephcox.com/
 */


namespace WPMember_Accounts; 


if (!defined('ABSPATH')) exit;

// Dependencies
require_once('classes/utilities.class.php');


// Activation Hook
register_activation_hook( __FILE__, array( 'WPMember_Accounts', 'install' ) );





/**
 * Main WPMember_Accounts Class
 *
 * @class WPMember_Accounts
 * @version 2.0
 */

class Accounts {

	public $errors = false;
	public $notices = false;
	public $slug = 'accounts';

	function __construct() {

		$this->path = plugin_dir_path(__FILE__);
		$this->folder = basename($this->path);
		$this->dir = plugin_dir_url(__FILE__);
		$this->version = '2.0';
		$this->sections = array();
		$this->message = false;

		// Example Section
		// $this->sections[0]['href'] = '/account/#details';
		// $this->sections[0]['text'] = 'My Account Details';

		

		// Actions
		add_action('init', array($this, 'check_for_messages'), 10, 0);
		add_action('wp_loaded', array($this , 'forms'));
		add_action('parse_request', array($this , 'router'));
		add_action('after_setup_theme', array($this,'remove_admin_bar'));


		// Template Rewrite
		add_action( 'init', array($this,'url_rewrite_rules'));
		add_filter( 'query_vars', array($this,'register_query_vars'));
		add_action( 'template_redirect', array($this,'url_rewrite_templates'));

		// Add Link to Logout
		add_filter( 'wp_nav_menu_items', array($this,'add_logout_link'), 10, 2);

		// Validate Users
		add_filter( 'authenticate', array($this,'validate_active_users'), 99);

		// Default Admin Sections
		add_filter( 'accounts_sections', array($this,'add_default_sections'),1);
		add_action( 'accounts_the_content_dashboard', function(){ Utils::template_include('edit-account.php'); }, 3);

		// Create Account Page
		add_action( 'accounts_the_content_create', function(){ Utils::template_include('new-account.php'); });

		// Activate Account Page
		add_action( 'accounts_the_content_activate', function(){ Utils::template_include('activate.php'); });

		// Password Reset Page
		add_action( 'accounts_the_content_lostpassword', function(){ Utils::template_include('lost-password.php'); }, 4);
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );
		add_action( 'accounts_the_content_passwordreset', function(){ Utils::template_include('password-reset.php'); });

		// Overwrites
		add_action( 'wp_login_failed', array($this, 'login_redirect') );

		// Default List of Errors (Override using filters)
		$this->message_list = array();
		$this->message_list['editaccounterror']['status'] = 'bad';
		$this->message_list['editaccounterror']['message'] = "Sorry, something went wrong with the update. Please try again.";
		$this->message_list['editaccountsuccess']['status'] = 'good';
		$this->message_list['editaccountsuccess']['message'] = "Thanks! We've updated your details.";
		$this->message_list['newaccountsuccess']['status'] = 'good';
		$this->message_list['newaccountsuccess']['message'] = "Thanks! We've just sent you an email to activate your account.";
		$this->message_list['newaccountexists']['status'] = 'bad';
		$this->message_list['newaccountexists']['message'] = "The username or email is already in use. Please try a different username or email.";
		$this->message_list['newaccounterror']['status'] = 'bad';
		$this->message_list['newaccounterror']['message'] = "Sorry, something went wrong creating your account. Please try again.";
		$this->message_list['invalidpassword']['status'] = 'bad';
		$this->message_list['invalidpassword']['message'] = 'The username or password entered didn\'t match our records. Please check and try again.';
		$this->message_list['activationfail']['status'] = 'bad';
		$this->message_list['activationfail']['message'] = 'Sorry, something went wrong with activation. Please try again.';
		$this->message_list['passwordresetfail']['status'] = 'bad';
		$this->message_list['passwordresetfail']['message'] = 'The passwords entered don\'t match. Please try again.';
		$this->message_list['notactive']['status'] = 'bad';
		$this->message_list['notactive']['message'] = "Your account hasn't been activated yet, please check your email for your activation link. Can't find it? Register to resend your activation link.";
		$this->message_list['editrecurringsuccess']['status'] = 'good';
		$this->message_list['editrecurringsuccess']['message'] = "Thanks! We've updated your regular donation.";
		$this->message_list['emailpreferencesuccess']['status'] = 'good';
		$this->message_list['emailpreferencesuccess']['message'] = "Thanks! Your email preferences have been saved.";
		$this->message_list['passwordresetsent']['status'] = 'good';
		$this->message_list['passwordresetsent']['message'] = "Check your email for a link to reset your password.";
		$this->message_list['passwordresetsuccess']['status'] = 'good';
		$this->message_list['passwordresetsuccess']['message'] = "Thanks! We've updated your password.";
		$this->message_list['passwordeditsuccess']['status'] = 'good';
		$this->message_list['passwordeditsuccess']['message'] = "Thanks! Your password has been changed. Please log in with your new password.";
		$this->message_list['accountsupdated']['status'] = 'good';
		$this->message_list['accountsupdated']['message'] = "Thanks! We've updated your details.";
		$this->message_list['newcardfailed']['status'] = 'bad';
		$this->message_list['newcardfailed']['message'] = "Sorry, it looks like that's not a valid card number or you already have it on your account. Please check it and try again.";
		$this->message_list['newcardsuccess']['status'] = 'good';
		$this->message_list['newcardsuccess']['message'] = "Thanks! We've added your credit card.";

	}


	/**
	 * Install
	 * Runs on plugin activation to add a new user role of Member
	 * @return null
	 */

	public static function install() {

		// New Role
		$result = add_role( 'member', __('Member'), array() );

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
	 * URL Rewrite Rules
	 * Create a page called "account" that can't be deleted.
	 * @return null
	 */

	public function url_rewrite_rules () {
		global $wp_rewrite;
		add_rewrite_rule( 'account(.*?)$', 'index.php?account=true&accountpage=$matches[1]', 'top' );
		$wp_rewrite->flush_rules();
	}

	/**
	 * Register Query Vars
	 * Add the accounts page to the available variables
	 * @param array $vars 
	 * @return array
	 */

	public function register_query_vars ( $vars ) {
		$vars[] = 'account';
		$vars[] = 'accountpage';
		return $vars;
	}



	/**
	 * URL Rewrite Template
	 * Render a custom template for the new "accounts" page.
	 * @return null
	 */

	public function url_rewrite_templates() {

		// Guard
		if(!get_query_var( 'account' )) return;

		// A Few Helpers
    	add_filter('body_class', function($classes){ $classes[] = 'accounts-account'; return $classes; });
        add_filter( 'template_include', function() { return Utils::template('page.php'); });
        add_filter( 'wp_title', function(){ echo "My Account | "; return ''; }, 10, 2 );
        do_action('accounts_page_actions');
	   
	}

	/**
	 * Add Logout Link
	 * Add a log out link to the primary navigation
	 * @param array $items 
	 * @param array $args 
	 * @return array
	 */

	public function add_logout_link($items, $args){

		if(is_user_logged_in() && $args->theme_location == 'primary'){

			$items .= '<li><a href="'.esc_url( wp_logout_url('/') ) . '">Logout</a></l1>';

		}

		return $items;
	}

	/**
	 * Check For Messages
	 * On load check for messages and return them 
	 * @return array|null
	 */

	public function check_for_messages(){

		$this->message_list = apply_filters('accounts_message_list',$this->message_list);

		if(empty($_GET['msg'])){
			return false;
		} else {
			$this->message = $this->message_list[$_GET['msg']];
			return $this->message;
		}
	}


	/**
	 * Redirect to /Accounts on login
	 * @return null
	 */

	public function login_redirect() {

		$referrer = $_SERVER['HTTP_REFERER'];  // where did the post submission come from?
	   // if there's a valid referrer, and it's not the default log-in screen
	   if ( !empty($referrer) && !strstr($referrer,'wp-login') && !strstr($referrer,'wp-admin') ) {
	      if (strstr($referrer, '?')) {
			  wp_redirect( $referrer . '&msg=invalidpassword' );
		  } else {
			  wp_redirect( $referrer . '?msg=invalidpassword' );
		  }
	      exit;
	   }

	}

	/**
	 * Render Content
	 * This is the guts of this plugin. Action based rendering with a few defaults.
	 * @return null
	 */

	public function render_content(){

		// Get The Current Page
		$accountpage = get_query_var( 'accountpage' );
		if(empty($accountpage)){ $accountpage = 'dashboard'; }
		else { $parts = explode('/',$accountpage); $accountpage = $parts[1]; }

		// Display Messages
		if(!empty($this->message) || !empty($this->errors)) Utils::template_include('notifications.php',$this->message,'notification');
		
		// Logged In vs Logged Out

		if(!is_user_logged_in()){

			if($accountpage == 'create'){

				do_action('accounts_before_content_create');
				do_action('accounts_the_content_create');
				do_action('accounts_after_content_create');

			} else if($accountpage == 'lostpassword'){

				do_action('accounts_before_content_lostpassword');
				do_action('accounts_the_content_lostpassword');
				do_action('accounts_after_content_lostpassword');

			} else if($accountpage == 'passwordreset'){

				if(empty($_GET['key'])){
					Utils::template_include('login.php');
				} else {
					do_action('accounts_before_content_passwordreset');
					do_action('accounts_the_content_passwordreset');
					do_action('accounts_after_content_passwordreset');
				}

			} else if($accountpage == 'activate'){

				$user = get_users(array(
					'meta_key' => 'activation_key',
					'meta_value' => $_GET['key'],
					'number' => 1,
					'count_total' => false
				));

				if(empty($user[0])){
					Utils::template_include('login.php');
				} else {
					do_action('accounts_before_content_activate');
					do_action('accounts_the_content_activate');
					do_action('accounts_after_content_activate');
				}

			} else {

				Utils::template_include('login.php');

			}

		} else {

			// Don't Create Account if already logged in
			if($accountpage == 'create'){ $accountpage = 'dashboard'; }

			// Render navigation
			$this->render_navigation();


			// Dynamic Pages. Register an action and render anything you'd like via a template/function
			do_action('accounts_before_content_'.$accountpage);
			do_action('accounts_the_content_'.$accountpage);
			do_action('accounts_after_content_'.$accountpage);

		}
	}


	/**
	 * Account Navigation
	 * @param array $sections 
	 * @return array
	 */

	public function add_default_sections($sections){

		$sections[] = array('id' => 'details', 'href' => '/account/#details', 'text' => 'My Details');

		return $sections;

	}

	/**
	 * Render Navigation
	 * @return null
	 */

   	public function render_navigation(){

   		$the_final_sections = apply_filters('accounts_sections', $this->sections);
   		Utils::template_include('navigation.php',$the_final_sections);
   	}


	/**
	 * Validate Active Users on Login
	 * @param type $user
	 * @param type $username
	 * @param type $password
	 * @return type
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
	 * Edit Account Save
	 * Save the default fields
	 * @param array $vars  $_POST vars
	 * @return null
	 */

	public function edit_account_save($vars) {

		$user_id = get_current_user_id();

		// Data Mapping
		$args = array();
		$args['ID'] = $user_id;
		$args['first_name'] = $vars['account']['first_name'];
		$args['last_name']  = $vars['account']['last_name'];
		$args['user_email'] = $vars['account']['email'];

		// Validate Email Against Other Wordpress Users
		$valid_email = $this->validate_email_update($vars['account']['email']);

		if ($valid_email != true) {

			$this->message['status'] = 'bad';
			$this->message['message'] = "This email address is already taken.";

			return false;
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
	 * Create Account
	 * Create a new user account
	 * @param array $vars $_POST variables
	 * @return null
	 */
   	public function create_account($vars) {

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
	 * Activate New account
	 *
	 * @param  Array $vars $_POST Variables
	 * @return none
	 */

	public function activate_account($vars){

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
    * Validate Email Update
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

   	public function validate_email_update($email){

   		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) { return false; }

   		$user = wp_get_current_user();
   		$current_email = strtolower($user->data->user_email);

   		if(strtolower($email) == $current_email) { return true; }

   		if (email_exists($email)) {
   			return false;
   		} else {
   			return true;
   		}

   	}

   /**
    * Validate Username Availability
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

   	public function valide_username_availability($vars){

   		$username = strtolower(trim($vars['account']['username']));
		$result = (username_exists( $username )) ? false : true;

		Utils::output_json($result);

   	}



   	/**
   	 * Redirect To Custom Lost Password Page
   	 * @return null
   	 */
   	public function redirect_to_custom_lostpassword(){

   		if(is_user_logged_in()) Utils::redirect('/account');
   		else Utils::redirect('/account/lostpassword');
   		
   	}

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

   	public function reset_password($vars){

   		// Get User
   		$args = array();
   		$args['meta_key'] = 'password_reset_token';
   		$args['meta_value'] = $vars['token'];
   		$users = get_users($args);

   		if(!$users[0]) Utils::redirect('/account/passwordreset/?key='.$vars['token'].'&msg=passwordresetfail');
   		

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

   	/**
   	 * Forms Processing
   	 * Validates nonce and runs actions if needed.
   	 * @return null
   	 */

	public function forms() {


		if (!isset($_POST['accounts_action'])) return;
		if(!wp_verify_nonce( $_POST['_wpnonce'], 'accounts')){ Utils::redirect($_POST['_wp_http_referer']); }

		switch ($_POST['accounts_action']) {

			case 'account_update':
				$this->edit_account_save($_POST);
				break;

			case 'accounts_create_account':
				$this->create_account($_POST);
				break;

			case 'accounts_activate_account':
				$this->activate_account($_POST);
				break;

			case 'accounts_lost_password':
				$this->request_password_reset($_POST);
				break;

			case 'accounts_reset_password':
				$this->reset_password($_POST);
				break;

			default:
				break;

		}

	}


   	/**
   	 * Router
   	 * Add custom endpoints for theme validation
   	 * @param OBJECT $wp 
   	 * @return null
   	 */

	public function router($wp) {

		$pagename = (isset($wp->query_vars['pagename'])) ? $wp->query_vars['pagename'] : $wp->request;

		switch ($pagename) {

			case 'api/accounts/validate/email':
				$this->validate_email_update($_POST);
				break;

			case 'api/accounts/validate/username':
				$this->valide_username_availability($_POST);
				break;

			default:
				break;

		}

	}

}


// Go

require_once('wp-accounts-template-tags.php');
$wpmember_accounts = new Accounts();
