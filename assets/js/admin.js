/**
 * Cite Plugin - Admin JavaScript
 * Version: 2.8.0
 * 
 * Handles admin interface interactions including:
 * - Tab navigation
 * - Radio card selections
 * - Citation preview formatting (20 formats)
 * - Preview display mode live-switching (dropdown/tabs/buttons)
 * - Format selection management
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        /**
         * Tab Switching
         * Handles navigation between different settings tabs
         */
        $('.wpcp-tab-button').on('click', function(e) {
            e.preventDefault();
            var tab = $(this).data('tab');
            $('.wpcp-tab-button').removeClass('active');
            $('.wpcp-tab-content').removeClass('active');
            $(this).addClass('active');
            $('#' + tab + '-tab').addClass('active');
        });
        
        /**
         * Radio Card Selection
         * Adds visual selection state to radio button cards
         */
        $('.wpcp-radio-card input[type="radio"]').on('change', function() {
            $(this).closest('.wpcp-radio-group').find('.wpcp-radio-card').removeClass('selected');
            $(this).closest('.wpcp-radio-card').addClass('selected');
        });
        
        /**
         * Citation Preview Formatting
         * Updates preview based on selected citation style
         */
        if (typeof wpcpPreviewData !== 'undefined') {
            
            /**
             * Format citation based on style
             * @param {string} style - Citation style identifier
             * @return {string} Formatted citation HTML
             */
            function formatPreviewCitation(style) {
                var a = wpcpPreviewData.author;
                var d = wpcpPreviewData.date;
                var dDMY = wpcpPreviewData.dateDMY;
                var year = d.split(' ')[2]; // Extract year from date
                var t = wpcpPreviewData.title;
                var s = '<em>' + wpcpPreviewData.site + '</em>';
                var u = wpcpPreviewData.url;
                
                switch(style) {
                    // Original 10 formats
                    case 'apa':
                        return a + '. (' + d + '). ' + t + '. ' + s + '. ' + u;
                    case 'mla':
                        return a + '. "' + t + '." ' + s + ', ' + d + ', ' + u;
                    case 'chicago':
                        return a + '. "' + t + '." ' + s + '. Last modified ' + d + '. ' + u;
                    case 'harvard':
                        return a + '. (' + d + '). ' + t + '. ' + s + '. Available at: ' + u + ' (Accessed: ' + d + ')';
                    case 'vancouver':
                        return a + '. ' + t + '. ' + s + '. ' + d + '; [cited ' + d + ']. Available from: ' + u;
                    case 'ieee':
                        return a + ', "' + t + '," ' + s + ', ' + d + '. [Online]. Available: ' + u + '. [Accessed: ' + d + ']';
                    case 'ama':
                        return a + '. ' + t + '. ' + s + '. Published ' + d + '. Accessed ' + d + '. ' + u;
                    case 'asa':
                        return a + '. ' + d + '. "' + t + '." ' + s + '. Retrieved ' + d + ' (' + u + ')';
                    case 'turabian':
                        return a + '. "' + t + '." ' + s + '. ' + d + '. ' + u;
                    case 'wikipedia':
                        return '<code class="wpcp-wikipedia-code">{{cite web |url=' + u + ' |title=' + t + ' |website=' + wpcpPreviewData.site + ' |author=' + a + ' |date=' + dDMY + ' |access-date=' + dDMY + ' |language=' + wpcpPreviewData.language + '}}</code>';
                    
                    // New 10 formats (v2.4.0)
                    case 'acs':
                        return a + '. ' + t + '. ' + s + ' [Online] ' + d + '. ' + u + ' (accessed ' + d + ').';
                    case 'aip':
                        return a + ', "' + t + '," ' + s + ' ' + year + '. [Online]. Available: ' + u;
                    case 'nlm':
                        return a + '. ' + t + '. ' + s + ' [Internet]. ' + d + ' [cited ' + d + ']. Available from: ' + u;
                    case 'aaa':
                        return a + ' ' + d + ' ' + t + '. ' + s + ', electronic document, ' + u + ', accessed ' + d + '.';
                    case 'apsa':
                        return a + '. ' + d + '. "' + t + '." ' + s + '. ' + u + ' (accessed ' + d + ').';
                    case 'oscola':
                        return a + ', \'' + t + '\' (' + s + ', ' + d + ') &lt;' + u + '&gt; accessed ' + d;
                    case 'nature':
                        return a + '. ' + t + '. ' + s + ' ' + u + ' (' + d + ').';
                    case 'acm':
                        return a + '. ' + d + '. ' + t + '. ' + s + '. Retrieved ' + d + ' from ' + u;
                    case 'bluebook':
                        return a + ', <em>' + t + '</em>, ' + wpcpPreviewData.site + ' (' + d + '), ' + u + '.';
                    case 'iso690':
                        return a + '. ' + t + '. ' + s + ' [online]. ' + d + ' [viewed ' + d + ']. Available from: ' + u;
                    
                    default:
                        return a + '. (' + d + '). ' + t + '. ' + s + '. ' + u;
                }
            }
            
            /**
             * Handle preview style change — dropdown
             */
            $('.wpcp-preview-select').on('change', function() {
                var style = $(this).val();
                updatePreviewCitation(style);
            });
            
            /**
             * Handle preview style change — tabs
             */
            $(document).on('click', '.wpcp-preview-box .wpcp-style-tab', function(e) {
                e.preventDefault();
                var $tabs = $(this).closest('.wpcp-style-tabs');
                $tabs.find('.wpcp-style-tab').removeClass('wpcp-style-tab--active').attr('aria-selected', 'false');
                $(this).addClass('wpcp-style-tab--active').attr('aria-selected', 'true');
                updatePreviewCitation($(this).data('style'));
            });
            
            /**
             * Handle preview style change — buttons
             */
            $(document).on('click', '.wpcp-preview-box .wpcp-style-btn', function(e) {
                e.preventDefault();
                var $buttons = $(this).closest('.wpcp-style-buttons');
                $buttons.find('.wpcp-style-btn').removeClass('wpcp-style-btn--active').attr('aria-checked', 'false');
                $(this).addClass('wpcp-style-btn--active').attr('aria-checked', 'true');
                updatePreviewCitation($(this).data('style'));
            });
            
            /**
             * Update preview citation text
             */
            function updatePreviewCitation(style) {
                var citation = formatPreviewCitation(style);
                $('.wpcp-preview-citation').html(citation);
            }
        }
        
        /**
         * Live-switch preview selector when Format Selector Style radios change.
         * Uses class selector to avoid jQuery bracket-escaping issues with
         * name="wpcp_setting[format_display_mode]".
         */
        $(document).on('change', '.wpcp-display-mode-radio', function() {
            var mode = $(this).val();
            $('.wpcp-preview-selector').hide();
            $('.wpcp-preview-selector[data-mode="' + mode + '"]').show();
        });
        
        /**
         * Preview box interactive buttons
         */
        // Toggle button — collapse/expand citation content
        $(document).on('click', '.wpcp-preview-box .toggle-button', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $content = $btn.closest('.wpcp-citation-box').find('.citation-content');
            var isCollapsed = $content.hasClass('collapsed');
            
            $content.toggleClass('collapsed');
            $btn.toggleClass('collapsed');
            $btn.attr('aria-expanded', isCollapsed ? 'true' : 'false');
        });
        
        // Copy button — copy citation text to clipboard
        $(document).on('click', '.wpcp-preview-box .copy-button', function(e) {
            e.preventDefault();
            var $box = $(this).closest('.wpcp-citation-box');
            var text = $box.find('.wpcp-preview-citation').text().trim();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    var $notif = $box.find('.copy-notification');
                    $notif.addClass('show');
                    setTimeout(function() { $notif.removeClass('show'); }, 1500);
                });
            }
        });
        
        // Export button — toggle dropdown visibility
        $(document).on('click', '.wpcp-preview-box .export-button', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var $dropdown = $(this).siblings('.export-dropdown');
            var isOpen = $dropdown.hasClass('show');
            $dropdown.toggleClass('show');
            $(this).attr('aria-expanded', !isOpen ? 'true' : 'false');
        });
        
        // Close export dropdown on outside click
        $(document).on('click', function() {
            $('.wpcp-preview-box .export-dropdown').removeClass('show');
            $('.wpcp-preview-box .export-button').attr('aria-expanded', 'false');
        });
        
        /**
         * Live-sync export format checkboxes with preview export dropdown
         */
        $(document).on('change', '.wpcp-export-format-toggle', function() {
            var format = $(this).val();
            var checked = $(this).is(':checked');
            $('.wpcp-preview-export-option[data-export-format="' + format + '"]').toggle(checked);
        });
        
    });
    
})(jQuery);

/**
 * Format Selection Tab Functionality (v2.4.0)
 */
(function() {
    'use strict';
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormatSelection);
    } else {
        initFormatSelection();
    }
    
    function initFormatSelection() {
        var selectAllBtn = document.querySelector('.wpcp-select-all');
        var selectNoneBtn = document.querySelector('.wpcp-select-none');
        var formatCheckboxes = document.querySelectorAll('.wpcp-format-toggle');
        
        if (!selectAllBtn || !selectNoneBtn || !formatCheckboxes.length) {
            return;
        }
        
        // Select all formats
        selectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            formatCheckboxes.forEach(function(checkbox) {
                checkbox.checked = true;
            });
            updateFormatCounts();
        });
        
        // Clear all formats (but ensure at least one remains)
        selectNoneBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var checkedCount = 0;
            formatCheckboxes.forEach(function(checkbox) {
                if (checkbox.checked) checkedCount++;
            });
            
            if (checkedCount === formatCheckboxes.length) {
                // If all are checked, uncheck all except the first one
                formatCheckboxes.forEach(function(checkbox, index) {
                    checkbox.checked = (index === 0);
                });
                alert('At least one citation format must remain enabled.');
            } else {
                formatCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = false;
                });
            }
            updateFormatCounts();
        });
        
        // Update counts when checkboxes change
        formatCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                validateMinimumSelection();
                updateFormatCounts();
            });
        });
        
        // Prevent unchecking the last checkbox
        function validateMinimumSelection() {
            var checkedCount = 0;
            var lastChecked = null;
            
            formatCheckboxes.forEach(function(checkbox) {
                if (checkbox.checked) {
                    checkedCount++;
                    lastChecked = checkbox;
                }
            });
            
            if (checkedCount === 0 && lastChecked) {
                lastChecked.checked = true;
                alert('At least one citation format must remain enabled.');
            }
        }
        
        // Update format counts in category headers
        function updateFormatCounts() {
            var categories = document.querySelectorAll('.wpcp-format-category');
            
            categories.forEach(function(category) {
                var checkboxes = category.querySelectorAll('.wpcp-format-toggle');
                var countSpan = category.querySelector('.wpcp-format-count');
                
                if (!countSpan) return;
                
                var total = checkboxes.length;
                var checked = 0;
                
                checkboxes.forEach(function(checkbox) {
                    if (checkbox.checked) checked++;
                });
                
                countSpan.textContent = checked + ' of ' + total + ' enabled';
            });
        }
        
        // Initialize counts on page load
        updateFormatCounts();
    }
})();
