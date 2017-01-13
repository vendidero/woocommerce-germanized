<?php
namespace Ekomi\Request;

/**
 * Class PutClient
 * @package Ekomi\Request
 */
class PutClient extends AbstractRequest{

    private $clientId;
    private $email;
    private $locale;
    private $screenname;
    private $firstname;
    private $lastname;
    private $country;
    private $city;
    private $zip;
    private $gender;
    private $birthday;
    private $metaData;
    /**
     * @var string
     */
    private $update = 'true';

    /**
     * Exclude parameters from SOAP Call
     * SOAP API Does not support update parameter
     * @var array
     */
    protected $soapExcludeItems = array('type','update');

    public function getName(){
        return 'putClient';
    }

    public function getQuery($type='CURL'){
        $query = array(
            'auth' => '',
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'client_id' => $this->getclientId(),
            'email' => $this->getEmail(),
            'locale' => $this->getLocale(),
            'screenname' => $this->getScreenname(),
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'country' => $this->getCountry(),
            'city' => $this->getCity(),
            'zip' => $this->getZip(),
            'gender' => $this->getGender(),
            'birthday' => $this->getBirthday(),
            'metadata' => json_encode($this->getMetaData()),
            'update' => $this->getUpdate(),
            'charset' => $this->getCharset()
        );

        if($type==='SOAP'){
            return $this->unsetItemsInArray($query);
        }

        return $query;
    }

    /**
     * Add Meta Data
     * @param $key
     * @param $value
     * @return PutClient
     */
    public function addMetaData($key,$value){
        $this->metaData[$key] = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     * @return PutClient
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     * @return PutClient
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param mixed $locale
     * @return PutClient
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getScreenname()
    {
        return $this->screenname;
    }

    /**
     * @param mixed $screenname
     * @return PutClient
     */
    public function setScreenname($screenname)
    {
        $this->screenname = $screenname;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param mixed $firstname
     * @return PutClient
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     * @return PutClient
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     * @return PutClient
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     * @return PutClient
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param mixed $zip
     * @return PutClient
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param mixed $gender
     * @return PutClient
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param mixed $birthday
     * @return PutClient
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * @param mixed $metaData
     * @return PutClient
     */
    public function setMetaData($metaData)
    {
        $this->metaData = $metaData;
        return $this;
    }

    /**
     * @return string
     */
    public function getUpdate()
    {
        return $this->update;
    }

    /**
     * @param string $update
     * @return PutClient
     */
    public function setUpdate($update)
    {
        $this->update = $update;
        return $this;
    }

}