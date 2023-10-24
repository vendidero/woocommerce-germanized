import { allSettings } from './settings-init';
import { getSetting as getWooSetting } from '@woocommerce/settings'

/**
 * Retrieves a setting value from the setting state.
 *
 * If a setting with key `name` does not exist or is undefined,
 * the `fallback` will be returned instead. An optional `filter`
 * callback can be passed to format the returned value.
 */
export const getSetting = (name, fallback= false) => {
    let value = fallback;
    
    if ( name in allSettings ) {
        value = allSettings[ name ];
    } else {
        value = getWooSetting( name, fallback );
    }

    return value;
};