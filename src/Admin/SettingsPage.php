<?php
namespace AcfFieldsIndexer\Admin;

use AcfFieldsIndexer\Core\Indexer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SettingsPage {

    const OPTION_GROUP    = 'afi_settings_group';
    const OPTION_FIELDS   = 'afi_indexed_fields';
    const OPTION_TYPES    = 'afi_target_post_types';
    const PAGE_SLUG       = 'acf-fields-indexer';

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_afi_trigger_reindex', [$this, 'handle_batch_process']);
    }

    public function register_menu_page() {
        add_options_page(
            'ACF Fields Indexer',
            'ACF Indexer',
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_view']
        );
    }

    public function register_settings() {
        // Register Field List Setting
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_FIELDS,
            ['type' => 'array', 'sanitize_callback' => [$this, 'sanitize_csv_input']]
        );

        // Register Post Types Setting
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_TYPES,
            ['type' => 'array', 'sanitize_callback' => [$this, 'sanitize_csv_input']]
        );

        add_settings_section('afi_main_section', 'Configuration', null, self::PAGE_SLUG);
        
        add_settings_field(
            'afi_types_input',
            'Target Post Types',
            [$this, 'render_post_types_input'],
            self::PAGE_SLUG,
            'afi_main_section'
        );

        add_settings_field(
            'afi_fields_input',
            'ACF Fields to Index',
            [$this, 'render_fields_input'],
            self::PAGE_SLUG,
            'afi_main_section'
        );
    }

    /**
     * Sanitizes comma-separated strings into arrays.
     */
    public function sanitize_csv_input($input) {
        // 1. Guard Clause: Empty input
        if ( empty($input) ) {
            return [];
        }
        
        // 2. Defensive Coding: If input is ALREADY an array (WordPress pre-casting),
        // we skip explode and just sanitize the items inside.
        if ( is_array($input) ) {
            return array_map('sanitize_key', $input);
        }
        
        // 3. String Processing: Explode comma-separated string
        $items = explode(',', $input);
        $clean = [];
        
        foreach ( $items as $item ) {
            $slug = sanitize_key(trim($item));
            if ( ! empty($slug) ) {
                $clean[] = $slug;
            }
        }
        
        return array_unique($clean);
    }

    public function render_post_types_input() {
        $this->render_text_input(self::OPTION_TYPES, 'e.g., post, page, product, custom_post_type');
    }

    public function render_fields_input() {
        $this->render_text_input(self::OPTION_FIELDS, 'e.g., isbn, author_bio, release_year');
    }

    private function render_text_input($option_name, $placeholder = '') {
        $options = get_option($option_name, []);
        $value   = is_array($options) ? implode(', ', $options) : '';
        
        echo sprintf(
            '<input type="text" name="%s" value="%s" class="regular-text" style="width:100%%; max-width:600px;">',
            esc_attr($option_name),
            esc_attr($value)
        );
        
        if ( $placeholder ) {
            echo '<p class="description">Comma-separated values. ' . esc_html($placeholder) . '</p>';
        }
    }

    /**
     * Handles the batch re-indexing process.
     */
    public function handle_batch_process() {
        if ( ! current_user_can('manage_options') ) wp_die('Unauthorized');
        check_admin_referer('afi_reindex_action', 'afi_nonce');

        if ( ! class_exists('AcfFieldsIndexer\Core\Indexer') ) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'Core/Indexer.php';
        }
        
        $indexer = new Indexer();
        $target_types = get_option(self::OPTION_TYPES, []);

        if ( empty($target_types) ) {
            wp_die('No post types configured. Please save settings first.');
        }

        $paged = isset($_GET['batch_page']) ? intval($_GET['batch_page']) : 1;
        $limit = 50; 

        $posts = get_posts([
            'post_type'      => $target_types, // Dynamic types
            'posts_per_page' => $limit,
            'paged'          => $paged,
            'fields'         => 'ids',
            'post_status'    => 'any'
        ]);

        if ( empty($posts) ) {
            wp_redirect(admin_url('options-general.php?page=' . self::PAGE_SLUG . '&msg=done'));
            exit;
        }

        foreach ( $posts as $post_id ) {
            $indexer->handle_post_update($post_id);
        }

        // Recursive redirect
        $next_page = $paged + 1;
        $redirect_url = admin_url('admin-post.php?action=afi_trigger_reindex&batch_page=' . $next_page);
        
        $redirect_url = add_query_arg([
            'afi_nonce'        => $_POST['afi_nonce'] ?? $_GET['afi_nonce'],
            '_wp_http_referer' => urlencode($_POST['_wp_http_referer'] ?? $_GET['_wp_http_referer'])
        ], $redirect_url);

        wp_redirect($redirect_url);
        exit;
    }

    public function render_view() {
        ?>
        <div class="wrap">
            <h1>ACF Fields Indexer</h1>
            
            <?php if ( isset($_GET['msg']) && $_GET['msg'] === 'done' ): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Success!</strong> All selected post types have been processed.</p>
                </div>
            <?php endif; ?>

            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button('1. Save Settings');
                ?>
            </form>

            <hr style="margin: 30px 0;">

            <h2>Maintenance</h2>
            <div class="card" style="max-width: 600px; padding: 10px 20px;">
                <h3>Regenerate Search Index</h3>
                <p>Use this tool if you have modified the settings above. It will iterate through all existing posts and update their search index.</p>
                
                <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                    <input type="hidden" name="action" value="afi_trigger_reindex">
                    <?php wp_nonce_field('afi_reindex_action', 'afi_nonce'); ?>
                    
                    <?php 
                    submit_button(
                        '2. Start Batch Indexing', 
                        'primary', 
                        'submit', 
                        true, 
                        ['onclick' => "return confirm('Are you sure? This will update all posts within the selected types.');"]
                    ); 
                    ?>
                </form>
            </div>
        </div>
        <?php
    }
}