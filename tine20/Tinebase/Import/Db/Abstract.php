<?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * import data from db
 *
 * @package     Tinebase
 * @subpackage  Import
 */
abstract class Tinebase_Import_Db_Abstract
{
    protected Zend_Db_Adapter_Abstract $_importDb;
    protected ?string $_mainTableName = null;
    protected bool $_duplicateCheck = true;
    protected array $_descriptionFields = [];

    public function __construct(?Zend_Db_Adapter_Abstract $db = null)
    {
        $this->_importDb = $db ?: Tinebase_Core::getDb();
    }

    /**
     * @return array of imported IDs
     */
    public function import(): array
    {
        $count = 0;
        $skipcount = 0;
        $failcount = 0;
        $pageNumber = 0;
        $pageCount = 100;
        $importedIds = [];
        do {
            $cont = false;
            $stmt = $this->_importDb->select()->from($this->_mainTableName)->limitPage(++$pageNumber, $pageCount)->query();
            $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' fetched ' . count($rows) . ' rows  / pagenumber: ' . $pageNumber);
            foreach ($rows as $row) {
                try {
                    if ($record = $this->_importRecord($row)) {
                        $count++;
                        $importedIds[] = $record->getId();
                    } else {
                        $failcount++;
                    }
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::ERR)) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__
                        . ' Could not import ' . $this->_mainTableName . ' record: ' . $e);
                    $failcount++;
                }
                $cont = count($rows) >= $pageCount;
            }
        } while ($cont);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Imported ' . $count . ' records (failcount: ' . $failcount . ' | skipcount: ' . $skipcount . ')');

        return $importedIds;
    }

    protected function _importRecord($row)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Importing data ' . print_r($row, true));

        $recordToImport = $this->_getRecord($row);

        $controller = $this->_getController();
        try {
            $record = $controller->get($recordToImport->getId());
            $record->merge($recordToImport);
            $record = $controller->update($record);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $record = $controller->create($recordToImport, $this->_duplicateCheck);
        }

        $this->_onAfterImportRecord($record);

        return $record;
    }

    abstract protected function _getRecord($row): Tinebase_Record_Interface;
    abstract protected function _getController(): Tinebase_Controller_Record_Abstract;

    protected function _onAfterImportRecord(Tinebase_Record_Interface $record)
    {
    }

    protected function _getDescription(array $row): string
    {
        $note = '';
        foreach ($this->_descriptionFields as $field) {
            if ($row[$field] !== null) {
                $note .= "$field: " . $row[$field] . "\n";
            }
        }

        return $note;
    }
}
