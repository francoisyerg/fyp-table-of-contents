<?php

/**
 * Plugin Name: FYP Table of Contents
 * Description: Generate a table of contents for posts based on headings.
 * Version: 1.0.1
 * Author: François Yerg
 * Author URI: https://www.francoisyerg.net
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fyp-table-of-contents
 * Domain Path: /languages
*/

// Exit if accessed directly
defined('ABSPATH') || exit;

class FYPTACO_Table_of_Contents
{
    private const CACHE_GROUP = 'fyplugins_table_of_contents';
    private const CACHE_EXPIRATION = 3600; // 1 hour

    private static $instance = null;
    private bool $processing = false;
    private bool $processing_shortcode = false;

    /**
     * Get the singleton instance of the class.
     *
     * @return FYPTACO_Table_of_Contents
     */
    public static function get_instance(): FYPTACO_Table_of_Contents
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation.
     *
     * @return void
     */
    private function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);

        add_filter('the_content', [$this, 'add_heading_ids'], 10);

        add_shortcode('fyplugins_table_of_contents', [$this, 'render_shortcode']);

        // Cache management hooks
        add_action('save_post', [$this, 'clear_post_cache']);
        add_action('delete_post', [$this, 'clear_post_cache']);
        add_action('wp_update_post', [$this, 'clear_post_cache']);
        add_action('switch_theme', [$this, 'clear_all_cache']);
        add_action('activated_plugin', [$this, 'clear_all_cache']);
        add_action('deactivated_plugin', [$this, 'clear_all_cache']);
    }

    /**
     * Enqueue styles for the table of contents.
     *
     * @return void
     */
    public function enqueue_styles(): void
    {
        wp_enqueue_style('fyp-table-of-contents-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], '1.0.0');
    }

    /**
     * Check if the shortcode is present in the content.
     *
     * @param string $content The content to check.
     * @return bool True if the shortcode is present, false otherwise.
     */
    private function has_shortcode($content): bool
    {
        return has_shortcode($content, 'fyplugins_table_of_contents');
    }

    /**
     * Add IDs to headings in the content for linking.
     * Only processes content if the shortcode is present.
     *
     * @param string $content The post content.
     * @param string|null $render_slug Optional render slug.
     * @return string The modified content with IDs added to headings.
     */
    public function add_heading_ids($content, $render_slug = null): string
    {
        if (
            $this->processing || // évite la récursion
            !is_singular() ||
            (!is_admin() && !in_the_loop()) ||
            (!is_admin() && !is_main_query())
        ) {
            return $content;
        }

        global $post;
        if (!$post) {
            return $content;
        }

        // Only process if the shortcode is present in the content
        if (!$this->has_shortcode($content)) {
            return $content;
        }

        // Try to get cached content first
        $cache_key = $this->get_content_cache_key($post->ID, $content);
        $cached_content = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached_content !== false) {
            return $cached_content;
        }

        $this->processing = true;

        list($content, $_tree) = $this->build_heading_tree($content, ['h2', 'h3'], []);

        $this->processing = false;

        // Cache the processed content
        wp_cache_set($cache_key, $content, self::CACHE_GROUP, self::CACHE_EXPIRATION);
        $this->track_cache_key($post->ID, $cache_key);

        return $content;
    }

    /**
     * Render the shortcode for the table of contents.
     *
     * @param array $atts Shortcode attributes.
     * @return string The rendered HTML for the table of contents.
     */
    public function render_shortcode($atts): string
    {
        if ($this->processing_shortcode) {
            return '';
        }

        $this->processing_shortcode = true;

        $atts = shortcode_atts([
            'min_headings' => 3,
            'included' => 'h2,h3',
            'excluded' => '',
            'title' => __('Table of Contents', 'fyp-table-of-contents'),
            'class' => '',
            'toggle' => 'false',
            'default_toggle' => 'show',
        ], $atts, 'fyplugins_table_of_contents');

        $min_headings = intval($atts['min_headings']);
        $title = sanitize_text_field($atts['title']);
        $class = sanitize_html_class($atts['class']);
        $toggle = filter_var($atts['toggle'], FILTER_VALIDATE_BOOLEAN);
        $default_toggle = in_array($atts['default_toggle'], ['show', 'hide']) ? $atts['default_toggle'] : 'show';

        $included_levels = $this->parse_heading_levels($atts['included']);
        $excluded_selectors = $this->parse_exclude_selectors($atts['excluded']);

        global $post;
        if (!$post) {
            return '';
        }

        // Create cache key for shortcode
        $cache_key = $this->get_shortcode_cache_key($post->ID, $atts);
        $cached_output = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached_output !== false) {
            $this->processing_shortcode = false;
            return $cached_output;
        }

        $content = get_post_field('post_content', $post->ID);
        $content = do_shortcode($content); // Important pour Divi
        list($_, $tree, $headings_count) = $this->build_heading_tree($content, $included_levels, $excluded_selectors, true);

        if ($headings_count < $min_headings || empty($tree)) {
            $this->processing_shortcode = false;
            return '';
        }

        ob_start();

        $wrapper_id = uniqid('fyptaco_');
        echo '<nav id="' . esc_attr($wrapper_id) . '" class="fyptaco_wrapper ' . esc_attr($class) . '">';
        echo '<div class="fyptaco-header">';

        if ($title) {
            echo sprintf('<h2 class="fyptaco-title">%s</h2>', esc_html($title));
        }

        if ($toggle) {
            echo '
                <label class="fyptaco-toggle-label" for="' . esc_attr($wrapper_id . '_toggle') . '">
                    <span class="fyptaco-toggle" role="button" aria-expanded="true" aria-controls="' . esc_attr($wrapper_id . '_toggle') . '">
                        ' . esc_html__('Show/Hide', 'fyp-table-of-contents') . '
                    </span>
                </label>';
        }
        echo '</div>';

        if ($toggle) {
            echo '<input type="checkbox" class="fyptaco-toggle-checkbox" id="' . esc_attr($wrapper_id . '_toggle') . '" aria-hidden="true" ' . ($default_toggle === "show" ? 'checked ' : '') . '/>';
        }

        echo '<ul class="fyptaco-list" id="' . esc_attr($wrapper_id . '_list') . '">';
        $this->render_tree($tree);
        echo '</ul>';

        echo '</nav>';

        $output = ob_get_clean();

        // Cache the rendered output
        wp_cache_set($cache_key, $output, self::CACHE_GROUP, self::CACHE_EXPIRATION);
        $this->track_cache_key($post->ID, $cache_key);

        $this->processing_shortcode = false;
        return $output;
    }

    /**
     * Render the tree of headings.
     *
     * @param array $nodes The tree structure of headings.
     * @return void
     */
    private function render_tree($nodes): void
    {
        foreach ($nodes as $node) {
            echo '<li>';
            echo '<a href="#' . esc_attr($node['id']) . '">' . esc_html($node['title']) . '</a>';
            if (!empty($node['children'])) {
                echo '<ul>';
                $this->render_tree($node['children']);
                echo '</ul>';
            }
            echo '</li>';
        }
    }

    /**
     * Build a tree structure from the headings in the content.
     *
     * @param string $content The post content.
     * @param array $include_levels The heading levels to include (e.g., ['h2', 'h3']).
     * @param array $exclude_selectors Selectors to exclude from the headings.
     * @param bool $count_headings Whether to count the headings.
     * @return array An array containing the modified content, the tree structure, and the count of headings.
     */
    private function build_heading_tree($content, $included_levels, $excluded_selectors = [], $count_headings = false): array
    {
        // Create cache key for tree building
        $cache_key = $this->get_tree_cache_key($content, $included_levels, $excluded_selectors);
        $cached_result = wp_cache_get($cache_key, self::CACHE_GROUP);

        if ($cached_result !== false) {
            return $cached_result;
        }

        $pattern = '/<h([1-6])([^>]*)>(.*?)<\/h\1>/is';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $tree = [];
        $stack = [];
        $headings_count = 0;

        foreach ($matches as $index => $heading) {
            $level = intval($heading[1]);
            $tag = 'h' . $level;

            if (!in_array($tag, $included_levels, true)) {
                continue;
            }

            $attrs = $heading[2];
            $text = wp_strip_all_tags($heading[3]);
            $skip = false;
            foreach ($excluded_selectors as $selector) {
                if ($selector && (
                    stripos($attrs, $selector) !== false ||
                    stripos($text, $selector) !== false
                )) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            $slug = sanitize_title($text);
            $unique_id = 'fyptaco-heading-' . $index . '-' . $slug;

            if (strpos($attrs, 'id=') === false) {
                $new_heading = "<h{$level}{$attrs} id=\"{$unique_id}\">{$heading[3]}</h{$level}>";
                $content = str_replace($heading[0], $new_heading, $content);
            }

            $node = [
                'level' => $level,
                'title' => $text,
                'id' => $unique_id,
                'children' => [],
            ];

            while (!empty($stack) && $stack[count($stack) - 1]['level'] >= $level) {
                array_pop($stack);
            }

            if (empty($stack)) {
                $tree[] = $node;
                $stack[] = &$tree[count($tree) - 1];
            } else {
                $parent = &$stack[count($stack) - 1];
                $parent['children'][] = $node;
                $stack[] = &$parent['children'][count($parent['children']) - 1];
            }

            unset($node);
            $headings_count++;
        }

        $result = [$content, $tree, $headings_count];

        // Cache the result
        wp_cache_set($cache_key, $result, self::CACHE_GROUP, self::CACHE_EXPIRATION);

        return $result;
    }

    /**
     * Parse the heading levels from a comma-separated string.
     *
     * @param string $str The string containing heading levels.
     * @return array An array of valid heading levels.
     */
    private function parse_heading_levels($str): array
    {
        $levels = array_filter(array_map('trim', explode(',', strtolower($str))));
        $valid = [];
        foreach ($levels as $level) {
            if (preg_match('/^h[1-6]$/', $level)) {
                $valid[] = $level;
            }
        }
        return !empty($valid) ? $valid : ['h2', 'h3'];
    }

    /**
     * Parse the exclude selectors from a comma-separated string.
     *
     * @param string $str The string containing selectors to exclude.
     * @return array An array of selectors to exclude.
     */
    private function parse_exclude_selectors($str): array
    {
        $selectors = array_filter(array_map('trim', explode(',', $str)));
        return $selectors;
    }

    /**
     * Generate cache key for content processing.
     *
     * @param int $post_id The post ID.
     * @param string $content The content to process.
     * @return string The cache key.
     */
    private function get_content_cache_key($post_id, $content): string
    {
        return 'content_' . $post_id . '_' . md5($content . '_with_shortcode');
    }

    /**
     * Generate cache key for shortcode output.
     *
     * @param int $post_id The post ID.
     * @param array $atts The shortcode attributes.
     * @return string The cache key.
     */
    private function get_shortcode_cache_key($post_id, $atts): string
    {
        return 'shortcode_' . $post_id . '_' . md5(serialize($atts));
    }

    /**
     * Generate cache key for heading tree building.
     *
     * @param string $content The content to process.
     * @param array $included_levels The included heading levels.
     * @param array $excluded_selectors The excluded selectors.
     * @return string The cache key.
     */
    private function get_tree_cache_key($content, $included_levels, $excluded_selectors): string
    {
        $key_data = [
            'content' => md5($content),
            'levels' => $included_levels,
            'excluded' => $excluded_selectors
        ];
        return 'tree_' . md5(serialize($key_data));
    }

    /**
     * Track cache keys for a specific post to enable efficient cache clearing.
     *
     * @param int $post_id The post ID.
     * @param string $cache_key The cache key to track.
     * @return void
     */
    private function track_cache_key($post_id, $cache_key): void
    {
        if (!$post_id) {
            return;
        }

        $cache_keys = wp_cache_get('post_cache_keys_' . $post_id, self::CACHE_GROUP);

        if (!is_array($cache_keys)) {
            $cache_keys = [];
        }

        $cache_keys[] = $cache_key;

        // Store the cache keys list (expires after 1 day)
        wp_cache_set('post_cache_keys_' . $post_id, $cache_keys, self::CACHE_GROUP, 86400);
    }

    /**
     * Clear cache for a specific post.
     *
     * @param int $post_id The post ID.
     * @return void
     */
    public function clear_post_cache($post_id): void
    {
        if (!$post_id) {
            return;
        }

        // Get all cache keys for this post
        $cache_keys = wp_cache_get('post_cache_keys_' . $post_id, self::CACHE_GROUP);

        if ($cache_keys && is_array($cache_keys)) {
            foreach ($cache_keys as $key) {
                wp_cache_delete($key, self::CACHE_GROUP);
            }
        }

        // Clear the cache keys list
        wp_cache_delete('post_cache_keys_' . $post_id, self::CACHE_GROUP);

        // Clear common patterns for this post
        $patterns = [
            'content_' . $post_id,
            'shortcode_' . $post_id,
            'tree_'
        ];

        foreach ($patterns as $pattern) {
            // Note: WordPress object cache doesn't support wildcard deletion
            // This is a limitation, but we clear what we can
            wp_cache_delete($pattern, self::CACHE_GROUP);
        }
    }

    /**
     * Clear all plugin cache.
     *
     * @return void
     */
    public function clear_all_cache(): void
    {
        // WordPress object cache doesn't support group deletion
        // This is a limitation of the wp_cache_* functions
        // In a production environment, you might want to use a more sophisticated
        // caching system like Redis or Memcached with proper group deletion

        // For now, we'll just clear the cache we can track
        wp_cache_flush();
    }

    /**
     * Get cache statistics (for debugging).
     *
     * @return array Cache statistics.
     */
    public function get_cache_stats(): array
    {
        return [
            'cache_group' => self::CACHE_GROUP,
            'cache_expiration' => self::CACHE_EXPIRATION,
            'cache_enabled' => function_exists('wp_cache_get')
        ];
    }
}

FYPTACO_Table_of_Contents::get_instance();

// Plugin activation hook
register_activation_hook(__FILE__, function () {
    // Set a transient to show cache info notice
    set_transient('fyptaco_show_cache_notice', true, 30);
});
