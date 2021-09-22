<?php
/**
 * Tine 2.0
 * @package     Dfcom
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * cli server for Dfcom
 *
 * This class handles cli requests for the Dfcom
 *
 * @package     Dfcom
 */
class DFCom_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'DFCom';

    /**
     * import demodata default definitions
     *
     * @var array
     */
    protected $_defaultDemoDataDefinition = [
        'DFCom_Model_Device' => 'dfcom_import_device_csv'
    ];

    /**
     * help array with function names and param descriptions
     */
    protected $_help = array(
        'import' => array(
            'description'   => 'Import new items into the Dfcom.',
            'params'        => array(
                'filenames'   => 'Filename(s) of import file(s) [required]',
                'definition'  => 'Name of the import definition or filename [required] -> for example admin_user_import_csv(.xml)',
            )
        )
    );
    
    /**
     * import items
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function import($_opts)
    {
        parent::_import($_opts);
    }
}
