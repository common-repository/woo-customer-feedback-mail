<?php
/**
 *
 * This plugin provides the ability to send custom feedback mail from woocommerce on  register user. 
 *
 * @since             1.0.0
 * @package           Woo Customer Feedback Mail
 *
 * @wordpress-plugin
 * Plugin Name:       Woo Customer Feedback Mail
 * Plugin URI:        http://wordpress.org
 * Description:       This plugin provides the ability to send custom feedback mail from woocommerce on  register user.
 * Version:           1.0.0
 * Author:            hiren1094
 * Author URI:        http://resumedirectory.in
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

define('WCFM_ADMINPAGE_URL',admin_url('admin.php?page=wcfm-customer-feedback-mail'));
require_once( dirname(__FILE__) . '/wcfm-admin-field.php' );
  
