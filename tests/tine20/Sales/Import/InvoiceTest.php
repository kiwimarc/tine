<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Christian Feitl<c.feitl@metawas.de>
 */

/**
 * Test class for Sales
 */
class Sales_Import_InvoiceTest extends TestCase
{
    /**
     * @var Tinebase_Model_Container
     */
    protected $_importContainer = null;

    protected function tearDown()
    {
        parent::tearDown();
        self::clear('Sales', 'Invoice');
    }

    public function testImportDemoData()
    {
        if (!extension_loaded('yaml')) {
            $this->markTestSkipped('Yaml are not install');
        }
        self::clear('Sales', 'Invoice');
        $this->_importContainer = $this->_getTestContainer('Sales', 'Sales_Model_Invoice');
        $importer = new Tinebase_Setup_DemoData_ImportSet('Sales', [
            'container_id' => $this->_importContainer->getId(),
            'files' => array('Sales.yml')
        ]);

        $importer->importDemodata();
        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel('Sales_Model_Invoice', [
            ['field' => 'creation_time', 'operator' => 'within', 'value' => 'dayThis']
        ]);
        $result = Sales_Controller_Invoice::getInstance()->search($filter);
        self::assertEquals(1, count($result));
    }
}