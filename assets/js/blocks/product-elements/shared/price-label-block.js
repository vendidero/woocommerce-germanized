/**
 * External dependencies
 */
import classnames from 'classnames';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
    useInnerBlockLayoutContext,
    useProductDataContext,
} from '@woocommerce/shared-context';
import { __, _x, sprintf } from '@wordpress/i18n';
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
        'unit_product': 0,
        'unit_product_html': '',
        'delivery_time_html': '',
        'tax_info_html': '',
        'shipping_costs_info_html': '',
        'defect_description_html': '',
        'nutri_score': '',
        'nutri_score_html': '',
        'deposit_html': '',
        'deposit_prices': {
            'price': 0,
            'quantity': 0,
            'amount': 0
        },
        'deposit_packaging_type_html': '',
        'manufacturer_html': '',
        'product_safety_attachments_html': '',
        'safety_instructions_html': '',
    };

    const prices            = productData.prices;
    const currency          = isDescendentOfSingleProductTemplate
        ? getCurrencyFromPriceResponse()
        : getCurrencyFromPriceResponse( prices );

    const labelTypeData = labelType.replace( /-/g, '_' );
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
        formattedPreview = _x( 'Delivery time: 2-3 days', 'preview', 'woocommerce-germanized' );
    } else if ( 'tax_info' === labelTypeData ) {
        formattedPreview = _x( 'incl. 19 % VAT', 'preview', 'woocommerce-germanized' );
    } else if ( 'shipping_costs_info' === labelTypeData ) {
        formattedPreview = _x( 'plus shipping costs', 'preview', 'woocommerce-germanized' );
    } else if ( 'unit_product' === labelTypeData ) {
        formattedPreview = sprintf( _x( 'Product includes: %1$s kg', 'preview', 'woocommerce-germanized' ), 10 );
    } else if ( 'defect_description' === labelTypeData ) {
        formattedPreview = _x( 'This product has a serious defect.', 'preview', 'woocommerce-germanized' );
    } else if ( 'deposit' === labelTypeData ) {
        formattedPreview = (
            <>
                <span className="additional">{ _x( 'Plus', 'preview', 'woocommerce-germanized' ) }</span> <FormattedMonetaryAmount
                    currency={ currency }
                    value={ 40 }
                /> <span className="deposit-notice">{ _x( 'deposit', 'preview', 'woocommerce-germanized' ) }</span>
            </>
        );
    } else if ( 'deposit_packaging_type' === labelTypeData ) {
        formattedPreview = _x( 'Disposable', 'preview', 'woocommerce-germanized' );
    } else if ( 'nutri_score' === labelTypeData ) {
        formattedPreview = (
            <>
                <span className="wc-gzd-nutri-score-value wc-gzd-nutri-score-value-a">A</span>
            </>
        );
    } else if ( 'manufacturer' === labelTypeData ) {
        formattedPreview = (
            <>
                <p>
                    <stong>{ _x( 'Sample company name', 'preview', 'woocommerce-germanized' ) }</stong><br/>
                    { _x( 'Sample address', 'preview', 'woocommerce-germanized' ) }<br/>
                    { _x( '12345 Berlin', 'preview', 'woocommerce-germanized' ) }<br/>
                    { _x( 'sample@sample.com', 'preview', 'woocommerce-germanized' ) }
                </p>
                <h3>{ __( 'Person responsible for the EU', 'woocommerce-germanized' ) }</h3>
                <p>
                    <stong>{ _x( 'Sample company name', 'preview', 'woocommerce-germanized' ) }</stong><br/>
                    { _x( 'Sample address', 'preview', 'woocommerce-germanized' ) }<br/>
                    { _x( '12345 Berlin', 'preview', 'woocommerce-germanized' ) }<br/>
                    { _x( 'sample@sample.com', 'preview', 'woocommerce-germanized' ) }
                </p>
            </>
        );
    } else if ( 'product_safety_attachments' === labelTypeData ) {
        formattedPreview = (
            <>
                <ul>
                    <li><a href="#">{ _x( 'sample-filename.pdf', 'sample', 'woocommerce-germanized' ) }</a></li>
                </ul>
            </>
        );
    } else if ( 'safety_instructions' === labelTypeData ) {
        formattedPreview = (
            <>
                <p>
                    { _x( 'Sample safety instructions for a certain product.', 'preview', 'woocommerce-germanized' ) }<br/>
                </p>
            </>
        );
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
