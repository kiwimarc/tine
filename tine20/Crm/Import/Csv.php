<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Import class for the Crm
 * 
 * @package     Crm
 * @subpackage  Import
 */
class Crm_Import_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
    );

    /**
     * creates a new importer from an import definition
     *
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array                                 $_options
     * @return Tinebase_Import_Csv_Abstract
     *
     * @todo move this to abstract when we no longer need to be php 5.2 compatible
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_options = array())
    {
        return new self(self::getOptionsArrayFromDefinition($_definition, $_options));
    }

    /**
     * constructs a new importer from given config
     *
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);

        // disable lead notifications on import
        Crm_Controller_Lead::getInstance()->sendNotifications(false);

        // get container id from default container if not set
        if (empty($this->_options['container_id'])) {
            $defaultContainer = Tinebase_Container::getInstance()->getDefaultContainer('Crm_Model_Lead');
            $this->_options['container_id'] = $defaultContainer->getId();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Setting default container id: ' . $this->_options['container_id']);
        }
    }

    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    /**
     * do conversions (transformations, charset, replacements ...)
     *
     * @param array $_data
     * @return array
     *
     * TODO think about moving this to import definition
     * TODO simplify crm/lead config handling for leadstate/source/type
     */
    protected function _doConversions($_data)
    {
        $data = parent::_doConversions($_data);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($data, true));

        // adjust lead_name/leadstate/source/types if missing
        $configSettings = Crm_Controller::getInstance()->getConfigSettings()->toArray();

        $requiredFields = array(
            'leadstate_id' => 'leadstates',
            'leadtype_id' => 'leadtypes',
            'leadsource_id' => 'leadsources'
        );
        foreach ($requiredFields as $requiredField => $configKey) {
            if (! empty($data[$requiredField])) {
                continue;
            }

            switch ($requiredField) {
                default:
                    // get default leadstate/source/type OR try to find it by name if given
                    if (! isset($configSettings[$configKey])) {
                        continue;
                    }
                    $settingField = preg_replace('/s$/', '', $configKey);

                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' config settings' . print_r($configSettings[$configKey], true));

                    // init with default
                    $data[$requiredField] = isset($configSettings[$configKey][0]['id']) ? $configSettings[$configKey][0]['id'] : 1;
                    foreach ($configSettings[$configKey] as $setting) {
                        if (isset($setting[$settingField]) && isset($_data[$settingField]) && strtolower($setting[$settingField]) === strtolower($_data[$settingField])) {
                            $data[$requiredField] = $setting['id'];
                        }
                    }
            }
        }

        return $data;
    }

    /**
     * do something with the imported record
     *
     * @param $importedRecord
     */
    protected function _inspectAfterImport($importedRecord)
    {
        if (Crm_Config::getInstance()->get(Crm_Config::LEAD_IMPORT_AUTOTASK) && ! $this->_options['dryrun']) {
            $this->_addLeadAutoTaskForResponsibles($importedRecord);
        }
    }

    /**
     * add auto tasks if config option is set and lead has responsible person
     *
     * @param Crm_Model_Lead $lead
     */
    protected function _addLeadAutoTaskForResponsibles(Crm_Model_Lead $lead)
    {
        $responsibles = $lead->getResponsibles();

        if (count($responsibles) === 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No responsibles found');
            return;
        }

        $translate = Tinebase_Translation::getTranslation('Crm');

        // create task (if current user has edit grant for other users default tasks container)
        $autoTask = new Tasks_Model_Task(array(
            'summary'   => $translate->_('Edit new lead'),
            'due'       => Tinebase_DateTime::now()->addHour(2),
            'status'    => 'IN-PROCESS',
            'relations' => array(array(
                'own_model'              => 'Tasks_Model_Task',
                'own_backend'            => 'Sql',
                'own_id'                 => 0,
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                'type'                   => 'TASK',
                'related_record'         => $lead,
                'related_id'             => $lead->getId(),
                'related_model'          => 'Crm_Model_Lead',
                'related_backend'        => 'Sql'
            )),
        ));

        foreach ($responsibles as $responsible) {
            if ($responsible->type !== Addressbook_Model_Contact::CONTACTTYPE_USER) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Responsible is no user');
                continue;
            }
            try {
                $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $responsible->account_id);
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Could not find user');
                continue;
            }

            $autoTaskForResponsible = clone($autoTask);
            $responsiblePersonalContainer = Tinebase_Container::getInstance()->getPersonalContainer(
                Tinebase_Core::getUser(),
                'Tasks_Model_Task',
                $user->getId(),
                Tinebase_Model_Grants::GRANT_ADD
            )->getFirstRecord();
            if (! $responsiblePersonalContainer) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Could not find personal container of user with ADD grant');
                continue;
            }
            $autoTaskForResponsible->container_id = $responsiblePersonalContainer->getId();
            $autoTaskForResponsible->organizer = $responsible->account_id;
            Tasks_Controller_Task::getInstance()->create($autoTaskForResponsible);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Created auto task for user ' . $user->getId() . ' in container ' . $responsiblePersonalContainer->name);
        }
    }
}