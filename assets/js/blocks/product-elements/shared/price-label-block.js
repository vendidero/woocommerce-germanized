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

import FormattedPriceLabel from './formatted-price-label';

const getPreviewData = ( labelType, productData, isDescendentOfSingleProductTemplate ) => {
    const gzdData = productData.hasOwnProperty( 'extensions' ) ? productData.extensions['woocommerce-germanized'] : {
        'unit_price_html': '',
        'unit_prices': {
            'price': 0,
            'regular_price': 0,
            'sale_price': 0
        },
        'delivery_time_html': '',
        'tax_info_html': '',
    };

    const prices            = productData.prices;
    const currency          = isDescendentOfSingleProductTemplate
        ? getCurrencyFromPriceResponse()
        : getCurrencyFromPriceResponse( prices );

    const labelTypeData = labelType.replace( '-', '_' );
    const data = gzdData.hasOwnProperty( labelTypeData + '_html' ) ? gzdData[ labelTypeData + '_html' ] : '';
    let formattedPreview  = '';

    if ( 'unit_price' === labelTypeData ) {
        formattedPreview = (
            <>
                <FormattedMonetaryAmount
                    currency={ currency }
                    value={ 1000 }
                /> / <span className="unit">{ _x( 'kg', 'unit', 'woocommerce-germanized' ) }</span>
            </>
        );
    } else if ( 'delivery_time' === labelTypeData ) {
        formattedPreview = __( 'Delivery time: 2-3 days', 'preview', 'woocommerce-germanized' );
    } else if ( 'tax_info' === labelTypeData ) {
        formattedPreview = __( 'incl. 19 % VAT', 'preview', 'woocommerce-germanized' );
    } else if ( 'shipping_info' === labelTypeData ) {
        formattedPreview = __( 'plus shipping costs', 'preview', 'woocommerce-germanized' );
    }

    return {
        'preview': formattedPreview,
        'data': data,
    }
};

const PriceLabelBlock = ( props ) => {
    const { className, textAlign, isDescendentOfSingleProductTemplate, labelType } = props;
    const { parentName, parentClassName } = useInnerBlockLayoutContext();
    const { product } = useProductDataContext();
    const styleProps = useStyleProps( props );

    const isDescendentOfAllProductsBlock = parentName === 'woocommerce/all-products';

    const wrapperClassName = classnames(
        'wc-gzd-block-components-product-' + labelType,
        className,
        styleProps.className,
        {
            [ `${ parentClassName }__product-${ labelType }` ]: parentClassName,
        }
    );

    if ( ! product.id && ! isDescendentOfSingleProductTemplate ) {
        const productComponent = (
            <FormattedPriceLabel align={ textAlign } className={ wrapperClassName } labelType={ labelType } />
        );

        if ( isDescendentOfAllProductsBlock ) {
            const allProductsClassName = `wp-block-woocommerce-gzd-product-${ labelType }`;

            return (
                <div className={ allProductsClassName }>
                    { productComponent }
                </div>
            );
        }

        return productComponent;
    }

    const previewData = getPreviewData( labelType, product, isDescendentOfSingleProductTemplate );

    const productComponent = (
        <FormattedPriceLabel
            align={ textAlign }
            className={ wrapperClassName }
            labelType={ labelType }
            style={ styleProps.style }
            labelStyle={ styleProps.style }
            formattedLabel={
                isDescendentOfSingleProductTemplate
                    ? previewData['preview']
                    : previewData['data']
            }
        />
    );

    if ( isDescendentOfAllProductsBlock ) {
        const allProductsClassName = `wp-block-woocommerce-gzd-product-${ labelType }`;

        return (
            <div className={ allProductsClassName }>
                { productComponent }
            </div>
        );
    }
    return productComponent;
};

export default PriceLabelBlock;
