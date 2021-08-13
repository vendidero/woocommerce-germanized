<?php

namespace WsdlToPhp\WsSecurity;

class Security extends Element
{
    /**
     * Element name.
     *
     * @var string
     */
    const NAME = 'Security';
    /**
     * Element attribute mustunderstand name.
     *
     * @var string
     */
    const ATTRIBUTE_MUST_UNDERSTAND = ':mustunderstand';
    /**
     * Element attribute mustunderstand name.
     *
     * @var string
     */
    const ATTRIBUTE_ACTOR = ':actor';
    /**
     * Envelop namespace.
     *
     * @var string
     */
    const ENV_NAMESPACE = 'SOAP-ENV';
    /**
     * UsernameToken element.
     *
     * @var UsernameToken
     */
    protected $usernameToken;
    /**
     * Timestamp element.
     *
     * @var Timestamp
     */
    protected $timestamp;

    /**
     * Constructor for Nonce element.
     *
     * @param bool   $mustunderstand
     * @param string $actor
     * @param string $envelopeNamespace
     * @param string $namespace         the namespace
     */
    public function __construct($mustunderstand = false, $actor = null, $namespace = self::NS_WSSE, $envelopeNamespace = self::ENV_NAMESPACE)
    {
        parent::__construct(self::NAME, $namespace);
        // Sets attributes
        if (true === $mustunderstand) {
            $this->setAttribute($envelopeNamespace . self::ATTRIBUTE_MUST_UNDERSTAND, $mustunderstand);
        }
        if (!empty($actor)) {
            $this->setAttribute($envelopeNamespace . self::ATTRIBUTE_ACTOR, $actor);
        }
    }

    /**
     * Overrides methods in order to set the values.
     *
     * @param bool $asDomElement returns elements as a DOMElement or as a string
     *
     * @return \DOMElement|string
     */
    protected function __toSend($asDomElement = false)
    {
        $this->setValue([
            $this->getUsernameToken(),
            $this->getTimestamp(),
        ]);

        return parent::__toSend($asDomElement);
    }

    /**
     * @return UsernameToken
     */
    public function getUsernameToken()
    {
        return $this->usernameToken;
    }

    /**
     * @param UsernameToken $usernameToken
     *
     * @return Security
     */
    public function setUsernameToken(UsernameToken $usernameToken)
    {
        $this->usernameToken = $usernameToken;

        return $this;
    }

    /**
     * @return Timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param Timestamp $timestamp
     *
     * @return Security
     */
    public function setTimestamp(Timestamp $timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}
