/**
 * External dependencies
 */
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { extensionCartUpdate } from '@woocommerce/blocks-checkout';
import _ from 'lodash';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';

import Modal from './modal';
import LegalCheckbox from "./checkboxes/legal-checkbox";
import PrivacyCheckbox from "./checkboxes/privacy-checkbox";
import SepaCheckbox from "./checkboxes/sepa-checkbox";

const Block = ({
   children,
   checkoutExtensionData,
	extensions,
   cart
}) => {
	const [ showModal, setShowModal ] = useState( false );
	const { setExtensionData } = checkoutExtensionData;
	const gzdCartData = extensions.hasOwnProperty( 'woocommerce-germanized' ) ? extensions['woocommerce-germanized'] : {};
	const availableCheckboxes = gzdCartData.hasOwnProperty( 'checkboxes' ) ? gzdCartData['checkboxes'] : [];
	/**
	 * Default state
	 */
	const cartCheckboxes = availableCheckboxes.reduce(( acc, cur ) => (
		{ ...acc, [ cur.id ]: { ...cur, 'hidden': cur.default_hidden, 'checked': cur.default_checked } }
	), {} );
	const [ checkboxes, setCheckboxes ] = useState( cartCheckboxes );
	const [ modalUrl, setModalUrl ] = useState( '' );
	const hasRendered = useRef( false );

	const {
		currentPaymentMethod
	} = useSelect( ( select ) => {
		const paymentStore = select( PAYMENT_STORE_KEY );

		return {
			currentPaymentMethod: paymentStore.getActivePaymentMethod(),
		}
	} );
	const getExtensionDataFromCheckboxes = ( checkboxes ) => {
		return Object.values( checkboxes ).filter( ( checkbox ) => {
			if ( checkbox.checked || ( ! checkbox.has_checkbox && ! checkbox.hidden ) ) {
				return checkbox;
			}

			return null;
		} );
	};

	// Update extension data
	useEffect( () => {
		setExtensionData(
			'woocommerce-germanized',
			'checkboxes',
			getExtensionDataFromCheckboxes( checkboxes )
		);
	}, [
		checkboxes
	] );

	useEffect( () => {
		Object.keys( checkboxes ).map( ( checkboxId ) => {
			if ( checkboxes[ checkboxId ].show_for_payment_methods.length > 0 ) {
				onChangeCheckbox( checkboxes[ checkboxId ] );
			}
		});
	}, [
		currentPaymentMethod
	] );

	const onChangeCheckbox = useCallback(
		( checkbox ) => {
			setCheckboxes( ( currentCheckboxes ) => {
				const needsUpdate = currentCheckboxes && currentCheckboxes.hasOwnProperty( checkbox.id ) && currentCheckboxes[ checkbox.id ].checked !== checkbox.checked;

				/**
				 * This is a tweak that overrides current checkbox hidden state
				 * in case the checkbox is conditionally shown for certain payment methods only
				 * as current payment method is only available client-side.
				 */
				if ( checkbox.show_for_payment_methods.length > 0 ) {
					let isHidden = checkbox.default_hidden;

					if ( ! isHidden ) {
						checkbox.hidden = ! _.includes( checkbox.show_for_payment_methods, currentPaymentMethod );
					} else {
						checkbox.hidden = isHidden;
					}
				}

				const updatedCheckboxes = { ...currentCheckboxes, [ checkbox.id ]: { ...checkbox } };

				if ( needsUpdate ) {
					extensionCartUpdate( {
						namespace: 'woocommerce-germanized-checkboxes',
						data: {
							'checkboxes': getExtensionDataFromCheckboxes( updatedCheckboxes )
						},
					} );
				}

				return updatedCheckboxes;
			} );
		},
		[
			setExtensionData,
			checkboxes,
			setCheckboxes,
			extensionCartUpdate,
			currentPaymentMethod
		]
	);

	// Check for new/adjusted cart data, e.g. retrieved via cart updates
	useEffect( () => {
		if ( hasRendered.current ) {
			let newCheckboxes = {};

			Object.keys( cartCheckboxes ).map( ( checkboxId ) => {
				const currentCheckbox = checkboxes.hasOwnProperty( checkboxId ) ? checkboxes[ checkboxId ] : {};
				const newCheckbox = checkboxes.hasOwnProperty( checkboxId ) ? { 'checked': checkboxes[ checkboxId ].checked, 'hidden': checkboxes[ checkboxId ].hidden } : {};

				newCheckboxes[ checkboxId ] = { ...cartCheckboxes[ checkboxId ], ...newCheckbox };

				if ( newCheckboxes[ checkboxId ] !== currentCheckbox ) {
					onChangeCheckbox( newCheckboxes[ checkboxId ] );
				}
			});
		}

		hasRendered.current = true;
	}, [
		availableCheckboxes
	] );

	return (
		<div className="wc-gzd-checkboxes">
			<Modal
				show={ showModal }
				url={ modalUrl }
				onClose={ () => {
					setShowModal( false );
				} }
			></Modal>

			{ Object.keys( checkboxes ).map( ( checkboxId ) => {
				const checkbox = { ...checkboxes[ checkboxId ] };

				if ( 'sepa' === checkbox.id ) {
					return (
						<SepaCheckbox
							checkbox={ checkbox }
							setShowModal={ setShowModal }
							setModalUrl={ setModalUrl }
							key={ checkbox.id }
							onChangeCheckbox={ onChangeCheckbox }
						/>
					)
				} else if ( 'privacy' === checkbox.id ) {
					return (
						<PrivacyCheckbox
							checkbox={ checkbox }
							setShowModal={ setShowModal }
							setModalUrl={ setModalUrl }
							key={ checkbox.id }
							onChangeCheckbox={ onChangeCheckbox }
						/>
					)
				} else {
					return (
						<LegalCheckbox
							checkbox={ checkbox }
							setShowModal={ setShowModal }
							setModalUrl={ setModalUrl }
							key={ checkbox.id }
							onChangeCheckbox={ onChangeCheckbox }
						/>
					)
				}
			} ) }
		</div>
	);
};
export default Block;
