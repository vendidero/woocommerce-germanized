<?php

namespace baltpeter\Internetmarke;

class PartnerInformation {
    /**
     * @var string The partner's ID as supplied by DPDHL
     */
    protected $partnerId;
    /**
     * @var int The secret to be used for the signature (usually 1)
     */
    protected $keyPhase;
    /**
     * @var string A secret (and static) key supplied to the partner by DPDHL
     */
    protected $schlusselDpwnMeinmarktplatz;

    /**
     * PartnerInformation constructor.
     *
     * @param string $partner_id The partner's ID as supplied by DPDHL
     * @param int $key_phase The secret to be used for the signature (usually 1)
     * @param string $schlussel_dpwn_meinmarktplatz A secret (and static) key supplied to the partner by DPDHL
     */
    public function __construct($partner_id, $key_phase, $schlussel_dpwn_meinmarktplatz) {
        $this->setPartnerId($partner_id);
        $this->setKeyPhase($key_phase);
        $this->setSchlusselDpwnMeinmarktplatz($schlussel_dpwn_meinmarktplatz);
    }

    /**
     * @return string The partner's ID as supplied by DPDHL
     */
    public function getPartnerId() {
        return $this->partnerId;
    }

    /**
     * @param string $partnerId The partner's ID as supplied by DPDHL
     */
    public function setPartnerId($partnerId) {
        $this->partnerId = $partnerId;
    }

    /**
     * @return int The secret to be used for the signature (usually 1)
     */
    public function getKeyPhase() {
        return $this->keyPhase;
    }

    /**
     * @param int $keyPhase The secret to be used for the signature (usually 1)
     */
    public function setKeyPhase($keyPhase) {
        $this->keyPhase = $keyPhase;
    }

    /**
     * @return string A secret (and static) key supplied to the partner by DPDHL
     */
    public function getSchlusselDpwnMeinmarktplatz() {
        return $this->schlusselDpwnMeinmarktplatz;
    }

    /**
     * @param string $schlusselDpwnMeinmarktplatz A secret (and static) key supplied to the partner by DPDHL
     */
    public function setSchlusselDpwnMeinmarktplatz($schlusselDpwnMeinmarktplatz) {
        $this->schlusselDpwnMeinmarktplatz = $schlusselDpwnMeinmarktplatz;
    }

    /**
     * @return string The signature to be appended to the request header
     */
    protected function calculateSignature() {
        return substr(md5($this->partnerId . '::' . date('dmY-His') . '::' . $this->keyPhase . '::' . $this->schlusselDpwnMeinmarktplatz), 0, 8);
    }

    /**
     * @return array An array of SOAP headers to authenticate the request with the Internetmarke server. Valid for four minutes from `REQUEST_TIMESTAMP`
     */
    public function soapHeaderArray() {
        date_default_timezone_set('Europe/Berlin'); // The DPAG server requires a date from the German timezone
        return array(
            new \SoapHeader('https://internetmarke.deutschepost.de', 'PARTNER_ID', $this->partnerId),
            new \SoapHeader('https://internetmarke.deutschepost.de', 'REQUEST_TIMESTAMP', date('dmY-His')),
            new \SoapHeader('https://internetmarke.deutschepost.de', 'KEY_PHASE', $this->keyPhase),
            new \SoapHeader('https://internetmarke.deutschepost.de', 'PARTNER_SIGNATURE', $this->calculateSignature())
        );
    }
}
