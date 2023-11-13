<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

class CSVFileUtils
{
    public function getExpectedUTF8BOM(): string
    {
        return chr(0xEF) . chr(0xBB) . chr(0xBF);
    }

    public function readUTF8Bytes($csvFile): string
    {
        return fread($csvFile, strlen($this->getExpectedUTF8BOM()));
    }
}
