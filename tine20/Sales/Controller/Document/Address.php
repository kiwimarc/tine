<?php declare(strict_types=1);

/**
 * Address controller for Sale Documents
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Address controller for Sale Documents
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Document_Address extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_Address::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_Address::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_Address::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Sales_Model_SubProductMapping $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {

        parent::_inspectBeforeCreate($_record);
    }

    /**
     * @param Sales_Model_SubProductMapping $_record
     * @param Sales_Model_SubProductMapping $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {

        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }
}
