<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de
 * 
 */

/**
 * class Tinebase_Model_Import
 * 
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property string id
 * @property string timestamp
 * @property string user_id
 * @property string model
 * @property string application_id
 * @property string synctoken
 * @property string interval
 * @property string container_id
 * @property string sourcetype
 * @property string options
 * @property string source
 * @property int    failcount
 * @property string lastfail
 */
class Tinebase_Model_Import extends Tinebase_Record_Abstract 
{
    /**
     * identifier field name
     *
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * Type is uploaded file
     */
    const SOURCETYPE_UPLOAD = 'upload';
    
    /**
     * Source is at remote
     *  - http / https?
     */
    const SOURCETYPE_REMOTE = 'remote';
    
    /**
     * Source is at the local filesystem
     */
    const SOURCETYPE_LOCAL = 'local';
    
    /**
     * Sync daily
     */
    const INTERVAL_DAILY = 'daily';
    
    /**
     * Sync minutely
     */
    const INTERVAL_WEEKLY = 'weekly';
    
    /**
     * Sync hourly
     */
    const INTERVAL_HOURLY = 'hourly';
    
    /**
     * Sync once
     */
    const INTERVAL_ONCE = 'once';
    
    /**
     * record validators
     * 
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'timestamp'          => array('allowEmpty' => true),
        'user_id'            => array('presence' => 'required'),
        'model'              => array('presence' => 'required'),
        'application_id'     => array('presence' => 'required'),
        'synctoken'          => array('allowEmpty' => true),
        'interval'           => array(
            Zend_Filter_Input::ALLOW_EMPTY => false,
            array('InArray', array(
                Tinebase_Model_Import::INTERVAL_ONCE,
                Tinebase_Model_Import::INTERVAL_DAILY,
                Tinebase_Model_Import::INTERVAL_HOURLY,
                Tinebase_Model_Import::INTERVAL_WEEKLY
            )),
            Zend_Filter_Input::DEFAULT_VALUE => Tinebase_Model_Import::INTERVAL_ONCE,
        ),
        // NOTE: if container_id is also in options, options should win
        'container_id'       => array('presence' => 'required'),
        'sourcetype'         => array('presence' => 'required'),
        'options'            => array('allowEmpty' => true),
        'source'             => array('allowEmpty' => true),
        'failcount'          => array('allowEmpty' => true, 'default' => 0),
        'lastfail'           => array('allowEmpty' => true),
        'created_by'         => array('allowEmpty' => true),
        'creation_time'      => array('allowEmpty' => true),
        'last_modified_by'   => array('allowEmpty' => true),
        'last_modified_time' => array('allowEmpty' => true),
        'is_deleted'         => array('allowEmpty' => true),
        'deleted_time'       => array('allowEmpty' => true),
        'deleted_by'         => array('allowEmpty' => true),
        'seq'                => array('allowEmpty' => true),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'timestamp'
    );

    /**
     * sets an option
     * 
     * @param string|array $_key
     * @param scalar|array of scalar $_value
     */
    public function setOption($_key, $_value = null)
    {
        $options = $this->options ? Zend_Json::decode($this->options) : array();
        
        $_key = is_array($_key) ?: array($_key => $_value);
        foreach ($_key as $key => $value) {
            $options[$key] = $value;
        }
        
        $this->options = Zend_Json::encode($options);
    }
    
    /**
     * gets an option
     * 
     * @param  string $_key
     * @return scalar|array of scalar
     */
    public function getOption($_key)
    {
        $options = $this->options ? Zend_Json::decode($this->options) : array();
        return isset($options[$_key]) ? $options[$_key] : NULL;
    }

    /**
     * gets an option
     *
     * @param  string $_key
     * @return scalar|array of scalar
     */
    public function deleteOption($_key)
    {
        $options = $this->options ? Zend_Json::decode($this->options) : array();
        unset($options[$_key]);

        $this->options = Zend_Json::encode($options);
    }

    /**
     * sets the record related properties from user generated input.
     *
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromArray(array &$_data)
    {
        if (isset($_data['options']) && is_array($_data['options'])) {
            $_data['options'] = Zend_Json::encode($_data['options']);
        }

        parent::setFromArray($_data);
        $timestamp = Tinebase_DateTime::now();
        
        // set timestamp according to interval, if it is not set
        if (! isset($_data['timestamp'])) {
            switch (isset($_data['interval']) ? $_data['interval'] : null) {
                case Tinebase_Model_Import::INTERVAL_DAILY:
                    $timestamp->subDay(1)->subSecond(1);
                    break;
                case Tinebase_Model_Import::INTERVAL_WEEKLY:
                    $timestamp->subWeek(1)->subSecond(1);
                    break;
                case Tinebase_Model_Import::INTERVAL_HOURLY:
                    $timestamp->subHour(1)->subSecond(1);
                    break;
                default:
                    $timestamp = null;
            }
            
            $this->timestamp = $timestamp;
        }
    }
}
