=== Cite ===
Contributors: MENJ
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CEJ9HFWJ94BG4
Tags: Cite, citation, reference, academic, guest author, BibTeX, RIS, EndNote, CSL-JSON, CFF, Dublin Core, analytics, Wikipedia
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 2.8.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Professional academic citation plugin with 6 export formats (BibTeX, RIS, EndNote, CSL-JSON, CFF, Dublin Core), analytics, JSON-LD structured data, and 20 citation formats including Wikipedia {{cite web}} template

== Description ==

The most comprehensive citation plugin for WordPress! Help readers properly cite your academic articles with professional citation formats, export options, and powerful analytics.

= Key Features =

**📝 20 Citation Formats**
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
* **ACS (American Chemical Society)** - Chemistry citations
* **AIP (American Institute of Physics)** - Physics citations
* **NLM (National Library of Medicine)** - Medical citations
* **AAA (American Anthropological Association)** - Anthropology citations
* **APSA (American Political Science Association)** - Political science citations
* **OSCOLA (Oxford Legal Citations)** - UK legal citations
* **Nature (Nature Journal Style)** - Nature journal citations
* **ACM (Association for Computing Machinery)** - Computer science citations
* **Bluebook (US Legal Citation)** - US legal citations
* **ISO 690 (International Standard)** - International bibliographic standard

**📤 Export Options**
* BibTeX format (.bib) — LaTeX / Overleaf
* RIS format (.ris) — Universal legacy format
* EndNote format (.enw) — EndNote desktop
* CSL-JSON format (.json) — Modern reference managers (native)
* CFF format (.cff) — GitHub CITATION.cff
* Dublin Core format (.xml) — Institutional repositories
* Admin setting to enable/disable individual export formats
* One-click download

**👥 Multiple Author Support**
* Guest author custom field
* Co-Authors Plus plugin compatibility
* "et al." formatting for many authors
* Proper author name formatting

**🔬 Academic Metadata & SEO**
* JSON-LD ScholarlyArticle structured data for rich results
* Google Scholar meta tags (per-author tags for multi-author posts)
* Open Graph academic article tags for social media previews
* ORCID identifier support
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
* Gutenberg block with full sidebar controls
* Position control (top/bottom)
* Per-post-type settings
* Exclude specific posts/pages
* Manual placement with shortcode
* Format selector: dropdown, tabs, or pill buttons
* Per-post format overrides via shortcode attributes

**📑 Bibliography & Inline Citations**
* `[cite_bibliography]` shortcode for automatic numbered reference lists
* Inline mode: parenthetical in-text citations (Author, Year) or [1]
* Footnote-style markers with backlinks and deduplication
* 20 style-specific inline templates

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
Add `[cite]` shortcode to any post or page, or use the "Citation Box" Gutenberg block.

**Guest Authors:**
Add a custom field: `guest-author` with the author's name.

**Academic Identifiers:**
Add custom fields:
* `orcid` - Author ORCID

**Auto-Display:**
Enable in Settings → Cite → Display Options

= Languages =

The plugin is internationalized and ready for translation. Want to help translate? [Contact us](https://menj.net/contact)

== Installation ==

1. Upload the `cite` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu
3. Configure settings in WordPress Admin → Cite
4. Add `[cite]` shortcode to posts, use the "Citation Box" Gutenberg block, or enable auto-display
5. (Optional) Add custom fields for guest authors, ORCID, etc.

== Frequently Asked Questions ==

= Who should use this plugin? =

Perfect for academics, researchers, scholars, and anyone publishing citable content on WordPress.

= How do I add a guest author? =

Add a custom field named `guest-author` with the author's name. The plugin will use this instead of the WordPress author.

= Does it work with Co-Authors Plus? =

Yes! The plugin automatically detects and formats multiple authors from Co-Authors Plus.

= Which citation formats are available? =

The plugin includes 20 professional citation formats: APA, MLA, IEEE, Harvard, Chicago, Vancouver, AMA, ASA, Turabian, Wikipedia, ACS, AIP, NLM, AAA, APSA, OSCOLA, Nature, ACM, Bluebook, and ISO 690. Users can switch between them using the dropdown on the frontend. Site administrators can enable/disable specific formats in Settings → Cite → Formats. The Wikipedia format generates a ready-to-paste {{cite web}} template.

= How do I enable analytics? =

Go to Settings → Cite → Metadata & SEO and check "Enable citation analytics". View analytics in Admin → Cite → Analytics.

= What are Google Scholar meta tags? =

Meta tags that help Google Scholar properly index your academic content. Enable in Settings → Cite → Metadata & SEO.

= Can users export citations? =

Yes! Users can export citations in BibTeX, RIS, EndNote, CSL-JSON, CFF, and Dublin Core formats with one click.

= Is it accessible? =

Yes! The plugin includes ARIA labels, keyboard navigation support, and screen reader compatibility.

= How do I customize the styling? =

The plugin includes comprehensive CSS that you can override in your theme's stylesheet.

= Can I exclude certain posts? =

Yes! Go to Settings → Cite → Display Options and enter post IDs to exclude (comma-separated).

= Does it work on mobile? =

Yes! The citation box is fully responsive and works perfectly on all devices.

= Is this plugin secure? =

Yes! The plugin includes comprehensive security hardening with XSS protection, SQL injection prevention, CSRF protection, rate limiting, and proper input validation.

== Screenshots ==

1. The Cite settings screen with tabbed interface
2. Citation box with multiple export options
3. Analytics dashboard showing usage statistics
4. Google Scholar meta tags in page source
5. Mobile responsive citation display

== Changelog ==

= 2.8.0 - 2026-02-08 =
**REWRITTEN: Gutenberg Block**
* Converted from static save to dynamic block with server-side `render_callback`
* Eliminates block validation errors when re-editing saved posts
* Added all v2.6.0+ attributes: Display Mode, Page Number, Format Filtering, Clickable Links
* All 20 citation formats now available in the editor dropdown (previously only 9)
* Format list synced from PHP via `wp_localize_script` — stays in sync automatically
* Organised sidebar into four panels: Citation Settings, Features, Format Filtering, Override Metadata
* Updated block editor CSS to match plugin colour palette
* Updated export help text to include CSL-JSON, CFF, and Dublin Core

**FIXED: Toggle and Export Not Working with Gutenberg Block**
* Added unique instance counter to each citation box (`citation-box-{post_id}-{instance}`)
* Frontend JS now scopes all DOM queries within the specific container element
* Fixes double-binding issue when both block and shortcode render on the same post
* Toggle and export handlers no longer cancel each other out

**FIXED: Export Dropdown Clipping**
* Fixed export dropdown being clipped by `overflow: hidden` on the citation box — all 6 formats now visible
* Fixed same overflow clipping issue in the admin Preview tab
* Preserved rounded corner styling by applying border-radius directly to child elements

**FIXED: Analytics Not Tracking New Export Formats**
* Updated AJAX handler export format whitelist to include CSL-JSON, CFF, and Dublin Core (previously only BibTeX, RIS, EndNote were allowed)
* Added `publicationDateISO` to frontend JS config for accurate date output in CFF and Dublin Core exports

**IMPROVED: Shortcode Usage Reference**
* Updated Shortcode Usage section in the Preview tab with all supported attributes
* Added examples for `mode`, `page`, `formats`, `exclude_formats`, `link`, and `[cite_bibliography]` shortcode

= 2.7.0 - 2026-02-08 =
**NEW: Export Format Selection**
* Added Export Formats settings card in the Display tab
* Administrators can enable/disable individual export formats (BibTeX, RIS, EndNote, CSL-JSON, CFF, Dublin Core)
* Frontend export dropdown dynamically renders only the enabled formats
* Checkbox grid with format name, file extension badge, and compatibility description
* Validation ensures at least one export format remains enabled
* All 6 formats enabled by default

**IMPROVED: Preview Export Dropdown**
* Preview tab now includes a fully functional export dropdown matching enabled formats
* Toggling export format checkboxes in the Display tab instantly shows/hides the corresponding option in the Preview
* Export button in Preview opens and closes the dropdown with proper `aria-expanded` state

= 2.6.0 - 2026-02-07 =
**MAJOR FEATURE UPDATE: BIBLIOGRAPHY, INLINE CITATIONS, DISPLAY MODES & FORMAT OVERRIDES**

**NEW: Automatic Reference List (Bibliography)**
* Added `[cite_bibliography]` shortcode for generating numbered reference lists
* When `[cite_bibliography]` is present, `[cite]` shortcodes render as superscript footnote markers [1], [2], etc.
* Automatic deduplication: same post cited multiple times shares one bibliography entry with multiple backlinks
* Attributes: `style`, `heading`, `heading_tag`, `numbered`, `link_back`
* Filters: `wpcp_bibliography_heading`, `wpcp_bibliography_entry`, `wpcp_bibliography_output`
* Full ARIA accessibility: `doc-endnotes`, `doc-endnote`, `doc-noteref` roles

**NEW: Inline Citation Variants**
* Added `mode` attribute to `[cite]` shortcode: `box` (default), `inline`, `bibliography`
* Inline mode renders parenthetical in-text citations, e.g., (Smith, 2024) or [1]
* 20 style-specific inline templates (author-year and numeric styles)
* Added `page` attribute for page numbers in inline citations, e.g., `[cite mode="inline" page="42"]`
* Added `link` attribute to control clickable links on inline citations
* Numeric styles (IEEE, Vancouver, ACS, etc.) automatically use reference numbers when bibliography present
* Filters: `wpcp_inline_citation`, `wpcp_inline_template`

**NEW: Frontend Display Mode Toggle**
* Added `format_display_mode` setting: Dropdown (default), Tabs, or Pill Buttons
* Tabs mode: connected horizontal tabs with `role="tablist"` and full arrow key navigation
* Buttons mode: pill-style buttons with `role="radiogroup"` and keyboard support
* New admin card in Display Settings tab with visual descriptions for each mode
* Responsive: tabs and buttons wrap gracefully on narrow screens

**NEW: Per-Post Shortcode Format Overrides**
* Added `formats` attribute: `[cite formats="apa,mla,chicago"]` to show only specified formats
* Added `exclude_formats` attribute: `[cite exclude_formats="wikipedia"]` to hide specific formats
* When only 1 format is active, the selector is hidden entirely (no dropdown/tabs/buttons)
* Processing priority: `formats` override → `exclude_formats` filter → global enabled_formats fallback
* Works with all display modes (dropdown, tabs, buttons)

**IMPROVED: Admin Preview & Design Consistency**
* Preview tab now reflects the active Format Selector Style (dropdown, tabs, or buttons) instead of always showing a dropdown
* Live preview switching: changing the Format Selector Style radio in the Display tab instantly updates the Preview tab
* Tab and button selectors in the Preview are fully interactive (click to change format, citation text updates)
* Aligned frontend CSS colour palette to match admin design tokens (slate/cyan) — removed legacy bright blue and emerald green values
* Aligned admin analytics notice, stat icons, and format badges to the established slate/cyan/orange palette
* Added `.wpcp-radio-desc` admin style for radio card descriptions

**NEW: Additional Export Formats**
* Added CSL-JSON export (Citation Style Language JSON) — native format for Zotero, Mendeley, and modern reference managers
* Added CFF export (Citation File Format) — YAML-based format gaining traction via GitHub's native `CITATION.cff` support
* Added Dublin Core export (XML) — metadata standard for institutional repositories and digital libraries
* Export dropdown now offers 6 formats: BibTeX, RIS, EndNote, CSL-JSON, CFF, Dublin Core
* Proper MIME types: `application/json` for CSL-JSON, `application/xml` for Dublin Core
* All new formats tracked by analytics

= 2.5.0 - 2026-02-06 =
**NEW FEATURE: JSON-LD STRUCTURED DATA & ENHANCED SEO**

* Added: JSON-LD ScholarlyArticle structured data output in the page head for improved rich results eligibility
* Added: Open Graph academic article tags (`og:type`, `article:published_time`, `article:modified_time`, `article:author`, `article:section`)
* Added: `citation_language` and `citation_public_url` Google Scholar meta tags
* Improved: Google Scholar meta tags now output one `citation_author` tag per author for multi-author posts (previously combined into single tag)
* Added: Post type restriction — all SEO output (JSON-LD, Google Scholar, Open Graph) now respects the enabled post types setting
* Added: `wpcp_jsonld_data` filter for themes/plugins to modify JSON-LD schema before output
* Added: `wpcp_scholar_meta` filter for modifying Google Scholar meta tag data
* Added: `wpcp_og_academic_data` filter for modifying Open Graph tag data
* Added: Publisher logo from theme custom logo in JSON-LD output
* Added: Featured image and post excerpt included in JSON-LD when available
* UI: New "JSON-LD Structured Data" and "Open Graph Academic Tags" cards in Metadata & SEO settings tab

= 2.4.1 - 2026-02-06 =
**BUG FIX: CITATION ANALYTICS TRACKING**

* Fixed: Analytics not recording despite being enabled — consent gate blocked all tracking because the `wpcp_analytics_consent_granted` filter defaulted to `false` with no built-in mechanism to grant consent
* Fixed: Reordered server-side checks so `enable_analytics` is validated before the consent gate, avoiding unnecessary consent evaluation when analytics is disabled
* Added: Built-in consent cookie detection for popular GDPR plugins (CookieYes, Complianz, CookieBot, Moove GDPR)
* Added: Frontend analytics gating — tracking AJAX requests are now skipped client-side when analytics is disabled, eliminating unnecessary network requests
* Added: Client-side consent detection mirrors server-side checks for consistent behavior
* Changed: The `wpcp_analytics_consent_granted` filter now defaults to `null` (unset) instead of `false`, allowing the built-in cookie detection to run as a fallback when no custom filter is hooked
* Fixed: Admin settings description still showed "10 professional citation formats" instead of 20

= 2.4.0 - 2026-02-06 =
**MAJOR FEATURE UPDATE: COMPREHENSIVE CITATION MANAGEMENT**

**NEW CITATION FORMATS (10 added - now 20 total)**
* ACS (American Chemical Society) - Chemistry citations
* AIP (American Institute of Physics) - Physics citations
* NLM (National Library of Medicine) - Medical citations
* AAA (American Anthropological Association) - Anthropology citations
* APSA (American Political Science Association) - Political science citations
* OSCOLA (Oxford Legal Citations) - UK legal citations
* Nature (Nature Journal Style) - Nature journal citations
* ACM (Association for Computing Machinery) - Computer science citations
* Bluebook (US Legal Citation) - US legal citations
* ISO 690 (International Standard) - International bibliographic standard
* Expanded from 10 to 20 citation formats for comprehensive academic coverage
* All new formats support copy to clipboard, export (BibTeX/RIS/EndNote), and analytics tracking

**NEW FEATURE: SELECTIVE FORMAT VISIBILITY**
* Added new "Formats" settings tab (5th tab) to enable/disable specific citation formats
* Site administrators can now customize which formats appear to users
* Formats organized by category: General Purpose, Sciences, Medical & Health, Social Sciences, Engineering & Technology, Humanities, Legal, Web & Digital
* Bulk actions: "Select All" and "Clear All" buttons for quick configuration
* All 20 formats enabled by default for existing installations (no breaking changes)
* Real-time format count display in category headers (e.g., "3 of 5 enabled")
* Smart validation ensures at least one format remains enabled
* Reduces dropdown clutter for specialized sites (e.g., chemistry blogs can show only relevant formats)
* Clean checkbox-based UI matching plugin's minimalist design aesthetic

**IMPROVEMENTS**
* Dynamic dropdown generation based on enabled formats (frontend and admin preview)
* Format metadata system with full names, display names, icons, and category assignments
* Enhanced admin JavaScript with format selection logic and count updates
* Added format validation during settings save with user-friendly error messages
* Updated Settings Preview tab to respect enabled formats selection
* Improved organization with dedicated Formats management tab
* Updated readme with comprehensive format list and enhanced documentation
* Enhanced user experience for specialized academic publications

**TECHNICAL DETAILS**
* New helper functions: wpcp_get_enabled_formats() and wpcp_get_format_metadata()
* Format templates use existing {author}, {title}, {date} tag system for consistency
* All formats follow WordPress security best practices (escaping, sanitization, nonces)
* Backwards compatible - existing installations retain all functionality
* No database migrations required - settings stored in options table
* Settings validation prevents configuration errors and ensures minimum 1 format
* Added 10 new format templates to get_citation_styles() function
* CSS and JavaScript properly organized in external asset files
* All 20 formats automatically work with existing export and analytics features

= 2.3.1 - 2026-02-06 =
* Fixed: Settings link in Analytics page pointed to incorrect page slug

= 2.3.0 - 2026-02-05 =
**CODE ARCHITECTURE IMPROVEMENT**
* Extracted all inline JavaScript into dedicated external file (assets/js/admin.js)
* Removed 71 lines of inline jQuery from main plugin file
* Proper separation of concerns - JavaScript now in /assets/js/ directory
* Admin JavaScript properly enqueued with version control and cache busting
* Converted inline script data to wp_localize_script for proper data passing
* Improved code maintainability and WordPress coding standards compliance
* Better performance through browser caching of external JavaScript files
* Cleaner PHP code with no embedded script blocks in HTML output
* All CSS and JavaScript assets now properly organized in external files
* Enhanced developer experience with modular, well-documented code structure

= 2.2.2 - 2026-02-05 =
**DATA MANAGEMENT UI REDESIGN**
* Completely redesigned Privacy & Data Management section to match Analytics page aesthetic
* Separated into two side-by-side cards using analytics grid layout
* "Data Privacy" card with clean list of tracked items and privacy note
* "Purge Analytics Data" card with danger styling and prominent warning
* Improved visual hierarchy with section header and descriptive subtitle
* Consistent card styling, typography, and spacing with rest of Analytics page
* Better information architecture - privacy info separate from destructive action
* Enhanced warning message with inline icon for better scannability
* Fully responsive design maintains consistency across all devices

= 2.2.1 - 2026-02-05 =
**IMPROVED INFORMATION ARCHITECTURE**
* Moved "Privacy & Data Management" section from Settings to Analytics page
* Better contextual placement - data purge tools now located with analytics data
* Improved navigation flow - users manage analytics data where they view it
* Cleaner Settings page focused on configuration options
* Analytics page now serves as complete analytics management hub
* Enhanced user experience with logical grouping of related functionality

= 2.2.0 - 2026-02-05 =
**ANALYTICS STATUS NOTIFICATION**
* Added prominent notice banner on Analytics page when tracking is disabled
* Blue information banner with icon explains analytics is disabled
* Direct link to Settings page to enable analytics tracking
* Prevents confusion about why no data appears in analytics dashboard
* Banner only appears when analytics tracking is turned off (default state for GDPR compliance)
* Clean, professional design matching overall plugin aesthetic

= 2.1.9 - 2026-02-05 =
**PRIVACY SECTION REDESIGN**
* Completely redesigned "Privacy Tools" section with sophisticated warning card design
* Renamed to "Privacy & Data Management" for clearer purpose
* Added prominent warning icon with contextual alert styling
* Redesigned purge button with solid red styling and hover effects
* Improved layout with proper spacing and visual hierarchy
* Enhanced warning message with better typography and contrast
* Added responsive design for mobile devices
* Button now right-aligned with professional styling
* Removed ugly inline styles and implemented proper CSS architecture

= 2.1.8 - 2026-02-05 =
**UI/UX HEADER ENHANCEMENT**
* Redesigned Settings and Analytics page headers with sophisticated typography
* Added large document icon to Settings page header
* Added download/analytics icon to Analytics page header
* Integrated version badge (v2.1.8) display in page header
* Added descriptive subtitle text under main page titles
* Enhanced visual hierarchy with refined spacing and borders
* Modern, minimalist header design matching overall aesthetic

= 2.1.7 - 2026-02-05 =
**UI/UX COLOR SCHEME UPDATE**
* Updated admin interface to refined modern minimalist color palette
* Changed primary color from bright blue (#2563eb) to sophisticated slate (#64748b)
* Updated accent colors to more subdued, professional tones
* Success color changed to calm cyan (#0891b2)
* Warning color refined to muted orange (#ea580c)
* Enhanced visual hierarchy while maintaining clean, modernist aesthetic
* All interactive elements updated to match new refined palette

= 2.1.6 - 2026-02-05 =
**GDPR COMPLIANCE**
* Analytics disabled by default - users must explicitly enable
* Added consent gate filter (`wpcp_analytics_consent_granted`) for cookie consent integration
* Added opt-out filter (`wpcp_analytics_optout_active`) for user preference handling
* New cooldown modes: Session-based (default, no IP storage), IP hash, or None
* Configurable data retention periods (30/90/180/365 days)
* Automatic daily purge of old analytics data via WP-Cron
* Manual purge tool in admin settings with nonce protection
* Privacy policy content registered with WordPress privacy tools
* Personal data exporter integration for GDPR data requests
* Personal data eraser integration for GDPR erasure requests
* Identity resolver filter (`wpcp_cite_resolve_identity`) for custom user mapping
* Added GitHub repository link to plugin header and documentation

**BUG FIX**
* Fixed Settings Preview tab not rendering citation box correctly

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

= 2.1.6 =
GDPR COMPLIANCE! Comprehensive privacy controls including consent gates, opt-out filters, session-based tracking (no IP storage), configurable retention, auto-purge, and WordPress privacy tools integration. Also fixes Settings Preview tab. Highly recommended for EU compliance.

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
* GitHub: [https://github.com/menj/cite](https://github.com/menj/cite)
* Visit the [support forum](https://wordpress.org/support/plugin/cite/)
* Email: support@menj.net
* Website: https://menj.net

== Security ==

Security issues should be reported privately to security@menj.net. Please do not create public issues for security vulnerabilities.

Version 2.2.2 has been thoroughly tested for:
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

**File Structure (v2.2.2):**
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
