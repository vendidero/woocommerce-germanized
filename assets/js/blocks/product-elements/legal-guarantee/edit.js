/**
 * External dependencies
 */
import {
    AlignmentToolbar,
    BlockControls,
    useBlockProps,
    InspectorControls
} from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { PanelBody, SelectControl } from "@wordpress/components";
import { __, _x } from '@wordpress/i18n';
import { getSetting } from '@germanized/settings';

/**
 * Internal dependencies
 */
import Block from './block';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';
const Edit = ( {
    attributes,
    setAttributes,
    context,
} ) => {
    const blockProps = useBlockProps();
    const blockAttrs = {
        ...attributes,
        ...context,
    };
    const isDescendentOfQueryLoop = Number.isFinite( context.queryId );

    const { variant } = attributes;

    let { isDescendentOfSingleProductTemplate } =
        useIsDescendentOfSingleProductTemplate( { isDescendentOfQueryLoop } );

    if ( isDescendentOfQueryLoop ) {
        isDescendentOfSingleProductTemplate = false;
    }

    const variants = getSetting( 'legalGuaranteeVariants', {} );

    useEffect(
        () =>
            setAttributes( {
                isDescendentOfQueryLoop,
                isDescendentOfSingleProductTemplate,
            } ),
        [
            isDescendentOfQueryLoop,
            isDescendentOfSingleProductTemplate,
            setAttributes,
        ]
    );

    return (
        <>
            <BlockControls>
                <AlignmentToolbar
                    value={ attributes.textAlign }
                    onChange={ ( textAlign ) => {
                        setAttributes( { textAlign } );
                    } }
                />
            </BlockControls>
            <div { ...blockProps }>
                <Block { ...blockAttrs } />
            </div>
            <InspectorControls>
                <PanelBody>
                    <SelectControl
                        label={ __( 'Variant', 'woocommerce-germanized' ) }
                        value={ variant }
                        onChange={ ( value ) => setAttributes({ variant: value }) }
                        options={ Object.keys( variants ).map( ( key ) => {
                            return {
                                'label': variants[ key ],
                                'value': key
                            }
                        } ) }
                        __next40pxDefaultSize={ true }
                        __nextHasNoMarginBottom={ true }
                    />
                </PanelBody>
            </InspectorControls>
        </>
    );
};

export default Edit;