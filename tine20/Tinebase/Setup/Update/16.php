<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 * this is 2023.11 (ONLY!)
 */
class Tinebase_Setup_Update_16 extends Setup_Update_Abstract
{
    const RELEASE016_UPDATE000 = __CLASS__ . '::update000';
    const RELEASE016_UPDATE001 = __CLASS__ . '::update001';

    static protected $_allUpdates = [
        self::PRIO_NORMAL_APP_STRUCTURE     => [
            self::RELEASE016_UPDATE001          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update001',
            ],
        ],
        self::PRIO_NORMAL_APP_UPDATE        => [
            self::RELEASE016_UPDATE000          => [
                self::CLASS_CONST                   => self::class,
                self::FUNCTION_CONST                => 'update000',
            ],
        ],
    ];

    public function update000()
    {
        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.0', self::RELEASE016_UPDATE000);
    }

    public function update001()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();

        $this->_backend->dropForeignKey('customfield_config', 'config_customfields::application_id--applications::id');
        $this->_backend->dropIndex('customfield_config', 'application_id-name');
        $this->_backend->addIndex('customfield_config', new Setup_Backend_Schema_Index_Xml(
            '<index>
                    <name>application_id-name</name>
                    <unique>true</unique>
                    <field>
                        <name>application_id</name>
                    </field>
                    <field>
                        <name>model</name>
                    </field>
                    <field>
                        <name>name</name>
                    </field>
                </index>'));
        $this->_backend->addForeignKey('customfield_config', new Setup_Backend_Schema_Index_Xml(
            '<index>
                    <name>config_customfields::application_id--applications::id</name>
                    <field>
                        <name>application_id</name>
                    </field>
                    <foreign>true</foreign>
                    <reference>
                        <table>applications</table>
                        <field>id</field>
                        <ondelete>CASCADE</ondelete>
                    </reference>
                </index>'));

        if ($this->getTableVersion('customfield_config') < 7) {
            $this->setTableVersion('customfield_config', 7);
        }

        $this->addApplicationUpdate(Tinebase_Config::APP_NAME, '16.1', self::RELEASE016_UPDATE001);
    }
}
