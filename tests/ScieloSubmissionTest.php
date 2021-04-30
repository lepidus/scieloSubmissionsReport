<?php
use PHPUnit\Framework\TestCase;
import ('plugins.reports.scieloSubmissionsReport.classes.SubmissionAuthor');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');

class ScieloSubmissionTest extends TestCase {
    
    private $submission;
    private $submissionId = 1233;
    private $title = "Rethinking linguistic relativity";
    private $submitter = "Atila Iamarino";
    private $dateSubmitted = "2013-09-06 19:07:02";
    private $daysUntilStatusChange = 3;
    private $status = "Published";
    private $authors;
    private $section = "Biological Sciences";
    private $language = "en_US";
    private $finalDecision = "Accepted";
    private $finalDecisionDate = "2013-09-14 22:00:00";
    private $expectedReviewingTime = 8;

    private function createScieloSubmission() : ScieloSubmission {
        $this->authors = array(new SubmissionAuthor("Atila", "Brasil", "USP"));
        return new ScieloSubmission($this->submissionId, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate);
    }

    private function createSubmissionWithoutFinalDecision() : ScieloSubmission {
        $emptyFinalDecisionDate = "";
        return new ScieloSubmission($this->submissionId, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $emptyFinalDecisionDate);
    }
    
    private function getTestSubmissions() : array {
        return array($this->createScieloSubmission(), $this->createScieloSubmission());
    }

    public function setUp() : void {
        $this->submission = $this->createScieloSubmission();
    }

    public function testSubmissionHasId() : void {
        $this->assertEquals($this->submissionId, $this->submission->getId());
    }

    public function testHasTitle() : void {
        $this->assertEquals($this->title, $this->submission->getTitle());
    }

    public function testHasSubmitter() : void {
        $this->assertEquals($this->submitter, $this->submission->getSubmitter());
    }

    public function testWhenEmptySubmitter() : void {
        $submission = new ScieloSubmission($this->submissionId, $this->title, "", $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate);
        $this->assertEquals("The submitting author was not found", $submission->getSubmitter());
    }

    public function testHasDateSubmitted() : void {
        $this->assertEquals($this->dateSubmitted, $this->submission->getDateSubmitted());
    }

    public function testHasStatus() : void {
        $this->assertEquals($this->status, $this->submission->getStatus());
    }

    public function testHasAuthors() : void {
        $this->assertEquals($this->authors, $this->submission->getAuthors());
    }

    public function testHasSection() : void {
        $this->assertEquals($this->section, $this->submission->getSection());
    }

    public function testHasLanguage() : void {
        $this->assertEquals($this->language, $this->submission->getLanguage());
    }

    public function testFinalDecision() : void {
        $this->assertEquals($this->finalDecision, $this->submission->getFinalDecision());
    }

    public function testDaysUntilStatusChange() : void {
        $this->assertEquals($this->daysUntilStatusChange, $this->submission->getDaysUntilStatusChange());
    }
    
    public function testFinalDecisionDate() : void {
        $this->assertEquals($this->finalDecisionDate, $this->submission->getFinalDecisionDate());
    }

    public function testTimeUnderReviewWithFinalDecisionMade() : void {
        $this->assertEquals($this->expectedReviewingTime, $this->submission->getTimeUnderReview());
    }

    public function testTimeUnderReviewWithoutFinalDecisionMade() : void {
        $submission = $this->createSubmissionWithoutFinalDecision();
        
        $expectedReviewingTime = date_diff(new DateTime(trim($this->dateSubmitted)), new DateTime());
        $expectedReviewingTime = $expectedReviewingTime->format('%a');

        $this->assertEquals($expectedReviewingTime, $submission->getTimeUnderReview());
    }

    public function testTimeBetweenSubmissionAndFinalDecisionWithFinalDecision() : void {
        $this->assertEquals($this->expectedReviewingTime, $this->submission->getTimeBetweenSubmissionAndFinalDecision());
    }

    public function testTimeBetweenSubmissionAndFinalDecisionWithoutFinalDecision() : void {
        $submission = $this->createSubmissionWithoutFinalDecision();
        $expectedTimeBetweenSubmissionAndFinalDecision = "";
        $this->assertEquals($expectedTimeBetweenSubmissionAndFinalDecision, $submission->getTimeBetweenSubmissionAndFinalDecision());
    }
}
