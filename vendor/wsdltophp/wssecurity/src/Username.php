<?php

namespace WsdlToPhp\WsSecurity;

class Username extends Element
{
    /**
     * Element name.
     *
     * @var string
     */
    const NAME = 'Username';

    /**
     * Constructor for Username element.
     *
     * @param string $username  the username
     * @param string $namespace the namespace
     */
    public function __construct($username, $namespace = self::NS_WSSE)
    {
        parent::__construct(self::NAME, $namespace, $username);
    }
}
