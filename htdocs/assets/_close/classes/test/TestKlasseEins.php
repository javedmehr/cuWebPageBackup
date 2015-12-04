<?php
/*
 * Copyright by Jörg Wrase - www.Computer-Und-Sound.de
 * Date: 28.11.2015
 * Time: 03:28
 * 
 * Created by IntelliJ IDEA
 *
 */

namespace test;

/**
 * Class TestKlasseEins
 *
 * @package test
 */
class TestKlasseEins {

    protected $testValue;

    /**
     * TestKlasseEins constructor.
     */
    public function __construct() {

        $this->testValue = mt_rand(0,1000);

    }


}