import {useEffect, useState} from "@wordpress/element";
import {closeSmall, Icon} from "@wordpress/icons";
import Block from "./block";

const Modal = ({
                   show,
                   url,
                   onClose,
                   content
               }) => {
    const [ isLoading, setIsLoading ] = useState( true );
    const [ modalContent, setModalContent ] = useState( '' );
    let relUrl = '';

    if ( content ) {
        setModalContent( content );
        setIsLoading( false );
    } else if ( url ) {
        try {
            const requestUrl = new URL( url );
            relUrl = url.toString().substring( requestUrl.origin.length );
        } catch {}
    }

    useEffect( () => {
        if ( show ) {
            document.body.classList.add( 'checkout-modal-open' );
        } else {
            document.body.classList.remove( 'checkout-modal-open' );
        }

        if ( relUrl ) {
            setIsLoading( true );
            setModalContent( '' );

            fetch( relUrl, {
                method: 'get',
            } )
                .then( response => response.text() )
                .then( response => {
                        setModalContent( response );
                        setIsLoading( false );
                    }
                )
                .catch(function(err) {
                    setIsLoading( false );
                });
        }
    }, [
        relUrl,
        setModalContent,
        show
    ] );

    if ( ! show ) {
        return null;
    }

    return (
        <>
            <div className="wc-gzd-checkout-modal-bg"></div>
            <div className="wc-gzd-checkout-modal-wrapper">
                <div className="wc-gzd-checkout-modal">
                    <div className="actions">
                        <a
                            className="wc-gzd-checkout-modal-close"
                            onClick={ ( e ) => {
                                document.body.classList.remove( 'checkout-modal-open' );
                                onClose( e );
                            } }
                        >
                            <Icon
                                className="wc-gzd-checkout-modal-close-icon"
                                icon={ closeSmall }
                                size={ 24 }
                            />
                        </a>
                    </div>
                    { isLoading ?
                        <div className="content is-loading"><span className="wc-block-components-spinner" aria-hidden="true" /></div>
                        :
                        <>
                            <div className="content"
                                 dangerouslySetInnerHTML={ {
                                     __html: modalContent,
                                 } }
                            />
                        </>
                    }
                </div>
            </div>
        </>
    );
};

export default Modal;