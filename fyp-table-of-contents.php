<?php

/**
 * Plugin Name: FYP Table of Contents
 * Description: Generate a table of contents for posts based on headings.
 * Version: 1.0.0
 * stable: 1.0.0
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

define('FYPTACO_PLUGIN_URL', plugin_dir_url(__FILE__));

class FYPTACO_Table_of_Contents
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_filter('the_content', [$this, 'add_heading_ids'], 10);
        add_shortcode('fyplugins_table_of_contents', [$this, 'render_shortcode']);
    }

    public function enqueue_styles()
    {
        wp_enqueue_style('fyp-table-of-contents-style', FYPTACO_PLUGIN_URL . 'assets/css/style.css', [], '1.0.0');
    }

    public function add_heading_ids($content)
    {
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        // Default to h2,h3 for content processing
        list($content, $_tree) = $this->build_heading_tree($content, ['h2', 'h3'], []);
        return $content;
    }

    public function render_shortcode($atts)
    {
        $atts = shortcode_atts([
            'min_headings' => 3,
            'include' => 'h2,h3',
            'exclude' => '',
            'title' => __('Table of Contents', 'fyp-table-of-contents'),
            'class' => '',
            'toggle' => 'false',
            'default_toggle' => 'show', // 'show' or 'hide'
        ], $atts, 'fyplugins_table_of_contents');

        $min_headings = intval($atts['min_headings']);
        $title = sanitize_text_field($atts['title']);
        $class = sanitize_html_class($atts['class']);
        $toggle = filter_var($atts['toggle'], FILTER_VALIDATE_BOOLEAN);
        $default_toggle = in_array($atts['default_toggle'], ['show', 'hide']) ? $atts['default_toggle'] : 'show';

        // Parse include/exclude
        $include_levels = $this->parse_heading_levels($atts['include']);
        $exclude_selectors = $this->parse_exclude_selectors($atts['exclude']);

        global $post;
        if (!$post) {
            return;
        }

        $content = get_post_field('post_content', $post->ID);
        list($_, $tree, $headings_count) = $this->build_heading_tree($content, $include_levels, $exclude_selectors, true);

        if ($headings_count < $min_headings || empty($tree)) {
            return;
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
            echo '<input type="checkbox" class="fyptaco-toggle-checkbox" id="' . esc_attr($wrapper_id . '_toggle') . '" aria-hidden="true" ' . ($default_toggle = "show" ? 'checked ' : '') . '/>';
        }

        echo '<ul class="fyptaco-list" id="' . esc_attr($wrapper_id . '_list') . '">';
        $this->render_tree($tree);
        echo '</ul>';

        echo '</nav>';
        return ob_get_clean();
    }

    private function render_tree($nodes)
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
     * Build heading tree and inject IDs.
     * @param string $content
     * @param array $include_levels (e.g. ['h2','h3'])
     * @param array $exclude_selectors
     * @param bool $count_headings
     * @return array [$content, $tree, $headings_count]
     */
    private function build_heading_tree($content, $include_levels, $exclude_selectors = [], $count_headings = false)
    {
        $pattern = '/<h([1-6])([^>]*)>(.*?)<\/h\1>/is';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $tree = [];
        $stack = [];
        $headings_count = 0;

        foreach ($matches as $index => $heading) {
            $level = intval($heading[1]);
            $tag = 'h' . $level;

            // Check include
            if (!in_array($tag, $include_levels, true)) {
                continue;
            }

            // Check exclude
            $attrs = $heading[2];
            $text = wp_strip_all_tags($heading[3]);
            $skip = false;
            foreach ($exclude_selectors as $selector) {
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

            // Inject ID if missing
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
     * Parse include parameter (e.g. "h2,h3,h4")
     * @param string $str
     * @return array
     */
    private function parse_heading_levels($str)
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
     * Parse exclude parameter (e.g. "Introduction,.no-toc")
     * @param string $str
     * @return array
     */
    private function parse_exclude_selectors($str)
    {
        $selectors = array_filter(array_map('trim', explode(',', $str)));
        return $selectors;
    }
}

FYPTACO_Table_of_Contents::get_instance();

// Add a minimal JS for toggle functionality (assets/js/toggle.js):
// (You need to create this file in your plugin's assets/js/ directory)
/*
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.fyptaco-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var nav = btn.closest('.fyptaco_wrapper');
            var list = nav.querySelector('.fyptaco-table');
            var expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            list.style.display = expanded ? 'none' : 'block';
        });
    });
});
*/
