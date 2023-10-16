/**
 * External dependencies
 */
import classNames from 'classnames';
import { isValidElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './editor.scss';

const FormattedPriceLabel = ( {
    align,
    className,
    labelType,
    formattedLabel,
    labelClassName,
    labelStyle,
    style,
} ) => {
    const wrapperClassName = classNames(
        className,
        'wc-gzd-block-components-product-' + labelType,
        'wc-gzd-block-components-product-price-label',
        {
            [ `wc-gzd-block-components-product-price-label--align-${ align }` ]: align,
        }
    );

    let labelComponent = (
        <span
            className={ classNames(
                'wc-gzd-block-components-product-' + labelType + '__value',
                labelClassName
            ) }
        />
    );

    if ( formattedLabel ) {
        if ( isValidElement( formattedLabel ) ) {
            labelComponent = (
                <span
                    className={ classNames(
                        'wc-gzd-block-components-product-' + labelType + '__value',
                        labelClassName
                    ) }
                    style={ labelStyle }
                >
                    { formattedLabel }
                </span>
            );
        } else {
            labelComponent = (
                <span
                    className={ classNames(
                        'wc-gzd-block-components-product-' + labelType + '__value',
                        labelClassName
                    ) }
                    style={ labelStyle }
                    dangerouslySetInnerHTML={ {
                        __html: formattedLabel,
                    } }
                />
            );
        }
    }

    return (
        <span className={ wrapperClassName } style={ style }>
			{ labelComponent }
		</span>
    );
};

export default FormattedPriceLabel;