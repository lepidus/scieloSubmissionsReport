<?php

class ScieloSubmissionsOPSReportTest extends ScieloSubmissionsReportTest {

    protected function createScieloSubmissionReport() {
        parent::createScieloSubmissionReport();
        return new ScieloSubmissionsOPSReport($this->sections, $this->submissions);
    }
    
    public function testGeneratedCSVHeadersFromOPSSubmissions() {
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');
        $this->readUTF8Bytes($csvFile);

        $firstLine = fgetcsv($csvFile);
        $expectedLine = ["ID da submissão","Título da Submissão","Submetido por","Data de submissão","Dias até mudança de status","Estado da submissão","Moderador de área","Moderadores","Autores","Seção","Idioma","Estado de publicação","DOI da publicação","Notas","Decisão final","Data da decisão final","Tempo em avaliação","Tempo entre submissão e decisão final"];

        $this->assertEquals($expectedLine, $firstLine);

        fclose($csvFile);
    }
}