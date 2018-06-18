<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * foreign id filter
 * 
 * Expects:
 * - a filtergroup in options->filtergroup
 * - a controller  in options->controller
 * 
 * Hands over all options to filtergroup
 * Hands over AclFilter functions to filtergroup
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_ForeignId extends Tinebase_Model_Filter_ForeignRecord
{
    /**
     * get foreign controller
     * 
     * @return Tinebase_Controller_Record_Abstract
     */
    protected function _getController()
    {
        if ($this->_controller === NULL) {
            $this->_controller = call_user_func($this->_options['controller'] . '::getInstance');
        }
        
        return $this->_controller;
    }
    
    /**
     * set options 
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        if (! (isset($_options['controller']) || array_key_exists('controller', $_options)) || ! (isset($_options['filtergroup']) || array_key_exists('filtergroup', $_options))) {
            throw new Tinebase_Exception_InvalidArgument('a controller and a filtergroup must be specified in the options');
        }
        parent::_setOptions($_options);
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (! is_array($this->_foreignIds)) {
            $this->_foreignIds = $this->_getController()->search($this->_filterGroup, new Tinebase_Model_Pagination(), FALSE, TRUE);
        }
        
        $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', empty($this->_foreignIds) ? new Zend_Db_Expr('NULL') : $this->_foreignIds);
    }
    
    /**
     * set required grants
     * 
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants)
    {
        $this->_filterGroup->setRequiredGrants($_grants);
    }
    
    /**
     * get filter information for toArray()
     * 
     * @return array
     */
    protected function _getGenericFilterInformation()
    {
        list($appName, , $filterName) = explode('_', static::class);
        
        $result = array(
            'linkType'      => 'foreignId',
            'appName'       => $appName,
            'filterName'    => $filterName,
        );
        
        if (isset($this->_options['modelName'])) {
            list(,, $modelName) = explode('_', $this->_options['modelName']);
            $result['modelName'] = $modelName;
        }
        
        return $result;
    }
}
