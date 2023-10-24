import { useEffect } from "@wordpress/element";
import classnames from "classnames";
import { useSelect, useDispatch } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { CheckboxControl } from '@woocommerce/blocks-checkout';

const LegalCheckbox = ({
   checkbox,
   setShowModal,
   setModalUrl,
   onChangeCheckbox
}) => {
    const validationErrorId = 'checkbox-' + checkbox.id;
    const { setValidationErrors, clearValidationError } = useDispatch( VALIDATION_STORE_KEY );
    const isHidden = checkbox.hidden ? checkbox.hidden : false;

    const error = useSelect( ( select ) => {
        return select( VALIDATION_STORE_KEY ).getValidationError(
            validationErrorId
        );
    } );

    const hasError = !! ( error?.message && ! error?.hidden );

    // Track validation errors for this input.
    useEffect( () => {
        if ( ! checkbox.has_checkbox ) {
            return;
        }

        if ( true === checkbox.checked || true === checkbox.hidden ) {
            clearValidationError( validationErrorId );
        } else if ( checkbox.is_required ) {
            setValidationErrors( {
                [ validationErrorId ]: {
                    message: checkbox.error_message,
                    hidden: true,
                },
            } );
        }
        return () => {
            clearValidationError( validationErrorId );
        };
    }, [
        checkbox.is_required,
        checkbox.checked,
        checkbox.hidden,
        validationErrorId,
        clearValidationError,
        setValidationErrors,
    ] );

    const fieldProps = {
        id: `checkbox-${ checkbox.html_id }`,
        className: `wc-gzd-checkbox`,
        name: `${ checkbox.name }`,
        checked: checkbox.checked ? true : false,
        hasError: checkbox.is_required && hasError,
        required: checkbox.is_required
    };

    if ( isHidden ) {
        return null;
    }

    return (
        <div
            className={ classnames(
                `wc-gzd-block-checkout-checkboxes__${ checkbox.id }`,
                Object.values( checkbox.wrapper_classes ).join( ' ' )
            ) }
            key={ `wrapper-${ checkbox.id }` }
        >
            { checkbox.has_checkbox ? (
                <>
                    <CheckboxControl
                        key={ `checkbox-${ checkbox.id }` }
                        { ...fieldProps }
                        onChange={ ( isChecked ) => {
                            onChangeCheckbox( { ...checkbox, 'checked': isChecked } );
                        } }
                    >
						<span
                            onClick={ ( e ) => {
                                const el = e.target.closest( "a" );

                                if ( el && e.currentTarget.contains( el ) && el.classList.contains( 'wc-gzd-modal' ) ) {
                                    e.stopPropagation();
                                    e.preventDefault();

                                    let href = el.getAttribute( 'href' );

                                    if ( href ) {
                                        setModalUrl( href );
                                        setShowModal( true );
                                    }
                                }
                            } }
                            dangerouslySetInnerHTML={ {
                                __html: checkbox.label,
                            } }
                        />
                    </CheckboxControl>
                </>
            ) : (
                <div className="wc-gzd-checkbox has-no-checkbox">
					<span
                        dangerouslySetInnerHTML={ {
                            __html: checkbox.label,
                        } }
                    />
                </div>
            ) }
        </div>
    );
};

export default LegalCheckbox;