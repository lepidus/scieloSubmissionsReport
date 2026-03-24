<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests\article;

use PKP\tests\DatabaseTestCase;
use APP\facades\Repo;
use APP\core\Application;
use PKP\core\Core;
use APP\decision\Decision;
use APP\submission\Submission;
use PKP\db\DAORegistry;
use PKP\security\Role;
use PKP\stageAssignment\StageAssignment;
use PKP\userGroup\UserGroup;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\submission\reviewRound\ReviewRound;
use PKP\userGroup\relationships\UserGroupStage;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloArticle;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloArticleFactory;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloArticlesDAO;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionAuthor;

class ScieloArticleFactoryTest extends DatabaseTestCase
{
    private $contextId = 1;
    private $submissionId;
    private $publicationId;
    private $sectionId;
    private $editorUserGroupId;
    private $author1Id;
    private $author2Id;
    private $editorsUsersIds;
    private $reviewAssignmentId;
    private $stageAssignmentIds = [];

    private $locale = 'en';
    private $title = 'eXtreme Programming: A practical guide';
    private $submitter = 'Don Vito Corleone';
    private $dateSubmitted = '2021-05-31 15:38:24';
    private $statusCode = Submission::STATUS_PUBLISHED;
    private $statusMessage;
    private $sectionName = 'Biological Sciences';
    private $dateLastActivity = '2021-06-03 16:00:00';
    private $submissionAuthors;
    private $doi = '10.666/949494';

    public function setUp(): void
    {
        parent::setUp();

        if (Application::getName() != 'ojs2') {
            $this->markTestSkipped(
                'Not OJS',
            );
        }

        $this->editorsUsersIds = [];
        $this->sectionId = $this->createSection();
        $this->createSubmission();
        $this->submissionAuthors = $this->createAuthors();
        $this->statusMessage = __('submission.status.published', [], 'en');
    }

    protected function tearDown(): void
    {
        $this->clearDB();
        parent::tearDown();
    }

    private function clearDB(): void
    {
        $submission = Repo::submission()->get($this->submissionId);
        if ($submission) {
            Repo::publication()->dao->deleteById($submission->getData('currentPublicationId'));
            Repo::submission()->delete($submission);
        }

        $section = Repo::section()->get($this->sectionId, $this->contextId);
        if ($section) {
            Repo::section()->delete($section);
        }

        UserGroup::destroy($this->editorUserGroupId);

        $author1 = $this->author1Id ? Repo::author()->get($this->author1Id) : null;
        if ($author1) {
            Repo::author()->delete($author1);
        }

        $author2 = $this->author2Id ? Repo::author()->get($this->author2Id) : null;
        if ($author2) {
            Repo::author()->delete($author2);
        }

        if (!empty($this->editorsUsersIds)) {
            foreach ($this->editorsUsersIds as $editorUserId) {
                $editorUser = Repo::user()->get($editorUserId, true);
                if ($editorUser) {
                    Repo::user()->delete($editorUser);
                }
            }
        }

        if (empty($this->stageAssignmentIds)) {
            return;
        }

        foreach ($this->stageAssignmentIds as $stageAssignmentId) {
            StageAssignment::destroy($stageAssignmentId);
        }

        if ($this->reviewAssignmentId) {
            $reviewAssignment = Repo::reviewAssignment()->get($this->reviewAssignmentId);
            Repo::reviewAssignment()->delete($reviewAssignment);
        }
    }

    private function createSubmission()
    {
        $context = Application::get()->getContextDAO()->getById($this->contextId);

        $submission = Repo::submission()->newDataObject();
        $submission->setAllData([
            'contextId' => $this->contextId,
            'dateSubmitted' => $this->dateSubmitted,
            'status' => $this->statusCode,
            'locale' => $this->locale,
            'dateLastActivity' => $this->dateLastActivity,
        ]);

        $publication = Repo::publication()->newDataObject();
        $publication->setAllData([
            'title' => $this->title ? [$this->locale => $this->title] : null,
            'sectionId' =>  $this->sectionId,
            'relationStatus' =>  '1',
            'vorDoi' =>  $this->doi,
            'status' =>  $this->statusCode
        ]);

        $this->submissionId = Repo::submission()->add($submission, $publication, $context);

        $submission = Repo::submission()->get($this->submissionId);
        $this->publicationId = $submission->getData('currentPublicationId');
    }

    private function createSectionEditorUserGroup(): int
    {
        $sectionEditorUserGroupLocalizedNames = [
            'en' => 'section editor',
            'pt_BR' => 'editor de seção',
            'es' => 'editor de sección'
        ];

        $sectionEditorUserGroup = UserGroup::create([
            'name' => $sectionEditorUserGroupLocalizedNames,
            'roleId' => Role::ROLE_ID_SUB_EDITOR,
            'contextId' => $this->contextId
        ]);

        return $sectionEditorUserGroup->id;
    }

    private function createJournalEditorUserGroup(): int
    {
        $editorUserGroupLocalizedNames = [
            'en' => 'Journal editor',
            'pt_BR' => 'Editor da revista',
            'es' => 'Editor/a de la revista'
        ];
        $editorsUserGroup = UserGroup::create([
            'name' => $editorUserGroupLocalizedNames,
            'roleId' => Role::ROLE_ID_MANAGER,
            'contextId' => $this->contextId
        ]);
        return $editorsUserGroup->id;
    }

    private function createSection(): int
    {
        $section = Repo::section()->newDataObject();
        $section->setAllData([
            'title' => [$this->locale => $this->sectionName],
            'abbrev' => [$this->locale => __('section.default.abbrev')],
            'metaIndexed' => true,
            'metaReviewed' => true,
            'policy' => [$this->locale => __('section.default.policy')],
            'editorRestricted' => false,
            'hideTitle' => false,
            'contextId' => $this->contextId
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
            'country' => 'US'
        ]);
        $author2->setAllData([
            'publicationId' => $this->publicationId,
            'email' => 'seizi.tagima@ufam.edu.br',
            'givenName' => [$this->locale => 'Seizi'],
            'familyName' => [$this->locale => 'Tagima'],
            'country' => 'BR'
        ]);

        $this->author1Id = Repo::author()->dao->insert($author1);
        $this->author2Id = Repo::author()->dao->insert($author2);

        return [new SubmissionAuthor('Ana Alice Caldas Novas', 'United States', 'Harvard University'), new SubmissionAuthor('Seizi Tagima', 'Brazil', 'Amazonas Federal University')];
    }

    private function createEditorUsers(array $editorsUsersData, bool $asSectionEditors = false)
    {
        $editorsUsers = [];
        foreach ($editorsUsersData as $editorUserData) {
            $editorUser = Repo::user()->newDataObject();
            $editorUser->setAllData($editorUserData);
            $editorsUsers[] = $editorUser;
        }

        $this->editorUserGroupId = $asSectionEditors
            ? $this->createSectionEditorUserGroup()
            : $this->createJournalEditorUserGroup();

        foreach ($editorsUsers as $editorUser) {
            $editorUserId = Repo::user()->add($editorUser);

            Repo::userGroup()->assignUserToGroup($editorUserId, $this->editorUserGroupId);
            $this->createStageAssignments([$editorUserId], $this->editorUserGroupId);
            $this->editorsUsersIds[] = $editorUserId;
        }

        UserGroupStage::create([
            'contextId' => $this->contextId,
            'userGroupId' => $this->editorUserGroupId,
            'stageId' => 5
        ]);

        return $editorsUsers;
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
        foreach ($userIds as $userId) {
            StageAssignment::create([
                'submissionId' => $this->submissionId,
                'userId' => $userId,
                'userGroupId' => $groupId,
                'dateAssigned' => Core::getCurrentDate()
            ]);
        }
    }

    public function testSubmissionIsArticle(): void
    {
        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertTrue($scieloArticle instanceof ScieloArticle);
    }

    public function testArticleCreationWhenItHasNoTitles(): void
    {
        $this->clearDB();
        $this->title = null;
        $this->sectionId = $this->createSection();
        $this->createSubmission();
        $this->submissionAuthors = $this->createAuthors();

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertTrue($scieloArticle instanceof ScieloArticle);
        $this->assertEquals(__('plugins.reports.scieloSubmissionsReport.warning.noTitles'), $scieloArticle->getTitle());
    }

    public function testSubmissionGetsJournalEditors(): void
    {
        $journalEditorsData = [
            [
                'userName' => 'examplePeter',
                'email' => 'peter@example.com',
                'password' => 'examplepass',
                'givenName' => [$this->locale => "Peter"],
                'familyName' => [$this->locale => "Parker"],
                'dateRegistered' => Core::getCurrentDate()
            ],
            [
                'userName' => 'exampleJhon',
                'email' => 'jhon@example.com',
                'password' => 'examplepass',
                'givenName' => [$this->locale => "Jhon"],
                'familyName' => [$this->locale => "Carter"],
                'dateRegistered' => Core::getCurrentDate()
            ]
        ];
        $editorsUsers = $this->createEditorUsers($journalEditorsData);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $expectedEditors = $editorsUsers[0]->getFullName() . ',' . $editorsUsers[1]->getFullName();
        $this->assertEquals($expectedEditors, $scieloArticle->getJournalEditors());
    }

    public function testSubmissionGetsDisabledJournalEditors(): void
    {
        $journalEditorsData = [
            [
                'userName' => 'examplePeter',
                'email' => 'peter@example.com',
                'password' => 'examplepass',
                'givenName' => [$this->locale => "Peter"],
                'familyName' => [$this->locale => "Parker"],
                'dateRegistered' => Core::getCurrentDate()
            ],
            [
                'userName' => 'exampleCharles',
                'email' => 'charles@example.com',
                'password' => 'examplepass',
                'givenName' => [$this->locale => "Charles"],
                'familyName' => [$this->locale => "Xavier"],
                'dateRegistered' => Core::getCurrentDate(),
                'disabled' => true
            ]
        ];
        $editorsUsers = $this->createEditorUsers($journalEditorsData);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $expectedEditors = $editorsUsers[0]->getFullName()
            . "," . $editorsUsers[1]->getFullName();
        $this->assertEquals($expectedEditors, $scieloArticle->getJournalEditors());
    }

    public function testSubmissionGetsNoJournalEditors(): void
    {
        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals(__('plugins.reports.scieloSubmissionsReport.warning.noEditors'), $scieloArticle->getJournalEditors());
    }

    public function testSubmissionGetsSectionEditor(): void
    {
        $sectionEditorData = [
            'userName' => 'exampleJhon',
            'email' => 'jhon@example.com',
            'password' => 'examplepass',
            'givenName' => [$this->locale => "Jhon"],
            'familyName' => [$this->locale => "Carter"],
            'dateRegistered' => Core::getCurrentDate()
        ];
        $sectionEditorsUser = $this->createEditorUsers([$sectionEditorData], true)[0];

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($sectionEditorsUser->getFullName(), $scieloArticle->getSectionEditor());
    }

    public function testSubmissionGetsDisabledSectionEditor(): void
    {
        $sectionEditorData = [
            'userName' => 'exampleCharles',
            'email' => 'charles@example.com',
            'password' => 'examplepass',
            'givenName' => [$this->locale => "Charles"],
            'familyName' => [$this->locale => "Xavier"],
            'dateRegistered' => Core::getCurrentDate(),
            'disabled' => true
        ];
        $sectionEditorsUser = $this->createEditorUsers([$sectionEditorData], true)[0];

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($sectionEditorsUser->getFullName(), $scieloArticle->getSectionEditor());
    }

    public function testSubmissionGetsNoSectionEditor(): void
    {
        $this->editorUserGroupId = $this->createSectionEditorUserGroup();
        UserGroupStage::create([
            'contextId' => $this->contextId,
            'userGroupId' => $this->editorUserGroupId,
            'stageId' => 5
        ]);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $noEditorMessage = __('plugins.reports.scieloSubmissionsReport.warning.noEditors');
        $this->assertEquals($noEditorMessage, $scieloArticle->getSectionEditor());
    }

    public function testSubmissionGetsLastDecision(): void
    {
        $decision = Decision::INITIAL_DECLINE;
        $this->createDecision($this->submissionId, $decision, date(Core::getCurrentDate()));

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $scieloArticlesDAO = new ScieloArticlesDAO();
        $this->assertEquals($scieloArticlesDAO->getDecisionMessage($decision), $scieloArticle->getLastDecision());
    }

    public function testSubmissionGetsLastDecisionOfNewRound(): void
    {
        $reviewRound = $this->createReviewRound($this->submissionId);

        $decision = Decision::NEW_EXTERNAL_ROUND;
        $this->createDecision($this->submissionId, $decision, date(Core::getCurrentDate()), $reviewRound->getId());

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $scieloArticlesDAO = new ScieloArticlesDAO();
        $this->assertEquals($scieloArticlesDAO->getDecisionMessage($decision), $scieloArticle->getLastDecision());
    }

    public function testSubmissionGetsNoLastDecision(): void
    {
        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals(__('plugins.reports.scieloSubmissionsReport.warning.noDecision'), $scieloArticle->getLastDecision());
    }

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

        $this->reviewAssignmentId = Repo::reviewAssignment()->add($reviewAssignment);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($reviewAssignment->getLocalizedRecommendation(), $scieloArticle->getReviews());
    }

    public function testSubmissionGetsFinalDecisionWithDateInitialDecline(): void
    {
        $finalDecisionCode = Decision::INITIAL_DECLINE;
        $finalDecision = __('common.declined', [], $this->locale);
        $finalDecisionDate = '2021-05-29';
        $this->createDecision($this->submissionId, $finalDecisionCode, $finalDecisionDate);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloArticle->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloArticle->getFinalDecisionDate());
    }

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
