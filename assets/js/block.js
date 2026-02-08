/**
 * Cite Plugin - Gutenberg Block
 * Version: 2.8.0
 * Dynamic block — rendered server-side via render_callback.
 */
const { registerBlockType } = wp.blocks;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, SelectControl, ToggleControl, TextControl } = wp.components;
const { __ } = wp.i18n;

// Format options passed from PHP via wp_localize_script
const formatOptions = (window.wpcpBlockData && window.wpcpBlockData.formats) || [
    { label: 'APA', value: 'apa' },
];

const modeOptions = [
    { label: __('Box (default)', 'cite'), value: 'box' },
    { label: __('Inline parenthetical', 'cite'), value: 'inline' },
    { label: __('Bibliography footnote', 'cite'), value: 'bibliography' },
];

registerBlockType('cite/block', {
    title: __('Citation Box', 'cite'),
    description: __('Display a citation box with multiple formats and export options', 'cite'),
    icon: 'book-alt',
    category: 'common',
    keywords: [__('cite', 'cite'), __('citation', 'cite'), __('reference', 'cite'), __('bibliography', 'cite')],
    supports: {
        html: false,
        multiple: true,
    },
    attributes: {
        citationStyle: {
            type: 'string',
            default: 'apa',
        },
        mode: {
            type: 'string',
            default: 'box',
        },
        showExport: {
            type: 'boolean',
            default: true,
        },
        showToggle: {
            type: 'boolean',
            default: true,
        },
        showCopy: {
            type: 'boolean',
            default: true,
        },
        showLink: {
            type: 'boolean',
            default: true,
        },
        customAuthor: {
            type: 'string',
            default: '',
        },
        formats: {
            type: 'string',
            default: '',
        },
        excludeFormats: {
            type: 'string',
            default: '',
        },
        page: {
            type: 'string',
            default: '',
        },
    },
    example: {
        attributes: {
            citationStyle: 'apa',
            mode: 'box',
        },
    },
    edit: function({ attributes, setAttributes }) {
        var citationStyle = attributes.citationStyle;
        var mode = attributes.mode;
        var showExport = attributes.showExport;
        var showToggle = attributes.showToggle;
        var showCopy = attributes.showCopy;
        var showLink = attributes.showLink;
        var customAuthor = attributes.customAuthor;
        var formats = attributes.formats;
        var excludeFormats = attributes.excludeFormats;
        var page = attributes.page;

        var blockProps = useBlockProps({
            className: 'wpcp-citation-block-editor',
        });

        var modeLabel = 'Box';
        for (var i = 0; i < modeOptions.length; i++) {
            if (modeOptions[i].value === mode) {
                modeLabel = modeOptions[i].label;
                break;
            }
        }

        var featureBadges = [];
        if (showCopy) featureBadges.push(__('Copy', 'cite'));
        if (showExport) featureBadges.push(__('Export', 'cite'));
        if (showToggle) featureBadges.push(__('Toggle', 'cite'));
        if (showLink) featureBadges.push(__('Link', 'cite'));

        return (
            React.createElement('div', blockProps,
                React.createElement(InspectorControls, null,
                    React.createElement(PanelBody, { title: __('Citation Settings', 'cite'), initialOpen: true },
                        React.createElement(SelectControl, {
                            label: __('Display Mode', 'cite'),
                            value: mode,
                            options: modeOptions,
                            onChange: function(value) { setAttributes({ mode: value }); },
                            help: __('Box: full citation widget. Inline: parenthetical. Bibliography: footnote marker.', 'cite')
                        }),
                        React.createElement(SelectControl, {
                            label: __('Default Citation Style', 'cite'),
                            value: citationStyle,
                            options: formatOptions,
                            onChange: function(value) { setAttributes({ citationStyle: value }); },
                            help: __('Select the default citation format to display', 'cite')
                        }),
                        mode === 'inline' && React.createElement(TextControl, {
                            label: __('Page Number', 'cite'),
                            value: page,
                            onChange: function(value) { setAttributes({ page: value }); },
                            help: __('Optional page number for inline citations (e.g. "42")', 'cite')
                        })
                    ),
                    React.createElement(PanelBody, { title: __('Features', 'cite'), initialOpen: true },
                        React.createElement(ToggleControl, {
                            label: __('Show Copy Button', 'cite'),
                            checked: showCopy,
                            onChange: function(value) { setAttributes({ showCopy: value }); },
                            help: __('Allow users to copy citation to clipboard', 'cite')
                        }),
                        React.createElement(ToggleControl, {
                            label: __('Show Export Menu', 'cite'),
                            checked: showExport,
                            onChange: function(value) { setAttributes({ showExport: value }); },
                            help: __('Show BibTeX, RIS, CSL-JSON, CFF, Dublin Core, and EndNote export options', 'cite')
                        }),
                        React.createElement(ToggleControl, {
                            label: __('Show Toggle Button', 'cite'),
                            checked: showToggle,
                            onChange: function(value) { setAttributes({ showToggle: value }); },
                            help: __('Allow users to collapse/expand citation box', 'cite')
                        }),
                        React.createElement(ToggleControl, {
                            label: __('Clickable Links', 'cite'),
                            checked: showLink,
                            onChange: function(value) { setAttributes({ showLink: value }); },
                            help: __('Display permalink as a clickable link in inline citations', 'cite')
                        })
                    ),
                    React.createElement(PanelBody, { title: __('Format Filtering', 'cite'), initialOpen: false },
                        React.createElement(TextControl, {
                            label: __('Include Formats', 'cite'),
                            value: formats,
                            onChange: function(value) { setAttributes({ formats: value }); },
                            help: __('Comma-separated list of format IDs to show (e.g. "apa,mla,chicago"). Leave empty for all.', 'cite')
                        }),
                        React.createElement(TextControl, {
                            label: __('Exclude Formats', 'cite'),
                            value: excludeFormats,
                            onChange: function(value) { setAttributes({ excludeFormats: value }); },
                            help: __('Comma-separated list of format IDs to hide (e.g. "wikipedia")', 'cite')
                        })
                    ),
                    React.createElement(PanelBody, { title: __('Override Metadata', 'cite'), initialOpen: false },
                        React.createElement(TextControl, {
                            label: __('Custom Author', 'cite'),
                            value: customAuthor,
                            onChange: function(value) { setAttributes({ customAuthor: value }); },
                            help: __('Override the post author (optional)', 'cite')
                        })
                    )
                ),
                React.createElement('div', { className: 'wpcp-citation-preview' },
                    React.createElement('div', { className: 'wpcp-block-preview-header' },
                        React.createElement('span', { className: 'dashicons dashicons-book-alt' }),
                        React.createElement('strong', null, __('Citation Box', 'cite')),
                        React.createElement('span', { className: 'wpcp-preview-style' }, citationStyle.toUpperCase()),
                        React.createElement('span', { className: 'wpcp-preview-mode' }, modeLabel)
                    ),
                    React.createElement('div', { className: 'wpcp-block-preview-content' },
                        mode === 'inline'
                            ? React.createElement('p', { className: 'wpcp-preview-text' },
                                __('Inline parenthetical citation will appear here when published.', 'cite'),
                                page ? ' (' + __('p.', 'cite') + ' ' + page + ')' : ''
                            )
                            : mode === 'bibliography'
                            ? React.createElement('p', { className: 'wpcp-preview-text' },
                                __('Footnote marker will appear here. Use [cite_bibliography] to render the reference list.', 'cite')
                            )
                            : React.createElement('p', { className: 'wpcp-preview-text' },
                                __('Citation will appear here with the selected format when published.', 'cite')
                            ),
                        featureBadges.length > 0 && React.createElement('div', { className: 'wpcp-preview-features' },
                            featureBadges.map(function(badge, idx) {
                                return React.createElement('span', { key: idx, className: 'wpcp-feature-badge' }, badge);
                            })
                        ),
                        (customAuthor || formats || excludeFormats) && React.createElement('div', { className: 'wpcp-preview-overrides' },
                            customAuthor && React.createElement('span', null, __('Author:', 'cite') + ' ' + customAuthor),
                            formats && React.createElement('span', null, __('Formats:', 'cite') + ' ' + formats),
                            excludeFormats && React.createElement('span', null, __('Excluded:', 'cite') + ' ' + excludeFormats)
                        )
                    ),
                    React.createElement('div', { className: 'wpcp-block-preview-footer' },
                        React.createElement('p', null,
                            React.createElement('span', { className: 'dashicons dashicons-info' }),
                            __('Configure citation options in the sidebar →', 'cite')
                        )
                    )
                )
            )
        );
    },
    // Dynamic block — rendered server-side via render_callback
    save: function() {
        return null;
    },
});
