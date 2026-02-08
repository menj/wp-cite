/**
 * Cite Plugin - Frontend JavaScript
 * Version: 2.8.0
 * 
 * Handles citation display, copying, exporting, analytics tracking,
 * and multiple display modes (dropdown, tabs, buttons).
 */
(function() {
    'use strict';

    if (typeof window.wpcpCitationInitialized !== 'undefined') {
        return;
    }
    window.wpcpCitationInitialized = true;

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function showNotification(notification) {
        if (!notification) return;
        notification.classList.add('show');
        setTimeout(function() {
            notification.classList.remove('show');
        }, 2000);
    }

    function hasAnalyticsConsent() {
        var cookies = document.cookie;
        if (cookies.indexOf('cookieyes-consent') !== -1) {
            return cookies.indexOf('analytics:yes') !== -1;
        }
        if (cookies.indexOf('cmplz_statistics=allow') !== -1) {
            return true;
        }
        if (cookies.indexOf('CookieConsent') !== -1) {
            return cookies.indexOf('statistics:true') !== -1;
        }
        if (cookies.indexOf('moove_gdpr_popup') !== -1) {
            try {
                var match = cookies.match(/moove_gdpr_popup=([^;]+)/);
                if (match) {
                    var moove = JSON.parse(decodeURIComponent(match[1]));
                    return moove && moove.thirdparty === '1';
                }
            } catch (e) { /* ignore */ }
        }
        return false;
    }

    function trackAction(postId, action, style, ajaxUrl, nonce, enableAnalytics, requireConsent) {
        if (!enableAnalytics) return;
        if (postId <= 0) return;
        if (typeof jQuery === 'undefined') return;
        var consentGranted = !requireConsent || hasAnalyticsConsent();
        if (!consentGranted) return;

        jQuery.post(ajaxUrl, {
            action: 'wpcp_track_analytics',
            nonce: nonce,
            post_id: postId,
            citation_style: style,
            action_type: action,
            consent_granted: consentGranted ? '1' : '0'
        }).fail(function(xhr, status, error) {
            console.error('Analytics tracking failed:', error);
        });
    }

    function buildWikipediaCitation(config) {
        var parts = ['{{cite web'];
        parts.push(' |url=' + config.permalink);
        parts.push(' |title=' + config.title);
        var authors = config.authors || [config.author];
        if (authors.length === 1) {
            parts.push(' |author=' + authors[0]);
        } else {
            for (var i = 0; i < authors.length; i++) {
                parts.push(' |author' + (i + 1) + '=' + authors[i]);
            }
        }
        parts.push(' |date=' + (config.publicationDateDMY || config.publicationDate));
        parts.push(' |website=' + config.siteName);
        parts.push(' |language=' + (config.language || 'en'));
        parts.push(' |access-date=' + (config.dateAccessedDMY || config.dateAccessed));
        parts.push('}}');
        return parts.join('');
    }

    /**
     * Get the currently selected style from any display mode.
     */
    function getSelectedStyle(config) {
        var mode = config.displayMode || 'dropdown';
        var container = config.container;

        if (mode === 'tabs') {
            var activeTab = container.querySelector('.wpcp-style-tab--active');
            return activeTab ? activeTab.getAttribute('data-style') : config.defaultStyle;
        } else if (mode === 'buttons') {
            var activeBtn = container.querySelector('.wpcp-style-btn--active');
            return activeBtn ? activeBtn.getAttribute('data-style') : config.defaultStyle;
        } else {
            return config.dropdown ? config.dropdown.value : config.defaultStyle;
        }
    }

    function updateCitation(config) {
        var citationBox = config.citationBox;
        var styles = config.styles;
        if (!citationBox) return;

        var selectedStyle = getSelectedStyle(config);
        var template = styles[selectedStyle];
        if (!template) return;

        var citation;
        if (selectedStyle === 'wikipedia') {
            citation = buildWikipediaCitation(config);
            citationBox.textContent = citation;
            citationBox.classList.add('wikipedia-format');
        } else {
            citation = template
                .replace('{author}', escapeHtml(config.author))
                .replace('{sitename}', escapeHtml(config.siteName))
                .replace('{title}', escapeHtml(config.title))
                .replace('{date}', escapeHtml(config.dateAccessed))
                .replace('{publication_date}', escapeHtml(config.publicationDate))
                .replace('{publication_year}', escapeHtml(config.publicationYear))
                .replace('{permalink}', config.permalinkHTML);
            citation = citation.replace(/\s\./g, '.').replace(/\s\,/g, ',').replace(/\s+/g, ' ');
            citationBox.innerHTML = citation;
            citationBox.classList.remove('wikipedia-format');
        }

        trackAction(config.postId, 'view', selectedStyle, config.ajaxUrl, config.nonce, config.enableAnalytics, config.requireConsent);
    }

    function copyToClipboard(config) {
        var citationBox = config.citationBox;
        var notification = config.notification;
        if (!citationBox) return;

        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = citationBox.innerHTML;
        var plainText = tempDiv.textContent || tempDiv.innerText || '';
        var selectedStyle = getSelectedStyle(config);

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(plainText).then(function() {
                showNotification(notification);
                trackAction(config.postId, 'copy', selectedStyle, config.ajaxUrl, config.nonce, config.enableAnalytics, config.requireConsent);
            }).catch(function() {
                fallbackCopy(plainText, config);
            });
        } else {
            fallbackCopy(plainText, config);
        }
    }

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
            var selectedStyle = getSelectedStyle(config);
            trackAction(config.postId, 'copy', selectedStyle, config.ajaxUrl, config.nonce, config.enableAnalytics, config.requireConsent);
        } catch (err) {
            console.error('Failed to copy citation:', err);
        }
        document.body.removeChild(textarea);
    }

    function exportCitation(format, config) {
        trackAction(config.postId, 'export', format, config.ajaxUrl, config.nonce, config.enableAnalytics, config.requireConsent);

        var content = '';
        var mimeType = 'text/plain;charset=utf-8';
        var cleanTitle = config.title.replace(/"/g, '\\"').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        var cleanAuthor = config.author.replace(/"/g, '\\"').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        var cleanSiteName = config.siteName.replace(/"/g, '\\"').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        var citeKey = config.siteName.replace(/\s/g, '').replace(/[^a-zA-Z0-9]/g, '') + config.publicationYear;

        if (format === 'bibtex') {
            content = '@article{' + citeKey + ',\n  title={' + cleanTitle + '},\n  author={' + cleanAuthor + '},\n  journal={' + cleanSiteName + '},\n  year={' + config.publicationYear + '},\n  url={' + config.permalink + '}\n}';
        } else if (format === 'ris') {
            content = 'TY  - JOUR\nTI  - ' + cleanTitle + '\nAU  - ' + cleanAuthor + '\nJO  - ' + cleanSiteName + '\nPY  - ' + config.publicationYear + '\nUR  - ' + config.permalink + '\nER  -';
        } else if (format === 'endnote') {
            content = '%0 Journal Article\n%T ' + cleanTitle + '\n%A ' + cleanAuthor + '\n%J ' + cleanSiteName + '\n%D ' + config.publicationYear + '\n%U ' + config.permalink;
        } else if (format === 'csl-json') {
            var cslObj = {
                'type': 'webpage',
                'id': citeKey,
                'title': config.title,
                'author': [{ 'literal': config.author }],
                'container-title': config.siteName,
                'URL': config.permalink,
                'issued': { 'date-parts': [[parseInt(config.publicationYear, 10)]] },
                'accessed': { 'date-parts': [[new Date().getFullYear(), new Date().getMonth() + 1, new Date().getDate()]] },
                'language': config.language || 'en'
            };
            content = JSON.stringify([cslObj], null, 2);
            mimeType = 'application/json;charset=utf-8';
        } else if (format === 'cff') {
            var dateISO = config.publicationDateISO || config.publicationYear + '-01-01';
            content = 'cff-version: 1.2.0\n';
            content += 'message: "If you use this work, please cite it as below."\n';
            content += 'title: "' + config.title.replace(/"/g, '\\"') + '"\n';
            content += 'authors:\n';
            content += '  - name: "' + config.author.replace(/"/g, '\\"') + '"\n';
            content += 'date-released: ' + dateISO + '\n';
            content += 'url: "' + config.permalink + '"\n';
            content += 'type: article\n';
            content += 'journal: "' + config.siteName.replace(/"/g, '\\"') + '"\n';
        } else if (format === 'dublin-core') {
            var dateISO = config.publicationDateISO || config.publicationYear + '-01-01';
            content = '<?xml version="1.0" encoding="UTF-8"?>\n';
            content += '<metadata xmlns:dc="http://purl.org/dc/elements/1.1/">\n';
            content += '  <dc:title>' + config.title.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</dc:title>\n';
            content += '  <dc:creator>' + config.author.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</dc:creator>\n';
            content += '  <dc:publisher>' + config.siteName.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</dc:publisher>\n';
            content += '  <dc:date>' + dateISO + '</dc:date>\n';
            content += '  <dc:identifier>' + config.permalink.replace(/&/g, '&amp;') + '</dc:identifier>\n';
            content += '  <dc:type>Text</dc:type>\n';
            content += '  <dc:format>text/html</dc:format>\n';
            content += '  <dc:language>' + (config.language || 'en') + '</dc:language>\n';
            content += '</metadata>\n';
            mimeType = 'application/xml;charset=utf-8';
        }

        var fileExtensions = {
            'bibtex': 'bib',
            'ris': 'ris',
            'endnote': 'enw',
            'csl-json': 'json',
            'cff': 'cff',
            'dublin-core': 'xml'
        };

        var blob = new Blob([content], { type: mimeType });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'citation.' + (fileExtensions[format] || 'txt');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }

    /**
     * Set up tabs display mode event handlers.
     */
    function initTabsMode(config) {
        var container = config.container;
        var tabs = container.querySelectorAll('.wpcp-style-tab');
        if (!tabs.length) return;

        tabs.forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                tabs.forEach(function(t) {
                    t.classList.remove('wpcp-style-tab--active');
                    t.setAttribute('aria-selected', 'false');
                    t.setAttribute('tabindex', '-1');
                });
                this.classList.add('wpcp-style-tab--active');
                this.setAttribute('aria-selected', 'true');
                this.setAttribute('tabindex', '0');
                updateCitation(config);
            });

            tab.addEventListener('keydown', function(e) {
                var tabsArray = Array.prototype.slice.call(tabs);
                var index = tabsArray.indexOf(this);
                var newIndex = -1;

                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    newIndex = (index + 1) % tabsArray.length;
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    newIndex = (index - 1 + tabsArray.length) % tabsArray.length;
                } else if (e.key === 'Home') {
                    e.preventDefault();
                    newIndex = 0;
                } else if (e.key === 'End') {
                    e.preventDefault();
                    newIndex = tabsArray.length - 1;
                }

                if (newIndex >= 0) {
                    tabsArray[newIndex].click();
                    tabsArray[newIndex].focus();
                }
            });
        });
    }

    /**
     * Set up buttons (pill) display mode event handlers.
     */
    function initButtonsMode(config) {
        var container = config.container;
        var buttons = container.querySelectorAll('.wpcp-style-btn');
        if (!buttons.length) return;

        buttons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                buttons.forEach(function(b) {
                    b.classList.remove('wpcp-style-btn--active');
                    b.setAttribute('aria-checked', 'false');
                });
                this.classList.add('wpcp-style-btn--active');
                this.setAttribute('aria-checked', 'true');
                updateCitation(config);
            });

            btn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
    }

    function initCitationBox(data) {
        var postIdStr = data.postId.toString();
        var instanceStr = data.instance ? data.instance.toString() : '1';
        var containerId = 'citation-box-' + postIdStr + '-' + instanceStr;
        var container = document.getElementById(containerId);

        if (!container) return;

        var citationBox = container.querySelector('.citation-output');
        var dropdown = container.querySelector('.citation-style-select');
        var copyButton = container.querySelector('.copy-button');
        var notification = container.querySelector('.copy-notification');
        var exportButton = container.querySelector('.export-button');
        var exportDropdown = exportButton ? exportButton.nextElementSibling : null;
        var toggleButton = container.querySelector('.toggle-button');
        var citationContent = container.querySelector('.citation-content');

        var displayMode = data.displayMode || 'dropdown';
        var defaultStyle = (data.activeFormats && data.activeFormats.length > 0) ? data.activeFormats[0] : 'apa';

        var config = {
            postId: data.postId,
            container: container,
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
            publicationDateISO: data.publicationDateISO || data.publicationYear + '-01-01',
            permalink: data.permalink,
            permalinkHTML: '<a href="' + escapeHtml(data.permalinkUrl) + '" itemprop="url" rel="bookmark">' + escapeHtml(data.permalink) + '</a>',
            language: data.language || 'en',
            ajaxUrl: data.ajaxUrl,
            nonce: data.nonce,
            enableAnalytics: data.enableAnalytics,
            requireConsent: data.requireConsent,
            dropdown: dropdown,
            citationBox: citationBox,
            notification: notification,
            displayMode: displayMode,
            defaultStyle: defaultStyle
        };

        // Bind display mode handlers
        if (displayMode === 'tabs') {
            initTabsMode(config);
        } else if (displayMode === 'buttons') {
            initButtonsMode(config);
        } else if (dropdown) {
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

            document.addEventListener('click', function(e) {
                if (!exportButton.contains(e.target) && !exportDropdown.contains(e.target)) {
                    exportDropdown.classList.remove('show');
                    exportButton.setAttribute('aria-expanded', 'false');
                }
            });
        }

        if (toggleButton && citationContent) {
            toggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                var isExpanded = !citationContent.classList.toggle('collapsed');
                toggleButton.setAttribute('aria-expanded', isExpanded.toString());
                toggleButton.classList.toggle('collapsed');
            });
        }

        updateCitation(config);
    }

    function init() {
        if (typeof window.wpcpCitationData === 'undefined' || !Array.isArray(window.wpcpCitationData)) {
            return;
        }
        window.wpcpCitationData.forEach(function(data) {
            initCitationBox(data);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
