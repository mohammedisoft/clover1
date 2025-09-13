// File: dashboard-qahwtea/includes/blocks/subscription-list.js

const { registerBlockType } = wp.blocks;
const { createElement } = wp.element;
const { useBlockProps, InspectorControls, PanelColorSettings } = wp.blockEditor;
const { PanelBody, TextControl, RangeControl, SelectControl } = wp.components;

registerBlockType("dashboard-qahwtea/subscription-list", {
    title: "Subscription List",
    icon: "list-view",
    category: "widgets",
    attributes: {
        // General block attributes
        title: { type: "string", default: "Choose Your Subscription" },
        productsPerRowDesktop: { type: "number", default: 4 },
        productsPerRowMobile: { type: "number", default: 2 },
        // Title styling attributes
        titleAlignment: { type: "string", default: "center" },
        titleFontSize: { type: "number", default: 24 },
        titleTextColor: { type: "string", default: "#000000" },
        titleBackgroundColor: { type: "string", default: "transparent" },
        titleMargin: { type: "string", default: "10px 0" },
        titlePadding: { type: "string", default: "5px" },
        titleBorderWidth: { type: "number", default: 0 },
        titleBorderStyle: { type: "string", default: "none" },
        titleBorderColor: { type: "string", default: "#000000" },
        titleBorderRadius: { type: "number", default: 0 },
        // Content styling attributes
        contentAlignment: { type: "string", default: "left" },
        contentFontSize: { type: "number", default: 16 },
        contentTextColor: { type: "string", default: "#000000" },
        contentBackgroundColor: { type: "string", default: "transparent" },
        contentMargin: { type: "string", default: "10px 0" },
        contentPadding: { type: "string", default: "5px" },
        contentBorderWidth: { type: "number", default: 0 },
        contentBorderStyle: { type: "string", default: "none" },
        contentBorderColor: { type: "string", default: "#000000" },
        contentBorderRadius: { type: "number", default: 0 }
    },
    edit: (props) => {
        const { attributes, setAttributes } = props;
        // Handle color reset by checking for undefined/null values
        const titleBgColor = attributes.titleBackgroundColor || 'transparent';
        const contentBgColor = attributes.contentBackgroundColor || 'transparent';

        // Define inline styles for Title and Content preview areas
        const titleStyles = {
            textAlign: attributes.titleAlignment,
            backgroundColor: titleBgColor,
            fontSize: attributes.titleFontSize + "px",
            color: attributes.titleTextColor,
            margin: attributes.titleMargin,
            padding: attributes.titlePadding,
            borderWidth: attributes.titleBorderWidth + "px",
            borderStyle: attributes.titleBorderStyle,
            borderColor: attributes.titleBorderColor,
            borderRadius: attributes.titleBorderRadius + "px"
        };

        const contentStyles = {
            textAlign: attributes.contentAlignment,
            fontSize: attributes.contentFontSize + "px",
            color: attributes.contentTextColor,
            backgroundColor: contentBgColor,
            margin: attributes.contentMargin,
            padding: attributes.contentPadding,
            borderWidth: attributes.contentBorderWidth + "px",
            borderStyle: attributes.contentBorderStyle,
            borderColor: attributes.contentBorderColor,
            borderRadius: attributes.contentBorderRadius + "px"
        };

        const blockProps = useBlockProps();
        return createElement(
            "div",
            blockProps,
            createElement(
                InspectorControls,
                null,
                // General Settings Panel
                createElement(
                    PanelBody,
                    { title: "General Settings", initialOpen: true },
                    createElement(TextControl, {
                        label: "Block Title",
                        value: attributes.title,
                        onChange: (value) => setAttributes({ title: value })
                    }),
                    createElement(RangeControl, {
                        label: "Products Per Row (Desktop)",
                        value: attributes.productsPerRowDesktop,
                        onChange: (value) => setAttributes({ productsPerRowDesktop: value }),
                        min: 1,
                        max: 6
                    }),
                    createElement(RangeControl, {
                        label: "Products Per Row (Mobile)",
                        value: attributes.productsPerRowMobile,
                        onChange: (value) => setAttributes({ productsPerRowMobile: value }),
                        min: 1,
                        max: 4
                    })
                ),
                // Title Settings Panel
                createElement(
                    PanelBody,
                    { title: "Title Settings", initialOpen: false },
                    createElement(SelectControl, {
                        label: "Text Alignment",
                        value: attributes.titleAlignment,
                        options: [
                            { label: "Left", value: "left" },
                            { label: "Center", value: "center" },
                            { label: "Right", value: "right" }
                        ],
                        onChange: (value) => setAttributes({ titleAlignment: value })
                    }),
                    createElement(RangeControl, {
                        label: "Font Size (px)",
                        value: attributes.titleFontSize,
                        onChange: (value) => setAttributes({ titleFontSize: value }),
                        min: 10,
                        max: 72
                    }),
                    createElement(
                        PanelColorSettings,
                        {
                            title: "Color Settings",
                            initialOpen: false,
                            colorSettings: [
                                {
                                    label: "Text Color",
                                    value: attributes.titleTextColor,
                                    onChange: (value) => setAttributes({ titleTextColor: value })
                                },
                                {
                                    label: "Background Color",
                                    value: attributes.titleBackgroundColor,
                                    onChange: (value) => setAttributes({ titleBackgroundColor: value })
                                }
                            ]
                        }
                    ),
                    createElement(TextControl, {
                        label: "Margin",
                        value: attributes.titleMargin,
                        onChange: (value) => setAttributes({ titleMargin: value }),
                        help: "CSS shorthand (e.g., '10px 20px')"
                    }),
                    createElement(TextControl, {
                        label: "Padding",
                        value: attributes.titlePadding,
                        onChange: (value) => setAttributes({ titlePadding: value }),
                        help: "CSS shorthand (e.g., '5px')"
                    }),
                    createElement(RangeControl, {
                        label: "Border Width (px)",
                        value: attributes.titleBorderWidth,
                        onChange: (value) => setAttributes({ titleBorderWidth: value }),
                        min: 0,
                        max: 10
                    }),
                    createElement(SelectControl, {
                        label: "Border Style",
                        value: attributes.titleBorderStyle,
                        options: [
                            { label: "None", value: "none" },
                            { label: "Solid", value: "solid" },
                            { label: "Dotted", value: "dotted" },
                            { label: "Dashed", value: "dashed" },
                            { label: "Double", value: "double" }
                        ],
                        onChange: (value) => setAttributes({ titleBorderStyle: value })
                    }),
                    createElement(
                        PanelColorSettings,
                        {
                            title: "Border Color",
                            initialOpen: false,
                            colorSettings: [
                                {
                                    label: "Border Color",
                                    value: attributes.titleBorderColor,
                                    onChange: (value) => setAttributes({ titleBorderColor: value })
                                }
                            ]
                        }
                    ),
                    createElement(RangeControl, {
                        label: "Border Radius (px)",
                        value: attributes.titleBorderRadius,
                        onChange: (value) => setAttributes({ titleBorderRadius: value }),
                        min: 0,
                        max: 50
                    })
                ),
                // Content Settings Panel
                createElement(
                    PanelBody,
                    { title: "Content Settings", initialOpen: false },
                    createElement(SelectControl, {
                        label: "Text Alignment",
                        value: attributes.contentAlignment,
                        options: [
                            { label: "Left", value: "left" },
                            { label: "Center", value: "center" },
                            { label: "Right", value: "right" }
                        ],
                        onChange: (value) => setAttributes({ contentAlignment: value })
                    }),
                    createElement(RangeControl, {
                        label: "Font Size (px)",
                        value: attributes.contentFontSize,
                        onChange: (value) => setAttributes({ contentFontSize: value }),
                        min: 10,
                        max: 72
                    }),
                    createElement(
                        PanelColorSettings,
                        {
                            title: "Color Settings",
                            initialOpen: false,
                            colorSettings: [
                                {
                                    label: "Text Color",
                                    value: attributes.contentTextColor,
                                    onChange: (value) => setAttributes({ contentTextColor: value })
                                },
                                {
                                    label: "Background Color",
                                    value: attributes.contentBackgroundColor,
                                    onChange: (value) => setAttributes({ contentBackgroundColor: value })
                                }
                            ]
                        }
                    ),
                    createElement(TextControl, {
                        label: "Margin",
                        value: attributes.contentMargin,
                        onChange: (value) => setAttributes({ contentMargin: value }),
                        help: "CSS shorthand (e.g., '10px 20px')"
                    }),
                    createElement(TextControl, {
                        label: "Padding",
                        value: attributes.contentPadding,
                        onChange: (value) => setAttributes({ contentPadding: value }),
                        help: "CSS shorthand (e.g., '5px')"
                    }),
                    createElement(RangeControl, {
                        label: "Border Width (px)",
                        value: attributes.contentBorderWidth,
                        onChange: (value) => setAttributes({ contentBorderWidth: value }),
                        min: 0,
                        max: 10
                    }),
                    createElement(SelectControl, {
                        label: "Border Style",
                        value: attributes.contentBorderStyle,
                        options: [
                            { label: "None", value: "none" },
                            { label: "Solid", value: "solid" },
                            { label: "Dotted", value: "dotted" },
                            { label: "Dashed", value: "dashed" },
                            { label: "Double", value: "double" }
                        ],
                        onChange: (value) => setAttributes({ contentBorderStyle: value })
                    }),
                    createElement(
                        PanelColorSettings,
                        {
                            title: "Border Color",
                            initialOpen: false,
                            colorSettings: [
                                {
                                    label: "Border Color",
                                    value: attributes.contentBorderColor,
                                    onChange: (value) => setAttributes({ contentBorderColor: value })
                                }
                            ]
                        }
                    ),
                    createElement(RangeControl, {
                        label: "Border Radius (px)",
                        value: attributes.contentBorderRadius,
                        onChange: (value) => setAttributes({ contentBorderRadius: value }),
                        min: 0,
                        max: 50
                    })
                )
            ),
            // Editor preview of the block
            createElement(
                "div",
                null,
                // Title Preview
                createElement("h3", { style: titleStyles }, attributes.title),
                // Content Preview (static preview)
                createElement(
                    "div",
                    { style: contentStyles },
                    createElement("p", null, "Subscription categories and products will be displayed here.")
                )
            )
        );
    },
    save: () => {
        // Front-end output is rendered via PHP render_callback.
        return null;
    }
});
