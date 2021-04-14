<?php

namespace WsdlToPhp\WsSecurity;

class Created extends Element
{
    /**
     * Element name.
     *
     * @var string
     */
    const NAME = 'Created';

    /**
     * Constructor for Created element.
     *
     * @param int    $_timestamp the timestamp value
     * @param string $_namespace the namespace
     */
    public function __construct($_timestamp, $_namespace = self::NS_WSSU)
    {
        $this->setTimestampValue($_timestamp);
        parent::__construct(self::NAME, $_namespace, $this->getTimestampValue(true));
    }
}
