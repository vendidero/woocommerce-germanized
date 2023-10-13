/**
 * Internal dependencies
 */

import { isNull } from './null';

export const isObject = (
    term
) => {
    return (
        ! isNull( term ) &&
        term instanceof Object &&
        term.constructor === Object
    );
};

export function objectHasProp(
    target,
    property
) {
    // The `in` operator throws a `TypeError` for non-object values.
    return isObject( target ) && property in target;
}

export const isEmptyObject = (
    object
) => {
    return Object.keys( object ).length === 0;
};
