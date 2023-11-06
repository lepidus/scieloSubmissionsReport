<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

use APP\submission\Submission;
use PKP\core\PKPApplication;
use PKP\core\Core;
use DateTime;
use APP\facades\Repo;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloSubmissionFactory;
use PKP\log\event\PKPSubmissionEventLogEntry;

class ScieloSubmissionFactoryTest extends ScieloFactoryTestCase
{
    private $submitterUser;

    protected function getSectionData(): array
    {
        return [
            ...parent::getSectionData(),
            'title' => [
                $this->getLocale() => 'Biological Sciences'
            ]
        ];
    }

    protected function getSubmissionData(): array
    {
        return [
            ...parent::getSubmissionData(),
            'dateSubmitted' => '2021-05-31 15:38:24',
            'dateLastActivity' => '2021-06-03 16:00:00',
            'status' => Submission::STATUS_PUBLISHED
        ];
    }

    protected function getPublicationData(): array
    {
        return [
            ...parent::getPublicationData(),
            'title' => [
                $this->getLocale() => 'eXtreme Programming: A practical guide',
            ],
            'relationStatus' => 1,
            'doiObject' => Repo::doi()->newDataObject(
                [
                    'doi' => '10.666/949494',
                    'contextId' => $this->context->getId()
                ]
            ),
            'status' => Submission::STATUS_PUBLISHED
        ];
    }

    private function createSubmitterUser()
    {
        $locale = $this->getLocale();

        $user = Repo::user()->newDataObject([
            'userName' => 'the_godfather',
            'email' => 'donvito@corleone.com',
            'password' => 'miaumiau',
            'country' => 'BR',
            'givenName' => [$locale => 'Don'],
            'familyName' => [$locale => 'Vito Corleone'],
            'dateRegistered' => Core::getCurrentDate()
        ]);

        $userId = Repo::user()->add($user);

        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => PKPApplication::ASSOC_TYPE_SUBMISSION,
            'assocId' => $this->submission->getId(),
            'eventType' => PKPSubmissionEventLogEntry::SUBMISSION_LOG_SUBMISSION_SUBMIT,
            'userId' => $user->getId(),
            'dateLogged' => '2021-05-28 15:19:24'
        ]);

        Repo::eventLog()->add($eventLog);

        $this->submitterUser = Repo::user()->get($userId);
    }

    private function deleteSubmitterUser(): void
    {
        if (!$this->submitterUser) {
            return;
        }

        Repo::user()->delete($this->submitterUser);
    }

    public function testGetScieloSubmissionTitle(): void
    {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submission->getId(), $this->getLocale());

        $this->assertEquals(
            $this->publication->getLocalizedTitle($this->getLocale()),
            $scieloSubmission->getTitle()
        );
    }

    public function testSubmissionGetsSubmitter(): void
    {
        $this->createSubmitterUser();

        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submission->getId(), $this->getLocale());

        $this->assertEquals($this->submitterUser->getFullName(), $scieloSubmission->getSubmitter());
    }

    public function testSubmissionGetsSubmitterCountry(): void
    {
        $this->createSubmitterUser();

        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submission->getId(), $this->getLocale());

        $this->assertEquals($this->submitterUser->getCountryLocalized(), $scieloSubmission->getSubmitterCountry());
    }

    public function testSubmissionGetsDateSubmitted(): void
    {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submission->getId(), $this->getLocale());

        $this->assertEquals($this->submission->getData('dateSubmitted'), $scieloSubmission->getDateSubmitted());
    }

    public function testSubmissionGetsStatus(): void
    {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submission->getId(), $this->getLocale());

        $statusKey = $this->submission->getStatusKey();
        $this->assertEquals(__($statusKey), $scieloSubmission->getStatus());
    }

    public function testSubmissionGetsSectionName(): void
    {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submission->getId(), $this->getLocale());

        $this->assertEquals($this->section->getLocalizedTitle(), $scieloSubmission->getSection());
    }

    public function testSubmissionGetsLanguage(): void
    {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submission->getId(), $this->getLocale());

        $this->assertEquals($this->getLocale(), $scieloSubmission->getLanguage());
    }

    public function testSubmissionsGetsDaysUntilStatusChange(): void
    {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submission->getId(), $this->getLocale());

        $dateSubmitted = new DateTime('2021-05-31 15:38:24');
        $dateLastActivity = new DateTime('2021-06-03 16:00:00');
        $daysUntilStatusChange = $dateLastActivity->diff($dateSubmitted)->format('%a');

        $this->assertEquals($daysUntilStatusChange, $scieloSubmission->getDaysUntilStatusChange());
    }

    public function testSubmissionGetsSubmissionAuthors(): void
    {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submission->getId(), $this->getLocale());

        dump($this->authors);
        dump($scieloSubmission->getAuthors());

        $this->assertEquals($this->authors, $scieloSubmission->getAuthors());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteSubmitterUser();
    }
}
