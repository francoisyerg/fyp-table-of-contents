=== FYP Table of Contents ===
Contributors: francoisyerg
Donate link: https://buymeacoffee.com/francoisyerg
Tags: table of contents, toc, navigation, seo, user experience
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

- **🚀 Automatic Generation:** Instantly creates a structured table of contents from your content headings
- **🎨 Fully Customizable:** Control appearance, styling, and which headings to include/exclude
- **📱 Responsive Design:** Works perfectly on desktop, tablet, and mobile devices
- **⚡ Performance Optimized:** Lightweight code with minimal impact on page load times
- **🔧 Easy Integration:** Simple shortcode implementation - no coding required
- **🎪 Toggle Functionality:** Optional collapse/expand feature for better UX
- **🌈 Theme Compatible:** Works seamlessly with any WordPress theme
- **📊 Smart Detection:** Only displays when minimum heading requirements are met

== Usage ==

To display the table of contents on your website, simply add the `[fyplugins_table_of_contents]` shortcode to any page or post where you want it to appear.

== Shortcode Parameters ==

The `[fyplugins_table_of_contents]` shortcode supports the following parameters to customize its behavior:

- **`min_headings`** (integer): Minimum number of headings required to display the table of contents. Default: `3`.
- **`included`** (string): Comma-separated list of heading levels to include (e.g., `h2,h3,h4`). Default: `h2,h3`.
- **`excluded`** (string): Comma-separated list of headings or CSS selectors to exclude from the table of contents (e.g., `.hidden_title,h4`). Default: empty.
- **`title`** (string): Custom title for the table of contents. Default: `Table of Contents`.
- **`class`** (string): Additional CSS classes for custom styling. Default: empty.
- **`toggle`** (boolean): Show or hide a toggle button to collapse/expand the table of contents. Accepts `true` or `false`. Default: `false`.
- **`default_toggle`** (string): Set the initial toggle state of the table of contents. Accepts `show` or `hide`. Default: `show`.

**Basic Usage:**
```
[fyplugins_table_of_contents]
```

**Advanced Usage Example:**
```
[fyplugins_table_of_contents min_headings="2" included="h2,h3,h4" excluded=".no-toc" title="Content Navigation" class="custom-toc" toggle="true" default_toggle="show"]
```

== Installation ==

= Automatic Installation =
1. Log in to your WordPress admin panel
2. Go to Plugins → Add New
3. Search for "FYP Table of Contents"
4. Click "Install Now" and then "Activate"
5. Add the shortcode `[fyplugins_table_of_contents]` to any post or page

= Manual Installation =
1. Download the plugin files from WordPress.org
2. Upload the plugin files to the `/wp-content/plugins/fyp-table-of-contents` directory via FTP
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Add the shortcode `[fyplugins_table_of_contents]` to your content

= Getting Started =
Once activated, simply add the shortcode `[fyplugins_table_of_contents]` anywhere in your post or page content where you want the table of contents to appear. The plugin will automatically scan your content for headings and generate the table of contents.

== Frequently Asked Questions ==

= Does this plugin work with any theme? =
Yes, FYP Table of Contents is designed to work with all WordPress themes. If you notice any display issues, you can adjust your theme's CSS or customize the table of contents output using the plugin's shortcode parameters.

= Can I customize how the table of contents is displayed? =
Absolutely! You can personalize the table of contents using shortcode parameters such as `title`, `class`, `include`, `exclude`, and `toggle`. This allows you to match the table of contents to your site's style and needs.

= Can I choose which headings appear in the table of contents? =
Yes, you can specify which heading levels (H1–H6) to include or exclude using the `include` and `exclude` shortcode parameters.

= Will this plugin affect my site's performance? =
FYP Table of Contents is lightweight and optimized for performance. It only generates and displays the table of contents when needed, so it has minimal impact on your site's speed.

= Can I use multiple table of contents on the same page? =
Yes, you can add multiple instances of the shortcode on the same page with different parameters to create customized table of contents for different sections.

= Can I style the table of contents to match my theme? =
Yes, you can use the `class` parameter to add custom CSS classes and style the table of contents to perfectly match your theme's design.

= What happens if my content doesn't have enough headings? =
The plugin only displays the table of contents when the minimum number of headings (default: 3) is met. You can adjust this using the `min_headings` parameter.

= Is the plugin translation ready? =
The plugin is built with internationalization in mind and can be translated into other languages.

= How do I exclude specific headings from the table of contents? =
Use the `excluded` parameter with CSS selectors or specific heading text. For example: `excluded=".no-toc,#skip-this"`

== Screenshots ==

1. **Basic Table of Contents** - Simple, clean table of contents generated automatically from page headings
2. **Toggle Functionality** - Table of contents with collapse/expand toggle button

== Support ==

If you need help with the plugin, please:

1. Check the FAQ section above for common questions
2. Visit the [plugin support forum](https://wordpress.org/support/plugin/fyp-table-of-contents/)

== Upgrade Notice ==

= 1.0.0 =
Initial release of FYP Table of Contents. Start improving your content navigation today!

== Changelog ==

= 1.0.0 =
* Initial release
* Automatic table of contents generation from heading tags
* Customizable shortcode with multiple parameters
* Responsive design for all devices
* Theme compatibility across all WordPress themes
* Toggle functionality for collapsible table of contents
* Smart heading detection and filtering
