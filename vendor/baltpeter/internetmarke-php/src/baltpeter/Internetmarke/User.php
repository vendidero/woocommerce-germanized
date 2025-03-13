<?php

namespace baltpeter\Internetmarke;

class User extends ApiResult {
    /**
     * @var string Unique user ID. Used for those web services that require user authentication. The userToken is only valid for one day.
     */
    protected $userToken;
    /**
     * @var int Balance of the postage account in eurocents
     */
    protected $walletBalance;
    /**
     * @var bool Has this user already agreed to the current GT&C and DP DHL data protection provisions?
     */
    protected $showTermsAndConditions;
    /**
     * @var string Optional information text
     */
    protected $info_message;

    /**
     * User constructor.
     *
     * @param string $user_token Unique user ID
     * @param int $wallet_balance Balance of the account
     * @param bool $show_terms_and_conditions Has this user agreed to the T&C?
     * @param string $info_message Optional information text
     */
    public function __construct($user_token, $wallet_balance, $show_terms_and_conditions, $info_message) {
        $this->setUserToken($user_token);
        $this->setWalletBalance($wallet_balance);
        $this->setShowTermsAndConditions($show_terms_and_conditions);
        $this->setInfoMessage($info_message);
    }

    /**
     * @return string Unique user ID
     */
    public function getUserToken() {
        return $this->userToken;
    }

    /**
     * @param string $userToken Unique user ID
     */
    public function setUserToken($userToken) {
        $this->userToken = $userToken;
    }

    /**
     * @return int Balance of the account
     */
    public function getWalletBalance() {
        return $this->walletBalance;
    }

    /**
     * @param int $walletBalance Balance of the account
     */
    public function setWalletBalance($walletBalance) {
        $this->walletBalance = $walletBalance;
    }

    /**
     * @return boolean Has this user agreed to the T&C?
     */
    public function isShowTermsAndConditions() {
        return $this->showTermsAndConditions;
    }

    /**
     * @param boolean $show_terms_and_conditions Has this user agreed to the T&C?
     */
    public function setShowTermsAndConditions($show_terms_and_conditions) {
        $this->showTermsAndConditions = $show_terms_and_conditions;
    }

    /**
     * @return string Optional information text
     */
    public function getInfoMessage() {
        return $this->info_message;
    }

    /**
     * @param string $info_message Optional information text
     */
    public function setInfoMessage($info_message) {
        $this->info_message = $info_message;
    }
}
