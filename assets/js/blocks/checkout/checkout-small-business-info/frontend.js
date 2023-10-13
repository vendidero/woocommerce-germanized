import { getSetting } from '@germanized/settings';

const SmallBusinessInfo = ({
    extensions,
    cart
}) => {
    if ( ! getSetting( 'isSmallBusiness' ) || ! getSetting( 'smallBusinessNotice' ) ) {
        return null;
    }

    return (
        <div className="wc-gzd-small-business-info"
           dangerouslySetInnerHTML={ {
               __html: getSetting( 'smallBusinessNotice' ),
           } }
        />
    );
};

export default SmallBusinessInfo;