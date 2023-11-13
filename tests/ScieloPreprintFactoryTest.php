<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

use APP\core\Application;
use APP\decision\Decision;
use APP\facades\Repo;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloPreprint;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloPreprintFactory;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionAuthor;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionStats;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\Facades\DB;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\log\event\PKPSubmissionEventLogEntry;
use PKP\statistics\PKPStatisticsHelper;
use PKP\tests\DatabaseTestCase;
use PKP\userGroup\relationships\UserGroupStage;

class ScieloPreprintFactoryTest extends DatabaseTestCase
{
    protected const WORKFLOW_STAGE_ID_SUBMISSION = 5;

    private $locale = 'en';
    private $contextId = 1;
    private $submissionId;
    private $publicationId;
    private $sectionId;
    private $title = 'eXtreme Programming: A practical guide';
    private $submitter = 'Don Vito Corleone';
    private $dateSubmitted = '2021-05-31 15:38:24';
    private $statusCode = Submission::STATUS_PUBLISHED;
    private $statusMessage;
    private $sectionName = 'Biological Sciences';
    private $dateLastActivity = '2021-06-03 16:00:00';
    private $submissionAuthors;
    private $vorDoi = '10.666/949494';
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
        $submission = Repo::submission()->newDataObject();
        $submission->setData('contextId', $this->contextId);
        $submission->setData('dateSubmitted', $this->dateSubmitted);
        $submission->setData('status', $statusCode);
        $submission->setData('locale', $this->locale);
        $submission->setData('dateLastActivity', $this->dateLastActivity);

        return Repo::submission()->dao->insert($submission);
    }

    private function createPublication($submissionId, $datePublished = null): int
    {
        $publication = Repo::publication()->newDataObject();
        $publication->setData('submissionId', $submissionId);
        $publication->setData('title', $this->title, $this->locale);
        $publication->setData('sectionId', $this->sectionId);
        $publication->setData('relationStatus', $this->relationStatus);
        $publication->setData('vorDoi', $this->vorDoi);
        if (!is_null($datePublished)) {
            $publication->setData('datePublished', $datePublished);
        }

        return Repo::publication()->dao->insert($publication);
    }

    private function createDecision($submissionId, $decision, $dateDecided): void
    {
        $params = [
            'decision' => $decision,
            'submissionId' => $submissionId,
            'dateDecided' => $dateDecided,
            'editorId' => 1,
        ];

        $decision = Repo::decision()->newDataObject($params);
        $decisionId = Repo::decision()->dao->insert($decision);
    }

    private function createSection(): int
    {
        $section = Repo::section()->newDataObject();
        $section->setTitle($this->sectionName, $this->locale);
        $section->setAbbrev(__('section.default.abbrev'), $this->locale);
        $section->setPath(__('section.default.path'));
        $section->setMetaIndexed(true);
        $section->setMetaReviewed(true);
        $section->setPolicy(__('section.default.policy'), $this->locale);
        $section->setEditorRestricted(false);
        $section->setHideTitle(false);
        $section->setContextId($this->contextId);
        $sectionId = Repo::section()->dao->insert($section);

        return $sectionId;
    }

    private function createAuthors(): array
    {
        $author1 = Repo::author()->newDataObject();
        $author2 = Repo::author()->newDataObject();
        $author1->setData('publicationId', $this->publicationId);
        $author2->setData('publicationId', $this->publicationId);
        $author1->setData('email', 'anaalice@harvard.com');
        $author2->setData('email', 'seizi.tagima@ufam.edu.br');
        $author1->setGivenName('Ana Alice', $this->locale);
        $author1->setFamilyName('Caldas Novas', $this->locale);
        $author2->setGivenName('Seizi', $this->locale);
        $author2->setFamilyName('Tagima', $this->locale);
        $author1->setAffiliation('Harvard University', $this->locale);
        $author2->setAffiliation('Amazonas Federal University', $this->locale);
        $author1->setData('country', 'US');
        $author2->setData('country', 'BR');

        Repo::author()->dao->insert($author1);
        Repo::author()->dao->insert($author2);

        return [
            new SubmissionAuthor('Ana Alice Caldas Novas', 'United States', 'Harvard University'),
            new SubmissionAuthor('Seizi Tagima', 'Brazil', 'Amazonas Federal University')
        ];
    }

    private function addCurrentPublicationToSubmission($submissionId, $publicationId): void
    {
        $submission = Repo::submission()->get($submissionId);
        $submission->setData('currentPublicationId', $publicationId);
        Repo::submission()->dao->update($submission);
    }

    private function createSubmitter(): int
    {
        $userSubmitter = Repo::user()->newDataObject();
        $userSubmitter->setUsername('the_godfather');
        $userSubmitter->setEmail('donvito@corleone.com');
        $userSubmitter->setPassword('miaumiau');
        $userSubmitter->setCountry('BR');
        $userSubmitter->setGivenName('Don', $this->locale);
        $userSubmitter->setFamilyName('Vito Corleone', $this->locale);
        $userSubmitter->setDateRegistered(Core::getCurrentDate());
        $userSubmitterId = Repo::user()->dao->insert($userSubmitter);

        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => Application::ASSOC_TYPE_SUBMISSION,
            'assocId' => $this->submissionId,
            'eventType' => PKPSubmissionEventLogEntry::SUBMISSION_LOG_SUBMISSION_SUBMIT,
            'userId' => $userSubmitterId,
            'dateLogged' => $this->dateSubmitted
        ]);
        Repo::eventLog()->add($eventLog);

        return $userSubmitterId;
    }

    private function createResponsibleUsers(): array
    {
        $userResponsible = Repo::user()->newDataObject();
        $userResponsible->setUsername('f4ustao');
        $userResponsible->setEmail('faustosilva@noexists.com');
        $userResponsible->setPassword('oloco');
        $userResponsible->setGivenName('Fausto', $this->locale);
        $userResponsible->setFamilyName('Silva', $this->locale);
        $userResponsible->setDateRegistered(Core::getCurrentDate());

        $secondUserResponsible = Repo::user()->newDataObject();
        $secondUserResponsible->setUsername('silvinho122');
        $secondUserResponsible->setEmail('silvio@stb.com');
        $secondUserResponsible->setPassword('aviaozinho');
        $secondUserResponsible->setGivenName('Silvio', $this->locale);
        $secondUserResponsible->setFamilyName('Santos', $this->locale);
        $secondUserResponsible->setDateRegistered(Core::getCurrentDate());

        return [$userResponsible, $secondUserResponsible];
    }

    private function createStageAssignments(array $userIds, $groupId): void
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

        foreach ($userIds as $userId) {
            $stageAssignment = $stageAssignmentDao->newDataObject();
            $stageAssignment->setSubmissionId($this->submissionId);
            $stageAssignment->setUserId($userId);
            $stageAssignment->setUserGroupId($groupId);
            $stageAssignment->setStageId(5);
            $stageAssignmentDao->insertObject($stageAssignment);
        }
    }

    private function createResponsiblesUserGroup(): int
    {
        $responsiblesUserGroupLocalizedAbbrev = [
            'en' => 'resp',
            'pt_BR' => 'resp',
            'es' => 'resp'
        ];
        $responsiblesUserGroup = Repo::userGroup()->newDataObject();
        $responsiblesUserGroup->setData('abbrev', $responsiblesUserGroupLocalizedAbbrev);
        $responsiblesUserGroup->setData('roleId', ROLE_ID_SUB_EDITOR);
        $responsiblesUserGroup->setData('contextId', $this->contextId);

        return Repo::userGroup()->add($responsiblesUserGroup);
    }

    private function createSectionModeratorUserGroup(): int
    {
        $sectionModeratorUserGroupLocalizedAbbrev = [
            'en' => 'am',
            'pt_BR' => 'ma',
            'es' => 'ma'
        ];
        $sectionModeratorUserGroup = Repo::userGroup()->newDataObject();
        $sectionModeratorUserGroup->setData('abbrev', $sectionModeratorUserGroupLocalizedAbbrev);
        $sectionModeratorUserGroup->setData('roleId', ROLE_ID_SUB_EDITOR);
        $sectionModeratorUserGroup->setData('contextId', $this->contextId);

        return Repo::userGroup()->add($sectionModeratorUserGroup);
    }

    private function createScieloJournalUserGroup(): int
    {
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
        $scieloJournalUserGroup = Repo::userGroup()->newDataObject();
        $scieloJournalUserGroup->setData('name', $scieloJournalUserGroupLocalizedNames);
        $scieloJournalUserGroup->setData('abbrev', $scieloJournalUserGroupLocalizedAbbrev);
        $scieloJournalUserGroup->setData('roleId', ROLE_ID_SUB_EDITOR);
        $scieloJournalUserGroup->setData('contextId', $this->contextId);

        return Repo::userGroup()->add($scieloJournalUserGroup);
    }

    private function createMetrics(): void
    {
        DB::table('metrics_submission')->insert([
            'load_id' => 'test_events_20220520',
            'context_id' => $this->contextId,
            'submission_id' => $this->submissionId,
            'assoc_type' => Application::ASSOC_TYPE_SUBMISSION,
            'date' => '20220520',
            'metric' => $this->abstractViews,
        ]);
        DB::table('metrics_submission')->insert([
            'load_id' => 'test_events_20220520',
            'context_id' => $this->contextId,
            'submission_id' => $this->submissionId,
            'assoc_type' => Application::ASSOC_TYPE_SUBMISSION_FILE,
            'file_type' => PKPStatisticsHelper::STATISTICS_FILE_TYPE_PDF,
            'date' => '20220520',
            'metric' => $this->pdfViews,
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
        $datePosted = '2021-08-27';
        $submissionId = $this->createSubmission(Submission::STATUS_PUBLISHED);
        $publicationId = $this->createPublication($submissionId, $datePosted);
        $this->addCurrentPublicationToSubmission($submissionId, $publicationId);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($submissionId, $this->locale);

        $relationsMap = [
            Publication::PUBLICATION_RELATION_NONE => 'publication.relation.none',
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
        $publication = Repo::publication()->get($this->publicationId);
        $publication->setData('vorDoi', null);
        Repo::publication()->dao->update($publication);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);
        $expectedResult = __('plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI');

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
        $submitterId = $this->createSubmitter();
        $scieloJournalGroupId = $this->createScieloJournalUserGroup();
        Repo::userGroup()->assignUserToGroup(
            $submitterId,
            $scieloJournalGroupId
        );

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals(__('common.yes'), $scieloPreprint->getSubmitterIsScieloJournal());
    }

    /**
     * @group OPS
     */
    public function testSubmissionGetsResponsibles(): void
    {
        $responsiblesGroupId = $this->createResponsiblesUserGroup();

        $responsibleUsers = $this->createResponsibleUsers();
        $firstResponsibleId = Repo::user()->dao->insert($responsibleUsers[0]);
        $secondResponsibleId = Repo::user()->dao->insert($responsibleUsers[1]);

        Repo::userGroup()->assignUserToGroup($firstResponsibleId, $responsiblesGroupId);
        Repo::userGroup()->assignUserToGroup($secondResponsibleId, $responsiblesGroupId);

        $this->createStageAssignments([$firstResponsibleId, $secondResponsibleId], $responsiblesGroupId);

        UserGroupStage::create([
            'contextId' => $this->contextId,
            'userGroupId' => $responsiblesGroupId,
            'stageId' => self::WORKFLOW_STAGE_ID_SUBMISSION
        ]);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $expectedResponsibles = $responsibleUsers[0]->getFullName() . ',' . $responsibleUsers[1]->getFullName();

        $this->assertEquals($expectedResponsibles, $scieloPreprint->getResponsibles());
    }

    /**
     * @group OPS
    */
    public function testSubmissionGetsSectionModerator(): void
    {
        $userSectionModerator = $this->createResponsibleUsers()[0];
        $userSectionModeratorId = Repo::user()->add($userSectionModerator);
        $sectionModeratorUserGroupId = $this->createSectionModeratorUserGroup();

        Repo::userGroup()->assignUserToGroup($userSectionModeratorId, $sectionModeratorUserGroupId);

        $this->createStageAssignments([$userSectionModeratorId], $sectionModeratorUserGroupId);

        UserGroupStage::create([
            'contextId' => $this->contextId,
            'userGroupId' => $sectionModeratorUserGroupId,
            'stageId' => self::WORKFLOW_STAGE_ID_SUBMISSION
        ]);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($userSectionModerator->getFullName(), $scieloPreprint->getSectionModerators());
    }

    /**
     * @group OPS
    */
    public function testSubmissionGetsNotes(): void
    {
        $userSectionModerator = $this->createResponsibleUsers()[0];
        $userSectionModeratorId = Repo::user()->add($userSectionModerator);

        $noteDao = DAORegistry::getDAO('NoteDAO');
        $contentsForFirstNote = 'Um breve resumo sobre a inteligência computacional';
        $contentsForSecondNote = 'Algoritmos Genéticos: Implementação no jogo do dino';

        $note = $noteDao->newDataObject();
        $note->setUserId($userSectionModeratorId);
        $note->setContents($contentsForFirstNote);
        $note->setAssocType(1048585);
        $note->setAssocId($this->submissionId);
        $noteDao->insertObject($note);

        $note = $noteDao->newDataObject();
        $note->setUserId($userSectionModeratorId);
        $note->setContents($contentsForSecondNote);
        $note->setAssocType(1048585);
        $note->setAssocId($this->submissionId);
        $noteDao->insertObject($note);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);
        $expectedResult = 'Note: Um breve resumo sobre a inteligência computacional. Note: Algoritmos Genéticos: Implementação no jogo do dino';

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
