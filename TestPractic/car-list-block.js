(function (blocks, element, components, i18n) {
    const el = element.createElement;
    const { ServerSideRender, PanelBody, ToggleControl, SelectControl } = components;
    const { __ } = i18n;

    blocks.registerBlockType("TestPractic/car-list-block", {
        "attributes": {
            "showFilters": {
                "type": "boolean",
                "default": true,
            },
            "fuel": {
                "type": "string",
            },
            "manufacturer": {
                "type": "string",
            },
            "color": {
                "type": "string",
            },
        },
        "category": "widgets",
        "edit": function (props) {
            const {attributes, setAttributes} = props;

            return [
                el(PanelBody, {
                        "title": __("Car List Settings", "your-theme"),
                        "initialOpen": true,
                    },
                    el(ToggleControl, {
                        "label": __("Show Filters", "your-theme"),
                        "checked": attributes.showFilters,
                        "onChange": function (value) {
                            setAttributes({"showFilters": value});
                        },
                    }),
                    el(SelectControl, {
                        "label": __("Fuel", "your-theme"),
                        "value": attributes.fuel,
                        "options": [
                            {"value": "", "label": __("All Fuels", "your-theme")},
                            {"value": "Gasoline", "label": __("Gasoline", "your-theme")},
                            {"value": "Diesel", "label": __("Diesel", "your-theme")},
                            {"value": "Electric", "label": __("Electric", "your-theme")},
                            {"value": "GPL", "label": __("GPL", "your-theme")},
                        ],
                        "onChange": function (value) {
                            setAttributes({"fuel": value});
                        },
                    }),
                    el(SelectControl, {
                        "label": __("Manufacturer", "your-theme"),
                        "value": attributes.manufacturer,
                        "options": [
                            {"value": "", "label": __("All Manufacturers", "your-theme")},
                            {"value": "VW", "label": __("VW", "your-theme")},
                            {"value": "Renault", "label": __("Renault", "your-theme")},
                            {"value": "Mercedes", "label": __("Mercedes", "your-theme")},
                        ],
                        "onChange": function (value) {
                            setAttributes({"manufacturer": value});
                        },
                    }),
                    el(SelectControl, {
                        "label": __("Color", "your-theme"),
                        "value": attributes.color,
                        "options": [
                            {"value": "", "label": __("All Colors", "your-theme")},
                            {"value": "Red", "label": __("Red", "your-theme")},
                            {"value": "Blue", "label": __("Blue", "your-theme")},
                            {"value": "Green", "label": __("Green", "your-theme")},
                            {"value": "Yellow", "label": __("Yellow", "your-theme")},
                        ],
                        "onChange": function (value) {
                            setAttributes({"color": value});
                        },
                    })
                ),
                el(ServerSideRender, {
                    "block": "your-theme/car-list-block",
                    "attributes": attributes,
                })
            ];
        },
        "icon": "list-view",
        "save": function () {
            return null;
        },
        "title": __("Car List Block", "your-theme"),
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.i18n
);
