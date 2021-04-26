<?php

class ScieloSubmissionsOPSReport extends ScieloSubmissionsReport {

    protected function getHeaders() : array {
        return ["ID da submissão","Título da Submissão","Submetido por","Data de submissão","Dias até mudança de status","Estado da submissão","Moderador de área","Moderadores","Autores","Seção","Idioma","Estado de publicação","DOI da publicação","Notas","Decisão final","Data da decisão final","Tempo em avaliação","Tempo entre submissão e decisão final"];
    }

    public function getAverageReviewingTime() : int {
        if (empty($this->submissions)) return 0;
        
        $totalReviewingTime = 0;

        foreach ( $this->submissions as $submission) {
            $totalReviewingTime += $submission->getTimeUnderReview();
        }

        return round($totalReviewingTime / sizeof($this->submissions));
    }
}