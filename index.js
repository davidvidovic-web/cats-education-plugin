(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var SelectControl = wp.components.SelectControl;
    var PanelBody = wp.components.PanelBody;

    registerBlockType('cats/educations', {
        title: 'Edukacije List',
        icon: 'welcome-learn-more',
        category: 'widgets',
        attributes: {
            za_koga: {
                type: 'string',
                default: ''
            }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps({
                className: 'edukacije-wrapper'
            });

            return el('div', blockProps,
                el(InspectorControls, {},
                    el(PanelBody, { title: 'Postavke', initialOpen: true },
                        el(SelectControl, {
                            label: 'Za koga?',
                            value: attributes.za_koga,
                            options: [
                                { label: 'Sve', value: '' },
                                { label: 'Suzana', value: 'suza' },
                                { label: 'Slađana', value: 'sladja' }
                            ],
                            onChange: function(newVal) {
                                setAttributes({ za_koga: newVal });
                            }
                        })
                    )
                ),
                el('div', { 
                    style: { 
                        padding: '20px', 
                        border: '1px dashed #ccc', 
                        backgroundColor: '#f0f0f0' 
                    } 
                },
                    el('strong', {}, 'Edukacije Lista'),
                    el('p', {}, 'Prikazuje se: ' + (attributes.za_koga ? (attributes.za_koga === 'suza' ? 'Suzana' : 'Slađana') : 'Sve edukacije'))
                )
            );
        },
        save: function() {
            return null; // Dynamic block
        }
    });
})(window.wp);
