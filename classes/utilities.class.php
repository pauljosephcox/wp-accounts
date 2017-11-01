<?php

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
   	 * Template
   	 * Returns the path of the template file checking for a theme override
   	 * then falling back to the plugin folder
   	 * @param string $filename 
   	 * @return string
   	 */

	public function template($filename) {

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

	public function template_include($template, $data = null, $name = null){
		if(isset($name)){ ${$name} = $data; }
		$path = self::template($template);
		include($path);
	}

}
?>