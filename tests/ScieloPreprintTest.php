<?php

require "ScieloSubmissionTest.php";

class ScieloPreprintTest extends ScieloSubmissionTest {
    
    private $moderators = array("Albert Einstein", "Richard Feynman");
    private $sectionModerator = "Clarice Lispector";
    private $publicationStatus = "Preprint has been published in a journal as an article";
    private $publicationDOI = "https://doi.org/10.1590/0100-3984.2020.53.2e1";

    protected function createScieloSubmission() {
        parent::createScieloSubmission();
        return new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->moderators, $this->sectionModerator, $this->publicationStatus, $this->publicationDOI);
    }

    public function testHasModerators() : void {
        $this->assertEquals($this->moderators, $this->submission->getModerators());
    }

    public function testHasSectionModerator() : void {
        $this->assertEquals($this->sectionModerator, $this->submission->getSectionModerator());
    }

    public function testHasPublicationStatus() : void {
        $this->assertEquals($this->publicationStatus, $this->submission->getPublicationStatus());
    }

    public function testHasPublicationDOI() : void {
        $this->assertEquals($this->publicationDOI, $this->submission->getPublicationDOI());
    }

    //testHasNotes
}
    