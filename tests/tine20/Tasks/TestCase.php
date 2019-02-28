<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * abstract class for tasks tests
 * 
 * @package     Tasks
 */
abstract class Tasks_TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * transaction id if test is wrapped in an transaction
     */
    protected $_transactionId = NULL;
    
    /**
     * set up tests
     *
     */
    public function setUp()
    {
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }
    
    /**
     * tear down tests
     *
     */
    public function tearDown()
    {
        if ($this->_transactionId) {
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
    }
    
    /**
     * 
     * @return Tasks_Model_Task
     */
    public static function getTestRecord()
    {
        $task = new Tasks_Model_Task(array(
            // tine record fields
            'container_id'         => NULL,
            'created_by'           => 6,
            'creation_time'        => Tinebase_DateTime::now(),
            'is_deleted'           => 0,
            'deleted_time'         => NULL,
            'deleted_by'           => NULL,
            // task only fields
            'percent'              => 70,
            'completed'            => NULL,
            'due'                  => Tinebase_DateTime::now()->addMonth(1),
            // ical common fields
            //'class_id'             => 2,
            'description'          => str_pad('', 1000, '.'),
            'geo'                  => 0.2345,
            'location'             => 'here and there',
            'organizer'            => 4,
            'priority'             => Tasks_Model_Priority::NORMAL,
            'status'               => 'NEEDS_ACTION',
            'summary'              => 'our first test task',
            'url'                  => 'http://www.testtask.com',
        ),true, false);
        
        $task->setConvertDates(true);
        
        return $task;
    }
}
