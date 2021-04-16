<?php

class ScieloSubmission {
    private $id;
    private $title;
    private $submitter;
    private $dateSubmitted;
    private $status;
    private $authors;
    private $language;
    private $finalDecision;

    public function __construct(int $id, string $title, string $submitter, string $dateSubmitted, string $status, array $authors, string $language, string $finalDecision) {
        $this->id = $id;
        $this->title = $title;
        $this->submitter = $submitter;
        $this->dateSubmitted = $dateSubmitted;
        $this->status = $status;
        $this->authors = $authors;
        $this->language = $language;
        $this->finalDecision = $finalDecision;
    }

    public function getId() {
        return $this->id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getSubmitter() {
        return $this->submitter;
    }

    public function getDateSubmitted() {
        return $this->dateSubmitted;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getAuthors() {
        return $this->authors;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function getFinalDecision() {
        return $this->finalDecision;
    }
}