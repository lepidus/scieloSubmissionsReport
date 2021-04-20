<?php

class ScieloSubmissionsReport {

    private $sections;
    private $submissions;

    public function __construct(array $sections, array $submissions) {
        $this->sections = $sections;
        $this->submissions = $submissions;
    }

    public function getSections() : array {
        return $this->sections;
    }

    public function getSubmissions() : array {
        return $this->submissions;
    }

    protected function getHeaders() : array {
        return [
            "ID da submissão",
            "Título da Submissão",
            "Submetido por",
            "Data de submissão",
            "Dias até mudança de status",
            "Estado da submissão",
            "Editores da Revista",
            "Editor de Seção",
            "Autores",
            "Seção",
            "Idioma",
            "Avaliações",
            "Última decisão",
            "Decisão final",
            "Data da decisão final",
            "Tempo em avaliação",
            "Tempo entre submissão e decisão final"
        ];
    }

    public function buildCSV($fileDescriptor) : void {
        fprintf($fileDescriptor, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($fileDescriptor, $this->getHeaders());

        foreach($this->submissions as $submission){
            fputcsv($fileDescriptor, $submission->asRecord());
        }
    }
}