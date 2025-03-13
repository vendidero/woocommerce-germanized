<?php

namespace baltpeter\Internetmarke;

class Service extends \SoapClient {
    protected $partner_information;

    /**
     * Service constructor.
     *
     * @param $partner_information PartnerInformation
     * @param array $options A array of config values for `SoapClient` (see PHP docs)
     * @param string $wsdl The wsdl file to use (defaults to 'https://internetmarke.deutschepost.de/OneClickForAppV3?wsdl')
     */
    public function __construct($partner_information, $options = array(), $wsdl = null) {
        $this->partner_information = $partner_information;
        $options = array_merge(array('features' => SOAP_SINGLE_ELEMENT_ARRAYS), $options);
        if($wsdl === null) {
            $wsdl = 'https://internetmarke.deutschepost.de/OneClickForAppV3?wsdl';
        }
        parent::__construct($wsdl, $options);
        $this->__setSoapHeaders($this->partner_information->soapHeaderArray());
    }

    /**
     * Used to authenticate a user on the system. Returns a token and some information about the user
     *
     * @param $username string The user's email address
     * @param $password string The user's (plaintext) password
     * @return User An object holding: - a token used as authentication for other methods, - the user's wallet balance,
     *      - whether the user accepted the T&C, - an (optional) information text
     */
    public function authenticateUser($username, $password) {
        $result = $this->__soapCall('authenticateUser', array('AuthenticateUserRequest' => array('username' => $username, 'password' => $password)));
        return User::fromStdObject($result);
    }

    /**
     * Fetch a list of all valid page formats
     *
     * @return PageFormat[]
     */
    public function retrievePageFormats() {
        $result = $this->__soapCall('retrievePageFormats', array());
        $array_result = array();
        foreach($result->pageFormat as $item) {
            $array_result[] = PageFormat::fromStdObject($item);
        }
        return $array_result;
    }

    /**
     * Generate a unique order number (if your system doesn't generate its own)
     *
     * @param $user_token string A token to authenticate the user (gotten from `authenticateUser`)
     * @return string Next available shop order ID
     */
    public function createShopOrderId($user_token) {
        $result = $this->__soapCall('createShopOrderId', array('CreateShopOrderIdRequest' => array('userToken' => $user_token)));
        return $result->shopOrderId;
    }

    /**
     * Fetch a hierarchical structure of image categories and the images in those categories
     *
     * @return PublicGalleryItem[]
     */
    public function retrievePublicGallery() {
        $result = $this->__soapCall('retrievePublicGallery', array());
        $array_result = array();
        foreach($result->items as $item) {
            $array_result[] = PublicGalleryItem::fromStdObject($item);
        }
        return $array_result;
    }

    /**
     * Fetch the user's private image gallery
     *
     * @param $user_token string A token to authenticate the user (gotten from `authenticateUser`)
     * @return array The user's images (empty if there are none)
     */
    public function retrievePrivateGallery($user_token) {
        $result = $this->__soapCall('retrievePrivateGallery', array('RetrievePublicGalleryRequest' => array('userToken' => $user_token)));
        return $result;
    }

    /**
     * Get a link to a preview of a stamp in PDF format
     *
     * @param $product_code int A product code for the type of stamp (a list of products is only available via the separate ProdWS service)
     * @param $voucher_layout string The layout of the stamp (possible values: 'FrankingZone' and 'AddressZone')
     * @param $page_format_id int ID of the page layout to be used (gotten from `retrievePageFormats`)
     * @param null $image_id An image ID to include in the stamp (optional, gotten from `retrievePublicGallery` or `retrievePrivateGallery`)
     * @return string A link to the preview stamp in PDF format
     */
    public function retrievePreviewVoucherPdf($product_code, $voucher_layout, $page_format_id, $image_id = null) {
        $result = $this->__soapCall('retrievePreviewVoucherPDF',
            array('RetrievePreviewVoucherPDFRequest' => array('productCode' => $product_code, 'imageID' => $image_id,
                'voucherLayout' => $voucher_layout, 'pageFormatId' => $page_format_id)));
        return $result->link;
    }

    /**
     * Get a link to a preview of a stamp in PNG format
     *
     * @param $product_code int A product code for the type of stamp (a list of products is only available via the separate ProdWS service)
     * @param $voucher_layout string The layout of the stamp (possible values: 'FrankingZone' and 'AddressZone')
     * @param null $image_id An image ID to include in the stamp (optional, gotten from `retrievePublicGallery` or `retrievePrivateGallery`)
     * @return string A link to the preview stamp in PNG format
     */
    public function retrievePreviewVoucherPng($product_code, $voucher_layout, $image_id = null) {
        $result = $this->__soapCall('retrievePreviewVoucherPNG',
            array('RetrievePreviewVoucherPNGRequest' => array('productCode' => $product_code, 'imageID' => $image_id,
                'voucherLayout' => $voucher_layout)));
        return $result->link;
    }

    /**
     * Create a stamp in PDF format (costs actual money, debited from the Portokasse account)
     *
     * @param $user_token string A token to authenticate the user (gotten from `authenticateUser`)
     * @param $page_format_id int ID of the page layout to be used (gotten from `retrievePageFormats`)
     * @param $positions OrderItem[] An array of items to be ordered
     * @param $total int The total value of the shopping cart in eurocents (this is actually checked by the server and has to be correct)
     * @param null $shop_order_id
     * @param null $ppl_id
     * @param null $create_manifest bool Whether to create a posting receipt
     * @param null $create_shipping_list int Type of shipping list to be created (0: No shipping list,
     *      1: Shipping list without addresses, 2: Shipping list with addresses)
     * @return \stdClass An object containing: - a link to the PDF version of the stamp, - a link to the shipping list (if requested),
     *      - the user's wallet balance after the order, - the order ID, - the voucher ID, - the tracking ID (if applicable)
     */
    public function checkoutShoppingCartPdf($user_token, $page_format_id, $positions, $total, $shop_order_id = null,
                                            $ppl_id = null, $create_manifest = null, $create_shipping_list = null) {
        $result = $this->__soapCall('checkoutShoppingCartPDF', array('CheckoutShoppingCartPDFRequest' => array(
            'userToken' => $user_token, 'shopOrderId' => $shop_order_id, 'pageFormatId' => $page_format_id, 'ppl' => $ppl_id,
            'positions' => array_map("DeepCopy\deep_copy", $positions), 'total' => $total, 'createManifest' => $create_manifest, 'createShippingList' => $create_shipping_list
        )));
        return $result;
    }

    /**
     * Create a stamp in PNG format (costs actual money, debited from the Portokasse account)
     *
     * @param $user_token string A token to authenticate the user (gotten from `authenticateUser`)
     * @param $positions OrderItem[] An array of items to be ordered
     * @param $total int The total value of the shopping cart in eurocents (this is actually checked by the server and has to be correct)
     * @param null $shop_order_id
     * @param null $ppl_id
     * @param null $create_manifest bool Whether to create a posting receipt
     * @param null $create_shipping_list int Type of shipping list to be created (0: No shipping list,
     *      1: Shipping list without addresses, 2: Shipping list with addresses)
     * @return \stdClass An object containing: - a link to the PNG version of the stamp, - a link to the shipping list (if requested),
     *      - the user's wallet balance after the order, - the order ID, - the voucher ID, - the tracking ID (if applicable)
     */
    public function checkoutShoppingCartPng($user_token, $positions, $total, $shop_order_id = null,
                                            $ppl_id = null, $create_manifest = null, $create_shipping_list = null) {
        $result = $this->__soapCall('checkoutShoppingCartPNG', array('CheckoutShoppingCartPNGRequest' => array(
            'userToken' => $user_token, 'shopOrderId' => $shop_order_id, 'ppl' => $ppl_id, 'positions' => array_map("DeepCopy\deep_copy", $positions),
            'total' => $total, 'createManifest' => $create_manifest, 'createShippingList' => $create_shipping_list
        )));
        return StampPngResult::fromStdObject($result);
    }

    /**
     * Fetch a previous order (from `checkoutShoppingCartPdf` or `checkoutShoppingCartPng`)
     *
     * @param $user_token string A token to authenticate the user (gotten from `authenticateUser`)
     * @param $shop_order_id int The order ID of the order to be fetched
     * @return \stdClass Same as for the corresponding call to `checkoutShoppingCart(Pdf|Png)`
     */
    public function retrieveOrder($user_token, $shop_order_id) {
        $result = $this->__soapCall('retrieveOrder',
            array('RetrieveOrderRequest' => array('userToken' => $user_token, 'shopOrderId' => $shop_order_id)));
        return $result;
    }
}
