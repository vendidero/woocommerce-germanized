/**
 * External dependencies
 */
import { isString, isObject } from '@germanized/types';

export const useTypographyProps = ( props ) => {
    const typography = isObject( props.style.typography )
        ? props.style.typography
        : {};
    const classNameFallback = isString( typography.fontFamily )
        ? typography.fontFamily
        : '';
    const className = props.fontFamily
        ? `has-${ props.fontFamily }-font-family`
        : classNameFallback;

    return {
        className,
        style: {
            fontSize: props.fontSize
                ? `var(--wp--preset--font-size--${ props.fontSize })`
                : typography.fontSize,
            fontStyle: typography.fontStyle,
            fontWeight: typography.fontWeight,
            letterSpacing: typography.letterSpacing,
            lineHeight: typography.lineHeight,
            textDecoration: typography.textDecoration,
            textTransform: typography.textTransform,
        },
    };
};
