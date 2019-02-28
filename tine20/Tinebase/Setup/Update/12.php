<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tinebase_Setup_Update_12 extends Setup_Update_Abstract
{
    const RELEASE012_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE012_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_TINEBASE_BEFORE_STRUCT => [
            self::RELEASE012_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ]
        ],
        self::PRIO_TINEBASE_UPDATE        => [
            self::RELEASE012_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        $release11 = new Tinebase_Setup_Update_Release11($this->_backend);
        $release11->update_45();
        $this->addApplicationUpdate('Tinebase', '12.19', self::RELEASE012_UPDATE001);
    }

    public function update002()
    {
        // clear open transactions
        Tinebase_TransactionManager::getInstance()->rollBack();
        try {
            Setup_SchemaTool::updateAllSchema();
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            Setup_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Schema update failed - retrying once ...');
            $this->clearCache();
            sleep(5);
            Setup_SchemaTool::updateAllSchema();
        }

        $this->addApplicationUpdate('Tinebase', '12.20', self::RELEASE012_UPDATE002);
    }
}
