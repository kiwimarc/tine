<?php

/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2022.11 (ONLY!)
 */
class Admin_Setup_Update_15 extends Setup_Update_Abstract
{
    const RELEASE015_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE015_UPDATE001 = __CLASS__ . '::update001';
    const RELEASE015_UPDATE002 = __CLASS__ . '::update002';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE015_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
            self::RELEASE015_UPDATE002          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update002',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE015_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate('Admin', '15.0', self::RELEASE015_UPDATE000);
    }

    public function update001()
    {
        $this->addApplicationUpdate('Admin', '15.1', self::RELEASE015_UPDATE001);
    }

    public function update002()
    {
        $this->dropTable('admin_ovpnapiaccount', Admin_Config::APP_NAME);
        $this->dropTable('admin_ovpnapirealm', Admin_Config::APP_NAME);
        $this->addApplicationUpdate('Admin', '15.2', self::RELEASE015_UPDATE002);
    }
}
