<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
Plugin Name: Launchpad SMTP
Description: Launchpad SMTP can help us to send emails via SMTP instead of the PHP mail() function.
Version: 1.0
Author: Vinhdd
Text Domain: launchpad-wp-smtp
Domain Path: /languages
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
 * The plugin was originally created by Vinhdd
 */

class Launchpad_WP_SMTP {

	private $lpOptions, $phpmailer_error;

	public function __construct() {
		$this->setup_vars();
		$this->hooks();
	}

	public function setup_vars(){
		$this->lpOptions = get_option( 'launchpad_wp_smtp_options' );
	}

	public function hooks() {
		register_activation_hook( __FILE__ , array( $this,'wp_smtp_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'wp_smtp_deactivate' ) );

		add_filter( 'plugin_action_links', array( $this, 'wp_smtp_settings_link' ), 10, 2 );
		add_action( 'init', array( $this,'load_textdomain' ) );
		add_action( 'phpmailer_init', array( $this,'wp_smtp' ) );
		add_action( 'wp_mail_failed', array( $this, 'catch_phpmailer_error' ) );
		add_action( 'admin_menu', array( $this, 'wp_smtp_admin' ) );
	}

	function wp_smtp_activate(){
		$lpOptions = array();
		$lpOptions["from"] = "";
		$lpOptions["fromname"] = "";
		$lpOptions["host"] = "";
		$lpOptions["smtpsecure"] = "";
		$lpOptions["port"] = "";
		$lpOptions["smtpauth"] = "yes";
		$lpOptions["username"] = "";
		$lpOptions["password"] = "";
		$lpOptions["deactivate"] = "";

		add_option( 'launchpad_wp_smtp_options', $lpOptions );
	}

	function wp_smtp_deactivate() {
		if( $this->lpOptions['deactivate'] == 'yes' ) {
			delete_option( 'launchpad_wp_smtp_options' );
		}
	}

	function load_textdomain() {
		load_plugin_textdomain( 'launchpad-wp-smtp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	function wp_smtp( $phpmailer ) {

		if( ! is_email($this->lpOptions["from"] ) || empty( $this->lpOptions["host"] ) ) {
			return;
		}

		$phpmailer->Mailer = "smtp";
		$phpmailer->From = $this->lpOptions["from"];
		$phpmailer->FromName = $this->lpOptions["fromname"];
		$phpmailer->Sender = $phpmailer->From; //Return-Path
		$phpmailer->AddReplyTo($phpmailer->From,$phpmailer->FromName); //Reply-To
		$phpmailer->Host = $this->lpOptions["host"];
		$phpmailer->SMTPSecure = $this->lpOptions["smtpsecure"];
		$phpmailer->Port = $this->lpOptions["port"];
		$phpmailer->SMTPAuth = ($this->lpOptions["smtpauth"]=="yes") ? TRUE : FALSE;

		if( $phpmailer->SMTPAuth ){
			$phpmailer->Username = $this->lpOptions["username"];
			$phpmailer->Password = $this->lpOptions["password"];
		}
	}

	function catch_phpmailer_error( $error ) {
		$this->phpmailer_error = $error;
	}

	function wp_smtp_settings_link($action_links,$plugin_file) {
		if( $plugin_file == plugin_basename( __FILE__ ) ) {
			$ws_settings_link = '<a href="options-general.php?page=' . dirname( plugin_basename(__FILE__) ) . '/launchpad-wp-smtp.php">' . __("Settings") . '</a>';
			array_unshift($action_links,$ws_settings_link);
		}

		return $action_links;
	}

	function wp_smtp_admin(){
		add_options_page('Launchpad WP SMTP Options', 'Launchpad SMTP','manage_options', __FILE__, array( $this, 'launchpad_smtp_page') );
	}

	function wp_smtp_page(){
		require_once __DIR__ . '/launchpad-admin.php';
	}
}

new Launchpad_WP_SMTP();
?>