<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests\preprint;

use APP\core\Application;
use APP\decision\Decision;
use APP\facades\Repo;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\Facades\DB;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\log\event\PKPSubmissionEventLogEntry;
use PKP\security\Role;
use PKP\stageAssignment\StageAssignment;
use PKP\note\Note;
use PKP\statistics\PKPStatisticsHelper;
use PKP\tests\DatabaseTestCase;
use PKP\userGroup\UserGroup;
use PKP\userGroup\relationships\UserGroupStage;
use APP\plugins\reports\scieloSubmissionsReport\classes\preprint\ScieloPreprint;
use APP\plugins\reports\scieloSubmissionsReport\classes\preprint\ScieloPreprintFactory;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionAuthor;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionStats;

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
    private $authorIds = [];
    private $submitterId;
    private $responsibleUserIds = [];
    private $userGroupIds = [];
    private $stageAssignmentIds = [];
    private $additionalSubmissionIds = [];

    public function setUp(): void
    {
        parent::setUp();

        if (Application::getName() != 'ops') {
            $this->markTestSkipped(
                'Not OPS',
            );
        }
        $this->mockRequest();

        $this->sectionId = $this->createSection();
        $this->submissionId = $this->createSubmission($this->statusCode);
        $this->publicationId = $this->createPublication($this->submissionId);
        $this->submissionAuthors = $this->createAuthors();
        $this->statusMessage = __('submission.status.published', [], 'en');
        $this->relationStatus = Publication::PUBLICATION_RELATION_PUBLISHED;
        $this->addCurrentPublicationToSubmission($this->submissionId, $this->publicationId);
    }

    protected function tearDown(): void
    {
        $this->clearDB();
        parent::tearDown();
    }

    private function clearDB(): void
    {
        foreach ($this->additionalSubmissionIds as $subId) {
            $submission = Repo::submission()->get($subId);
            if ($submission) {
                Repo::publication()->dao->deleteById($submission->getData('currentPublicationId'));
                Repo::submission()->delete($submission);
            }
        }

        $submission = Repo::submission()->get($this->submissionId);
        if ($submission) {
            Repo::publication()->dao->deleteById($submission->getData('currentPublicationId'));
            Repo::submission()->delete($submission);
        }

        $section = Repo::section()->get($this->sectionId, $this->contextId);
        if ($section) {
            Repo::section()->delete($section);
        }

        foreach ($this->userGroupIds as $groupId) {
            UserGroup::destroy($groupId);
        }

        foreach ($this->authorIds as $authorId) {
            $author = Repo::author()->get($authorId);
            if ($author) {
                Repo::author()->delete($author);
            }
        }

        if ($this->submitterId) {
            $user = Repo::user()->get($this->submitterId, true);
            if ($user) {
                Repo::user()->delete($user);
            }
        }

        if (!empty($this->responsibleUserIds)) {
            foreach ($this->responsibleUserIds as $userId) {
                $user = Repo::user()->get($userId, true);
                if ($user) {
                    Repo::user()->delete($user);
                }
            }
        }

        if (empty($this->stageAssignmentIds)) {
            return;
        }

        foreach ($this->stageAssignmentIds as $stageAssignmentId) {
            StageAssignment::destroy($stageAssignmentId);
        }
    }

    private function createSubmission($statusCode): int
    {
        $submission = Repo::submission()->newDataObject();
        $submission->setAllData([
            'contextId' => $this->contextId,
            'dateSubmitted' => $this->dateSubmitted,
            'status' => $statusCode,
            'locale' => $this->locale,
            'dateLastActivity' => $this->dateLastActivity,
        ]);

        return Repo::submission()->dao->insert($submission);
    }

    private function createPublication($submissionId, $datePublished = null): int
    {
        $publication = Repo::publication()->newDataObject();
        $publication->setAllData([
            'submissionId' => $submissionId,
            'title' => [$this->locale => $this->title],
            'sectionId' => $this->sectionId,
            'relationStatus' => $this->relationStatus,
            'vorDoi' => $this->vorDoi,
            'datePublished' => $datePublished,
        ]);

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
        $section->setAllData([
            'title' => [$this->locale => $this->sectionName],
            'abbrev' => [$this->locale => __('section.default.abbrev')],
            'path' => __('section.default.path'),
            'metaIndexed' => true,
            'metaReviewed' => true,
            'policy' => [$this->locale => __('section.default.policy')],
            'editorRestricted' => false,
            'hideTitle' => false,
            'contextId' => $this->contextId,
        ]);
        $sectionId = Repo::section()->add($section);

        return $sectionId;
    }

    private function createAuthors(): array
    {
        $author1 = Repo::author()->newDataObject();
        $author2 = Repo::author()->newDataObject();
        $author1->setAllData([
            'publicationId' => $this->publicationId,
            'email' => 'anaalice@harvard.com',
            'givenName' => [$this->locale => 'Ana Alice'],
            'familyName' => [$this->locale => 'Caldas Novas'],
            'country' => 'US',
        ]);
        $author2->setAllData([
            'publicationId' => $this->publicationId,
            'email' => 'seizi.tagima@ufam.edu.br',
            'givenName' => [$this->locale => 'Seizi'],
            'familyName' => [$this->locale => 'Tagima'],
            'country' => 'BR',
        ]);

        $this->authorIds[] = Repo::author()->dao->insert($author1);
        $this->authorIds[] = Repo::author()->dao->insert($author2);

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
        $userSubmitter->setAllData([
            'userName' => 'the_godfather',
            'email' => 'donvito@corleone.com',
            'password' => 'miaumiau',
            'country' => 'BR',
            'givenName' => [$this->locale => 'Don'],
            'familyName' => [$this->locale => 'Vito Corleone'],
            'dateRegistered' => Core::getCurrentDate(),
        ]);
        $this->submitterId = Repo::user()->add($userSubmitter);

        $eventLog = Repo::eventLog()->newDataObject([
            'assocType' => Application::ASSOC_TYPE_SUBMISSION,
            'assocId' => $this->submissionId,
            'eventType' => PKPSubmissionEventLogEntry::SUBMISSION_LOG_SUBMISSION_SUBMIT,
            'userId' => $this->submitterId,
            'dateLogged' => $this->dateSubmitted
        ]);
        Repo::eventLog()->add($eventLog);

        return $this->submitterId;
    }

    private function createResponsibleUsers(): array
    {
        $userResponsible = Repo::user()->newDataObject();
        $userResponsible->setAllData([
            'userName' => 'f4ustao',
            'email' => 'faustosilva@noexists.com',
            'password' => 'oloco',
            'givenName' => [$this->locale => 'Fausto'],
            'familyName' => [$this->locale => 'Silva'],
            'dateRegistered' => Core::getCurrentDate(),
        ]);

        $secondUserResponsible = Repo::user()->newDataObject();
        $secondUserResponsible->setAllData([
            'userName' => 'silvinho122',
            'email' => 'silvio@stb.com',
            'password' => 'aviaozinho',
            'givenName' => [$this->locale => 'Silvio'],
            'familyName' => [$this->locale => 'Santos'],
            'dateRegistered' => Core::getCurrentDate(),
        ]);

        return [$userResponsible, $secondUserResponsible];
    }

    private function createStageAssignments(array $userIds, $groupId): void
    {
        foreach ($userIds as $userId) {
            StageAssignment::create([
                'submissionId' => $this->submissionId,
                'userId' => $userId,
                'userGroupId' => $groupId,
                'dateAssigned' => Core::getCurrentDate(),
            ]);
        }
    }

    private function createResponsiblesUserGroup(): int
    {
        $responsiblesUserGroupLocalizedAbbrev = [
            'en' => 'resp',
            'pt_BR' => 'resp',
            'es' => 'resp'
        ];
        $responsiblesUserGroup = UserGroup::create([
            'abbrev' => $responsiblesUserGroupLocalizedAbbrev,
            'roleId' => Role::ROLE_ID_SUB_EDITOR,
            'contextId' => $this->contextId,
        ]);
        $this->userGroupIds[] = $responsiblesUserGroup->id;

        return $responsiblesUserGroup->id;
    }

    private function createSectionModeratorUserGroup(): int
    {
        $sectionModeratorUserGroupLocalizedAbbrev = [
            'en' => 'am',
            'pt_BR' => 'ma',
            'es' => 'ma'
        ];
        $sectionModeratorUserGroup = UserGroup::create([
            'abbrev' => $sectionModeratorUserGroupLocalizedAbbrev,
            'roleId' => Role::ROLE_ID_SUB_EDITOR,
            'contextId' => $this->contextId,
        ]);
        $this->userGroupIds[] = $sectionModeratorUserGroup->id;

        return $sectionModeratorUserGroup->id;
    }

    private function createScieloJournalUserGroup(): int
    {
        $scieloJournalUserGroupLocalizedNames = [
            'en' => 'SciELO Journal',
            'pt_BR' => 'Periódico SciELO',
            'es' => 'Revista SciELO'
        ];
        $scieloJournalUserGroupLocalizedAbbrev = [
            'en' => 'SciELO',
            'pt_BR' => 'SciELO',
            'es' => 'SciELO'
        ];
        $scieloJournalUserGroup = UserGroup::create([
            'name' => $scieloJournalUserGroupLocalizedNames,
            'abbrev' => $scieloJournalUserGroupLocalizedAbbrev,
            'roleId' => Role::ROLE_ID_SUB_EDITOR,
            'contextId' => $this->contextId,
        ]);
        $this->userGroupIds[] = $scieloJournalUserGroup->id;

        return $scieloJournalUserGroup->id;
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

    public function testSubmissionGetsFinalDecisionWithDatePosted(): void
    {
        $finalDecision = __('common.accepted', [], $this->locale);
        $finalDecisionDate = '2021-07-31';

        $submissionId = $this->createSubmission(Submission::STATUS_PUBLISHED);
        $this->additionalSubmissionIds[] = $submissionId;
        $publicationId = $this->createPublication($submissionId, $finalDecisionDate);
        $this->addCurrentPublicationToSubmission($submissionId, $publicationId);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloPreprint->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloPreprint->getFinalDecisionDate());
    }

    public function testSubmissionDecliningIsFinalDecisionEvenWhenHasPostedDate(): void
    {
        $datePosted = '2021-08-27';
        $finalDecision = __('common.declined', [], $this->locale);
        $finalDecisionDate = '2021-08-30';

        $submissionId = $this->createSubmission(Submission::STATUS_DECLINED);
        $this->additionalSubmissionIds[] = $submissionId;
        $publicationId = $this->createPublication($submissionId, $datePosted);
        $this->addCurrentPublicationToSubmission($submissionId, $publicationId);
        $this->createDecision($submissionId, Decision::DECLINE, $finalDecisionDate);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloPreprint->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloPreprint->getFinalDecisionDate());
    }

    public function testSubmissionGetsPublicationStatus(): void
    {
        $datePosted = '2021-08-27';
        $submissionId = $this->createSubmission(Submission::STATUS_PUBLISHED);
        $this->additionalSubmissionIds[] = $submissionId;
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

    public function testSubmissionGetsPublicationDoi(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($this->vorDoi, $scieloPreprint->getPublicationDOI());
    }

    public function testSubmissionWithoutPublicationDoi(): void
    {
        $publication = Repo::publication()->get($this->publicationId);
        $publication->setData('vorDoi', null);
        Repo::publication()->dao->update($publication);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);
        $expectedResult = __('plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI');

        $this->assertEquals($expectedResult, $scieloPreprint->getPublicationDOI());
    }

    public function testSubmissionIsPreprint(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertTrue($scieloPreprint instanceof ScieloPreprint);
    }

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

    public function testSubmissionGetsResponsibles(): void
    {
        $responsiblesGroupId = $this->createResponsiblesUserGroup();

        $responsibleUsers = $this->createResponsibleUsers();
        $firstResponsibleId = Repo::user()->add($responsibleUsers[0]);
        $secondResponsibleId = Repo::user()->add($responsibleUsers[1]);
        $this->responsibleUserIds[] = $firstResponsibleId;
        $this->responsibleUserIds[] = $secondResponsibleId;

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

    public function testSubmissionGetsSectionModerator(): void
    {
        $userSectionModerator = $this->createResponsibleUsers()[0];
        $userSectionModeratorId = Repo::user()->add($userSectionModerator);
        $this->responsibleUserIds[] = $userSectionModeratorId;
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

    public function testSubmissionGetsNotes(): void
    {
        $userSectionModerator = $this->createResponsibleUsers()[0];
        $userSectionModeratorId = Repo::user()->add($userSectionModerator);
        $this->responsibleUserIds[] = $userSectionModeratorId;

        $contentsForFirstNote = 'Um breve resumo sobre a inteligência computacional';
        $contentsForSecondNote = 'Algoritmos Genéticos: Implementação no jogo do dino';

        Note::create([
            'userId' => $userSectionModeratorId,
            'contents' => $contentsForFirstNote,
            'assocType' => 1048585,
            'assocId' => $this->submissionId,
        ]);

        Note::create([
            'userId' => $userSectionModeratorId,
            'contents' => $contentsForSecondNote,
            'assocType' => 1048585,
            'assocId' => $this->submissionId,
        ]);

        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);
        $expectedResult = 'Note: Um breve resumo sobre a inteligência computacional. Note: Algoritmos Genéticos: Implementação no jogo do dino';

        $this->assertEquals($expectedResult, $scieloPreprint->getNotes());
    }

    public function testSubmissionDoesNotHaveNotes(): void
    {
        $preprintFactory = new ScieloPreprintFactory();
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals(__('plugins.reports.scieloSubmissionsReport.warning.noNotes'), $scieloPreprint->getNotes());
    }

    public function testSubmissionGetsStats(): void
    {
        $this->createMetrics();
        $includeViews = true;
        $preprintFactory = new ScieloPreprintFactory($includeViews);
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $expectedStats = new SubmissionStats($this->abstractViews, $this->pdfViews);
        $this->assertEquals($expectedStats, $scieloPreprint->getStats());
    }

    public function testSubmissionDoesntGetViews(): void
    {
        $this->createMetrics();
        $includeViews = false;
        $preprintFactory = new ScieloPreprintFactory($includeViews);
        $scieloPreprint = $preprintFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertNull($scieloPreprint->getStats());
    }
}
