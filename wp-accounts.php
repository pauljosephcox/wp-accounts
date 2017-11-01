<?php
/**
 * @wordpress-plugin
 * Plugin Name: WP Member Accounts
 * Description: Basic User Accounts Management Plugin
 * Author: Paul Joseph Cox
 * Version: 2.0
 * Author URI: http://pauljosephcox.com/
 */




if (!defined('ABSPATH')) exit;

/*==========  Activation Hook  ==========*/
register_activation_hook( __FILE__, array( 'WPMember_Accounts', 'install' ) );



/**
 * Main WPMember_Accounts Class
 *
 * @class WPMember_Accounts
 * @version 2.0
 */

class WPMember_Accounts {

	public $errors = false;
	public $notices = false;
	public $slug = 'accounts';

	function __construct() {

		$this->path = plugin_dir_path(__FILE__);
		$this->folder = basename($this->path);
		$this->dir = plugin_dir_url(__FILE__);
		$this->version = '2.0';
		$this->active_section;
		$this->sections = array();

		// Example Section
		// $this->sections[0]['href'] = '/account/#details';
		// $this->sections[0]['text'] = 'My Account Details';

		$this->message = false;
		$this->errors  = false;
		$this->notice  = false;

		// Actions
		add_action('init', array($this, 'setup'), 10, 0);
		add_action('wp_loaded', array($this , 'forms'));
		add_action('parse_request', array($this , 'custom_url_paths'));
		add_action('after_setup_theme', array($this,'remove_admin_bar'));
		add_action('wp_enqueue_scripts', array($this, 'scripts'));


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
		add_action( 'accounts_the_content_dashboard', function(){ $this->template_include('edit-account.php'); }, 3);

		// Create Account Page
		add_action( 'accounts_the_content_create', function(){ $this->template_include('new-account.php'); });

		// Activate Account Page
		add_action( 'accounts_the_content_activate', function(){ $this->template_include('activate.php'); });

		// Password Reset Page
		add_action( 'accounts_the_content_lostpassword', function(){ $this->template_include('lost-password.php'); }, 4);
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );
		add_action( 'accounts_the_content_passwordreset', function(){ $this->template_include('password-reset.php'); });

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
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public static function install() {

		// New Role
		$result = add_role( 'member', __('Member'), array() );

	}

   /**
    * Setup
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function setup() {

		// Check For Message
		$this->check_for_messages();



	}

   /**
    * Scripts
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function scripts() {

		//wp_enqueue_script('jquery.validate', '//cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.15.0/jquery.validate.min.js', array('jquery'), $this->version, true);
		wp_enqueue_script('accounts', $this->dir.'/assets/accounts.js', array('jquery'), $this->version, true);

	}

   /**
    * Remove Admin Bar
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function remove_admin_bar() {

		if (current_user_can('member')) { show_admin_bar(false); }

	}


   /**
    * URL Rewrite Rules
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function url_rewrite_rules () {
		global $wp_rewrite;
		add_rewrite_rule( 'account(.*?)$', 'index.php?account=true&accountpage=$matches[1]', 'top' );
		$wp_rewrite->flush_rules();
	}

   /**
    * Register Query Vars
    * ---------------------------------------------
    * @return Array
    * ---------------------------------------------
    **/

	public function register_query_vars ( $vars ) {
		$vars[] = 'account';
		$vars[] = 'accountpage';
		return $vars;
	}

   /**
    * URL Rewrite Template
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	function url_rewrite_templates() {

	    if ( get_query_var( 'account' ) ) {

	    	add_filter('body_class', array($this,'add_body_class'));

	        add_filter( 'template_include', function() {
	        	return $this->template('page.php');
	        });

	        add_filter( 'wp_title', array($this,'custom_title'), 10, 2 );

	        do_action('accounts_page_actions');
	    }

	}

   /**
    * Add Body Class
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function add_body_class( $classes ){
		$classes[] = 'accounts-account';
    	return $classes;
	}

   /**
    * Custom Title
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function custom_title(){
		echo "My Account | ";
		return '';
	}

   /**
    * Add Logout Link
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function add_logout_link($items, $args){

		if(is_user_logged_in() && $args->theme_location == 'primary'){

			$items .= '<li><a href="'.esc_url( wp_logout_url('/') ) . '">Logout</a></l1>';

		}

		return $items;
	}

   /**
	* Check For Messages
	*
	* @return Boolean
	**/

	public function check_for_messages(){

		$this->message_list = apply_filters('accounts_message_list',$this->message_list);

		if(empty($_GET['msg'])){ return false; }
		else {
			$this->message = $this->message_list[$_GET['msg']];
			return $this->message;
		}
	}


	/**
	 * Change the login redirect away from WordPress
	 *
	 * @return none
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
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function render_content(){



		// Get Page
		$accountpage = get_query_var( 'accountpage' );
		if(empty($accountpage)){ $accountpage = 'dashboard'; }
		else { $parts = explode('/',$accountpage); $accountpage = $parts[1]; }



		// Display Messages
		if(!empty($this->message) || !empty($this->errors)){
			$this->template_include('notifications.php',$this->message,'notification');
		}

		// Display Forms
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
					$this->template_include('login.php');
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
					$this->template_include('login.php');
				} else {
					do_action('accounts_before_content_activate');
					do_action('accounts_the_content_activate');
					do_action('accounts_after_content_activate');
				}

			} else {

				$this->template_include('login.php');

			}

		} else {

			// Don't Create Account if already logged in
			if($accountpage == 'create'){ $accountpage = 'dashboard'; }

			// Render navigation
			$this->render_navigation();


			// Run Actions To Render Content
			do_action('accounts_before_content_'.$accountpage);
			do_action('accounts_the_content_'.$accountpage);
			do_action('accounts_after_content_'.$accountpage);

		}
	}


   /**
    * Add Default Sections
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function add_default_sections($sections){

		// $sections[] = array('href' => '/account/info', 'text' => 'Account Information');
		$sections[] = array('id' => 'details', 'href' => '/account/#details', 'text' => 'My Details');

		return $sections;

	}

   /**
    * Render Navigation
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

   	public function render_navigation(){

   		$the_final_sections = apply_filters('accounts_sections', $this->sections);
   		$this->template_include('navigation.php',$the_final_sections);
   	}

   /**
    * Render Edit account
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function render_edit_account(){

		$this->template_include('edit-account.php');

	}

   /**
    * Render New account
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function render_new_account(){

		$this->template_include('new-account.php');

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

		if(is_wp_error($user)){
			return false;
		}

		if(array_intersect(array('administrator','editor','author','contributor'), $user->roles )){

			return $user;
		}

		$status = get_user_meta($user->ID,'activated',true);

		if($status != 'active'){
			$this->redirect('/account/?msg=notactive');
			return false;
		} else {
			return $user;
		}


	}

   /**
    * Edit account Save
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

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

		// TODO: apply_filters('edit_account_save_sucess',$vars);

		// Update User
		$user_id = wp_update_user( $args );
		if(is_wp_error($user_id)){

			$vars = apply_filters('accounts_edit_account_save_fail',$vars);
			$this->redirect('/account?msg=editaccounterror');

		} else {

			$vars = apply_filters('accounts_edit_account_save_success',$vars);
			$this->redirect('/account?msg=editaccountsuccess');
		}

	}

   /**
    * New account
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

   	public function create_account($vars) {

   		$userdata = array(
		    'user_login'  => $vars['account']['email'],
		    'user_pass'   => $vars['account']['password'],
		    'first_name'  => $vars['account']['first_name'],
		    'last_name'   => $vars['account']['last_name'],
		    'user_email'  => $vars['account']['email'],
		    'role'        => 'member'
		);

		// $user_id = username_exists( $vars['account']['username'] );
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
				$this->redirect('/account?msg=newaccountsuccess');

			} else {

				$this->redirect($current_url.'?msg=newaccountexists');

			}
		}

		if(!is_wp_error($user_id)){

			// Success Log In
			// $creds = array();
			// $creds['user_login']    = $vars['account']['email'];
			// $creds['user_password'] = $vars['account']['password'];
			// $creds['remember'] = true;
			// $user = wp_signon( $creds, false );

			// $this->redirect('/account/?msg=newaccountsuccess');

			// Set Status
			update_user_meta($user_id,'activated','not-active');

			// Send Activation Email
			$this->send_activation_email($user_id,$vars['account']['email']);

			// Run Actions
			$user_id = apply_filters('accounts_create_account_success',$user_id);

			// Redirect To Success
			$this->redirect('/account?msg=newaccountsuccess');

		} else {

			// Run Actions
			$vars = apply_filters('accounts_create_account_fail',$vars);
			$this->redirect($current_url.'?msg=newaccounterror');

		}

	}

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

		return $this->email($email['to'], $email['subject'], $email['body'], $replacements);


	}

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

			$this->redirect('/account/activate/?key='.$vars['account']['key'].'&msg='.$e);

		} else {

			// Update Activated
			update_user_meta($user->ID,'activated','active');

			// Run Actions
			$vars = apply_filters('accounts_active_account_success',$vars);

			// Redirect to account
			$this->redirect('/account');

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

   		if(username_exists( $username )){
			$result = false;
		} else {
			$result = true;
		}

		$this->output_json($result);

   	}



   	/**
   	 * Redirect To Custom Lost Password Page
   	 * @return null
   	 */
   	public function redirect_to_custom_lostpassword(){

   		if(is_user_logged_in()){
   			$this->redirect('/account');
   		} else {
   			$this->redirect('/account/lostpassword');
   		}

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
		$this->email($email['to'], $email['subject'], $email['body'], $replacements);

		// Run Actions
		$vars = apply_filters('accounts_request_lost_password',$vars);

		// Redirect
		$this->redirect('/account?msg=passwordresetsent');

   	}

   	public function reset_password($vars){

   		// Get User
   		$args = array();
   		$args['meta_key'] = 'password_reset_token';
   		$args['meta_value'] = $vars['token'];
   		$users = get_users($args);

   		if(!$users[0]){
   			$this->redirect('/account/passwordreset/?key='.$vars['token'].'&msg=passwordresetfail');
   		}

   		$user = $users[0];

   		if($vars['account']['password'] == $vars['account']['confirmpassword']){

			wp_set_password( $vars['account']['password'], $user->ID );

			delete_user_meta($user->ID,'password_reset_token');

			// Run Actions
			$vars = apply_filters('accounts_reset_account_password_success',$vars);

			// Redirect
			$this->redirect('/account/?msg=passwordresetsuccess');

		} else {

			// Run Actions
			$vars = apply_filters('accounts_reset_account_password_fail',$vars);

			// Redirect
			$this->redirect('/account/passwordreset/?key='.$vars['token'].'&msg=passwordresetfail1');

		}

   	}

   	public function google_tags(  $user_login, $user ){

   		set_google_tags(array('event'=>'userLogin','userID'=>$user->ID));
   	}


   /**
    * Forms
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function forms() {


		if (!isset($_POST['accounts_action'])) return;
		if(!wp_verify_nonce( $_POST['_wpnonce'], 'accounts')){ $this->redirect($_POST['_wp_http_referer']); }

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
    * Custom URL Paths
    * ---------------------------------------------
    * @return false
    * ---------------------------------------------
    **/

	public function custom_url_paths($wp) {

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

	/**
	 * Register options page
	 */
	public function register_options_page() {

		// main page
		add_options_page('Accounts', 'Accounts', 'manage_options', 'accounts_options', array($this, 'include_options'));
		add_action('admin_init', array($this, 'plugin_options'));

	}


	/**
	 * Get options template
	 */
	public function include_options() { require('templates/options.php'); }


	/**
	 * Register plugin settings
	 *
	 * Register each unique setting administered on your
	 * options page in a new line in the array.
	 */
	public function plugin_options() {

		$options = array();

		foreach ($options as $option) {
			register_setting('accounts_options', $option);
		}

	}

	/**
	 * Shortcode Include
	 */
	public function shortcode() {

		$errors = $this->errors;

		ob_start();
		// include $this->template('template.php');
		return ob_get_clean();

	}

	/**
	 * Outputs a WordPress error notice
	 *
	 * Push your error to $this->errors then show with:
	 * add_action( 'admin_notices', array($this, 'admin_error'));
	 */
	public function admin_error() {

		if(!$this->errors) return;

		foreach($this->errors as $error) :

	?>

		<div class="error settings-error notice is-dismissible">

			<p><strong><?php print $error ?></strong></p>
			<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>

		</div>

	<?php

		endforeach;

	}

	/**
	 * Outputs a WordPress notice
	 *
	 * Push your error to $this->notices then show with:
	 * add_action( 'admin_notices', array($this, 'admin_success'));
	 */
	public function admin_success() {

		if(!$this->notices) return;

		foreach($this->notices as $notice) :

	?>

		<div class="updated settings-error notice is-dismissible">

			<p><strong><?php print $notice ?></strong></p>
			<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>

		</div>

	<?php

		endforeach;

	}

	/**
	 * Email wrapper, to allow for string replacement
	 *
	 * @param string $to email address
	 * @param string $subject
	 * @param string $message
	 * @param array $replacements array of key => value replacements
	 */
	public function email($to, $subject, $message, $replacements = array()) {

		//replacements
		if ($replacements) foreach ($replacements as $variable => $replacement) {
			$message = str_replace($variable, $replacement, $message);
			$subject = str_replace($variable, $replacement, $subject);
		}

		//Send from the site email
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
		);

		//WP mail function
		wp_mail( $to, $subject, $message , $headers);

	}


	/**
	 * Output JSON
	 *
	 * @param $array Array to encode
	 */
	public function output_json($array) {

		header('Content-type: application/json');
		echo json_encode($array);
		exit();

	}

   /**
    * Template
    * ---------------------------------------------
    * @param $filename | String | name of the template
    * @return false
    * ---------------------------------------------
    **/

	public function template($filename) {

		// check theme
		$theme = get_template_directory() . '/'.$this->slug.'/' . $filename;

		if (file_exists($theme)) {
			$path = $theme;
		} else {
			$path = $this->path . 'templates/' . $filename;
		}
		return $path;

	}

   /**
    * Template Include
    * ---------------------------------------------
    * @param $template | String | name of the template
    * @param $data | Anything | Data to pass to a template
    * @return false
    * ---------------------------------------------
    **/

	public function template_include($template, $data = null, $name = null){
		if(isset($name)){ ${$name} = $data; }
		$path = $this->template($template);
		include($path);
	}

   /**
    * Redirect
    * ---------------------------------------------
    * @param $path | String/Int | url of post id
    * @return false
    * ---------------------------------------------
    **/

	public function redirect($path) {

		if(is_numeric($path)){ $path = get_permalink($path); }
		wp_safe_redirect( $path );
	  	exit();

	}

	/**
	 * Output CSV
	 *
	 * @param $array Array to output (keyed)
	 * @param $filename Filename to download
	 */
	public function output_csv($array, $filename = 'report.csv') {

		ob_clean();
		ob_start();

		$file = fopen('php://output', 'w');

		// generate csv lines from the inner arrays
		$headings = array();
		foreach ($array[0] as $key => $line) {
			$headings[] = $key;
		}

		fputcsv($file, $headings);
		foreach($array as $row) {
		    fputcsv($file, $row);
		}

	    // rewind file
	    $output = stream_get_contents($file);
	    fclose($file);

	    // prep download
	    header("Content-type: text/x-csv");
	    header("Content-Transfer-Encoding: binary");
	    header('Content-Disposition: attachement; filename="' . $filename . '";');
	    header("Pragma: no-cache");
	    header("Expires: 0");

	    echo $output;
	    exit();

	}

}


// Go

require_once('wp-accounts-template-tags.php');
$wpmember_accounts = new WPMember_Accounts();
