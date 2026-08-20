import { useEffect, useState } from "@wordpress/element";
import classnames from "classnames";

/**
 * External dependencies
 */
const Block = ({
   children,
   extensions,
   className
}) => {
    const [ show, setShow ] = useState(false );
    const gzdData = extensions.hasOwnProperty( 'woocommerce-germanized' ) ? extensions['woocommerce-germanized'] : {};
    const needsLegalGuarantee = gzdData['needs_legal_guarantee'];

    console.log(needsLegalGuarantee);
    console.log(className);

    useEffect( () => {
        if ( needsLegalGuarantee ) {
            setShow( true );
        } else {
            setShow( false );
        }
    }, [
        needsLegalGuarantee,
        setShow
    ] );

    if ( ! show ) {
        return null;
    }

    return (
		<div
            className={ classnames(
                `wc-gzd-block-checkout__legal-guarantee wc-block-components-checkout-step`,
                className,
            ) }>
            { children }
        </div>
	);
};

export default Block;
