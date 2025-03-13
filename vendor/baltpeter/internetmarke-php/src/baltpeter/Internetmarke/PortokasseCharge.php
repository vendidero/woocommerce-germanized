<?php

namespace baltpeter\Internetmarke;

/**
 * Helper class to create form
 * for loading the portokasse
 */
class PortokasseCharge
{
  /**
   * @var string Generated during authentification in service class
   */
  protected $user_token;
  /**
   * @var datetime in format dmY-His. The form is only valid for the next 4 minutes
   */
  protected $time;
  /**
   * @var string url where user is redirected after successfully loading the portokasse
   */
  protected $success_url;
  /**
   * @var string url where user is redirected after failing to load the portokasse
   */
  protected $cancel_url;
  /**
   * @var integer number of eurocents of the balance of the portokasse after payment.
   *  Example: When user has an actual balance of 400 eurocents on his portokasse
   *  and he submits this form with $balance=1500 he will be asked
   *  to pay 1100. However, he has to pay at least 1000 eurocents regardless of his current balance.
   */
  protected $balance;
  /**
   * @var ParterInformation
   */
  protected $partner_information;

  /**
   * [__construct description]
   * @param ParterInformation  $parter_information
   * @param string  $user_token   Unique user ID
   * @param string  $success_url redirect url after success
   * @param string  $cancel_url  redirect url after failure
   * @param integer $balance balance in eurocents of the portokasse after payment
   */
  public function __construct(PartnerInformation $partner_information, $user_token, $success_url, $cancel_url, $balance = 1000)
  {
    $this->user_token          = $user_token;
    $this->time                = date('dmY-His');
    $this->success_url         = $success_url;
    $this->cancel_url          = $cancel_url;
    $this->balance             = $balance;
    $this->partner_information = $partner_information;
  }

  /**
   *
   * @return string
   */
  public function getSignature()
  {
    return substr(md5($this->partner_information->getPartnerId() . '::' . $this->time . '::' . $this->success_url . '::' . $this->cancel_url . '::' . $this->user_token . '::' . $this->balance . '::' . $this->partner_information->getSchlusselDpwnMeinmarktplatz()), 0, 8);
  }

  /**
   *
   * @return string
   */
  public function getUserToken()
  {
    return $this->user_token;
  }

  public function getRequestTimestamp() {
    return $this->time;
  }

  /**
   *
   * @return string
   */
  public function getSuccessUrl()
  {
    return $this->success_url;
  }

  /**
   *
   * @return string
   */
  public function getCancelUrl()
  {
    return $this->cancel_url;
  }

  /**
   *
   * @return integer
   */
  public function getBalance()
  {
    return $this->balance;
  }


}
