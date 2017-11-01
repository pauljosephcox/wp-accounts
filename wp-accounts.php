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
require_once('classes/user.class.php');

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
		$this->message_list = Utils::default_messages();

		// Setup User Default Functionality (activation, password reset...)
		$this->user = new User();

		// Setup
		add_action('init', array($this, 'check_for_messages'), 10, 0);
		add_action('wp_loaded', array($this , 'forms'));
		add_action('parse_request', array($this , 'router'));

		// Template Rewrite
		add_action('init', array($this,'url_rewrite_rules'));
		add_filter('query_vars', array($this,'register_query_vars'));
		add_action('template_redirect', array($this,'url_rewrite_templates'));

		// Add Link to Logout
		add_filter('wp_nav_menu_items', array($this,'add_logout_link'), 10, 2);

		// Authenticate Users
		add_action('wp_login_failed', array($this, 'login_redirect') );
		add_action('login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );

		// Default Admin Sections
		add_filter('accounts_sections', array($this,'add_default_sections'),1);
		add_action('accounts_the_content_dashboard', function(){ Utils::template_include('edit-account.php'); }, 3);


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

		$referrer = $_SERVER['HTTP_REFERER'];
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
	 * Get URI
	 * Get the page to display
	 * @return string
	 */

	public function get_uri(){

		$accountpage = get_query_var( 'accountpage' );
		if(empty($accountpage)) return 'dashboard';

		$parts = explode('/',$accountpage);
		return $parts[1];

	}

	/**
	 * Render Content
	 * This is the guts of this plugin. Action based rendering with a few defaults.
	 * @return null
	 */

	public function render_content(){

		// Get The Current Page
		$accountpage = $this->get_uri();

		// Display Messages
		if(!empty($this->message) || !empty($this->errors)) Utils::template_include('notifications.php',$this->message,'notification');
		
		// Logged In vs Logged Out

		if(!is_user_logged_in()){

			if($accountpage == 'dashboard'){

				Utils::template_include('login.php');
				return;

			} elseif($accountpage == 'passwordreset' && empty($_GET['key'])){

				Utils::template_include('login.php');
				return;

			} elseif($accountpage == 'activate'){

				// Get The User 
				$user = get_users(array(
					'meta_key' => 'activation_key',
					'meta_value' => $_GET['key'],
					'number' => 1,
					'count_total' => false
				));

				if(empty($user[0])){

					Utils::template_include('login.php');
					return;

				}


			}


		} else {

			// Don't Allow Account Creation if already logged in
			if($accountpage == 'create') $accountpage = 'dashboard';

			// Render navigation
			$this->render_navigation();	

		}

		// Dynamic Pages. Register an action and render anything you'd like via a template/function
		do_action('accounts_before_content_'.$accountpage);
		do_action('accounts_the_content_'.$accountpage);
		do_action('accounts_after_content_'.$accountpage);

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
   	 * Validate Username Availability
   	 * Checks if a username exists or not
   	 * @param string $vars 
   	 * @return JSON
   	 */

   	public function validate_username_availability($vars){

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
				$this->message = $this->user->save($_POST);
				break;

			case 'accounts_create_account':
				$this->user->create($_POST);
				break;

			case 'accounts_activate_account':
				$this->user->activate($_POST);
				break;

			case 'accounts_lost_password':
				$this->user->request_password_reset($_POST);
				break;

			case 'accounts_reset_password':
				$this->user->reset_password($_POST);
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
				Utils::validate_email_update($_POST);
				break;

			case 'api/accounts/validate/username':
				$this->validate_username_availability($_POST);
				break;

			default:
				break;

		}

	}

}


// Init
require_once('wp-accounts-template-tags.php');
$wpmember_accounts = new Accounts();
