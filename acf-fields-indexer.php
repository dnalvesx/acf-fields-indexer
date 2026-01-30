<?php
/**
 * Plugin Name: ACF Fields Indexer
 * Description: Indexes selected ACF fields into the main post content to enhance native WordPress search capabilities.
 * Version:     1.0.0
 * Author:      WordPress Engineer
 * Text Domain: acf-fields-indexer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AFI_VERSION', '1.0.0' );
define( 'AFI_PATH', plugin_dir_path( __FILE__ ) );

// Autoload classes
require_once AFI_PATH . 'src/Core/Indexer.php';
require_once AFI_PATH . 'src/Admin/SettingsPage.php';

// Initialize Plugin
add_action( 'plugins_loaded', function() {
    
    // Initialize Core Logic
    if ( class_exists( 'AcfFieldsIndexer\Core\Indexer' ) ) {
        new \AcfFieldsIndexer\Core\Indexer();
    }

    // Initialize Admin Interface
    if ( is_admin() && class_exists( 'AcfFieldsIndexer\Admin\SettingsPage' ) ) {
        new \AcfFieldsIndexer\Admin\SettingsPage();
    }

});