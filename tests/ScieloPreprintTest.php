<?php
use PHPUnit\Framework\TestCase;
import ('plugins.reports.scieloSubmissionsReport.classes.SubmissionAuthor');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprint');

class ScieloPreprintTest extends TestCase {
    
    private $submission;
    private $submissionId = 1233;
    private $title = "Rethinking linguistic relativity";
    private $submitter = "Atila Iamarino";
    private $submitterCountry = "Brasil";
    private $dateSubmitted = "2013-09-06 19:07:02";
    private $daysUntilStatusChange = 3;
    private $status = "Published";
    private $authors;
    private $section = "Biological Sciences";
    private $language = "en_US";
    private $finalDecision = "Accepted";
    private $finalDecisionDate = "2013-09-14 22:00:00";
    private $expectedReviewingTime = 8;
    private $moderators = array("Albert Einstein", "Richard Feynman");
    private $sectionModerator = "Clarice Lispector";
    private $publicationStatus = "Preprint has been published in a journal as an article";
    private $publicationDOI = "https://doi.org/10.1590/0100-3984.2020.53.2e1";
    private $notes = array("The author forgot to cite relevant work on this preprint", "This work is wonderful! Congrats!");

    public function setUp() : void {
        $this->authors = array(new SubmissionAuthor("Atila", "Brasil", "USP"));
        $this->submission = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->moderators, $this->sectionModerator, $this->publicationStatus, $this->publicationDOI, $this->notes);
    }

    public function testHasModerators() : void {
        $this->assertEquals(implode(",", $this->moderators), $this->submission->getModerators());
    }
    
    public function testHasNoModerators() : void {
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, [], $this->sectionModerator, $this->publicationStatus, $this->publicationDOI, $this->notes);
        $messageNoModerators = __("plugins.reports.scieloSubmissionsReport.warning.noModerators");
        $this->assertEquals($messageNoModerators, $preprint->getModerators());
    }

    public function testHasSectionModerator() : void {
        $this->assertEquals($this->sectionModerator, $this->submission->getSectionModerator());
    }

    public function testHasNoSectionModerator() : void {
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->moderators, "", $this->publicationStatus, $this->publicationDOI, $this->notes);
        $messageNoModerators = __("plugins.reports.scieloSubmissionsReport.warning.noModerators");
        $this->assertEquals($messageNoModerators, $preprint->getSectionModerator());
    }

    public function testHasPublicationStatus() : void {
        $this->assertEquals($this->publicationStatus, $this->submission->getPublicationStatus());
    }

    public function testWhenPublicationStatusIsEmpty() : void {
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->moderators, $this->sectionModerator, "", $this->publicationDOI, $this->notes);
        $messageNoPublicationStatus = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationStatus");
        $this->assertEquals($messageNoPublicationStatus, $preprint->getPublicationStatus());
    }
    
    public function testHasPublicationDOI() : void {
        $this->assertEquals($this->publicationDOI, $this->submission->getPublicationDOI());
    }

    public function testWhenPublicationDOIIsEmpty() : void {
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->moderators, $this->sectionModerator, $this->publicationStatus, "", $this->notes);
        $messageNoPublicationDOI = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI");
        $this->assertEquals($messageNoPublicationDOI, $preprint->getPublicationDOI());
    }

    public function testHasNotes() : void {
        $this->assertEquals("Note: " . implode(". Note: ", $this->notes), $this->submission->getNotes());
    }

    public function testWhenNotesHaveLinebreaks() : void {
        $noteWithLineBreak = array("The author forgot to cite relevant work on this preprint.\nHe needs to cite more relevant works.");
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->moderators, $this->sectionModerator, $this->publicationStatus, $this->publicationDOI, $noteWithLineBreak);

        $this->assertEquals("Note: The author forgot to cite relevant work on this preprint. He needs to cite more relevant works.", $preprint->getNotes());
    }
    
    public function testWhenPreprintHasNoNotes() : void {
        $emptyNotes = array();
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->moderators, $this->sectionModerator, $this->publicationStatus, $this->publicationDOI, $emptyNotes);

        $messageNoNotes = __("plugins.reports.scieloSubmissionsReport.warning.noNotes");
        $this->assertEquals($messageNoNotes, $preprint->getNotes());
    }

    public function testGetRecord() : void {
        $preprint = new ScieloPreprint(1, "Title 1", "Paola Franchesca", "Brasil", "2021-04-21", 1, "Posted", array(new SubmissionAuthor("Paola Franchesca", "Italy", "University of Milan")), "Fashion Design", "en_US", "Accepted", "2021-04-23", ["Jean Paul Cardin"], "Jean Paul Cardin", "Sent to journal publication", "", [""]);
        $messageNoPublicationDOI = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI");

        $expectedRecord = ["1", "Title 1", "Paola Franchesca", "Brasil", "2021-04-21", "1", "Posted", "Jean Paul Cardin", "Jean Paul Cardin", "Paola Franchesca, Italy, University of Milan", "Fashion Design", "en_US", "Sent to journal publication", $messageNoPublicationDOI, "Note:", "Accepted", "2021-04-23", "2", "2"];
        $this->assertEquals($expectedRecord, $preprint->asRecord());
    }
}