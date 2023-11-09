<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloPreprint;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionAuthor;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionStats;
use PHPUnit\Framework\TestCase;

class ScieloPreprintTest extends TestCase
{
    private $submission;
    private $submissionId = 1233;
    private $title = 'Rethinking linguistic relativity';
    private $submitter = 'Atila Iamarino';
    private $submitterCountry = 'Brasil';
    private $submitterIsScieloJournal = false;
    private $dateSubmitted = '2013-09-06 19:07:02';
    private $daysUntilStatusChange = 3;
    private $status = 'Published';
    private $authors;
    private $section = 'Biological Sciences';
    private $language = 'en';
    private $finalDecision = 'Accepted';
    private $finalDecisionDate = '2013-09-14 22:00:00';
    private $expectedReviewingTime = 8;
    private $responsibles = ['Albert Einstein', 'Richard Feynman'];
    private $sectionModerators = ['Clarice Lispector', 'Oswaldo de Andrade'];
    private $publicationStatus = 'Preprint has been published in a journal as an article';
    private $publicationDOI = 'https://doi.org/10.1590/0100-3984.2020.53.2e1';
    private $notes = ['The author forgot to cite relevant work on this preprint', 'This work is wonderful! Congrats!'];
    private $abstractViews = 10;
    private $pdfViews = 21;

    public function setUp(): void
    {
        $this->authors = [new SubmissionAuthor('Atila', 'Brasil', 'USP')];
        $stats = new SubmissionStats($this->abstractViews, $this->pdfViews);
        $this->submission = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->submitterIsScieloJournal, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->responsibles, $this->sectionModerators, $this->publicationStatus, $this->publicationDOI, $this->notes, $stats);
    }

    public function testSubmitterIsScieloJournal(): void
    {
        $messageSubmitterIsJournal = __('common.no');
        $this->assertEquals($messageSubmitterIsJournal, $this->submission->getSubmitterIsScieloJournal());
    }

    public function testHasResponsibles(): void
    {
        $this->assertEquals(implode(',', $this->responsibles), $this->submission->getResponsibles());
    }

    public function testHasNoResponsibles(): void
    {
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->submitterIsScieloJournal, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, [], $this->sectionModerators, $this->publicationStatus, $this->publicationDOI, $this->notes);
        $messageNoResponsibles = __('plugins.reports.scieloSubmissionsReport.warning.noResponsibles');
        $this->assertEquals($messageNoResponsibles, $preprint->getResponsibles());
    }

    public function testHasSectionModerators(): void
    {
        $this->assertEquals(implode(',', $this->sectionModerators), $this->submission->getSectionModerators());
    }

    public function testHasNoSectionModerator(): void
    {
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->submitterIsScieloJournal, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->responsibles, [], $this->publicationStatus, $this->publicationDOI, $this->notes);
        $messageNoModerators = __('plugins.reports.scieloSubmissionsReport.warning.noModerators');
        $this->assertEquals($messageNoModerators, $preprint->getSectionModerators());
    }

    public function testHasPublicationStatus(): void
    {
        $this->assertEquals($this->publicationStatus, $this->submission->getPublicationStatus());
    }

    public function testWhenPublicationStatusIsEmpty(): void
    {
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->submitterIsScieloJournal, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->responsibles, $this->sectionModerators, '', $this->publicationDOI, $this->notes);
        $messageNoPublicationStatus = __('plugins.reports.scieloSubmissionsReport.warning.noPublicationStatus');
        $this->assertEquals($messageNoPublicationStatus, $preprint->getPublicationStatus());
    }

    public function testHasPublicationDOI(): void
    {
        $this->assertEquals($this->publicationDOI, $this->submission->getPublicationDOI());
    }

    public function testWhenPublicationDOIIsEmpty(): void
    {
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->submitterIsScieloJournal, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->responsibles, $this->sectionModerators, $this->publicationStatus, '', $this->notes);
        $messageNoPublicationDOI = __('plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI');
        $this->assertEquals($messageNoPublicationDOI, $preprint->getPublicationDOI());
    }

    public function testHasNotes(): void
    {
        $this->assertEquals('Note: ' . implode('. Note: ', $this->notes), $this->submission->getNotes());
    }

    public function testWhenNotesHaveLinebreaks(): void
    {
        $noteWithLineBreak = ["The author forgot to cite relevant work on this preprint.\nHe needs to cite more relevant works."];
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->submitterIsScieloJournal, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->responsibles, $this->sectionModerators, $this->publicationStatus, $this->publicationDOI, $noteWithLineBreak);

        $this->assertEquals('Note: The author forgot to cite relevant work on this preprint. He needs to cite more relevant works.', $preprint->getNotes());
    }

    public function testWhenPreprintHasNoNotes(): void
    {
        $emptyNotes = [];
        $preprint = new ScieloPreprint($this->submissionId, $this->title, $this->submitter, $this->submitterCountry, $this->submitterIsScieloJournal, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authors, $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->responsibles, $this->sectionModerators, $this->publicationStatus, $this->publicationDOI, $emptyNotes);

        $messageNoNotes = __('plugins.reports.scieloSubmissionsReport.warning.noNotes');
        $this->assertEquals($messageNoNotes, $preprint->getNotes());
    }

    public function testHasStats(): void
    {
        $stats = new SubmissionStats($this->abstractViews, $this->pdfViews);
        $this->assertEquals($stats, $this->submission->getStats());
    }

    public function testGetRecord(): void
    {
        $stats = new SubmissionStats($this->abstractViews, $this->pdfViews);
        $preprint = new ScieloPreprint(1, 'Title 1', 'Paola Franchesca', 'Brasil', false, '2021-04-21', 1, 'Posted', [new SubmissionAuthor('Paola Franchesca', 'Italy', 'University of Milan')], 'Fashion Design', 'en', 'Accepted', '2021-04-23', ['Jean Paul Cardin'], ['Jean Paul Cardin'], 'Sent to journal publication', '', [''], $stats);
        $messageNoPublicationDOI = __('plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI');
        $messageSubmitterIsNotScieloJournal = __('common.no');

        $expectedRecord = ['1', 'Title 1', 'Paola Franchesca', 'Brasil', $messageSubmitterIsNotScieloJournal, '2021-04-21', '1', 'Posted', 'Jean Paul Cardin', 'Jean Paul Cardin', 'Paola Franchesca, Italy, University of Milan', 'Fashion Design', 'en', 'Sent to journal publication', $messageNoPublicationDOI, 'Note:', 'Accepted', '2021-04-23', '2', '2', '10', '21'];
        $this->assertEquals($expectedRecord, $preprint->asRecord());
    }

    public function testRecordDoesntHaveStats(): void
    {
        $preprint = new ScieloPreprint(1, 'Title 1', 'Paola Franchesca', 'Brasil', false, '2021-04-21', 1, 'Posted', [new SubmissionAuthor('Paola Franchesca', 'Italy', 'University of Milan')], 'Fashion Design', 'en', 'Accepted', '2021-04-23', ['Jean Paul Cardin'], ['Jean Paul Cardin'], 'Sent to journal publication', '', ['']);
        $messageNoPublicationDOI = __('plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI');
        $messageSubmitterIsNotScieloJournal = __('common.no');

        $expectedRecord = ['1', 'Title 1', 'Paola Franchesca', 'Brasil', $messageSubmitterIsNotScieloJournal, '2021-04-21', '1', 'Posted', 'Jean Paul Cardin', 'Jean Paul Cardin', 'Paola Franchesca, Italy, University of Milan', 'Fashion Design', 'en', 'Sent to journal publication', $messageNoPublicationDOI, 'Note:', 'Accepted', '2021-04-23', '2', '2'];
        $this->assertEquals($expectedRecord, $preprint->asRecord());
    }
}
