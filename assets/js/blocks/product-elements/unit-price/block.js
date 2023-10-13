/**
 * External dependencies
 */
import classnames from 'classnames';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
    useInnerBlockLayoutContext,
    useProductDataContext,
} from '@woocommerce/shared-context';
import { __, _x } from '@wordpress/i18n';
import { withProductDataContext } from '@woocommerce/shared-hocs';
import { useStyleProps } from '@germanized/base-hooks';
import FormattedMonetaryAmount from '@germanized/base-components/formatted-monetary-amount';

import ProductUnitPrice from './product-unit-price';

export const Block = ( props ) => {
    const { className, textAlign, isDescendentOfSingleProductTemplate } = props;
    const { parentName, parentClassName } = useInnerBlockLayoutContext();
    const { product } = useProductDataContext();
    const styleProps = useStyleProps( props );

    const isDescendentOfAllProductsBlock =
        parentName === 'woocommerce/all-products';

    const wrapperClassName = classnames(
        'wc-gzd-block-components-product-unit-price',
        className,
        styleProps.className,
        {
            [ `${ parentClassName }__product-unit-price` ]: parentClassName,
        }
    );

    if ( ! product.id && ! isDescendentOfSingleProductTemplate ) {
        const productPriceComponent = (
            <ProductUnitPrice align={ textAlign } className={ wrapperClassName } />
        );
        if ( isDescendentOfAllProductsBlock ) {
            return (
                <div className="wp-block-woocommerce-gzd-product-unit-price">
                    { productPriceComponent }
                </div>
            );
        }
        return productPriceComponent;
    }

    const gzdData = product.hasOwnProperty( 'extensions' ) ? product.extensions['woocommerce-germanized'] : {
        'unit_price_html': '',
        'unit_prices': {
            'price': 0,
            'regular_price': 0,
            'sale_price': 0
        }
    };

    const unit_price = gzdData.unit_price_html;
    const unit_prices = gzdData.unit_prices;
    const prices            = product.prices;
    const currency          = isDescendentOfSingleProductTemplate
        ? getCurrencyFromPriceResponse()
        : getCurrencyFromPriceResponse( prices );

    const pricePreview  = (
        <>
            <FormattedMonetaryAmount
                currency={ currency }
                value={ 1000 }
            /> / <span className="unit">{ _x( 'kg', 'unit', 'woocommerce-germanized' ) }</span>
        </>
    );

    const isOnSale = unit_prices.price !== unit_prices.regular_price;
    const priceClassName = classnames( {
        [ `${ parentClassName }__product-unit-price__value` ]: parentClassName,
        [ `${ parentClassName }__product-unit-price__value--on-sale` ]: isOnSale,
    } );

    const productPriceComponent = (
        <ProductUnitPrice
            align={ textAlign }
            className={ wrapperClassName }
            style={ styleProps.style }
            regularPriceStyle={ styleProps.style }
            priceStyle={ styleProps.style }
            priceClassName={ priceClassName }
            formattedPrice={
                isDescendentOfSingleProductTemplate
                    ? pricePreview
                    : unit_price
            }
        />
    );

    if ( isDescendentOfAllProductsBlock ) {
        return (
            <div className="wp-block-woocommerce-unit-price-price">
                { productPriceComponent }
            </div>
        );
    }
    return productPriceComponent;
};

export default ( props ) => {
    // It is necessary because this block has to support serveral contexts:
    // - Inside `All Products Block` -> `withProductDataContext` HOC
    // - Inside `Products Block` -> Gutenberg Context
    // - Inside `Single Product Template` -> Gutenberg Context
    // - Without any parent -> `WithSelector` and `withProductDataContext` HOCs
    // For more details, check https://github.com/woocommerce/woocommerce-blocks/pull/8609
    if ( props.isDescendentOfSingleProductTemplate ) {
        return <Block { ...props } />;
    }
    return withProductDataContext( Block )( props );
};
