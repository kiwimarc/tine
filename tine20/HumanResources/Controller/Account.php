<?php
/**
 * Account controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Account controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Account extends Tinebase_Controller_Record_Abstract
{
    protected $_contractController = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_Account();
        $this->_modelName = 'HumanResources_Model_Account';
        $this->_purgeRecords = FALSE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
        $this->_contractController = HumanResources_Controller_Contract::getInstance();
    }

    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_Account
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_Account
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    /**
     * @param string|HumanResources_Model_Employee $employeeId
     * @param strig|Date $year default current year
     * @return HumanResources_Model_Account|NULL
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function getByEmployeeYear($employeeId, $year=null)
    {
        $employeeId = $employeeId instanceof HumanResources_Model_Employee ? $employeeId->getId() : $employeeId;
        $year = $year ?: Tinebase_DateTime::now();
        $year = $year instanceof DateTime ? $year->format('Y') : $year;
        
        // find account
        $filter = new HumanResources_Model_AccountFilter([
            ['field' => 'year',         'operator' => 'equals', 'value' => intval($year)],
            ['field' => 'employee_id',  'operator' => 'equals', 'value' => $employeeId]
        ]);

        return $this->search($filter)->getFirstRecord();
    }
    
    /**
     * creates missing accounts for all employees having a valid contract
     * if a year is given, missing accounts for this year will be built, otherwise the current and following year will be used
     *
     * @param integer $year
     * @param HumanResources_Model_Employee
     * @param boolean $useBackend Use Backend instead of this Controller (may called by setup also, skips rigts, creating modlog etc.)
     */
    public function createMissingAccounts($year = NULL, $employee = NULL, $useBackend = FALSE)
    {
        // if no year is given, call myself with this year and just go on with the next year
        if (! $year) {
            $date = new Tinebase_DateTime();
            $year = (int) $date->format('Y');
            $this->createMissingAccounts($year, $employee);
            $date->addYear(1);
            $year = (int) $date->format('Y');
        }
    
        // tine20 should last a hundred years :)
        if ($year < 2006 || $year >= 2106 || ! is_int($year)) {
            throw new Tinebase_Exception_Data('The year must be between 2006 and 2106');
        }
    
        // timezone problem here?
        $validEmployeeIds = array_unique($this->_contractController->getValidContracts([
            'from' => new Tinebase_DateTime($year . '-01-01 00:00:00'),
            'until' => new Tinebase_DateTime($year . '-12-31 23:59:59'),
        ], $employee)->employee_id);
        
        $existingFilter = new HumanResources_Model_AccountFilter(array(
            array('field' => 'year', 'operator' => 'equals', 'value' => $year)
        ));
        $existingFilter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'in', 'value' => $validEmployeeIds)));
        
        $result = $this->search($existingFilter)->employee_id;
        $validEmployeeIds = array_diff($validEmployeeIds, $result);
        $createdAccounts = new Tinebase_Record_RecordSet('HumanResources_Model_Account');
        
        if ($useBackend) {
            $be = new HumanResources_Backend_Account();
            foreach($validEmployeeIds as $id) {
                $createdAccounts->addRecord($be->create(new HumanResources_Model_Account(array('employee_id' => $id, 'year' => $year))));
            }
        } else {
            foreach($validEmployeeIds as $id) {
                $createdAccounts->addRecord($this->create(new HumanResources_Model_Account(array('employee_id' => $id, 'year' => $year))));
            }
        }
        
        return $createdAccounts;
    }
    
    /**
     * resolves all virtual fields for the account
     * 
     * @param HumanResources_Model_Account $account
     * @return array with property => value
     */
    public function resolveVirtualFields(HumanResources_Model_Account $account, $expandRecord=false)
    {
        $accountPeriod = $account->getAccountPeriod();
        
        $account->contracts = $this->_contractController->getValidContracts($accountPeriod, $account->employee_id);
        
        // find out free days (vacation, sickness)
        $freetimeController = HumanResources_Controller_FreeTime::getInstance();
        
        $filter = new HumanResources_Model_FreeTimeFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'account_id', 'operator' => 'equals', 'value' => $account->getId())));
        
        $freeTimes = $freetimeController->search($filter);
        
        $unexcusedSicknessTimes = $freeTimes->filter('type', 'sickness')->filter('status', 'UNEXCUSED');
        $excusedSicknessTimes   = $freeTimes->filter('type', 'sickness')->filter('status', 'EXCUSED');
        
        $freedayController = HumanResources_Controller_FreeDay::getInstance();
        $filter = new HumanResources_Model_FreeDayFilter(array(), 'AND');
        
        $unexcusedSicknessFilter = clone $filter;
        $unexcusedSicknessFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $unexcusedSicknessTimes->id)));
        $unexcusedSicknessDays = $freedayController->search($unexcusedSicknessFilter);

        $excusedSicknessFilter = clone $filter;
        $excusedSicknessFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $excusedSicknessTimes->id)));
        $excusedSicknessDays = $freedayController->search($excusedSicknessFilter);
        
        
        $datesToWorkOn = $this->_contractController->getDatesToWorkOn($account->contracts, $accountPeriod['from'], $accountPeriod['until']);
        $datesToWorkOnReal = $this->_contractController->getDatesToWorkOn($account->contracts, $accountPeriod['from'], $accountPeriod['until'], TRUE);
        
        $data = array_merge(array(
            'excused_sickness'        => $excusedSicknessDays->count(),
            'unexcused_sickness'      => $unexcusedSicknessDays->count(),
            'working_days'            => count($datesToWorkOn['results']),
            'working_hours'           => $datesToWorkOn['hours'],
            'working_days_real'       => count($datesToWorkOnReal['results']),
            'working_hours_real'      => $datesToWorkOnReal['hours']
        ), $this->resolveVacation($account));
        
        if (true === $expandRecord) {
            foreach($data as $key => $value) {
                $account->{$key} = $value;
            }
        }
        return $data;
    }

    /**
     * resolve vacation properties of given account
     * 
     * @param HumanResources_Model_Account $account
     * @return array with property => value
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function resolveVacation($account)
    {
        if (!$account) throw new HumanResources_Exception_NoAccount();
        $accountPeriod = $account->getAccountPeriod();
        $contracts = $account->contracts ?: $this->_contractController->getValidContracts($accountPeriod, $account->employee_id);
        
        // find out vacation days by contract(s) and interval
        $possibleVacationDays = round($this->_contractController->calculateVacationDays($contracts, $accountPeriod['from'], $accountPeriod['until']), 0);
        // find out free days (vacation, sickness)
        $freetimeController = HumanResources_Controller_FreeTime::getInstance();

        $filter = new HumanResources_Model_FreeTimeFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'account_id', 'operator' => 'equals', 'value' => $account->getId())));

        $freeTimes = $freetimeController->search($filter);
        
        $acceptedVacationTimes  = $freeTimes->filter('type', 'vacation')->filter('status', '/(ACCEPTED|REQUESTED|IN-PROCESS)/', true);

        $freedayController = HumanResources_Controller_FreeDay::getInstance();
        $filter = new HumanResources_Model_FreeDayFilter(array(), 'AND');

        $acceptedVacationFilter = clone $filter;
        $acceptedVacationFilter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $acceptedVacationTimes->id)));
        $acceptedVacationDays = $freedayController->search($acceptedVacationFilter);
        
        return [
            'vacation_expiary_date'   => HumanResources_Config::getInstance()->getVacationExpirationDate($account->year),
            'possible_vacation_days'  => intval($possibleVacationDays),
            'remaining_vacation_days' => intval(floor($possibleVacationDays)) - $acceptedVacationDays->count()/* - $rebookedVacationDays->count()*/,
            'taken_vacation_days'     => $acceptedVacationDays->count(),
        ];
    }
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * 
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     * @todo use getMigration()
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    { 
        $_record->employee_id = $_oldRecord->employee_id;
        $_record->year = $_oldRecord->year;
    }
}
