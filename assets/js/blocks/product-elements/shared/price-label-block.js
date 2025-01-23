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
        'power_supply_html': '',
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
    } else if ( 'power_supply' === labelTypeData ) {
        formattedPreview = (
            <>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 150 188"
                >
                    <line className="power-supply-excluded-line" x1="20" y1="1" x2="200" y2="270" stroke="black" strokeWidth="3"></line>
                    <path d="m 49.026981,2.6593224 a 0.80511101,0.78626814 0 0 0 -0.533384,0.4874861 l -0.05535,0.1454597 V 32.754718 l -7.922292,0.004 -7.920279,0.0068 -0.153977,0.05798 a 0.87555821,0.85506659 0 0 0 -0.459919,0.452103 l -0.05535,0.142511 v 29.488987 l -8.129609,0.0078 c -8.849177,0.0118 -8.180933,0 -8.774703,0.146444 a 4.9715604,4.8552058 0 0 0 -3.70653,4.0768 c -0.05132,0.376426 -0.05132,113.336627 0,113.714027 0.274745,1.94994 1.686707,3.53526 3.612936,4.04928 0.372365,0.0964 0.600815,0.13466 1.026517,0.16118 0.266692,0.0158 19.121385,0.0236 58.361489,0.0158 53.90923,-0.008 57.98612,-0.008 58.20953,-0.0492 1.10501,-0.19657 1.96548,-0.61526 2.73234,-1.33274 a 4.9514326,4.835549 0 0 0 1.4784,-3.00256 c 0.0352,-0.37642 0.0352,-113.014245 -0.005,-113.406396 a 4.9514326,4.835549 0 0 0 -3.66326,-4.211449 c -0.64107,-0.169048 0.10063,-0.153322 -8.84615,-0.161185 l -8.12157,-0.0078 V 33.37291 l -0.0785,-0.154305 a 0.80511101,0.78626814 0 0 0 -0.62898,-0.441293 c -0.11473,-0.01572 -2.73234,-0.0226 -7.98469,-0.0226 h -7.81461 l -0.008,-14.781842 -0.009,-14.7710286 -0.0594,-0.1159746 a 0.94600542,0.92386506 0 0 0 -0.4086,-0.387237 L 98.9752,2.6367118 h -8.63683 l -0.144919,0.068799 a 0.78498322,0.76661143 0 0 0 -0.468739,0.595605 c -0.02043,0.1081118 -0.02748,4.3303718 -0.02748,14.8034622 V 32.755701 H 58.333064 l -0.008,-14.754322 -0.009,-14.7523555 -0.06642,-0.1415284 A 0.82523878,0.80592484 0 0 0 57.836909,2.7045328 l -0.150958,-0.068799 -4.277153,-0.00492 c -3.397568,-0.00294 -4.298286,0.00492 -4.381817,0.028502 M 56.677549,18.491811 V 32.753735 H 50.06155 V 4.2318587 h 6.615999 z m 41.289109,0 V 32.753735 H 91.350661 V 4.2318587 h 6.615997 z m 16.511832,30.14552 V 62.9081 H 33.554762 V 34.361653 h 80.923728 z m 18.0828,15.951414 c 0.67628,0.176911 1.17143,0.453088 1.63134,0.917969 0.41765,0.425568 0.67228,0.874723 0.84134,1.488995 l 0.0594,0.219173 0.008,56.682078 c 0.008,50.04793 0,56.70467 -0.0312,56.90123 -0.0594,0.31845 -0.16102,0.60937 -0.31902,0.917 -0.46295,0.8865 -1.28113,1.48996 -2.30362,1.70422 -0.20431,0.0422 -3.18421,0.0422 -58.420876,0.0422 -49.35733,0 -58.233678,-0.003 -58.38665,-0.0344 -1.435111,-0.27225 -2.519997,-1.3858 -2.689071,-2.76374 -0.02416,-0.18085 -0.02818,-14.48502 -0.02416,-56.83146 l 0.008,-56.59362 0.07045,-0.253571 c 0.08664,-0.318439 0.169173,-0.518938 0.322144,-0.791183 a 3.2908911,3.213871 0 0 1 1.734007,-1.478184 4.5287494,4.4227582 0 0 1 0.708498,-0.179859 c 0.04629,-0.004 26.286874,-0.0078 58.323246,-0.0078 l 58.244752,0.004 z m 0,0"></path>
                </svg>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 150 188"
                    className="power-supply-icon power-supply-charge-icon"
                >
                    <text x="50%" y="100" className="power-supply-min-max-watt">50 - 90</text>
                    <text x="50%" y="130" className="power-supply-watt-abbr">W</text>
                    <text x="50%" y="160" className="power-supply-usb-pd">USB-PD</text>
                    <path d="m 49.026981,2.6593224 a 0.80511101,0.78626814 0 0 0 -0.533384,0.4874861 l -0.05535,0.1454597 V 32.754718 l -7.922292,0.004 -7.920279,0.0068 -0.153977,0.05798 a 0.87555821,0.85506659 0 0 0 -0.459919,0.452103 l -0.05535,0.142511 v 29.488987 l -8.129609,0.0078 c -8.849177,0.0118 -8.180933,0 -8.774703,0.146444 a 4.9715604,4.8552058 0 0 0 -3.70653,4.0768 c -0.05132,0.376426 -0.05132,113.336627 0,113.714027 0.274745,1.94994 1.686707,3.53526 3.612936,4.04928 0.372365,0.0964 0.600815,0.13466 1.026517,0.16118 0.266692,0.0158 19.121385,0.0236 58.361489,0.0158 53.90923,-0.008 57.98612,-0.008 58.20953,-0.0492 1.10501,-0.19657 1.96548,-0.61526 2.73234,-1.33274 a 4.9514326,4.835549 0 0 0 1.4784,-3.00256 c 0.0352,-0.37642 0.0352,-113.014245 -0.005,-113.406396 a 4.9514326,4.835549 0 0 0 -3.66326,-4.211449 c -0.64107,-0.169048 0.10063,-0.153322 -8.84615,-0.161185 l -8.12157,-0.0078 V 33.37291 l -0.0785,-0.154305 a 0.80511101,0.78626814 0 0 0 -0.62898,-0.441293 c -0.11473,-0.01572 -2.73234,-0.0226 -7.98469,-0.0226 h -7.81461 l -0.008,-14.781842 -0.009,-14.7710286 -0.0594,-0.1159746 a 0.94600542,0.92386506 0 0 0 -0.4086,-0.387237 L 98.9752,2.6367118 h -8.63683 l -0.144919,0.068799 a 0.78498322,0.76661143 0 0 0 -0.468739,0.595605 c -0.02043,0.1081118 -0.02748,4.3303718 -0.02748,14.8034622 V 32.755701 H 58.333064 l -0.008,-14.754322 -0.009,-14.7523555 -0.06642,-0.1415284 A 0.82523878,0.80592484 0 0 0 57.836909,2.7045328 l -0.150958,-0.068799 -4.277153,-0.00492 c -3.397568,-0.00294 -4.298286,0.00492 -4.381817,0.028502 M 56.677549,18.491811 V 32.753735 H 50.06155 V 4.2318587 h 6.615999 z m 41.289109,0 V 32.753735 H 91.350661 V 4.2318587 h 6.615997 z m 16.511832,30.14552 V 62.9081 H 33.554762 V 34.361653 h 80.923728 z m 18.0828,15.951414 c 0.67628,0.176911 1.17143,0.453088 1.63134,0.917969 0.41765,0.425568 0.67228,0.874723 0.84134,1.488995 l 0.0594,0.219173 0.008,56.682078 c 0.008,50.04793 0,56.70467 -0.0312,56.90123 -0.0594,0.31845 -0.16102,0.60937 -0.31902,0.917 -0.46295,0.8865 -1.28113,1.48996 -2.30362,1.70422 -0.20431,0.0422 -3.18421,0.0422 -58.420876,0.0422 -49.35733,0 -58.233678,-0.003 -58.38665,-0.0344 -1.435111,-0.27225 -2.519997,-1.3858 -2.689071,-2.76374 -0.02416,-0.18085 -0.02818,-14.48502 -0.02416,-56.83146 l 0.008,-56.59362 0.07045,-0.253571 c 0.08664,-0.318439 0.169173,-0.518938 0.322144,-0.791183 a 3.2908911,3.213871 0 0 1 1.734007,-1.478184 4.5287494,4.4227582 0 0 1 0.708498,-0.179859 c 0.04629,-0.004 26.286874,-0.0078 58.323246,-0.0078 l 58.244752,0.004 z m 0,0"></path>
                </svg>
            </>
        );
    }

    return {
        'preview': formattedPreview,
        'data': data,
    }
};

const PriceLabelBlock = (props) => {
    const {className, textAlign, isDescendentOfSingleProductTemplate, labelType} = props;
    const {parentName, parentClassName} = useInnerBlockLayoutContext();
    const {product} = useProductDataContext();
    const styleProps = useStyleProps(props);

    const isDescendentOfAllProductsBlock = parentName === 'woocommerce/all-products';

    const wrapperClassName = classnames(
        'wc-gzd-block-components-product-' + labelType,
        className,
        styleProps.className,
        {
            [`${parentClassName}__product-${labelType}`]: parentClassName,
        }
    );

    if (!product.id && !isDescendentOfSingleProductTemplate) {
        const productComponent = (
            <FormattedPriceLabel align={textAlign} className={wrapperClassName} labelType={labelType}/>
        );

        if (isDescendentOfAllProductsBlock) {
            const allProductsClassName = `wp-block-woocommerce-gzd-product-${labelType}`;

            return (
                <div className={allProductsClassName}>
                    {productComponent}
                </div>
            );
        }

        return productComponent;
    }

    const previewData = getPreviewData(labelType, product, isDescendentOfSingleProductTemplate);

    const productComponent = (
        <FormattedPriceLabel
            align={textAlign}
            className={wrapperClassName}
            labelType={labelType}
            style={styleProps.style}
            labelStyle={styleProps.style}
            formattedLabel={
                isDescendentOfSingleProductTemplate
                    ? previewData['preview']
                    : previewData['data']
            }
        />
    );

    if (isDescendentOfAllProductsBlock) {
        const allProductsClassName = `wp-block-woocommerce-gzd-product-${labelType}`;

        return (
            <div className={allProductsClassName}>
                {productComponent}
            </div>
        );
    }
    return productComponent;
};

export default PriceLabelBlock;
