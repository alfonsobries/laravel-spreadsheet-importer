<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Contracts;

interface Importable
{
    /**
     * Return the full file path that will be imported
     * @return string
     */
    public function getFileToImportPath();

    /**
     * Return the temporaly table name that will be used to store the spreadsheet contents
     * @return string
     */
    public function getTemporalTableName();
}
