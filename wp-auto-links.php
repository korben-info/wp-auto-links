<?php
/*
Plugin Name:        WP Auto Links
Plugin URI:         https://github.com/LeoColomb/wp-auto-links
Description:        Adds automatically internal and external links to list of keywords.
Version:            1.0.0
Author:             Léo Colombaro
Author URI:         https://colombaro.fr/

License:            MIT License
License URI:        https://opensource.org/licenses/MIT
*/

// Exit if accessed directly
defined('ABSPATH') || exit;

// Load dependencies
if (!class_exists('WP_Auto_Links_Helper')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

register_activation_hook(__FILE__, [WP_Auto_Links_Helper::class, 'activate']);
register_deactivation_hook(__FILE__, [WP_Auto_Links_Helper::class, 'deactivate']);

WP_Auto_Links_Helper::get_instance();

if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('auto-links', \WP_Auto_Links_CLI_Commands::class);
}
