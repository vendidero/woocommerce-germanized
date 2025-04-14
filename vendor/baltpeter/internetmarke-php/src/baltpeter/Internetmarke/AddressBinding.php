<?php

namespace baltpeter\Internetmarke;

use function DeepCopy\deep_copy;

class AddressBinding extends ApiResult {
    /**
     * @var NamedAddress The sender's address
     */
    protected $sender;
    /**
     * @var NamedAddress The recipient's address
     */
    protected $receiver;

    /**
     * AddressBinding constructor.
     *
     * @param NamedAddress $sender_address
     * @param NamedAddress $receiver_address
     */
    public function __construct($sender_address, $receiver_address) {
        $this->setSender($sender_address);
        $this->setReceiver($receiver_address);
    }

    /**
     * @return NamedAddress
     */
    public function getSender() {
        return $this->sender;
    }

    /**
     * @param NamedAddress $sender
     */
    public function setSender($sender) {
        $this->sender = deep_copy($sender);
    }

    /**
     * @return NamedAddress
     */
    public function getReceiver() {
        return $this->receiver;
    }

    /**
     * @param NamedAddress $receiver
     */
    public function setReceiver($receiver) {
        $this->receiver = deep_copy($receiver);
    }
}
