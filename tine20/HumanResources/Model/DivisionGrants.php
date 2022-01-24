<?php
/**
 * class to handle grants
 * 
 * @package     HumanResources
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * defines Division grants
 * 
 * @package     HumanResources
 * @subpackage  Record
 *  */
class HumanResources_Model_DivisionGrants extends Tinebase_Model_Grants
{
    public const MODEL_NAME_PART    = 'DivisionGrants';

    /**
     * read only access to own: simplyfied employee, hr account, wtr, freetime
     */
    public const READ_OWN_DATA = 'readOwnDataGrant';

    /**
     * read only access to all employee / hr account
     */
    public const READ_EMPLOYEE_DATA = 'readEmployeeDataGrant';

    /**
     * write access to all employee / hr account
     */
    public const UPDATE_EMPLOYEE_DATA = 'updateEmployeeDataGrant';

    /**
     * read only access to all hr account / wtr / freetime
     */
    public const READ_TIME_DATA = 'readTimeDataGrant';

    /**
     * write access to all hr account / wtr / freetime
     */
    public const UPDATE_TIME_DATA = 'updateTimeDataGrant';

    /**
     * create change request for own user
     */
    public const CREATE_OWN_CHANGE_REQUEST = 'createOwnChangeRequestGrant';

    /**
     * read only access to all change request
     */
    public const READ_CHANGE_REQUEST = 'readChangeRequestGrant';

    /**
     * create change request for all user
     */
    public const CREATE_CHANGE_REQUEST = 'createChangeRequestGrant';

    /**
     * write access to all change request
     */
    public const UPDATE_CHANGE_REQUEST = 'updateChangeRequestGrant';

    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = HumanResources_Config::APP_NAME;
    
    /**
     * get all possible grants
     *
     * @return  array   all container grants
     */
    public static function getAllGrants()
    {
        return [
            self::READ_OWN_DATA,
            self::READ_EMPLOYEE_DATA,
            self::UPDATE_EMPLOYEE_DATA,
            self::READ_TIME_DATA,
            self::UPDATE_TIME_DATA,
            self::CREATE_OWN_CHANGE_REQUEST,
            self::READ_CHANGE_REQUEST,
            self::CREATE_CHANGE_REQUEST,
            self::UPDATE_CHANGE_REQUEST,
            self::GRANT_ADMIN,
        ];
    }

    protected static $_modelConfiguration = null;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static function getAllGrantsMC(): array
    {
        return [
            self::READ_OWN_DATA    => [
                self::LABEL         => 'Read own data', // _('Read own data')
                self::DESCRIPTION   => 'The grant to read own basic employee data, accounts, free times and working time reports.', // _('The grant to read own basic employee data, accounts, free times and working time reports.')
            ],
            self::READ_EMPLOYEE_DATA => [
                self::LABEL         => 'Read employee',  // _('Read employee')
                self::DESCRIPTION   => 'The grant to read full employees data, accounts, contracts and free times for all employees in this division.',  // _('The grant to read full employees data, accounts, contracts and free times for all employees in this division.')
            ],
            self::UPDATE_EMPLOYEE_DATA => [
                self::LABEL         => 'Update employee', // _('Update employee')
                self::DESCRIPTION   => 'The grant to update full employees data, accounts, contracts and free times for all employees in this division.', // _('The grant to update full employees data, accounts, contracts and free times for all employees in this division.')
            ],
            self::READ_TIME_DATA => [
                self::LABEL         => 'Read time data', // _('Read time data')
                self::DESCRIPTION   => 'The grant to read working time accounts for all employees in this division.', // _('The grant to read working time accounts for all employees in this division.')
            ],
            self::UPDATE_TIME_DATA => [
                self::LABEL         => 'Update time data', // _('Update time data')
                self::DESCRIPTION   => 'The grant to update working time accounts for all employees in this division.', // _('The grant to update working time accounts for all employees in this division.')
            ],
            self::CREATE_OWN_CHANGE_REQUEST => [
                self::LABEL         => 'Create own change requests', // _('Create own change requests')
                self::DESCRIPTION   => 'The grant to create own free times and working time reports change requests.', // _('The grant to create own free times and working time reports change requests.')
            ],
            self::READ_CHANGE_REQUEST => [
                self::LABEL         => 'Read change requests', // _('Read change requests')
                self::DESCRIPTION   => 'The grant to read free times and working time reports change requests for all employees in this division.', // _('The grant to read free times and working time reports change requests for all employees in this division.')
            ],
            self::CREATE_CHANGE_REQUEST => [
                self::LABEL         => 'Create change requests', // _('Create change requests')
                self::DESCRIPTION   => 'The grant to create free times and working time reports change requests for all employees in this division.', // _('The grant to create free times and working time reports change requests for all employees in this division.')
            ],
            self::UPDATE_CHANGE_REQUEST => [
                self::LABEL         => 'Update change requests', // _('Update change requests')
                self::DESCRIPTION   => 'The grant to update free times and working time reports change requests for all employees in this division.', // _('The grant to update free times and working time reports change requests for all employees in this division.')
            ],
            self::GRANT_ADMIN => [
                self::LABEL         => 'Admin', // _('Admin')
                self::DESCRIPTION   => 'The grant to administrate this division (implies all other grants and the grant to set grants as well).', // _('The grant to administrate this division (implies all other grants and the grant to set grants as well).')
            ],
        ];
    }
}
