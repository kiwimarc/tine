<?php
/**
 * MyPhone controller for Voipmanager Management application
 *
 * @package     Phone
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * MyPhone controller class for Phone application
 * 
 * @package     Phone
 * @subpackage  Controller
 */
class Phone_Controller_MyPhone extends Voipmanager_Controller_Snom_Phone
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_modelName   = 'Phone_Model_MyPhone';
        $this->_backend     = new Voipmanager_Backend_Snom_Phone(NULL, array(
            'modelName' => $this->_modelName
        ));
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Phone_Controller_MyPhone
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Phone_Controller_MyPhone
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * @throws  Voipmanager_Exception_Validation
     *
     * @todo do not overwrite update() -> use inspectBefore/After functions
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = TRUE, $_updateDeleted = false)
    {
        $oldRecord = $this->get($_record->getId());
        
        $rights = $this->_backend->getPhoneRights($_record->getId());
        $currentAccountId = Tinebase_Core::getUser()->getId();
        $hasRight = false;
        
        foreach($rights as $right) {
            if ($right->account_id == $currentAccountId) {
                // if  user has the right to dial and read the phone, he or she may edit the lines
                if ($right->dial_right && $right->read_right) {
                    $hasRight = true;
                }
            }
        }
        
        if (! $hasRight) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to edit this phone!');
        }
        
        // user is not allowed to add or remove lines
        $diff = $oldRecord->lines->diff($_record->lines);
        if (count($diff->added) > 0 || count($diff->removed) > 0) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to add or remove lines of this phone!');
        }

        // user may just edit the lines and settings of the phone
        $oldRecord->lines    = $_record->lines;
        $oldRecord->settings = $_record->settings;
        
        return parent::update($oldRecord, $_duplicateCheck, $_updateDeleted);
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        throw new Tinebase_Exception_AccessDenied('You are not alowed to create a phone. Use Voipmanager instead!');
    }
}
