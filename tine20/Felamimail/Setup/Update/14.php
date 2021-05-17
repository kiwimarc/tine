<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Felamimail_Setup_Update_14 extends Setup_Update_Abstract
{
    const RELEASE014_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_UPDATE   => [
            self::RELEASE014_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
    ];

    public function update001()
    {
        // remove obsolete Felamimail_Model_Message containers that have been created by accident (see \ActiveSync_Frontend_Abstract::createFolder)
        $this->_db->query('DELETE FROM ' . SQL_TABLE_PREFIX . 'container WHERE model = "Felamimail_Model_Message"');
        $this->addApplicationUpdate('Felamimail', '14.1', self::RELEASE014_UPDATE001);
    }
}
