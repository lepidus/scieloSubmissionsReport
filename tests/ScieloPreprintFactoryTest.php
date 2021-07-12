<?php

import('lib.pkp.tests.DatabaseTestCase');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('classes.journal.Section');
import('classes.article.Author');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprintFactory');
import('classes.workflow.EditorDecisionActionsManager');

class ScieloPreprintFactoryTest extends DatabaseTestCase
{
    private $locale = 'en_US';
    private $contextId = 1;
    private $submissionId;
    private $publicationId;
    private $title = "eXtreme Programming: A practical guide";
    private $submitter = "Don Vito Corleone";
    private $dateSubmitted = '2021-05-31 15:38:24';
    private $statusCode = STATUS_PUBLISHED;
    private $statusMessage;
    private $sectionName = "Biological Sciences";
    private $dateLastActivity = '2021-06-03 16:00:00';
    private $submissionAuthors;
    private $doi = "10.666/949494";

    public function setUp(): void
    {
        parent::setUp();
        $sectionId = $this->createSection();
        $this->submissionId = $this->createSubmission();
        $this->publicationId = $this->createPublication($sectionId);
        $this->submissionAuthors = $this->createAuthors();
        $this->statusMessage = __('submission.status.published', [], 'en_US');
        $this->addCurrentPublicationToSubmission();
    }

    protected function getAffectedTables()
    {
        return ['notes', 'submissions', 'submission_settings', 'publications', 'publication_settings',
        'users', 'user_groups', 'user_settings', 'user_group_settings', 'user_user_groups', 'event_log', 'sections',
        'section_settings', 'authors', 'author_settings', 'edit_decisions', 'stage_assignments', 'user_group_stage'];
    }

    private function createSubmission(): int
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = new Submission();
        $submission->setData('contextId', $this->contextId);
        $submission->setData('dateSubmitted', $this->dateSubmitted);
        $submission->setData('status', $this->statusCode);
        $submission->setData('locale', $this->locale);
        $submission->setData('dateLastActivity', $this->dateLastActivity);

        return $submissionDao->insertObject($submission);
    }

    private function createPublication($sectionId): int
    {
        $publicationDao = DAORegistry::getDAO('PublicationDAO');
        $publication = new Publication();
        $publication->setData('submissionId', $this->submissionId);
        $publication->setData('title', $this->title, $this->locale);
        $publication->setData('sectionId', $sectionId);
        $publication->setData('relationStatus', '1');
        $publication->setData('vorDoi', $this->doi);
        $publication->setData('status', $this->statusCode);

        return $publicationDao->insertObject($publication);
    }

    private function createSection(): int
    {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $section = new Section();
        $section->setTitle($this->sectionName, $this->locale);
        $sectionId = $sectionDao->insertObject($section);

        return $sectionId;
    }

    private function createAuthors(): array
    {
        $authorDao = DAORegistry::getDAO('AuthorDAO');
        $author1 = new Author();
        $author2 = new Author();
        $author1->setData('publicationId', $this->publicationId);
        $author2->setData('publicationId', $this->publicationId);
        $author1->setData('email', "anaalice@harvard.com");
        $author2->setData('email', "seizi.tagima@ufam.edu.br");
        $author1->setGivenName('Ana Alice', $this->locale);
        $author1->setFamilyName('Caldas Novas', $this->locale);
        $author2->setGivenName('Seizi', $this->locale);
        $author2->setFamilyName('Tagima', $this->locale);
        $author1->setAffiliation("Harvard University", $this->locale);
        $author2->setAffiliation("Amazonas Federal University", $this->locale);
        $author1->setData('country', 'US');
        $author2->setData('country', 'BR');

        $authorDao->insertObject($author1);
        $authorDao->insertObject($author2);

        return [new SubmissionAuthor("Ana Alice Caldas Novas", "United States", "Harvard University"), new SubmissionAuthor("Seizi Tagima", "Brazil", "Amazonas Federal University")];
    }

    private function addCurrentPublicationToSubmission(): void
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($this->submissionId);
        $submission->setData('currentPublicationId', $this->publicationId);
        $submissionDao->updateObject($submission);
    }

    private function createModeratorsUsers(): array
    {
        $userModerator = new User();
        $userModerator->setUsername('f4ustao');
        $userModerator->setEmail('faustosilva@noexists.com');
        $userModerator->setPassword('oloco');
        $userModerator->setGivenName("Fausto", $this->locale);
        $userModerator->setFamilyName("Silva", $this->locale);

        $secondUserModerator = new User();
        $secondUserModerator->setUsername('silvinho122');
        $secondUserModerator->setEmail('silvio@stb.com');
        $secondUserModerator->setPassword('aviaozinho');
        $secondUserModerator->setGivenName("Silvio", $this->locale);
        $secondUserModerator->setFamilyName("Santos", $this->locale);

        return [$userModerator, $secondUserModerator];
    }

    private function createStageAssignments(array $userIds, $groupId): void
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

        foreach ($userIds as $userId) {
            $stageAssignment = new StageAssignment();
            $stageAssignment->setSubmissionId($this->submissionId);
            $stageAssignment->setUserId($userId);
            $stageAssignment->setUserGroupId($groupId);
            $stageAssignment->setStageId(5);
            $stageAssignmentDao->insertObject($stageAssignment);
        }
    }

    private function createModeratorUserGroup(): int
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $moderatorUserGroupLocalizedNames = [
            'en_US'=>'moderator',
            'pt_BR'=>'moderador',
            'es_ES'=>'moderador'
        ];
        $moderatorUserGroup = new UserGroup();
        $moderatorUserGroup->setData("name", $moderatorUserGroupLocalizedNames);
        $moderatorUserGroup->setData('roleId', ROLE_ID_SUB_EDITOR);
        $moderatorUserGroup->setData('contextId', $this->contextId);

        return $userGroupDao->insertObject($moderatorUserGroup);
    }

    private function createSectionModeratorUserGroup(): int
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $sectionModeratorUserGroupLocalizedNames = [
            'en_US'=>'section moderator',
            'pt_BR'=>'moderador de área',
            'es_ES'=>'moderador de área'
        ];
        $sectionModeratorUserGroup = new UserGroup();
        $sectionModeratorUserGroup->setData('name', $sectionModeratorUserGroupLocalizedNames);
        $sectionModeratorUserGroup->setData('roleId', ROLE_ID_SUB_EDITOR);
        $sectionModeratorUserGroup->setData('contextId', $this->contextId);

        return $userGroupDao->insertObject($sectionModeratorUserGroup);
    }


    public function testSubmissionGetsFinalDecisionWithDatePosted(): void
    {
        $finalDecision = __('common.accepted', [], $this->locale);
        $finalDecisionDate = '2021-07-31';

        $publicationDao = DAORegistry::getDAO('PublicationDAO');
        $publication = $publicationDao->getById($this->publicationId);
        $publication->setData('datePublished', $finalDecisionDate);
        $publicationDao->updateObject($publication);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloPreprint->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloPreprint->getFinalDecisionDate());
    }

    public function testSubmissionGetsPublicationStatus(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($this->statusCode, $scieloPreprint->getPublicationStatus());
    }

	/**
	 * @group OPS
	 */
    public function testSubmissionGetsPublicationDOI(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($this->doi, $scieloPreprint->getPublicationDOI());
    }

    public function testSubmissionWithoutPublicationDOI(): void
    {
        $publicationDao = DAORegistry::getDAO('PublicationDAO');
        $publication = $publicationDao->getById($this->publicationId);
        $publication->setData('vorDoi', null);
        $publicationDao->updateObject($publication);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);
        $expectedResult = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI");

        $this->assertEquals($expectedResult, $scieloPreprint->getPublicationDOI());
    }

    public function testSubmissionIsPreprint(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertTrue($scieloPreprint instanceof ScieloPreprint);
    }

	/**
	 * @group OPS
	 */
    public function testSubmissionGetsModerators(): void
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $moderatorGroupId = $this->createModeratorUserGroup();

        $moderatorUsers = $this->createModeratorsUsers();
        $firstModeratorId = $userDao->insertObject($moderatorUsers[0]);
        $secondModeratorId = $userDao->insertObject($moderatorUsers[1]);

        $userGroupDao->assignUserToGroup($firstModeratorId, $moderatorGroupId);
        $userGroupDao->assignUserToGroup($secondModeratorId, $moderatorGroupId);

        $this->createStageAssignments([$firstModeratorId, $secondModeratorId], $moderatorGroupId);

        $submissionStageId = 5;
        $userGroupDao->assignGroupToStage($this->contextId, $moderatorGroupId, $submissionStageId);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $expectedModerators = $moderatorUsers[0]->getFullName() . "," . $moderatorUsers[1]->getFullName();

        $this->assertEquals($expectedModerators, $scieloPreprint->getModerators());
    }

    public function testSubmissionGetsSectionModerator(): void
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $userSectionModerator = $this->createModeratorsUsers()[0];
        $userSectionModeratorId = $userDao->insertObject($userSectionModerator);
        $sectionModeratorUserGroupId = $this->createSectionModeratorUserGroup();

        $userGroupDao->assignUserToGroup($userSectionModeratorId, $sectionModeratorUserGroupId);

        $this->createStageAssignments([$userSectionModeratorId], $sectionModeratorUserGroupId);

        $submissionStageId = 5;
        $userGroupDao->assignGroupToStage($this->contextId, $sectionModeratorUserGroupId, $submissionStageId);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($userSectionModerator->getFullName(), $scieloPreprint->getSectionModerator());
    }

    public function testSubmissionGetsNotes(): void
    {
        $noteDao = DAORegistry::getDAO('NoteDAO');
        $contentsForFirstNote = "Um breve resumo sobre a inteligência computacional";
        $contentsForSecondNote = "Algoritmos Genéticos: Implementação no jogo do dino";

        $note = $noteDao->newDataObject();
        $note->setContents($contentsForFirstNote);
        $note->setAssocType(1048585);
        $note->setAssocId($this->submissionId);
        $noteDao->insertObject($note);

        $note = $noteDao->newDataObject();
        $note->setContents($contentsForSecondNote);
        $note->setAssocType(1048585);
        $note->setAssocId($this->submissionId);
        $noteDao->insertObject($note);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);
        $expectedResult = "Note: Um breve resumo sobre a inteligência computacional. Note: Algoritmos Genéticos: Implementação no jogo do dino";

        $this->assertEquals($expectedResult, $scieloPreprint->getNotes());
    }

    public function testSubmissionDoesNotHaveNotes(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals(__('plugins.reports.scieloSubmissionsReport.warning.noNotes'), $scieloPreprint->getNotes());
    }
}
