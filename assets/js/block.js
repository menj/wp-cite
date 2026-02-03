/**
 * Cite Plugin - Gutenberg Block
 * Version: 2.1.5
 */
const { registerBlockType } = wp.blocks;
const { InspectorControls, useBlockProps } = wp.blockEditor;
const { PanelBody, SelectControl, ToggleControl, TextControl } = wp.components;
const { __ } = wp.i18n;

registerBlockType('cite/block', {
    title: __('Citation Box', 'cite'),
    description: __('Display a citation box with multiple formats and export options', 'cite'),
    icon: 'book-alt',
    category: 'common',
    keywords: [__('cite', 'cite'), __('citation', 'cite'), __('reference', 'cite')],
    attributes: {
        citationStyle: {
            type: 'string',
            default: 'apa',
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
        customAuthor: {
            type: 'string',
            default: '',
        },
    },
    example: {
        attributes: {
            citationStyle: 'apa',
        },
    },
    edit: function({ attributes, setAttributes }) {
        const { citationStyle, showExport, showToggle, showCopy, customAuthor } = attributes;
        const blockProps = useBlockProps({
            className: 'wpcp-citation-block-editor',
        });

        return (
            React.createElement('div', blockProps,
                React.createElement(InspectorControls, null,
                    React.createElement(PanelBody, { title: __('Citation Settings', 'cite'), initialOpen: true },
                        React.createElement(SelectControl, {
                            label: __('Default Citation Style', 'cite'),
                            value: citationStyle,
                            options: [
                                { label: 'APA', value: 'apa' },
                                { label: 'MLA', value: 'mla' },
                                { label: 'IEEE', value: 'ieee' },
                                { label: 'Harvard', value: 'harvard' },
                                { label: 'Chicago', value: 'chicago' },
                                { label: 'Vancouver', value: 'vancouver' },
                                { label: 'AMA', value: 'ama' },
                                { label: 'ASA', value: 'asa' },
                                { label: 'Turabian', value: 'turabian' },
                            ],
                            onChange: (value) => setAttributes({ citationStyle: value }),
                            help: __('Select the default citation format to display', 'cite')
                        }),
                        React.createElement(ToggleControl, {
                            label: __('Show Copy Button', 'cite'),
                            checked: showCopy,
                            onChange: (value) => setAttributes({ showCopy: value }),
                            help: __('Allow users to copy citation to clipboard', 'cite')
                        }),
                        React.createElement(ToggleControl, {
                            label: __('Show Export Menu', 'cite'),
                            checked: showExport,
                            onChange: (value) => setAttributes({ showExport: value }),
                            help: __('Show BibTeX, RIS, EndNote export options', 'cite')
                        }),
                        React.createElement(ToggleControl, {
                            label: __('Show Toggle Button', 'cite'),
                            checked: showToggle,
                            onChange: (value) => setAttributes({ showToggle: value }),
                            help: __('Allow users to collapse/expand citation box', 'cite')
                        })
                    ),
                    React.createElement(PanelBody, { title: __('Override Metadata', 'cite'), initialOpen: false },
                        React.createElement(TextControl, {
                            label: __('Custom Author', 'cite'),
                            value: customAuthor,
                            onChange: (value) => setAttributes({ customAuthor: value }),
                            help: __('Override the post author (optional)', 'cite')
                        })
                    )
                ),
                React.createElement('div', { className: 'wpcp-citation-preview' },
                    React.createElement('div', { className: 'wpcp-preview-header' },
                        React.createElement('span', { className: 'dashicons dashicons-book-alt' }),
                        React.createElement('strong', null, __('Citation Box', 'cite')),
                        React.createElement('span', { className: 'wpcp-preview-style' }, '(' + citationStyle.toUpperCase() + ')')
                    ),
                    React.createElement('div', { className: 'wpcp-preview-content' },
                        React.createElement('p', { className: 'wpcp-preview-text' },
                            __('Citation will appear here with the selected format when published.', 'cite')
                        ),
                        React.createElement('div', { className: 'wpcp-preview-features' },
                            showCopy && React.createElement('span', { className: 'wpcp-feature-badge' }, __('Copy', 'cite')),
                            showExport && React.createElement('span', { className: 'wpcp-feature-badge' }, __('Export', 'cite')),
                            showToggle && React.createElement('span', { className: 'wpcp-feature-badge' }, __('Toggle', 'cite'))
                        ),
                        customAuthor && React.createElement('div', { className: 'wpcp-preview-overrides' },
                            React.createElement('strong', null, __('Custom metadata:', 'cite')),
                            React.createElement('span', null, __('Author:', 'cite') + ' ' + customAuthor)
                        )
                    ),
                    React.createElement('div', { className: 'wpcp-preview-footer' },
                        React.createElement('p', null,
                            React.createElement('span', { className: 'dashicons dashicons-info' }),
                            __('Configure citation options in the sidebar →', 'cite')
                        )
                    )
                )
            )
        );
    },
    save: function({ attributes }) {
        const { citationStyle, showExport, showToggle, showCopy, customAuthor } = attributes;
        
        // Build shortcode with attributes
        let shortcode = '[cite';
        
        if (citationStyle && citationStyle !== 'apa') {
            shortcode += ' style="' + citationStyle + '"';
        }
        if (!showCopy) {
            shortcode += ' show_copy="false"';
        }
        if (!showExport) {
            shortcode += ' show_export="false"';
        }
        if (!showToggle) {
            shortcode += ' show_toggle="false"';
        }
        if (customAuthor) {
            shortcode += ' custom_author="' + customAuthor + '"';
        }
        
        shortcode += ']';
        
        return React.createElement('div', { className: 'wp-block-cite-block' }, shortcode);
    },
});
