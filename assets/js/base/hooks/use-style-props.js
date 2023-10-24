/**
 * External dependencies
 */
import classnames from 'classnames';
import { isString, isObject } from '@germanized/types';

/**
 * Internal dependencies
 */
import { useTypographyProps } from './use-typography-props';
import {
    getColorClassesAndStyles,
    getBorderClassesAndStyles,
    getSpacingClassesAndStyles,
} from '../utils';

/**
 * Parses incoming props.
 *
 * This may include style properties at the top level, or may include a nested `style` object. This ensures the expected
 * values are present and converts any string based values to objects as required.
 */
const parseStyleAttributes = ( rawProps ) => {
    const props = isObject( rawProps )
        ? rawProps
        : {
            style: {},
        };

    let style = props.style;

    if ( isString( style ) ) {
        style = JSON.parse( style ) || {};
    }

    if ( ! isObject( style ) ) {
        style = {};
    }

    return {
        ...props,
        style,
    };
};

/**
 * Returns the CSS class names and inline styles for a block when provided with its props/attributes.
 *
 * This hook (and its utilities) borrow functionality from the Gutenberg Block Editor package--something we don't want
 * to import on the frontend.
 */
export const useStyleProps = ( props ) => {
    const styleAttributes = parseStyleAttributes( props );
    const colorProps = getColorClassesAndStyles( styleAttributes );
    const borderProps = getBorderClassesAndStyles( styleAttributes );
    const spacingProps = getSpacingClassesAndStyles( styleAttributes );
    const typographyProps = useTypographyProps( styleAttributes );

    return {
        className: classnames(
            typographyProps.className,
            colorProps.className,
            borderProps.className,
            spacingProps.className
        ),
        style: {
            ...typographyProps.style,
            ...colorProps.style,
            ...borderProps.style,
            ...spacingProps.style,
        },
    };
};
