<?php

/**
 * Plugin Name: GMT EDD Free Purchases
 * Plugin URI: https://github.com/cferdinandi/gmt-edd-free-purchases/
 * GitHub Plugin URI: https://github.com/cferdinandi/gmt-edd-free-purchases/
 * Description: Let people purchase products through EDD just by supplying their email address.
 * Version: 1.2.0
 * Author: Chris Ferdinandi
 * Author URI: http://gomakethings.com
 * License: GPLv3
 */


// Includes
require_once( plugin_dir_path( __FILE__ ) . 'includes/wp-session-manager/wp-session-manager.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/helpers.php' );

// Purchase
require_once( plugin_dir_path( __FILE__ ) . 'includes/metabox.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/form.php' );