<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Sales_Backend_ContractTest
 */
class Sales_Backend_ContractTest extends \PHPUnit\Framework\TestCase
{
    /**
     * the contract backend
     *
     * @var Sales_Backend_Contract
     */
    protected $_backend;
    
    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
     */
    protected function setUp(): void
{
        $this->_backend = new Sales_Backend_Contract();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     */
    protected function tearDown(): void
{
    }
    
    /**
     * create new contract
     *
     */
    public function testCreateContract()
    {
        $contract = $this->_getContract();
        $created = $this->_backend->create($contract);
        
        $this->assertEquals($created->title, $contract->title);
        $this->assertGreaterThan(0, $created->number);
        $this->assertEquals($created->container_id, Sales_Controller_Contract::getSharedContractsContainer()->getId());
        
        $this->_backend->delete($contract);
        $this->_decreaseNumber();
    }

    /**
     * get contract
     *
     * @return Sales_Model_Contract
     */
    protected function _getContract()
    {
        $contract = new Sales_Model_Contract(array(
            'title'         => 'phpunit contract',
            'description'   => 'blabla'
        ), TRUE);
        
        // add container
        $contract->container_id = Sales_Controller_Contract::getSharedContractsContainer();
        
        // add number
        $numberBackend = new Sales_Backend_Number();
        $number = $numberBackend->getNext('Sales_Model_Contract', Tinebase_Core::getUser()->getId());
        $contract->number = $number->number;

        return $contract;
    }
    
    /**
     * decrease contracts number
     *
     */
    protected function _decreaseNumber()
    {
        $numberBackend = new Sales_Backend_Number();
        $number = $numberBackend->getNext('Sales_Model_Contract', Tinebase_Core::getUser()->getId());
        // reset or delete old number
        if ($number->number == 2) {
            $numberBackend->delete($number);
        } else {
            $number->number -= 2;
            $numberBackend->update($number);
        }
    }
}
