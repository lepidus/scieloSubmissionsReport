<?php

class ScieloSubmissionsOJSReport extends ScieloSubmissionsReport {
    
    protected function getHeaders() : array {
        return ["ID da submissão","Título da Submissão","Submetido por","Data de submissão","Dias até mudança de status","Estado da submissão","Editores da Revista","Editor de Seção","Autores","Seção","Idioma","Avaliações","Última decisão", "Decisão final","Data da decisão final","Tempo em avaliação","Tempo entre submissão e decisão final"];
    }

    protected function filterWithAverageReviewingTimeOnly() {
        $submissions = array();
        
        foreach ($this->submissions as $submission) {
            if (!empty($submission->getFinalDecision()) && $submission->hasReviews()) {
                $submissions[] = $submission;
            }
        }
        return $submissions;
    }
}