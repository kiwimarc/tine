<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  User
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * interface for user ldap plugins
 * 
 * @package Tinebase
 * @subpackage User
 */
interface Tinebase_User_Plugin_SqlInterface extends Tinebase_User_Plugin_Interface
{
    /**
     * inspect data used to create user
     *
     * @param Tinebase_Model_FullUser  $_addedUser
     * @param Tinebase_Model_FullUser  $_newUserProperties
     */
    public function inspectAddUser(Tinebase_Model_FullUser $_addedUser, Tinebase_Model_FullUser $_newUserProperties);
    
    /**
     * inspect get user by property
     * 
     * @param Tinebase_Model_User  $_user  the user object
     */
    public function inspectGetUserByProperty(Tinebase_Model_User $_user);
    
    /**
     * inspect data used to update user
     *
     * @param Tinebase_Model_FullUser  $_updatedUser
     * @param Tinebase_Model_FullUser  $_newUserProperties
     */
    public function inspectUpdateUser(Tinebase_Model_FullUser $_updatedUser, Tinebase_Model_FullUser $_newUserProperties);

    /**
     * update/set email user password
     *
     * @param string $_userId
     * @param string $_password
     * @param bool $_encrypt
     * @param bool $_mustChange
     * @param array $_additionalData
     * @return void
     */
    public function inspectSetPassword($_userId, string $_password, bool $_encrypt = true, bool $_mustChange = false, array &$_additionalData = []);

    /**
     * delete user by id
     *
     * @param   Tinebase_Model_FullUser $_user
     */
    public function inspectDeleteUser(Tinebase_Model_FullUser $_user);
}  
