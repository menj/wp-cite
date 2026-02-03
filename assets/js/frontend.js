/**
 * Cite Plugin - Frontend JavaScript
 * Version: 2.1.5
 * 
 * Handles citation display, copying, exporting, and analytics tracking.
 */
(function() {
    'use strict';

    // Prevent multiple initializations
    if (typeof window.wpcpCitationInitialized !== 'undefined') {
        return;
    }
    window.wpcpCitationInitialized = true;

    /**
     * Escape HTML entities to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} - Escaped text
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show copy notification
     * @param {HTMLElement} notification - Notification element
     */
    function showNotification(notification) {
        if (!notification) return;
        notification.classList.add('show');
        setTimeout(function() {
            notification.classList.remove('show');
        }, 2000);
    }

    /**
     * Track analytics action via AJAX
     * @param {number} postId - Post ID
     * @param {string} action - Action type (view, copy, export)
     * @param {string} style - Citation style or export format
     * @param {string} ajaxUrl - WordPress AJAX URL
     * @param {string} nonce - Security nonce
     */
    function trackAction(postId, action, style, ajaxUrl, nonce) {
        // Don't track if post ID is invalid (0 = preview/test)
        if (postId <= 0) return;
        if (typeof jQuery === 'undefined') return;

        jQuery.post(ajaxUrl, {
            action: 'wpcp_track_analytics',
            nonce: nonce,
            post_id: postId,
            citation_style: style,
            action_type: action
        }).fail(function(xhr, status, error) {
            console.error('Analytics tracking failed:', error);
        });
    }

    /**
     * Build Wikipedia {{cite web}} template
     * @param {Object} config - Citation configuration object
     * @returns {string} - Wikipedia citation template
     */
    function buildWikipediaCitation(config) {
        var parts = ['{{cite web'];
        
        // URL (required)
        parts.push(' |url=' + config.permalink);
        
        // Title (required)
        parts.push(' |title=' + config.title);
        
        // Authors - use |author= for single, |author1=, |author2=, etc. for multiple
        var authors = config.authors || [config.author];
        if (authors.length === 1) {
            parts.push(' |author=' + authors[0]);
        } else {
            for (var i = 0; i < authors.length; i++) {
                parts.push(' |author' + (i + 1) + '=' + authors[i]);
            }
        }
        
        // Date (publication date in D Month YYYY format)
        parts.push(' |date=' + (config.publicationDateDMY || config.publicationDate));
        
        // Website name
        parts.push(' |website=' + config.siteName);
        
        // Language
        parts.push(' |language=' + (config.language || 'en'));
        
        // Access date (in D Month YYYY format)
        parts.push(' |access-date=' + (config.dateAccessedDMY || config.dateAccessed));
        
        parts.push('}}');
        
        return parts.join('');
    }

    /**
     * Update citation display based on selected style
     * @param {Object} config - Citation configuration object
     */
    function updateCitation(config) {
        var dropdown = config.dropdown;
        var citationBox = config.citationBox;
        var styles = config.styles;

        if (!dropdown || !citationBox) return;

        var selectedStyle = dropdown.value;
        var template = styles[selectedStyle];
        if (!template) return;

        var citation;
        
        // Special handling for Wikipedia format
        if (selectedStyle === 'wikipedia') {
            citation = buildWikipediaCitation(config);
            // Display as plain text in monospace
            citationBox.textContent = citation;
            citationBox.classList.add('wikipedia-format');
        } else {
            citation = template
                .replace('{author}', escapeHtml(config.author))
                .replace('{sitename}', escapeHtml(config.siteName))
                .replace('{title}', escapeHtml(config.title))
                .replace('{date}', escapeHtml(config.dateAccessed))
                .replace('{publication_date}', escapeHtml(config.publicationDate))
                .replace('{permalink}', config.permalinkHTML);

            // Clean up extra spaces and punctuation
            citation = citation.replace(/\s\./g, '.').replace(/\s\,/g, ',').replace(/\s+/g, ' ');

            citationBox.innerHTML = citation;
            citationBox.classList.remove('wikipedia-format');
        }

        // Track analytics
        trackAction(config.postId, 'view', selectedStyle, config.ajaxUrl, config.nonce);
    }

    /**
     * Copy citation to clipboard
     * @param {Object} config - Citation configuration object
     */
    function copyToClipboard(config) {
        var citationBox = config.citationBox;
        var notification = config.notification;
        var dropdown = config.dropdown;

        if (!citationBox) return;

        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = citationBox.innerHTML;
        var plainText = tempDiv.textContent || tempDiv.innerText || '';

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(plainText).then(function() {
                showNotification(notification);
                trackAction(config.postId, 'copy', dropdown.value, config.ajaxUrl, config.nonce);
            }).catch(function() {
                fallbackCopy(plainText, config);
            });
        } else {
            fallbackCopy(plainText, config);
        }
    }

    /**
     * Fallback copy method for older browsers
     * @param {string} text - Text to copy
     * @param {Object} config - Citation configuration object
     */
    function fallbackCopy(text, config) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        textarea.setAttribute('readonly', '');
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showNotification(config.notification);
            trackAction(config.postId, 'copy', config.dropdown.value, config.ajaxUrl, config.nonce);
        } catch (err) {
            console.error('Failed to copy citation:', err);
        }
        document.body.removeChild(textarea);
    }

    /**
     * Export citation in specified format
     * @param {string} format - Export format (bibtex, ris, endnote)
     * @param {Object} config - Citation configuration object
     */
    function exportCitation(format, config) {
        trackAction(config.postId, 'export', format, config.ajaxUrl, config.nonce);

        var content = '';
        var cleanTitle = config.title.replace(/"/g, '\\"').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        var cleanAuthor = config.author.replace(/"/g, '\\"').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        var cleanSiteName = config.siteName.replace(/"/g, '\\"').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        var citeKey = config.siteName.replace(/\s/g, '').replace(/[^a-zA-Z0-9]/g, '') + config.publicationYear;

        if (format === 'bibtex') {
            content = '@article{' + citeKey + ',\n' +
                '  title={' + cleanTitle + '},\n' +
                '  author={' + cleanAuthor + '},\n' +
                '  journal={' + cleanSiteName + '},\n' +
                '  year={' + config.publicationYear + '},\n' +
                '  url={' + config.permalink + '}' +
                '\n}';
        } else if (format === 'ris') {
            content = 'TY  - JOUR\n' +
                'TI  - ' + cleanTitle + '\n' +
                'AU  - ' + cleanAuthor + '\n' +
                'JO  - ' + cleanSiteName + '\n' +
                'PY  - ' + config.publicationYear + '\n' +
                'UR  - ' + config.permalink +
                '\nER  -';
        } else if (format === 'endnote') {
            content = '%0 Journal Article\n' +
                '%T ' + cleanTitle + '\n' +
                '%A ' + cleanAuthor + '\n' +
                '%J ' + cleanSiteName + '\n' +
                '%D ' + config.publicationYear + '\n' +
                '%U ' + config.permalink;
        }

        var blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'citation.' + (format === 'bibtex' ? 'bib' : format === 'ris' ? 'ris' : 'enw');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    /**
     * Initialize a single citation box
     * @param {Object} data - Citation data from wp_localize_script
     */
    function initCitationBox(data) {
        var postIdStr = data.postId.toString();

        var citationBox = document.querySelector('.citation-output[data-post-id="' + postIdStr + '"]');
        var dropdown = document.querySelector('.citation-style-select[data-post-id="' + postIdStr + '"]');
        var copyButton = document.querySelector('.copy-button[data-post-id="' + postIdStr + '"]');
        var notification = document.querySelector('.copy-notification[data-post-id="' + postIdStr + '"]');
        var exportButton = document.querySelector('.export-button[data-post-id="' + postIdStr + '"]');
        var exportDropdown = exportButton ? exportButton.nextElementSibling : null;
        var toggleButton = document.querySelector('.toggle-button[data-post-id="' + postIdStr + '"]');
        var citationContent = document.querySelector('.citation-content[data-post-id="' + postIdStr + '"]');

        // Build config object
        var config = {
            postId: data.postId,
            styles: data.styles,
            author: data.author,
            authors: data.authors || [data.author],
            siteName: data.siteName,
            title: data.title,
            dateAccessed: data.dateAccessed,
            dateAccessedDMY: data.dateAccessedDMY || data.dateAccessed,
            publicationDate: data.publicationDate,
            publicationDateDMY: data.publicationDateDMY || data.publicationDate,
            publicationYear: data.publicationYear,
            permalink: data.permalink,
            permalinkHTML: '<a href="' + escapeHtml(data.permalinkUrl) + '" itemprop="url" rel="bookmark">' + escapeHtml(data.permalink) + '</a>',
            language: data.language || 'en',
            ajaxUrl: data.ajaxUrl,
            nonce: data.nonce,
            dropdown: dropdown,
            citationBox: citationBox,
            notification: notification
        };

        // Event listeners
        if (dropdown) {
            dropdown.addEventListener('change', function() {
                updateCitation(config);
            });
        }

        if (copyButton) {
            copyButton.addEventListener('click', function(e) {
                e.preventDefault();
                copyToClipboard(config);
            });

            copyButton.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    copyToClipboard(config);
                }
            });
        }

        // Export menu
        if (exportButton && exportDropdown) {
            exportButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var isExpanded = exportDropdown.classList.toggle('show');
                exportButton.setAttribute('aria-expanded', isExpanded.toString());
            });

            var exportOptions = exportDropdown.querySelectorAll('.export-option');
            exportOptions.forEach(function(option) {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    var format = this.getAttribute('data-format');
                    if (format) {
                        exportCitation(format, config);
                        exportDropdown.classList.remove('show');
                        exportButton.setAttribute('aria-expanded', 'false');
                    }
                });

                option.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        var format = this.getAttribute('data-format');
                        if (format) {
                            exportCitation(format, config);
                            exportDropdown.classList.remove('show');
                            exportButton.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!exportButton.contains(e.target) && !exportDropdown.contains(e.target)) {
                    exportDropdown.classList.remove('show');
                    exportButton.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // Toggle functionality
        if (toggleButton && citationContent) {
            toggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                var isExpanded = !citationContent.classList.toggle('collapsed');
                toggleButton.setAttribute('aria-expanded', isExpanded.toString());
                toggleButton.classList.toggle('collapsed');
            });
        }

        // Initialize citation display
        updateCitation(config);
    }

    /**
     * Initialize all citation boxes on the page
     */
    function init() {
        // Check if citation data exists
        if (typeof window.wpcpCitationData === 'undefined' || !Array.isArray(window.wpcpCitationData)) {
            return;
        }

        // Initialize each citation box
        window.wpcpCitationData.forEach(function(data) {
            initCitationBox(data);
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
