=== Cite ===
Contributors: MENJ
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CEJ9HFWJ94BG4
Tags: Cite, citation, reference, academic, guest author, BibTeX, RIS, EndNote, analytics, Wikipedia
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 2.1.5
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Professional academic citation plugin with BibTeX/RIS export, analytics, and 10 citation formats including Wikipedia {{cite web}} template

== Description ==

The most comprehensive citation plugin for WordPress! Help readers properly cite your academic articles with professional citation formats, export options, and powerful analytics.

= Key Features =

**📝 10 Citation Formats**
* APA (American Psychological Association)
* MLA (Modern Language Association)
* IEEE (Institute of Electrical and Electronics Engineers)
* Harvard
* Chicago
* Vancouver
* AMA (American Medical Association)
* ASA (American Sociological Association)
* Turabian
* **Wikipedia {{cite web}} template** - Ready to paste into Wikipedia articles

**📤 Export Options**
* BibTeX format (for LaTeX)
* RIS format (for Zotero, Mendeley, EndNote)
* EndNote format
* One-click download

**👥 Multiple Author Support**
* Guest author custom field
* Co-Authors Plus plugin compatibility
* "et al." formatting for many authors
* Proper author name formatting

**🔬 Academic Metadata**
* ORCID identifier support
* Google Scholar meta tags
* Schema.org structured data

**📊 Citation Analytics**
* Track most popular citation formats
* Monitor most cited posts
* Export format statistics
* Built-in analytics dashboard

**🎨 UI/UX Features**
* One-click copy to clipboard
* "Citation copied!" toast notification
* Expand/collapse toggle
* Light and dark mode support
* Fully accessible (ARIA labels, keyboard navigation)
* Mobile responsive

**⚙️ Display Options**
* Auto-display (no shortcode needed)
* Position control (top/bottom)
* Per-post-type settings
* Exclude specific posts/pages
* Manual placement with shortcode

**🔐 Security & Performance**
* XSS protection with proper output escaping
* SQL injection prevention with prepared statements
* CSRF protection with post-specific nonces
* Rate limiting on analytics
* Comprehensive input validation
* Sanitized and escaped output
* Optimized database queries
* Minimal performance impact

= How To Use =

**Basic Usage:**
Add `[cite]` shortcode to any post or page.

**Guest Authors:**
Add a custom field: `guest-author` with the author's name.

**Academic Identifiers:**
Add custom fields:
* `orcid` - Author ORCID

**Auto-Display:**
Enable in Settings → Cite → Display Options

= Demo =

*	[Click here](https://menj.net/demo) for live demo

= Languages =

The plugin is internationalized and ready for translation. Want to help translate? [Contact us](https://menj.net/contact)

== Installation ==

1. Upload the `cite` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Configure settings in WordPress Admin → Cite
4. Add `[cite]` shortcode to posts, or enable auto-display
5. (Optional) Add custom fields for guest authors, ORCID, etc.

== Frequently Asked Questions ==

= Who should use this plugin? =

Perfect for academics, researchers, scholars, and anyone publishing citable content on WordPress.

= How do I add a guest author? =

Add a custom field named `guest-author` with the author's name. The plugin will use this instead of the WordPress author.

= Does it work with Co-Authors Plus? =

Yes! The plugin automatically detects and formats multiple authors from Co-Authors Plus.

= Which citation formats are available? =

The plugin includes 10 professional citation formats: APA, MLA, IEEE, Harvard, Chicago, Vancouver, AMA, ASA, Turabian, and Wikipedia. Users can switch between them using the dropdown on the frontend. The Wikipedia format generates a ready-to-paste {{cite web}} template.

= How do I enable analytics? =

Go to Settings → Cite → Metadata & SEO and check "Enable citation analytics". View analytics in Admin → Cite → Analytics.

= What are Google Scholar meta tags? =

Meta tags that help Google Scholar properly index your academic content. Enable in Settings → Cite → Metadata & SEO.

= Can users export citations? =

Yes! Users can export citations in BibTeX, RIS, and EndNote formats with one click.

= Is it accessible? =

Yes! The plugin includes ARIA labels, keyboard navigation support, and screen reader compatibility.

= How do I customize the styling? =

The plugin includes comprehensive CSS that you can override in your theme's stylesheet.

= Can I exclude certain posts? =

Yes! Go to Settings → Cite → Display Options and enter post IDs to exclude (comma-separated).

= Does it work on mobile? =

Yes! The citation box is fully responsive and works perfectly on all devices.

= Is this plugin secure? =

Yes! Version 2.1.5 includes comprehensive security hardening with XSS protection, SQL injection prevention, CSRF protection, rate limiting, and proper input validation.

== Screenshots ==

1. The Cite settings screen with tabbed interface
2. Citation box with multiple export options
3. Analytics dashboard showing usage statistics
4. Google Scholar meta tags in page source
5. Mobile responsive citation display

== Changelog ==

= 2.1.5 - 2026-01-31 =
**CLEANUP**
* Removed unnecessary "New" badge from Wikipedia format card
* Removed unused CSS classes

= 2.1.4 - 2026-01-31 =
**BUG FIX**
* Fixed Analytics page Export Statistics not showing format labels
* Fixed CSS class conflict between badge styles

= 2.1.3 - 2026-01-31 =
**ICON CLEANUP**
* Replaced frontend citation box PNG icon with inline SVG
* Removed cite-icon.png file from plugin completely
* All icons now use consistent inline SVG approach

= 2.1.2 - 2026-01-31 =
**ICON UPDATE**
* Replaced PNG menu icon with clean, minimalist SVG quotation mark icon
* Icon now uses WordPress admin color scheme automatically

= 2.1.1 - 2026-01-31 =
**UI/UX IMPROVEMENTS**
* Completely redesigned Settings page with modern, minimalist interface
* New card-based layout with toggle switches and visual selectors
* Citation formats displayed in attractive grid with icons
* Wikipedia format highlighted with "New" badge
* Redesigned Analytics dashboard to match Settings page design
* Added stats overview cards (Total Views, Citations Copied, File Exports)
* Modern tables with badges and hover states
* Empty states with icons when no data available
* Improved responsive design for mobile devices
* Added SVG icons throughout the admin interface

= 2.1.0 - 2026-01-31 =
**WIKIPEDIA CITATION SUPPORT**
* Added Wikipedia {{cite web}} template as 10th citation format
* Wikipedia format uses proper parameter names per Wikipedia documentation:
  - Single author: `|author=Full Name`
  - Multiple authors: `|author1=Name1 |author2=Name2` etc.
  - Dates in "D Month YYYY" format (e.g., "31 January 2026")
  - Includes `|url=`, `|title=`, `|website=`, `|language=`, `|access-date=`
* Added `wpcp_get_authors_array()` helper function for multiple author support
* Added `wpcp_get_language_code()` helper function for site language detection
* Added `wpcp_format_date_dmy()` helper function for Wikipedia date format
* Wikipedia citations display in monospace font for easy copy/paste
* Ready to paste directly into Wikipedia articles

= 2.0.5 - 2026-01-30 =
**CODE REORGANIZATION RELEASE**
* Reorganized assets into `assets/css/` and `assets/js/` directories
* Extracted inline JavaScript to separate `assets/js/frontend.js` file
* JavaScript now uses `wp_localize_script` pattern for data passing
* Fixed CSS selectors to use class-based targeting instead of ID selectors
* Removed external Google Fonts dependency (GDPR compliance)
* Using system font stack with Roboto as optional enhancement
* Fixed version number inconsistencies across all files
* Improved code organization and maintainability
* Added `WPCP_ASSETS_URL` constant for cleaner asset paths
* Renamed asset handles to use `wpcp-` prefix consistently
* Sanitized `$_SERVER['REMOTE_ADDR']` access in rate limiting

= 2.0.4 - 2026-01-29 =
**MAINTENANCE RELEASE**
* Minor bug fixes and improvements

= 2.0.3 - 2026-01-29 =
**MAINTENANCE RELEASE**
* Minor bug fixes and improvements

= 2.0.2 - 2026-01-29 =
**UX IMPROVEMENTS RELEASE**
* Removed redundant Citation Template field from General Settings
* Improved Metadata & SEO tab with detailed explanations
* Added comprehensive info boxes for Google Scholar meta tags
* Added comprehensive info boxes for Citation Analytics
* Added "View Analytics Dashboard" button when analytics enabled
* Fixed Preview tab styling (now loads frontend CSS correctly)
* Clarified what Google Scholar meta tags are added
* Clarified what Citation Analytics tracks
* Added technical details (database, rate limiting, privacy)
* Improved admin interface user experience
* Better onboarding for new users
* More transparent about plugin functionality

= 2.0.1 - 2026-01-29 =
**SECURITY HARDENED RELEASE**
* **CRITICAL:** Fixed XSS vulnerabilities in JavaScript output
* **CRITICAL:** Added comprehensive output escaping for all dynamic content
* **HIGH:** Implemented SQL injection prevention with prepared statements
* **HIGH:** Enhanced CSRF protection with post-specific nonces
* **HIGH:** Added comprehensive input validation on all forms
* **MEDIUM:** Implemented rate limiting on analytics tracking
* **MEDIUM:** Added direct file access prevention
* Added security headers (X-Content-Type-Options, X-Frame-Options)
* Enhanced error handling with proper logging
* Improved database query security
* Added validation for all shortcode attributes
* Sanitized all helper function outputs
* Client-side XSS prevention in export functions
* Added constants for secure path management
* Database table indexes optimized
* All admin output properly escaped
* Enhanced capability checks
* Improved nonce implementation (post-specific)
* Added rate limiting to prevent DoS attacks
* Better error messages (no system info leakage)

= 2.0.0 - 2024 =
* **Major Update!** Complete rewrite with professional features
* Added 3 new citation styles: AMA, ASA, Turabian
* Added BibTeX export functionality
* Added RIS export for reference managers
* Added EndNote export format
* Added ORCID identifier support
* Added citation analytics tracking
* Added analytics dashboard
* Added guest-author custom field support
* Added Co-Authors Plus plugin compatibility
* Added "et al." formatting for multiple authors
* Added copy-to-clipboard button
* Added "Citation copied!" notification
* Added export menu with dropdown
* Added expand/collapse toggle
* Added auto-display option (no shortcode needed)
* Added position control (top/bottom)
* Added per-post-type settings
* Added post exclusion feature
* Added Google Scholar meta tags
* Added Schema.org structured data
* Added ARIA labels for accessibility
* Added keyboard navigation support
* Added light/dark mode support
* Added mobile responsive design
* Added tabbed admin interface
* Added live preview in admin
* Improved admin UI/UX
* Improved code organization
* Improved performance

= 1.3.0 =
* Added guest-author custom field support
* Added Co-Authors Plus plugin compatibility
* Added copy-to-clipboard button
* Added copy notification
* Improved admin interface with better documentation
* Enhanced CSS with light/dark mode support

= 1.2.3 =
* Fixed {date} template tag to get last accessed date (today's date)

= 1.2.1 =
* Added support for author name
* Updated default cite text
* Added reference samples

= 1.2 =
* Wrapped function displayTodaysDate in if statement

= 1.1 =
* Added publication date template tag

= 1.0 =
* Initial release

== Upgrade Notice ==

= 2.1.5 =
CLEANUP! Removed unnecessary "New" badge and unused CSS.

= 2.1.4 =
BUG FIX! Fixed Analytics page not showing export format labels (BibTeX, RIS, EndNote).

= 2.1.3 =
ICON CLEANUP! All PNG icons replaced with inline SVGs. Smaller plugin size and consistent icon styling.

= 2.1.2 =
ICON UPDATE! New minimalist SVG menu icon that integrates with WordPress admin color scheme.

= 2.1.1 =
UI/UX IMPROVEMENTS! Completely redesigned Settings and Analytics pages with modern, minimalist interface. New card-based layout, toggle switches, stats overview cards, and improved mobile responsiveness.

= 2.1.0 =
NEW FEATURE! Added Wikipedia {{cite web}} template support. Generate ready-to-paste citations for Wikipedia articles with proper author formatting and date format. Recommended upgrade for academic users.

= 2.0.5 =
CODE REORGANIZATION! JavaScript extracted to separate file, CSS selectors fixed, Google Fonts removed for GDPR compliance, version numbers synchronized. Recommended upgrade for better maintainability and privacy compliance.

= 2.0.2 =
UX IMPROVEMENTS! Simplified admin interface, removed redundant Citation Template field, added detailed explanations for Metadata & SEO features, fixed Preview tab styling. Recommended upgrade for better user experience.

= 2.0.1 =
CRITICAL SECURITY UPDATE! All users must upgrade immediately. Fixes XSS vulnerabilities, SQL injection risks, and implements comprehensive security hardening. No breaking changes - fully backward compatible.

= 2.0.0 =
MAJOR UPDATE! Professional citation plugin with BibTeX/RIS export, analytics, and 9 citation formats. Highly recommended upgrade for all users!

= 1.3.0 =
Added guest author support, copy-to-clipboard functionality, and Co-Authors Plus compatibility

== Privacy Policy ==

This plugin stores citation analytics data (if enabled) including:
- Post ID
- Citation style selected
- Action type (view, copy, export)
- Timestamp

This data is stored locally in your WordPress database and is not shared with any third parties. Analytics can be disabled in the plugin settings.

**Note:** As of version 2.1.5, this plugin no longer loads external Google Fonts, improving GDPR compliance.

== Credits ==

Developed by MENJ
Special thanks to all contributors and users who have provided feedback

== Support ==

For support, feature requests, or bug reports:
* Visit the [support forum](https://wordpress.org/support/plugin/cite/)
* Email: support@menj.net
* Website: https://menj.net

== Security ==

Security issues should be reported privately to security@menj.net. Please do not create public issues for security vulnerabilities.

Version 2.1.5 has been thoroughly tested for:
* XSS (Cross-Site Scripting)
* SQL Injection
* CSRF (Cross-Site Request Forgery)
* Authentication bypass
* Authorization issues
* Rate limiting
* Input validation
* Output escaping

== Roadmap ==

Planned features for future versions:
* Visual citation editor
* More citation styles
* Citation history/versioning
* CrossRef API integration
* Citation import from other formats
* Bulk citation operations
* Advanced analytics reports

== Technical Details ==

**Minimum Requirements:**
* WordPress 5.0 or higher
* PHP 7.2 or higher
* MySQL 5.6 or higher

**Recommended:**
* WordPress 6.4 or higher
* PHP 8.0 or higher
* MySQL 8.0 or higher

**Browser Support:**
* Chrome (latest)
* Firefox (latest)
* Safari (latest)
* Edge (latest)
* Mobile browsers

**Database:**
Creates one table: `wp_cite_analytics` (when analytics enabled)

**Custom Fields Supported:**
* `guest-author` - Override post author
* `orcid` - ORCID identifier

**Hooks & Filters:**
* `wpcp_default_setting` - Filter default settings
* `wpcp_citation_styles` - Filter citation style formats
* (More hooks available for developers)

**File Structure (v2.1.5):**
* `cite.php` - Main plugin file
* `assets/css/frontend.css` - Frontend styles
* `assets/css/admin.css` - Admin styles
* `assets/css/block-editor.css` - Block editor styles
* `assets/js/frontend.js` - Frontend JavaScript
* `assets/js/block.js` - Gutenberg block

== Contributing ==

Want to contribute? Visit our [GitHub repository](https://github.com/menj/cite)

== License ==

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.

This plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this plugin. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
