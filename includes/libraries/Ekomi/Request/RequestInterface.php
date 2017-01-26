<?php
namespace Ekomi\Request;

/**
 * Interface RequestInterface
 * @package Ekomi\Request
 */
interface RequestInterface{
    /**
     * @return mixed
     */
    public function getName();

    /**
     * @param string $type
     * @return mixed
     */
    public function getQuery($type='CURL');
}