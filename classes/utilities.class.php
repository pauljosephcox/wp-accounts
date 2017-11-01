<?php
/**
 * WPMember Utilities
 * ------------------------------
 * Reusable helpers to make life easier.
 */

namespace WPMember_Accounts;

class Utils {

	/**
	 * Constructor
	 */

	function __construct(){}

	/**
	 * Output JSON
	 * @param array $array The data to conver
	 * @return JSON
	 */

	public static function output_json($array) {

		header('Content-type: application/json');
		echo json_encode($array);
		exit();

	}

	/**
	 * Output CSV
	 * Takes and array and outputs a csv from it's keys and values
	 * @param array $array 
	 * @param string $filename 
	 * @return csv file
	 */

	public static function output_csv($array, $filename = 'report.csv') {

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


   	/**
   	 * Redirect
   	 * Redirect to either an ID or a url
   	 * @param sting|int $path 
   	 * @return null
   	 */

	public static function redirect($path) {

		if(is_numeric($path)){ $path = get_permalink($path); }
		wp_safe_redirect( $path );
	  	exit();

	}

	/**
	 * Email
	 * Sends an email with dynamic replacements
	 * @param string $to 
	 * @param string $subject 
	 * @param string $message 
	 * @param array $replacements 
	 * @return null
	 */

	public static function email($to, $subject, $message, $replacements = array()) {

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
   	 * Template
   	 * Returns the path of the template file checking for a theme override
   	 * then falling back to the plugin folder
   	 * @param string $filename 
   	 * @return string
   	 */

	public static function template($filename) {

		// Check Theme
		$theme = get_template_directory() . '/accounts/' . $filename;

		if (!file_exists($theme)) {

			$dir = str_replace('/classes', null, plugin_dir_path(__FILE__));
			return $dir  . 'templates/' . $filename;

		}

		return $theme;

		

	}

   	/**
   	 * Template Include
   	 * Find a file with checking for a theme override and pass data to the file
   	 * @param string $template The name of the template file
   	 * @param string|null $data The data to make available in the file
   	 * @param string|null $name How you want to reference the data in the file.
   	 * @return null
   	 */

	public static function template_include($template, $data = null, $name = null){
		if(isset($name)){ ${$name} = $data; }
		$path = self::template($template);
		include($path);
	}


	public static function default_messages(){

		$message_list = array();
		$message_list['editaccounterror']['status'] = 'bad';
		$message_list['editaccounterror']['message'] = "Sorry, something went wrong with the update. Please try again.";
		$message_list['editaccountsuccess']['status'] = 'good';
		$message_list['editaccountsuccess']['message'] = "Thanks! We've updated your details.";
		$message_list['newaccountsuccess']['status'] = 'good';
		$message_list['newaccountsuccess']['message'] = "Thanks! We've just sent you an email to activate your account.";
		$message_list['newaccountexists']['status'] = 'bad';
		$message_list['newaccountexists']['message'] = "The username or email is already in use. Please try a different username or email.";
		$message_list['newaccounterror']['status'] = 'bad';
		$message_list['newaccounterror']['message'] = "Sorry, something went wrong creating your account. Please try again.";
		$message_list['invalidpassword']['status'] = 'bad';
		$message_list['invalidpassword']['message'] = 'The username or password entered didn\'t match our records. Please check and try again.';
		$message_list['activationfail']['status'] = 'bad';
		$message_list['activationfail']['message'] = 'Sorry, something went wrong with activation. Please try again.';
		$message_list['passwordresetfail']['status'] = 'bad';
		$message_list['passwordresetfail']['message'] = 'The passwords entered don\'t match. Please try again.';
		$message_list['notactive']['status'] = 'bad';
		$message_list['notactive']['message'] = "Your account hasn't been activated yet, please check your email for your activation link. Can't find it? Register to resend your activation link.";
		$message_list['editrecurringsuccess']['status'] = 'good';
		$message_list['editrecurringsuccess']['message'] = "Thanks! We've updated your regular donation.";
		$message_list['emailpreferencesuccess']['status'] = 'good';
		$message_list['emailpreferencesuccess']['message'] = "Thanks! Your email preferences have been saved.";
		$message_list['passwordresetsent']['status'] = 'good';
		$message_list['passwordresetsent']['message'] = "Check your email for a link to reset your password.";
		$message_list['passwordresetsuccess']['status'] = 'good';
		$message_list['passwordresetsuccess']['message'] = "Thanks! We've updated your password.";
		$message_list['passwordeditsuccess']['status'] = 'good';
		$message_list['passwordeditsuccess']['message'] = "Thanks! Your password has been changed. Please log in with your new password.";
		$message_list['accountsupdated']['status'] = 'good';
		$message_list['accountsupdated']['message'] = "Thanks! We've updated your details.";
		$message_list['newcardfailed']['status'] = 'bad';
		$message_list['newcardfailed']['message'] = "Sorry, it looks like that's not a valid card number or you already have it on your account. Please check it and try again.";
		$message_list['newcardsuccess']['status'] = 'good';
		$message_list['newcardsuccess']['message'] = "Thanks! We've added your credit card.";
		return $message_list;

	}

 	public function validate_email_update($email){

   		if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) return false;

   		$user = wp_get_current_user();
   		$current_email = strtolower($user->data->user_email);

   		if(strtolower($email) == $current_email) return true;

   		if (email_exists($email)) {
   			return false;
   		} else {
   			return true;
   		}

   	}




}
?>