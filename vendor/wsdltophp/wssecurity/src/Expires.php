<?php

namespace WsdlToPhp\WsSecurity;

class Expires extends Element
{
    /**
     * Element name.
     *
     * @var string
     */
    const NAME = 'Expires';

    /**
     * Constructor for Expires element.
     *
     * @param int    $timestamp the timestamp value
     * @param int    $expiresIn the expires in time
     * @param string $namespace the namespace
     */
    public function __construct($timestamp, $expiresIn = 3600, $namespace = self::NS_WSSU)
    {
        $this->setTimestampValue($timestamp + $expiresIn);
        parent::__construct(self::NAME, $namespace, $this->getTimestampValue(true));
    }
}
