<?php

/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Zend Ldap Convert
 */
class Zend_Ldap_ConvertTest extends TestCase
{
    protected function _getUit()
    {
        if ($this->_uit === null) {
            $this->_uit = new Zend_Ldap_Converter();
        }

        return $this->_uit;
    }

    /**
     * @throws Exception
     */
    public function testHex32ToAsc()
    {
        $result = $this->_getUit()->hex32ToAsc('\\4D');
        $this->assertTrue('M' === $result, print_r($result, true) . ' is no string value "M"');
    }
}
