<?php

namespace WsdlToPhp\WsSecurity;

class Nonce extends Element
{
    /**
     * Element name.
     *
     * @var string
     */
    const NAME = 'Nonce';
    /**
     * Element name.
     *
     * @var string
     */
    const ATTRIBUTE_ENCODING_TYPE = 'EncodingType';
    /**
     * Element name.
     *
     * @var string
     */
    const NS_ENCODING = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';

    /**
     * Constructor for Nonce element.
     *
     * @param string $nonce     the nonce value
     * @param string $namespace the namespace
     */
    public function __construct($nonce, $namespace = self::NS_WSSE)
    {
        parent::__construct(self::NAME, $namespace, self::encodeNonce($nonce), [
            self::ATTRIBUTE_ENCODING_TYPE => self::NS_ENCODING,
        ]);
    }

    /**
     * Encode Nonce value.
     *
     * @param string $nonce
     *
     * @return string
     */
    public static function encodeNonce($nonce)
    {
        return base64_encode(pack('H*', $nonce));
    }
}
