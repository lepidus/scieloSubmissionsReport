<?php
use PHPUnit\Framework\TestCase;

final class ScieloSubmissionTest extends TestCase {
    //properties to test:
    //id, titulo, usuário submissor (nome), data de submissão, estado da submissão, autores, lingua
    //decisão final, data da decisão final, tempo em avaliação, tempo entre submissão e a decisão final

    private $submission;
    private $submissionId = 1233;
    private $title = "Rethinking linguistic relativity";
    private $submitter = "Atila Iamarino";
    private $dateSubmitted = "2013-09-06 19:07:02";
    private $status = "Published";
    private $authors;
    private $language = "en_US";
    private $finalDecision = "Accepted";

    private function createScieloSubmission() {
        $this->authors = array(new SubmissionAuthor("Atila", "Brasil", "USP"));
        return new ScieloSubmission($this->submissionId, $this->title, $this->submitter, $this->dateSubmitted, $this->status, $this->authors, $this->language, $this->finalDecision);
    }
    
    public function getTestSubmissions() {
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

    public function testHasDateSubmitted() : void {
        $this->assertEquals($this->dateSubmitted, $this->submission->getDateSubmitted());
    }

    public function testHasStatus() : void {
        $this->assertEquals($this->status, $this->submission->getStatus());
    }

    public function testHasAuthors() : void {
        $this->assertEquals($this->authors, $this->submission->getAuthors());
    }

    public function testHasLanguage() : void {
        $this->assertEquals($this->language, $this->submission->getLanguage());
    }

    public function testFinalDecision() : void {
        $this->assertEquals($this->finalDecision, $this->submission->getFinalDecision());
    }
}