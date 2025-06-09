=== FYP Table of Contents ===
Contributors: francoisyerg
Donate link: https://buymeacoffee.com/francoisyerg
Tags: 
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily add an automatic, customizable table of contents to your posts and pages to improve navigation and user experience.

== Description ==

FYP Table of Contents automatically generates a structured, customizable table of contents for your WordPress posts and pages. This helps readers quickly navigate long articles by providing clickable links to each section, improving both user experience and SEO.

Ideal for blogs, documentation, tutorials, and any content-rich site, FYP Table of Contents is lightweight, easy to use, and compatible with any theme. You can control where the table appears, customize its appearance, and exclude specific headings as needed.

Whether you want to enhance readability, boost engagement, or make your content more accessible, FYP Table of Contents offers a flexible and practical solution.

**Key Features:**

- **Automatic Table of Contents:** Instantly generates a structured table of contents for your posts and pages.
- **Customizable Appearance:** Easily adjust the style, position, and headings included in the TOC.
- **Supports All Headings:** Choose which heading levels (H1–H6) to include or exclude.
- **Smooth Navigation:** Clickable links allow readers to jump to any section of your content.
- **Theme Compatibility:** Works seamlessly with any WordPress theme.
- **Lightweight & Fast:** Optimized for performance with minimal impact on site speed.

== Usage ==

To display the table of contents on your website, simply add the `[fyplugins_table_of_contents]` shortcode to any page or post where you want it to appear.

== Shortcode Parameters ==
The `[fyplugins_table_of_contents]` shortcode supports the following parameters to customize its behavior:

- `min_headings` (integer): Minimum number of headings required to display the table of contents. Default is `3`.
- `include` (string): Comma-separated list of heading levels to include (e.g., `h2,h3,h4`). Default is `h2,h3`.
- `exclude` (string): Comma-separated list of headings or CSS selectors to exclude from the table of contents. Optional.
- `title` (string): Custom title for the table of contents. Default is `Table of Contents`.
- `class` (string): Additional CSS classes for custom styling. Optional.
- `toggle` (boolean): Show a toggle button to collapse/expand the table of contents. Accepts `true` or `false`. Default is `false`.

**Example usage:**

[fyplugins_table_of_contents min_headings="3" include="h2,h3" exclude="" title="Table of Contents" class="" toggle="false"]

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/fyp-table-of-contents` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Include the shortcode [fyplugins_table_of_content] in your pages / posts

== Frequently Asked Questions ==

= Does this plugin work with any theme? =
Yes, FYP Table of Contents is designed to work with all WordPress themes. If you notice any display issues, you can adjust your theme's CSS or customize the table of contents output using the plugin's shortcode parameters.

= Can I customize how the table of contents is displayed? =
Absolutely! You can personalize the table of contents using shortcode parameters such as `title`, `class`, `include`, `exclude`, and `toggle`. This allows you to match the table of contents to your site's style and needs.

= Can I choose which headings appear in the table of contents? =
Yes, you can specify which heading levels (H1–H6) to include or exclude using the `include` and `exclude` shortcode parameters.

= Will this plugin affect my site's performance? =
FYP Table of Contents is lightweight and optimized for performance. It only generates and displays the table of contents when needed, so it has minimal impact on your site's speed.

== Screenshots ==



== Changelog ==

= 1.0.0 =
* Initial release

== Upcoming Improvements (TODO) ==
