/**
 * External dependencies
 */
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import { extensionCartUpdate } from '@woocommerce/blocks-checkout';
import _ from 'lodash';

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

	const onChangeCheckbox = useCallback(
		( checkbox ) => {
			setCheckboxes( ( currentCheckboxes ) => {
				const needsUpdate = currentCheckboxes && currentCheckboxes.hasOwnProperty( checkbox.id ) && currentCheckboxes[ checkbox.id ].checked !== checkbox.checked;
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
			extensionCartUpdate
		]
	);

	// Check for new/adjusted cart data, e.g. retrieved via cart updates
	useEffect( () => {
		if ( hasRendered.current ) {
			let newCheckboxes = {};

			Object.keys( cartCheckboxes ).map( ( checkboxId ) => {
				const currentCheckbox = checkboxes.hasOwnProperty( checkboxId ) ? { 'checked': checkboxes[ checkboxId ].checked, 'hidden': checkboxes[ checkboxId ].hidden } : {};

				newCheckboxes[ checkboxId ] = { ...cartCheckboxes[ checkboxId ], ...currentCheckbox }
			});

			if ( !_.isEqual( newCheckboxes, checkboxes ) ) {
				setCheckboxes( newCheckboxes );
			}
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
