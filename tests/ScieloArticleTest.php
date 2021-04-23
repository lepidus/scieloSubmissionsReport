<?php

require_once "ScieloSubmissionTest.php";

class ScieloArticleTest extends ScieloSubmissionTest {

    private $editors = array("Albert Einstein", "Richard Feynman");
    private $sectionEditor = "Carl Sagan";
    private $reviews = array("Aceitar", "Ver comentários");
    private $lastDecision = "Enviar para avaliação";

    protected function createScieloSubmission() {
        parent::createScieloSubmission();
        return new ScieloArticle($this->submissionId, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->editors, $this->sectionEditor, $this->reviews, $this->lastDecision);
    }

    public function testJournalEditors() : void {
        $this->assertEquals(implode(",", $this->editors), $this->submission->getJournalEditors());
    }

    public function testWhenJournalEditorsIsEmpty() : void {
        $article = new ScieloArticle($this->submissionId, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, [], $this->sectionEditor, $this->reviews, $this->lastDecision);
        $this->assertEquals("No editors", $article->getJournalEditors());
    }
    
    public function testHasSectionEditor() : void {
        $this->assertEquals($this->sectionEditor, $this->submission->getSectionEditor());
    }

    public function testWhenSectionEditorIsEmpty() : void {
        $article = new ScieloArticle($this->submissionId, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->editors, "", $this->reviews ,$this->lastDecision);
        $this->assertEquals("No editors", $article->getSectionEditor());
    }

    public function testHasReviews() : void {
        $this->assertEquals(implode(",", $this->reviews), $this->submission->getReviews());
    }

    public function testHasNoReviews() : void {
        $article = new ScieloArticle($this->submissionId, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->editors, $this->sectionEditor, [], $this->lastDecision);
        $this->assertEquals("", $article->getReviews());
    }

    public function testHasLastDecision() : void {
        $this->assertEquals($this->lastDecision, $this->submission->getLastDecision());
    }

    public function testHasNoLastDecision() : void {
        $article = new ScieloArticle($this->submissionId, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->editors, $this->sectionEditor, $this->reviews, "");
        $this->assertEquals("", $article->getLastDecision());
    }
}