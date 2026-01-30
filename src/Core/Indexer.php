<?php
namespace AcfFieldsIndexer\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Indexer {

    public function __construct() {
        // Priority 20 ensures ACF has finished saving its own data
        add_action('acf/save_post', [$this, 'handle_post_update'], 20);
    }

    /**
     * Main handler triggered on save.
     */
    public function handle_post_update($post_id) {
        $post_type = get_post_type($post_id);
        $allowed_types = $this->get_allowed_post_types();

        // 1. Scope Validation
        if ( ! in_array($post_type, $allowed_types) ) {
            return;
        }

        // 2. Content Retrieval
        $current_content = get_post_field('post_content', $post_id);
        
        // 3. Cleanup (Always remove old index to prevent stale data)
        $clean_content = $this->remove_existing_index($current_content);

        // 4. Generate New Index
        $search_blob = $this->generate_search_blob($post_id);
        
        $final_content = $clean_content;

        // Append new data if available
        if ( ! empty($search_blob) ) {
            $final_content .= $this->wrap_hidden_block($search_blob);
        }

        // 5. Atomic Update
        // Only update if content actually changed to avoid infinite loops or unnecessary revisions
        if ( $current_content !== $final_content ) {
            
            // Temporarily unhook to prevent recursion
            remove_action('acf/save_post', [$this, 'handle_post_update'], 20);
            
            wp_update_post([
                'ID'           => $post_id,
                'post_content' => $final_content,
            ]);

            add_action('acf/save_post', [$this, 'handle_post_update'], 20);
        }
    }

    private function get_allowed_post_types(): array {
        $option = get_option('afi_target_post_types', []);
        return is_array($option) ? $option : [];
    }

    private function generate_search_blob($post_id): string {
        $fields = get_option('afi_indexed_fields', []); 

        if ( empty($fields) || ! is_array($fields) ) {
            return '';
        }

        $values = [];

        foreach ( $fields as $field_key ) {
            $value = get_field($field_key, $post_id);
            
            if ( is_array($value) ) {
                $value = implode(' ', $value); // Handle multi-selects/checkboxes
            }

            if ( $value ) {
                $values[] = wp_strip_all_tags($value);
            }
        }

        return implode(' ', $values);
    }

    private function remove_existing_index($content): string {
        // Regex targets the specific hidden Gutenberg block
        $pattern = '/\s*<div class="afi-search-index".*?<\/div>\s*/s';
        
        $new_content = preg_replace($pattern, '', $content);
        
        return $new_content ?? $content;
    }

    private function wrap_hidden_block($content): string {
        // Uses 'afi-search-index' class for uniqueness
        return <<<HTML

<div class="afi-search-index" style="display:none" aria-hidden="true">{$content}</div>
HTML;
    }
}