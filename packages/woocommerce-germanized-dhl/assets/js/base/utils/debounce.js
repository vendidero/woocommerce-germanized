export const debounce = (
    func,
    wait,
    immediate
) => {
    let timeout;
    let latestArgs;

    const debounced = ( ( ...args ) => {
        latestArgs = args;
        if ( timeout ) clearTimeout( timeout );
        timeout = setTimeout( () => {
            timeout = null;
            if ( ! immediate && latestArgs ) func( ...latestArgs );
        }, wait );
        if ( immediate && ! timeout ) func( ...args );
    } );

    debounced.flush = () => {
        if ( timeout && latestArgs ) {
            func( ...latestArgs );
            clearTimeout( timeout );
            timeout = null;
        }
    };

    return debounced;
};
