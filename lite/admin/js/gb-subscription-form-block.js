( function ( wp ) {
    let __            = wp.i18n.__,
    el                = wp.element.createElement,
    registerBlockType = wp.blocks.registerBlockType,
    ServerSideRender  = wp.serverSideRender,
    useBlockProps     = wp.blockEditor.useBlockProps,
    InspectorControls = wp.blockEditor.InspectorControls,
    PanelBody         = wp.components.PanelBody,
    Placeholder       = wp.components.Placeholder;

    const esForms     = window.es_forms;
    registerBlockType( 'email-subscribers/subscription-form-block', {
        apiVersion: 2,
        title: 'Icegram Express Form',
        icon: 'feedback',
        category: 'common',
        attributes: { // The data this block will be storing
            formID: { type: 'number' }
        },
        edit: function ( props ) {
            var blockProps = useBlockProps();
            return el(
                'div',
                blockProps,
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        {
                            title: __( 'Subscription Form', 'email-subscribers' )
                        },
                        displaySelectFormDropdown(props)
                    ),
                ),
                props.attributes.formID ? el( ServerSideRender, {
                    block: 'email-subscribers/subscription-form-block',
                    attributes: props.attributes,
                } ) : el('div', {}, el( Placeholder, {}, displaySelectFormDropdown( props ) ) )
            );
        }
    });

    const displaySelectFormDropdown = props => {
        return el(
            'select',
            {
                value:    props.attributes.formID ? props.attributes.formID : '',
                onChange: event  => props.setAttributes( { formID: parseInt(event.target.value) } )
            },
            el( 'option',
                {
                    value: ''
                }, 
                __( 'Select a form', 'email-subscribers' )
            ),
            ...esForms.map( form => {
                return el( 'option', { value: form.id }, form.name )
            })
        );
    }
} )(
    window.wp
);
