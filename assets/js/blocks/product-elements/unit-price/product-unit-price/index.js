/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import FormattedMonetaryAmount from '@germanized/base-components/formatted-monetary-amount';
import classNames from 'classnames';
import { createInterpolateElement, isValidElement } from '@wordpress/element';
import { formatPrice } from '@woocommerce/price-format';

/**
 * Internal dependencies
 */
// import './style.scss';

const ProductUnitPrice = ( {
                               align,
                               className,
                               formattedPrice,
                               priceClassName,
                               priceStyle,
                               style,
                           } ) => {
    const wrapperClassName = classNames(
        className,
        'price',
        'wc-gzd-block-components-product-unit-price',
        {
            [ `wc-gzd-block-components-product-unit-price--align-${ align }` ]: align,
        }
    );

    let priceComponent = (
        <span
            className={ classNames(
                'wc-gzd-block-components-product-unit-price__value',
                priceClassName
            ) }
        />
    );

    if ( formattedPrice ) {
        if ( isValidElement( formattedPrice ) ) {
            priceComponent = (
                <span
                    className={ classNames(
                        'wc-gzd-block-components-product-unit-price__value',
                        priceClassName
                    ) }
                    style={ priceStyle }
                >
                    { formattedPrice }
                </span>
            );
        } else {
            priceComponent = (
                <span
                    className={ classNames(
                        'wc-gzd-block-components-product-unit-price__value',
                        priceClassName
                    ) }
                    style={ priceStyle }
                    dangerouslySetInnerHTML={ {
                        __html: formattedPrice,
                    } }
                />
            );
        }
    }

    return (
        <span className={ wrapperClassName } style={ style }>
			{ priceComponent }
		</span>
    );
};

export default ProductUnitPrice;