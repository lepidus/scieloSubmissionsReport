<?php

require "ScieloSubmissionsReportTest.php";

class ScieloSubmissionsOJSReportTest extends ScieloSubmissionsReportTest {
    
    protected function createScieloSubmissionReport() {
        parent::createScieloSubmissionReport();
        return new ScieloSubmissionsOJSReport($this->sections, $this->submissions);
    }

    public function testGeneratedCSVHeadersFromOJSSubmissions() {
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');
        $this->readUTF8Bytes($csvFile);

        $firstLine = fgetcsv($csvFile);
        $expectedLine = ["ID da submissão","Título da Submissão","Submetido por","Data de submissão","Dias até mudança de status","Estado da submissão","Editores da Revista","Editor de Seção","Autores","Seção","Idioma","Avaliações","Última decisão", "Decisão final","Data da decisão final","Tempo em avaliação","Tempo entre submissão e decisão final"];

        $this->assertEquals($expectedLine, $firstLine);

        fclose($csvFile);
    }
}