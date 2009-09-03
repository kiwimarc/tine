<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  LDAP
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * class Tinebase_Model_EmailUser
 * 
 * - this class contains all email specific user settings like quota, forwards, ...
 * 
 * @package     Tinebase
 * @subpackage  LDAP
 */
class Tinebase_Model_EmailUser extends Tinebase_Record_Abstract 
{
   
    protected $_identifier = 'emailUID';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * validators / fields
     *
     * @var array
     */
    protected $_validators = array(
        'emailUID'          => array('allowEmpty' => true),
        'emailGID'          => array('allowEmpty' => true),
        'emailMailQuota'    => array('allowEmpty' => true),
        'emailMailSize'     => array('allowEmpty' => true),
        'emailSieveQuota'   => array('allowEmpty' => true),
        'emailSieveSize'    => array('allowEmpty' => true),
        'emailUserId'       => array('allowEmpty' => true),
        'emailLastLogin'    => array('allowEmpty' => true),
        'emailPassword'     => array('allowEmpty' => true),
        'emailForwards'     => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'emailForwardOnly'  => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'emailAliases'      => array('allowEmpty' => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'emailAddress'      => array('allowEmpty' => true),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'emailLastLogin'
    );
} 
