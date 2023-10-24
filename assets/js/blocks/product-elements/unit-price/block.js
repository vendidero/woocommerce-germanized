/**
 * External dependencies
 */
import { withProductDataContext } from '@woocommerce/shared-hocs';
import PriceLabelBlock from '../shared/price-label-block';

export default ( props ) => {
    props = { ...props, 'labelType': 'unit-price' };

    // It is necessary because this block has to support serveral contexts:
    // - Inside `All Products Block` -> `withProductDataContext` HOC
    // - Inside `Products Block` -> Gutenberg Context
    // - Inside `Single Product Template` -> Gutenberg Context
    // - Without any parent -> `WithSelector` and `withProductDataContext` HOCs
    // For more details, check https://github.com/woocommerce/woocommerce-blocks/pull/8609
    if ( props.isDescendentOfSingleProductTemplate ) {
        return <PriceLabelBlock { ...props } />;
    }
    return withProductDataContext( PriceLabelBlock )( props );
};
