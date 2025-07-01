<?php

/**
 * Plugin Name: FYP Table of Contents
 * Description: Generate a table of contents for posts based on headings.
 * Version: 1.0.0
 * Author: François Yerg
 * Author URI: https://www.francoisyerg.net
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fyp-table-of-contents
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

class FYPTACO_Table_of_Contents
{
    private static $instance = null;
    private bool $processing = false;
    private $processing_shortcode = false;

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
     * Add IDs to headings in the content for linking.
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

        $this->processing = true;

        list($content, $_tree) = $this->build_heading_tree($content, ['h2', 'h3'], []);

        $this->processing = false;

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

        $content = get_post_field('post_content', $post->ID);
        $content = do_shortcode($content); // Important pour Divi
        list($_, $tree, $headings_count) = $this->build_heading_tree($content, $included_levels, $excluded_selectors, true);

        if ($headings_count < $min_headings || empty($tree)) {
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
        return ob_get_clean();
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

        return [$content, $tree, $headings_count];
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
}

FYPTACO_Table_of_Contents::get_instance();
