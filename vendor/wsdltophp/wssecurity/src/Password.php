<?php

namespace WsdlToPhp\WsSecurity;

/**
 * Class that represents the Password element.
 *
 * @author WsdlToPhp Team <contact@wsdltophp.com>
 */
class Password extends Element
{
    /**
     * Element name.
     *
     * @var string
     */
    const NAME = 'Password';
    /**
     * Element attribute type name.
     *
     * @var string
     */
    const ATTRIBUTE_TYPE = 'Type';
    /**
     * Passwor must be sent using digest.
     *
     * @var string
     */
    const TYPE_PASSWORD_DIGEST = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest';
    /**
     * Passwor must be sent in text.
     *
     * @var string
     */
    const TYPE_PASSWORD_TEXT = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText';
    /**
     * TypeValue of password.
     *
     * @var string
     */
    protected $typeValue;

    /**
     * Constructor for Password element.
     *
     * @param string $password       the password
     * @param string $typeValue      the typeValue
     * @param string $timestampValue the timestamp to use
     * @param string $namespace      the namespace
     */
    public function __construct($password, $typeValue = self::TYPE_PASSWORD_TEXT, $timestampValue = 0, $namespace = self::NS_WSSE)
    {
        $this
            ->setTypeValue($typeValue)
            ->setTimestampValue($timestampValue ? $timestampValue : time())
            ->setNonceValue(mt_rand())
        ;
        parent::__construct(self::NAME, $namespace, $this->convertPassword($password), [
            self::ATTRIBUTE_TYPE => $typeValue,
        ]);
    }

    /**
     * Returns the converted form of the password accroding to the password typeValue.
     *
     * @param string $password
     */
    public function convertPassword($password)
    {
        if (self::TYPE_PASSWORD_DIGEST === $this->getTypeValue()) {
            $password = $this->digestPassword($password);
        }

        return $password;
    }

    /**
     * When generating the password digest, we define values (nonce and timestamp) that can be used in other place.
     *
     * @param string $password
     */
    public function digestPassword($password)
    {
        $packedNonce = pack('H*', $this->getNonceValue());
        $packedTimestamp = pack('a*', $this->getTimestampValue(true));
        $packedPassword = pack('a*', $password);
        $hash = sha1($packedNonce . $packedTimestamp . $packedPassword);
        $packedHash = pack('H*', $hash);

        return base64_encode($packedHash);
    }

    /**
     * @return string
     */
    public function getTypeValue()
    {
        return $this->typeValue;
    }

    /**
     * @param string $typeValue
     *
     * @return Password
     */
    public function setTypeValue($typeValue)
    {
        $this->typeValue = $typeValue;

        return $this;
    }
}
