<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

use PKP\tests\DatabaseTestCase;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\section\Section;
use APP\author\Author;
use APP\facades\Repo;
use PKP\core\Core;
use PKP\db\DAORegistry;
use APP\core\Application;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloArticleFactory;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloArticle;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionAuthor;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloArticlesDAO;
use APP\decision\Decision;
use PKP\userGroup\relationships\UserGroupStage;
use PKP\security\Role;
use PKP\submission\reviewRound\ReviewRound;
use PKP\submission\reviewAssignment\ReviewAssignment;

class ScieloArticleFactoryTest extends DatabaseTestCase
{
    private $contextId = 1;
    private $submissionId;
    private $publicationId;
    private $sectionId;
    private $sectionEditorUserGroupId;
    private $editorUserGroupId;
    private $author1Id;
    private $author2Id;
    private $firstEditorUserId;
    private $secondEditorUserId;
    private $reviewAssignmentId;
    private $stageAssignmentIds = [];

    private $locale = 'en';
    private $title = "eXtreme Programming: A practical guide";
    private $submitter = "Don Vito Corleone";
    private $dateSubmitted = '2021-05-31 15:38:24';
    private $statusCode = Submission::STATUS_PUBLISHED;
    private $statusMessage;
    private $sectionName = "Biological Sciences";
    private $dateLastActivity = '2021-06-03 16:00:00';
    private $submissionAuthors;
    private $doi = "10.666/949494";

    public function setUp(): void
    {
        parent::setUp();

        $this->sectionId = $this->createSection();
        $this->submissionId = $this->createSubmission();
        $this->publicationId = $this->createPublication();
        $this->submissionAuthors = $this->createAuthors();
        $this->statusMessage = __('submission.status.published', [], 'en');
        $this->addCurrentPublicationToSubmission();
    }

    protected function tearDown(): void
    {
        $this->clearDB();
        parent::tearDown();
    }

    private function clearDB(): void
    {
        $publication = Repo::publication()->get($this->publicationId);
        if ($publication) {
            Repo::publication()->delete($publication);
        }

        $submission = Repo::submission()->get($this->submissionId);
        if ($submission) {
            Repo::submission()->delete($submission);
        }

        $section = Repo::section()->get($this->sectionId, $this->contextId);
        if ($section) {
            Repo::section()->delete($section);
        }

        $sectionEditorUserGroup = $this->sectionEditorUserGroupId ? Repo::userGroup()->get($this->sectionEditorUserGroupId) : null;
        if ($sectionEditorUserGroup) {
            Repo::userGroup()->delete($sectionEditorUserGroup);
        }

        $editorUserGroup = $this->editorUserGroupId ? Repo::userGroup()->get($this->editorUserGroupId) : null;
        if ($editorUserGroup) {
            Repo::userGroup()->delete($editorUserGroup);
        }

        $author1 = $this->author1Id ? Repo::author()->get($this->author1Id) : null;
        if ($author1) {
            Repo::author()->delete($author1);
        }

        $author2 = $this->author2Id ? Repo::author()->get($this->author2Id) : null;
        if ($author2) {
            Repo::author()->delete($author2);
        }

        $firstEditorUser = $this->firstEditorUserId ? Repo::user()->get($this->firstEditorUserId) : null;
        if ($firstEditorUser) {
            Repo::user()->delete($firstEditorUser);
        }

        $secondEditorUser = $this->secondEditorUserId ? Repo::user()->get($this->secondEditorUserId) : null;
        if ($secondEditorUser) {
            Repo::user()->delete($secondEditorUser);
        }

        if (empty($this->stageAssignmentIds)) {
            return;
        }

        $stageAssignmentDAO = DAORegistry::getDAO('StageAssignmentDAO');
        foreach ($this->stageAssignmentIds as $stageAssignmentId) {
            $stageAssignmentDAO->getById($stageAssignmentId);
            $stageAssignmentDAO->deleteObject($stageAssignment);
        }

        if ($this->reviewAssignmentId) {
            $reviewRoundDAO = DAORegistry::getDAO('ReviewAssignmentDAO');
            $reviewAssignment = $reviewRoundDAO->getById($this->reviewAssignmentId);
            $reviewRoundDAO->deleteObject($reviewAssignment);
        }
    }

    private function createSubmission(): int
    {
        $submission = Repo::submission()->newDataObject();
        $submission->setData('contextId', $this->contextId);
        $submission->setData('dateSubmitted', $this->dateSubmitted);
        $submission->setData('status', $this->statusCode);
        $submission->setData('locale', $this->locale);
        $submission->setData('dateLastActivity', $this->dateLastActivity);

        return Repo::submission()->dao->insert($submission);
    }

    private function createPublication(): int
    {
        $publication = Repo::publication()->newDataObject();
        $publication->setData('submissionId', $this->submissionId);
        $publication->setData('title', $this->title, $this->locale);
        $publication->setData('sectionId', $this->sectionId);
        $publication->setData('relationStatus', '1');
        $publication->setData('vorDoi', $this->doi);
        $publication->setData('status', $this->statusCode);

        return Repo::publication()->dao->insert($publication);
    }

    private function createSectionEditorUserGroup(): int
    {
        $sectionEditorUserGroupLocalizedNames = [
            'en' => 'section editor',
            'pt_BR' => 'editor de seção',
            'es' => 'editor de sección'
        ];
        $sectionEditorsUserGroup = Repo::userGroup()->newDataObject();
        $sectionEditorsUserGroup->setData('name', $sectionEditorUserGroupLocalizedNames);
        $sectionEditorsUserGroup->setData('roleId', Role::ROLE_ID_SUB_EDITOR);
        $sectionEditorsUserGroup->setData('contextId', $this->contextId);
        return Repo::userGroup()->add($sectionEditorsUserGroup);
    }

    private function createEditorUserGroup(): int
    {
        $editorUserGroupLocalizedNames = [
            'en' => 'editor',
            'pt_BR' => 'editor',
            'es' => 'editor'
        ];
        $editorsUserGroup = Repo::userGroup()->newDataObject();
        $editorsUserGroup->setData('name', $editorUserGroupLocalizedNames);
        $editorsUserGroup->setData('roleId', Role::ROLE_ID_MANAGER);
        $editorsUserGroup->setData('contextId', $this->contextId);
        return Repo::userGroup()->add($editorsUserGroup);
    }

    private function createSection(): int
    {
        $section = Repo::section()->newDataObject();
        $section->setTitle($this->sectionName, $this->locale);
        $section->setAbbrev(__('section.default.abbrev'), $this->locale);
        $section->setMetaIndexed(true);
        $section->setMetaReviewed(true);
        $section->setPolicy(__('section.default.policy'), $this->locale);
        $section->setEditorRestricted(false);
        $section->setHideTitle(false);
        $section->setContextId($this->contextId);
        $sectionId = Repo::section()->add($section);

        return $sectionId;
    }

    private function createAuthors(): array
    {
        $author1 = Repo::author()->newDataObject();
        $author2 = Repo::author()->newDataObject();
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

        $this->author1Id = Repo::author()->dao->insert($author1);
        $this->author2Id = Repo::author()->dao->insert($author2);

        return [new SubmissionAuthor("Ana Alice Caldas Novas", "United States", "Harvard University"), new SubmissionAuthor("Seizi Tagima", "Brazil", "Amazonas Federal University")];
    }

    private function addCurrentPublicationToSubmission(): void
    {
        $submission = Repo::submission()->get($this->submissionId);
        Repo::submission()->edit($submission, ['currentPublicationId' => $this->publicationId]);
    }

    private function createEditorUsers(bool $isSectionEditor = false)
    {
        $firstEditorUser = Repo::user()->newDataObject();
        $firstEditorUser->setUsername('examplePeter');
        $firstEditorUser->setEmail('peter@exemple.com');
        $firstEditorUser->setPassword('examplepass');
        $firstEditorUser->setGivenName("Peter", $this->locale);
        $firstEditorUser->setFamilyName("Parker", $this->locale);
        $firstEditorUser->setDateRegistered(Core::getCurrentDate());

        $secondEditorUser = Repo::user()->newDataObject();
        $secondEditorUser->setUsername('exampleJhon');
        $secondEditorUser->setEmail('jhon@exemple.com');
        $secondEditorUser->setPassword('exemplepass');
        $secondEditorUser->setGivenName("Jhon", $this->locale);
        $secondEditorUser->setFamilyName("Carter", $this->locale);
        $secondEditorUser->setDateRegistered(Core::getCurrentDate());

        $this->firstEditorUserId = Repo::user()->add($firstEditorUser);
        $this->secondEditorUserId = Repo::user()->add($secondEditorUser);

        if ($isSectionEditor) {
            $this->sectionEditorGroupId = $this->createSectionEditorUserGroup();
            $userGroup = Repo::userGroup()->get($this->sectionEditorGroupId);
            Repo::userGroup()->assignUserToGroup($this->firstEditorUserId, $this->sectionEditorGroupId);
            $this->createStageAssignments([$this->firstEditorUserId], $this->sectionEditorGroupId);
            UserGroupStage::create([
                'contextId' => $this->contextId,
                'userGroupId' => $this->sectionEditorGroupId,
                'stageId' => 5
            ]);
            return $firstEditorUser;
        } else {
            $this->editorGroupId = $this->createEditorUserGroup();
            Repo::userGroup()->assignUserToGroup($this->firstEditorUserId, $this->editorGroupId);
            Repo::userGroup()->assignUserToGroup($this->secondEditorUserId, $this->editorGroupId);
            $this->createStageAssignments([$this->firstEditorUserId, $this->secondEditorUserId], $this->editorGroupId);
            UserGroupStage::create([
                'contextId' => $this->contextId,
                'userGroupId' => $this->editorGroupId,
                'stageId' => 5
            ]);
            return [$firstEditorUser, $secondEditorUser];
        }
    }

    private function createReviewRound($submissionId)
    {
        $reviewRoundDao = DAORegistry::getDAO('ReviewRoundDAO');
        $stageId = 2;
        $round = 1;

        $reviewRound = $reviewRoundDao->build(
            $submissionId,
            $stageId,
            $round,
            ReviewRound::REVIEW_ROUND_STATUS_PENDING_REVIEWERS
        );

        return $reviewRound;
    }

    private function createDecision($submissionId, $decision, $dateDecided, $reviewRoundId = null): void
    {

        $params = [
            'decision' => $decision,
            'submissionId' => $submissionId,
            'dateDecided' => $dateDecided,
            'editorId' => 1,
            'reviewRoundId' => $reviewRoundId,
        ];

        $decision = Repo::decision()->newDataObject($params);
        $decisionId = Repo::decision()->dao->insert($decision);
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
            $stageAssignmentIds[] = $stageAssignmentDao->insertObject($stageAssignment);
        }
    }

    /**
     * @group OJS
    */
    public function testSubmissionIsArticle(): void
    {
        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertTrue($scieloArticle instanceof ScieloArticle);
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsEditors(): void
    {
        $editorsUsers = $this->createEditorUsers();

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $expectedEditors = $editorsUsers[0]->getFullName() . "," . $editorsUsers[1]->getFullName();
        $this->assertEquals($expectedEditors, $scieloArticle->getJournalEditors());
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsNoEditors(): void
    {
        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals(__("plugins.reports.scieloSubmissionsReport.warning.noEditors"), $scieloArticle->getJournalEditors());
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsSectionEditor(): void
    {
        $sectionEditorsUser = $this->createEditorUsers(true);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($sectionEditorsUser->getFullName(), $scieloArticle->getSectionEditor());
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsNoSectionEditor(): void
    {
        $this->sectionEditorGroupId = $this->createSectionEditorUserGroup();
        UserGroupStage::create([
            'contextId' => $this->contextId,
            'userGroupId' => $this->sectionEditorGroupId,
            'stageId' => 5
        ]);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $noEditorMessage = __("plugins.reports.scieloSubmissionsReport.warning.noEditors");
        $this->assertEquals($noEditorMessage, $scieloArticle->getSectionEditor());
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsLastDecision(): void
    {
        $decision = SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE;
        $this->createDecision($this->submissionId, $decision, date(Core::getCurrentDate()));

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $scieloArticlesDAO = new ScieloArticlesDAO();
        $this->assertEquals($scieloArticlesDAO->getDecisionMessage($decision), $scieloArticle->getLastDecision());
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsLastDecisionOfNewRound(): void
    {
        $reviewRound = $this->createReviewRound($this->submissionId);

        $decision = SUBMISSION_EDITOR_DECISION_NEW_ROUND;
        $this->createDecision($this->submissionId, $decision, date(Core::getCurrentDate()), $reviewRound->getId());

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $scieloArticlesDAO = new ScieloArticlesDAO();
        $this->assertEquals($scieloArticlesDAO->getDecisionMessage($decision), $scieloArticle->getLastDecision());
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsNoLastDecision(): void
    {
        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals(__("plugins.reports.scieloSubmissionsReport.warning.noDecision"), $scieloArticle->getLastDecision());
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsReviews(): void
    {
        $recommendation = ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT;
        $reviewRound = $this->createReviewRound($this->submissionId);

        $reviewAssignment = new ReviewAssignment();
        $reviewAssignment->setRecommendation($recommendation);
        $reviewAssignment->setSubmissionId($this->submissionId);
        $reviewAssignment->setReviewerId(1);
        $reviewAssignment->setDateAssigned(Core::getCurrentDate());
        $reviewAssignment->setStageId(1);
        $reviewAssignment->setRound(1);
        $reviewAssignment->setReviewRoundId($reviewRound->getId());
        $reviewAssignment->setDateCompleted(Core::getCurrentDate());

        $this->reviewAssignmentId = DAORegistry::getDAO('ReviewAssignmentDAO')->insertObject($reviewAssignment);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($reviewAssignment->getLocalizedRecommendation(), $scieloArticle->getReviews());
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsFinalDecisionWithDateInitialDecline(): void
    {
        $finalDecisionCode = SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE;
        $finalDecision = __('common.declined', [], $this->locale);
        $finalDecisionDate = '2021-05-29';
        $this->createDecision($this->submissionId, $finalDecisionCode, $finalDecisionDate);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloArticle->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloArticle->getFinalDecisionDate());
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsFinalDecisionWithDateDecline(): void
    {
        $finalDecisionCode = Decision::DECLINE;
        $finalDecision = __('common.declined', [], $this->locale);
        $finalDecisionDate = '2021-04-21';
        $this->createDecision($this->submissionId, $finalDecisionCode, $finalDecisionDate);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloArticle->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloArticle->getFinalDecisionDate());
    }

    /**
     * @group OJS
    */
    public function testSubmissionGetsFinalDecisionWithDateAccept(): void
    {
        $finalDecisionCode = Decision::ACCEPT;
        $finalDecision = __('common.accepted', [], $this->locale);
        $finalDecisionDate = '2021-07-07';
        $this->createDecision($this->submissionId, $finalDecisionCode, $finalDecisionDate);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloArticle->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloArticle->getFinalDecisionDate());
    }
}
