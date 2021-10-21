<?php

namespace WsdlToPhp\WsSecurity;

class WsSecurity
{
    /**
     * @var Security
     */
    protected $security;

    /**
     * @param string $username
     * @param string $password
     * @param bool   $passwordDigest
     * @param int    $addCreated
     * @param int    $addExpires
     * @param bool   $mustunderstand
     * @param string $actor
     * @param string $usernameId
     * @param bool   $addNonce
     * @param string $envelopeNamespace
     */
    protected function __construct(
        $username,
        $password,
        $passwordDigest = false,
        $addCreated = 0,
        $addExpires = 0,
        $mustunderstand = false,
        $actor = null,
        $usernameId = null,
        $addNonce = true,
        $envelopeNamespace = Security::ENV_NAMESPACE
    ) {
        $this
            ->initSecurity($mustunderstand, $actor, $envelopeNamespace)
            ->setUsernameToken($username, $usernameId)
            ->setPassword($password, $passwordDigest, $addCreated)
            ->setNonce($addNonce)
            ->setCreated($addCreated)
            ->setTimestamp($addCreated, $addExpires)
        ;
    }

    /**
     * @return Security
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * Create the SoapHeader object to send as SoapHeader in the SOAP request.
     *
     * @param string $username
     * @param string $password
     * @param bool   $passwordDigest
     * @param int    $addCreated
     * @param int    $addExpires
     * @param bool   $returnSoapHeader
     * @param bool   $mustunderstand
     * @param string $actor
     * @param string $usernameId
     * @param bool   $addNonce
     * @param string $envelopeNamespace
     *
     * @return \SoapHeader|\SoapVar
     */
    public static function createWsSecuritySoapHeader(
        $username,
        $password,
        $passwordDigest = false,
        $addCreated = 0,
        $addExpires = 0,
        $returnSoapHeader = true,
        $mustunderstand = false,
        $actor = null,
        $usernameId = null,
        $addNonce = true,
        $envelopeNamespace = Security::ENV_NAMESPACE
    ) {
        $self = new WsSecurity($username, $password, $passwordDigest, $addCreated, $addExpires, $mustunderstand, $actor, $usernameId, $addNonce, $envelopeNamespace);
        if ($returnSoapHeader) {
            if (!empty($actor)) {
                return new \SoapHeader(Element::NS_WSSE, 'Security', new \SoapVar($self->getSecurity()->toSend(), XSD_ANYXML), $mustunderstand, $actor);
            }

            return new \SoapHeader(Element::NS_WSSE, 'Security', new \SoapVar($self->getSecurity()->toSend(), XSD_ANYXML), $mustunderstand);
        }

        return new \SoapVar($self->getSecurity()->toSend(), XSD_ANYXML);
    }

    /**
     * @param bool   $mustunderstand
     * @param string $actor
     * @param string $envelopeNamespace
     *
     * @return WsSecurity
     */
    protected function initSecurity($mustunderstand = false, $actor = null, $envelopeNamespace = Security::ENV_NAMESPACE)
    {
        $this->security = new Security($mustunderstand, $actor, Security::NS_WSSE, $envelopeNamespace);

        return $this;
    }

    /**
     * @param string $username
     * @param string $usernameId
     *
     * @return WsSecurity
     */
    protected function setUsernameToken($username, $usernameId = null)
    {
        $usernameToken = new UsernameToken($usernameId);
        $usernameToken->setUsername(new Username($username));
        $this->security->setUsernameToken($usernameToken);

        return $this;
    }

    /**
     * @param string $password
     * @param bool   $passwordDigest
     * @param int    $addCreated
     *
     * @return WsSecurity
     */
    protected function setPassword($password, $passwordDigest = false, $addCreated = 0)
    {
        $this->getUsernameToken()->setPassword(new Password($password, $passwordDigest ? Password::TYPE_PASSWORD_DIGEST : Password::TYPE_PASSWORD_TEXT, is_bool($addCreated) ? 0 : ($addCreated > 0 ? $addCreated : 0)));

        return $this;
    }

    /**
     * @param bool $addNonce
     *
     * @return WsSecurity
     */
    protected function setNonce($addNonce)
    {
        if ($addNonce) {
            $nonceValue = $this->getPassword()->getNonceValue();
            if (!empty($nonceValue)) {
                $this->getUsernameToken()->setNonce(new Nonce($nonceValue));
            }
        }

        return $this;
    }

    /**
     * @param int $addCreated
     *
     * @return WsSecurity
     */
    protected function setCreated($addCreated)
    {
        $passwordDigest = $this->getPassword()->getTypeValue();
        $timestampValue = $this->getPassword()->getTimestampValue();
        if (($addCreated || Password::TYPE_PASSWORD_DIGEST === $passwordDigest) && $timestampValue > 0) {
            $this->getUsernameToken()->setCreated(new Created($timestampValue));
        }

        return $this;
    }

    /**
     * @param int $addCreated
     * @param int $addExpires
     *
     * @return WsSecurity
     */
    protected function setTimestamp($addCreated = 0, $addExpires = 0)
    {
        $timestampValue = $this->getPassword()->getTimestampValue();
        if ($addCreated && $addExpires && $timestampValue) {
            $timestamp = new Timestamp();
            $timestamp->setCreated(new Created($timestampValue));
            $timestamp->setExpires(new Expires($timestampValue, $addExpires));
            $this->security->setTimestamp($timestamp);
        }

        return $this;
    }

    /**
     * @return UsernameToken
     */
    protected function getUsernameToken()
    {
        return $this->security->getUsernameToken();
    }

    /**
     * @return Password
     */
    protected function getPassword()
    {
        return $this->getUsernameToken()->getPassword();
    }
}
