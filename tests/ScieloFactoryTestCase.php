<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

use APP\facades\Repo;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionAuthor;
use PKP\tests\DatabaseTestCase;

class ScieloFactoryTestCase extends DatabaseTestCase
{
    protected $context;
    protected $submission;
    protected $section;
    protected $authors = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->createContext();
        $this->createSection();
        $this->createSubmission();
        $this->createAuthors();
    }

    protected function getLocale(): string
    {
        return 'en';
    }

    protected function getContextData(): array
    {
        $locale = $this->getLocale();

        return [
            'urlPath' => [$locale => 'test'],
            'primaryLocale' => $this->getLocale()
        ];
    }

    protected function getSubmissionData(): array
    {
        return [
            'contextId' => $this->context->getId(),
            'locale' => $this->getLocale()
        ];
    }

    protected function getPublicationData(): array
    {
        return [
            'sectionId' => $this->section->getId(),
        ];
    }

    protected function getSectionData(): array
    {
        $locale = $this->getLocale();

        return [
            'abbrev' => [$locale => __('section.default.abbrev')],
            'policy' => [$locale => __('section.default.policy')],
            'metaIndexed' => true,
            'metaReviewed' => true,
            'editorRestricted' => false,
            'hideTitle' => false,
            'contextId' => $this->context->getId()
        ];
    }

    protected function getAuthorsData(): array
    {
        $locale = $this->getLocale();

        return [
            'first' => [
                'publicationId' => $this->publication->getId(),
                'email' => 'anaalice@ufam.edu.br',
                'givenName' => [$locale => 'Ana Alice'],
                'familyName' => [$locale => 'Caldas Novas'],
                'affiliation' => [$locale => 'Amazonas Federal University'],
                'country' => 'BR'
            ],
            'second' => [
                'publicationId' => $this->publication->getId(),
                'email' => 'seizi.tagima@harvard.com',
                'givenName' => [$locale => 'Seizi'],
                'familyName' => [$locale => 'Tagima'],
                'affiliation' => [$locale => 'Harvard University'],
                'country' => 'US'
            ],
        ];
    }

    protected function createContext(): void
    {
        $contextDAO = \Application::getContextDAO();
        $context = $contextDAO->newDataObject();
        $context->setAllData($this->getContextData());
        $contextDAO->insertObject($context);

        $this->context = $context;
    }

    protected function createSubmission(): void
    {
        $submission = Repo::submission()->newDataObject($this->getSubmissionData());
        $submissionId = Repo::submission()->dao->insert($submission);

        $publication = Repo::publication()->newDataObject($this->getPublicationData());
        $publication->setData('submissionId', $submissionId);
        $publicationId = Repo::publication()->add($publication);

        $submission = Repo::submission()->get($submissionId);
        $submission->setData('currentPublicationId', $publicationId);
        Repo::submission()->dao->update($submission);

        $this->submission = Repo::submission()->get($submission->getId());
        $this->publication = Repo::publication()->get($publicationId);
    }

    protected function createSection(): void
    {
        $section = Repo::section()->newDataObject($this->getSectionData());
        $sectionId = Repo::section()->add($section);

        $this->section = Repo::section()->get($sectionId);
    }

    protected function createAuthors(): void
    {
        foreach ($this->getAuthorsData() as $authorData) {
            $author = Repo::author()->newDataObject($authorData);
            $authorId = Repo::author()->add($author);
            $author = Repo::author()->get($authorId);

            $this->authors[] = new SubmissionAuthor(
                $author->getFullName(),
                $author->getCountryLocalized(),
                $author->getLocalizedAffiliation()
            );
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteContext();
    }

    protected function deleteContext(): void
    {
        $contextDAO = \Application::getContextDAO();
        $contextDAO->deleteObject($this->context);
    }
}
