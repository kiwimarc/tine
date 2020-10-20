<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Log
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Class Tinebase_Log_Formatter_Json
 */
class Tinebase_Log_Formatter_Json extends Tinebase_Log_Formatter
{
    /**
     * Formats data into a single json_encoded line to be written by the writer.
     *
     * @param array $event event data
     * @return string formatted line to write to the log
     */
    public function format($event)
    {
        $data = $this->getLogData($event);
        
        // filebeat friendly format, make sure you have set "json.overwrite_keys: true" in your filebeat conf
        $data['@timestamp'] = date(sprintf('Y-m-d\TH:i:s%s\Z', substr(microtime(), 1, 4)));
        
        return @json_encode($data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)
            . PHP_EOL;
    }
}
