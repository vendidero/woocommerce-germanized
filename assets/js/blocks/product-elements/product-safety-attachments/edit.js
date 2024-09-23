/**
 * External dependencies
 */
import {
    AlignmentToolbar,
    BlockControls,
    useBlockProps,
} from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';

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

    let { isDescendentOfSingleProductTemplate } =
        useIsDescendentOfSingleProductTemplate( { isDescendentOfQueryLoop } );

    if ( isDescendentOfQueryLoop ) {
        isDescendentOfSingleProductTemplate = false;
    }

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
        </>
    );
};

export default Edit;