<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

use PKP\tests\DatabaseTestCase;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\section\Section;
use APP\author\Author;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloPreprintFactory;
use APP\decision\Decision;
use Illuminate\Database\Capsule\Manager as Capsule;

class ScieloPreprintFactoryTest extends DatabaseTestCase
{
    protected const WORKFLOW_STAGE_ID_SUBMISSION = 5;

    private $locale = 'en';
    private $contextId = 1;
    private $submissionId;
    private $publicationId;
    private $sectionId;
    private $title = "eXtreme Programming: A practical guide";
    private $submitter = "Don Vito Corleone";
    private $dateSubmitted = '2021-05-31 15:38:24';
    private $statusCode = Submission::STATUS_PUBLISHED;
    private $statusMessage;
    private $sectionName = "Biological Sciences";
    private $dateLastActivity = '2021-06-03 16:00:00';
    private $submissionAuthors;
    private $vorDoi = "10.666/949494";
    private $relationStatus;
    private $abstractViews = 10;
    private $pdfViews = 21;

    public function setUp(): void
    {
        parent::setUp();

        $application = Application::get();
        $applicationName = $application->getName();

        if ($applicationName != 'ops') {
            $this->markTestSkipped(
                'Not OPS',
            );
        }

        $this->sectionId = $this->createSection();
        $this->submissionId = $this->createSubmission($this->statusCode);
        $this->publicationId = $this->createPublication($this->submissionId);
        $this->submissionAuthors = $this->createAuthors();
        $this->statusMessage = __('submission.status.published', [], 'en');
        $this->relationStatus = Publication::PUBLICATION_RELATION_PUBLISHED;
        $this->addCurrentPublicationToSubmission($this->submissionId, $this->publicationId);
    }

    protected function getAffectedTables()
    {
        return ['notes', 'submissions', 'submission_settings', 'publications', 'publication_settings',
        'users', 'user_groups', 'user_settings', 'user_group_settings', 'user_user_groups', 'event_log', 'sections',
        'section_settings', 'authors', 'author_settings', 'edit_decisions', 'stage_assignments', 'user_group_stage'];
    }

    private function createSubmission($statusCode): int
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = new Submission();
        $submission->setData('contextId', $this->contextId);
        $submission->setData('dateSubmitted', $this->dateSubmitted);
        $submission->setData('status', $statusCode);
        $submission->setData('locale', $this->locale);
        $submission->setData('dateLastActivity', $this->dateLastActivity);

        return $submissionDao->insertObject($submission);
    }

    private function createPublication($submissionId, $datePublished = null): int
    {
        $publicationDao = DAORegistry::getDAO('PublicationDAO');
        $publication = new Publication();
        $publication->setData('submissionId', $submissionId);
        $publication->setData('title', $this->title, $this->locale);
        $publication->setData('sectionId', $this->sectionId);
        $publication->setData('relationStatus', $this->relationStatus);
        $publication->setData('vorDoi', $this->vorDoi);
        if (!is_null($datePublished)) {
            $publication->setData('datePublished', $datePublished);
        }

        return $publicationDao->insertObject($publication);
    }

    private function createDecision($submissionId, $decision, $dateDecided): void
    {
        $editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
        $editDecisionDao->updateEditorDecision($submissionId, ['editDecisionId' => null, 'decision' => $decision, 'dateDecided' => $dateDecided, 'editorId' => 1]);
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

    private function addCurrentPublicationToSubmission($submissionId, $publicationId): void
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($submissionId);
        $submission->setData('currentPublicationId', $publicationId);
        $submissionDao->updateObject($submission);
    }

    private function createSubmitter(): int
    {
        $submissionEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $userSubmitter = new User();
        $userSubmitter->setUsername('the_godfather');
        $userSubmitter->setEmail('donvito@corleone.com');
        $userSubmitter->setPassword('miaumiau');
        $userSubmitter->setCountry('BR');
        $userSubmitter->setGivenName("Don", $this->locale);
        $userSubmitter->setFamilyName("Vito Corleone", $this->locale);
        $userSubmitterId = $userDao->insertObject($userSubmitter);

        $submissionEvent = $submissionEventLogDao->newDataObject();
        $submissionEvent->setSubmissionId($this->submissionId);
        $submissionEvent->setEventType(SUBMISSION_LOG_SUBMISSION_SUBMIT);
        $submissionEvent->setUserId($userSubmitterId);
        $submissionEvent->setDateLogged($this->dateSubmitted);
        $submissionEventLogDao->insertObject($submissionEvent);

        return $userSubmitterId;
    }

    private function createResponsibleUsers(): array
    {
        $userResponsible = new User();
        $userResponsible->setUsername('f4ustao');
        $userResponsible->setEmail('faustosilva@noexists.com');
        $userResponsible->setPassword('oloco');
        $userResponsible->setGivenName("Fausto", $this->locale);
        $userResponsible->setFamilyName("Silva", $this->locale);

        $secondUserResponsible = new User();
        $secondUserResponsible->setUsername('silvinho122');
        $secondUserResponsible->setEmail('silvio@stb.com');
        $secondUserResponsible->setPassword('aviaozinho');
        $secondUserResponsible->setGivenName("Silvio", $this->locale);
        $secondUserResponsible->setFamilyName("Santos", $this->locale);

        return [$userResponsible, $secondUserResponsible];
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

    private function createResponsiblesUserGroup(): int
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $responsiblesUserGroupLocalizedAbbrev = [
            'en' => 'resp',
            'pt_BR' => 'resp',
            'es_ES' => 'resp'
        ];
        $responsiblesUserGroup = new UserGroup();
        $responsiblesUserGroup->setData("abbrev", $responsiblesUserGroupLocalizedAbbrev);
        $responsiblesUserGroup->setData('roleId', ROLE_ID_SUB_EDITOR);
        $responsiblesUserGroup->setData('contextId', $this->contextId);

        return $userGroupDao->insertObject($responsiblesUserGroup);
    }

    private function createSectionModeratorUserGroup(): int
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $sectionModeratorUserGroupLocalizedAbbrev = [
            'en' => 'am',
            'pt_BR' => 'ma',
            'es_ES' => 'ma'
        ];
        $sectionModeratorUserGroup = new UserGroup();
        $sectionModeratorUserGroup->setData('abbrev', $sectionModeratorUserGroupLocalizedAbbrev);
        $sectionModeratorUserGroup->setData('roleId', ROLE_ID_SUB_EDITOR);
        $sectionModeratorUserGroup->setData('contextId', $this->contextId);

        return $userGroupDao->insertObject($sectionModeratorUserGroup);
    }

    private function createScieloJournalUserGroup(): int
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $scieloJournalUserGroupLocalizedNames = [
            'en' => 'SciELO Journal',
            'pt_BR' => 'Periódico SciELO',
            'es_ES' => 'Revista SciELO'
        ];
        $scieloJournalUserGroupLocalizedAbbrev = [
            'en' => 'SciELO',
            'pt_BR' => 'SciELO',
            'es_ES' => 'SciELO'
        ];
        $scieloJournalUserGroup = new UserGroup();
        $scieloJournalUserGroup->setData('name', $scieloJournalUserGroupLocalizedNames);
        $scieloJournalUserGroup->setData('abbrev', $scieloJournalUserGroupLocalizedAbbrev);
        $scieloJournalUserGroup->setData('roleId', ROLE_ID_SUB_EDITOR);
        $scieloJournalUserGroup->setData('contextId', $this->contextId);

        return $userGroupDao->insertObject($scieloJournalUserGroup);
    }

    private function createMetrics(): void
    {
        Capsule::table('metrics')->insert([
            'load_id' => 'usage_events_20210520.log',
            'context_id' => $this->contextId,
            'assoc_type' => ASSOC_TYPE_SUBMISSION,
            'assoc_id' => $this->submissionId,
            'submission_id' => $this->submissionId,
            'metric_type' => METRIC_TYPE_COUNTER,
            'metric' => $this->abstractViews,
            'day' => '20220520'
        ]);
        Capsule::table('metrics')->insert([
            'load_id' => 'usage_events_20210520.log',
            'context_id' => $this->contextId,
            'assoc_type' => ASSOC_TYPE_SUBMISSION_FILE,
            'file_type' => STATISTICS_FILE_TYPE_PDF,
            'assoc_id' => 1,
            'submission_id' => $this->submissionId,
            'metric_type' => METRIC_TYPE_COUNTER,
            'metric' => $this->pdfViews,
            'day' => '20220520'
        ]);
    }

    /**
     * @group OPS
    */
    public function testSubmissionGetsFinalDecisionWithDatePosted(): void
    {
        $finalDecision = __('common.accepted', [], $this->locale);
        $finalDecisionDate = '2021-07-31';

        $submissionId = $this->createSubmission(Submission::STATUS_PUBLISHED);
        $publicationId = $this->createPublication($submissionId, $finalDecisionDate);
        $this->addCurrentPublicationToSubmission($submissionId, $publicationId);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloPreprint->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloPreprint->getFinalDecisionDate());
    }

    /**
     * @group OPS
    */
    public function testSubmissionDecliningIsFinalDecisionEvenWhenHasPostedDate(): void
    {
        $datePosted = '2021-08-27';
        $finalDecision = __('common.declined', [], $this->locale);
        $finalDecisionDate = '2021-08-30';

        $submissionId = $this->createSubmission(STATUS_DECLINED);
        $publicationId = $this->createPublication($submissionId, $datePosted);
        $this->addCurrentPublicationToSubmission($submissionId, $publicationId);
        $this->createDecision($submissionId, Decision::DECLINE, $finalDecisionDate);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloPreprint->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloPreprint->getFinalDecisionDate());
    }

    /**
     * @group OPS
    */
    public function testSubmissionGetsPublicationStatus(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $relationsMap = [
            Publication::PUBLICATION_RELATION_NONE => 'publication.relation.none',
            Publication::PUBLICATION_RELATION_SUBMITTED => 'publication.relation.submitted',
            Publication::PUBLICATION_RELATION_PUBLISHED => 'publication.relation.published'
        ];
        $expectedPublicationStatus = __($relationsMap[$this->relationStatus]);

        $this->assertEquals($expectedPublicationStatus, $scieloPreprint->getPublicationStatus());
    }

    /**
     * @group OPS
    */
    public function testSubmissionGetsPublicationDOI(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($this->vorDoi, $scieloPreprint->getPublicationDOI());
    }

    /**
     * @group OPS
    */
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

    /**
     * @group OPS
    */
    public function testSubmissionIsPreprint(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertTrue($scieloPreprint instanceof ScieloPreprint);
    }

    /**
     * @group OPS
     */
    public function testSubmissionGetsIfUserIsScieloJournal(): void
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $submitterId = $this->createSubmitter();
        $scieloJournalGroupId = $this->createScieloJournalUserGroup();
        $userGroupDao->assignUserToGroup($submitterId, $scieloJournalGroupId);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals(__("common.yes"), $scieloPreprint->getSubmitterIsScieloJournal());
    }

    /**
     * @group OPS
     */
    public function testSubmissionGetsResponsibles(): void
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $responsiblesGroupId = $this->createResponsiblesUserGroup();

        $responsibleUsers = $this->createResponsibleUsers();
        $firstResponsibleId = $userDao->insertObject($responsibleUsers[0]);
        $secondResponsibleId = $userDao->insertObject($responsibleUsers[1]);

        $userGroupDao->assignUserToGroup($firstResponsibleId, $responsiblesGroupId);
        $userGroupDao->assignUserToGroup($secondResponsibleId, $responsiblesGroupId);

        $this->createStageAssignments([$firstResponsibleId, $secondResponsibleId], $responsiblesGroupId);

        $userGroupDao->assignGroupToStage($this->contextId, $responsiblesGroupId, self::WORKFLOW_STAGE_ID_SUBMISSION);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $expectedResponsibles = $responsibleUsers[0]->getFullName() . "," . $responsibleUsers[1]->getFullName();

        $this->assertEquals($expectedResponsibles, $scieloPreprint->getResponsibles());
    }

    /**
     * @group OPS
    */
    public function testSubmissionGetsSectionModerator(): void
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $userSectionModerator = $this->createResponsibleUsers()[0];
        $userSectionModeratorId = $userDao->insertObject($userSectionModerator);
        $sectionModeratorUserGroupId = $this->createSectionModeratorUserGroup();

        $userGroupDao->assignUserToGroup($userSectionModeratorId, $sectionModeratorUserGroupId);

        $this->createStageAssignments([$userSectionModeratorId], $sectionModeratorUserGroupId);

        $userGroupDao->assignGroupToStage($this->contextId, $sectionModeratorUserGroupId, self::WORKFLOW_STAGE_ID_SUBMISSION);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($userSectionModerator->getFullName(), $scieloPreprint->getSectionModerators());
    }

    /**
     * @group OPS
    */
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

    /**
     * @group OPS
    */
    public function testSubmissionDoesNotHaveNotes(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals(__('plugins.reports.scieloSubmissionsReport.warning.noNotes'), $scieloPreprint->getNotes());
    }

    /**
     * @group OPS
    */
    public function testSubmissionGetsStats(): void
    {
        $this->createMetrics();
        $includeViews = true;
        $preprintFactory = new ScieloPreprintFactory($includeViews);
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $expectedStats = new SubmissionStats($this->abstractViews, $this->pdfViews);
        $this->assertEquals($expectedStats, $scieloPreprint->getStats());
    }

    /**
     * @group OPS
    */
    public function testSubmissionDoesntGetViews(): void
    {
        $this->createMetrics();
        $includeViews = false;
        $preprintFactory = new ScieloPreprintFactory($includeViews);
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertNull($scieloPreprint->getStats());
    }
}
