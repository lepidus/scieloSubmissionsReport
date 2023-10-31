<?php

use PKP\tests\DatabaseTestCase;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\section\Section;
use APP\author\Author;
use APP\facades\Repo;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloArticleFactory;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloArticle;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionAuthor;
use APP\decision\Decision;

class ScieloArticleFactoryTest extends DatabaseTestCase
{
    private $contextId = 1;
    private $submissionId;
    private $publicationId;
    private $sectionId;

    private $locale = 'en_US';
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
        $sectionId = $this->createSection();
        $this->submissionId = $this->createSubmission();
        $this->publicationId = $this->createPublication($sectionId);
        $this->submissionAuthors = $this->createAuthors();
        $this->statusMessage = __('submission.status.published', [], 'en_US');
        $this->addCurrentPublicationToSubmission();
    }

    private function clearDB()
    {

    }

    protected function getAffectedTables()
    {
        return ['notes', 'submissions', 'submission_settings', 'publications', 'publication_settings',
        'users', 'user_groups', 'user_settings', 'user_group_settings', 'user_user_groups', 'event_log', 'sections',
        'section_settings', 'authors', 'author_settings', 'edit_decisions', 'stage_assignments', 'user_group_stage', 'review_assignments'];
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

    private function createPublication($sectionId): int
    {
        $publication = Repo::publication()->newDataObject();
        $publication->setData('submissionId', $this->submissionId);
        $publication->setData('title', $this->title, $this->locale);
        $publication->setData('sectionId', $sectionId);
        $publication->setData('relationStatus', '1');
        $publication->setData('vorDoi', $this->doi);
        $publication->setData('status', $this->statusCode);

        return Repo::publication()->dao->insert($publication);
    }

    private function createSectionEditorUserGroup(): int
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $sectionEditorUserGroupLocalizedNames = [
            'en_US' => 'section editor',
            'pt_BR' => 'editor de seção',
            'es_ES' => 'editor de sección'];
        $sectionEditorsUserGroup = new UserGroup();
        $sectionEditorsUserGroup->setData('name', $sectionEditorUserGroupLocalizedNames);
        $sectionEditorsUserGroup->setData('roleId', ROLE_ID_SUB_EDITOR);
        $sectionEditorsUserGroup->setData('contextId', $this->contextId);
        return $userGroupDao->insertObject($sectionEditorsUserGroup);
    }

    private function createEditorUserGroup(): int
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $editorUserGroupLocalizedNames = [
            'en_US' => 'editor',
            'pt_BR' => 'editor',
            'es_ES' => 'editor'];
        $editorsUserGroup = new UserGroup();
        $editorsUserGroup->setData('name', $editorUserGroupLocalizedNames);
        $editorsUserGroup->setData('roleId', ROLE_ID_MANAGER);
        $editorsUserGroup->setData('contextId', $this->contextId);
        return $userGroupDao->insertObject($editorsUserGroup);
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

        Repo::author()->dao->insert($author1);
        Repo::author()->dao->insert($author2);

        return [new SubmissionAuthor("Ana Alice Caldas Novas", "United States", "Harvard University"), new SubmissionAuthor("Seizi Tagima", "Brazil", "Amazonas Federal University")];
    }

    private function addCurrentPublicationToSubmission(): void
    {
        $submission = Repo::submission()->get($this->submissionId);
        Repo::submission()->edit($submission, ['currentPublicationId' => $this->publicationId]);
    }

    private function createEditorUsers(bool $isSectionEditor = false)
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $firstEditorUser = new User();
        $firstEditorUser->setUsername('examplePeter');
        $firstEditorUser->setEmail('peter@exemple.com');
        $firstEditorUser->setPassword('examplepass');
        $firstEditorUser->setGivenName("Peter", $this->locale);
        $firstEditorUser->setFamilyName("Parker", $this->locale);

        $secondEditorUser = new User();
        $secondEditorUser->setUsername('exampleJhon');
        $secondEditorUser->setEmail('jhon@exemple.com');
        $secondEditorUser->setPassword('exemplepass');
        $secondEditorUser->setGivenName("Jhon", $this->locale);
        $secondEditorUser->setFamilyName("Carter", $this->locale);

        $firstEditorUserId = $userDao->insertObject($firstEditorUser);
        $secondEditorUserId = $userDao->insertObject($secondEditorUser);

        if ($isSectionEditor) {
            $sectionEditorGroupId = $this->createSectionEditorUserGroup();
            $userGroupDao->assignUserToGroup($firstEditorUserId, $sectionEditorGroupId);
            $this->createStageAssignments([$firstEditorUserId], $sectionEditorGroupId);
            $userGroupDao->assignGroupToStage($this->contextId, $sectionEditorGroupId, 5);
            return $firstEditorUser;
        } else {
            $editorGroupId = $this->createEditorUserGroup();
            $userGroupDao->assignUserToGroup($firstEditorUserId, $editorGroupId);
            $userGroupDao->assignUserToGroup($secondEditorUserId, $editorGroupId);
            $this->createStageAssignments([$firstEditorUserId, $secondEditorUserId], $editorGroupId);
            $userGroupDao->assignGroupToStage($this->contextId, $editorGroupId, 5);
            return [$firstEditorUser, $secondEditorUser];
        }
    }

    private function createDecision($submissionId, $decision, $dateDecided): void
    {
        $editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
        $editDecisionDao->updateEditorDecision($submissionId, ['editDecisionId' => null, 'decision' => $decision, 'dateDecided' => $dateDecided, 'editorId' => 1]);
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
        $sectionEditorGroupId = $this->createSectionEditorUserGroup();
        DAORegistry::getDAO('UserGroupDAO')->assignGroupToStage($this->contextId, $sectionEditorGroupId, 5);

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
        $decision = SUBMISSION_EDITOR_DECISION_NEW_ROUND;
        $this->createDecision($this->submissionId, $decision, date(Core::getCurrentDate()));

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
        $recommendation = SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT;

        $reviewAssignment = new ReviewAssignment();
        $reviewAssignment->setRecommendation($recommendation);
        $reviewAssignment->setSubmissionId($this->submissionId);
        $reviewAssignment->setReviewerId(1);
        $reviewAssignment->setDateAssigned(Core::getCurrentDate());
        $reviewAssignment->setStageId(1);
        $reviewAssignment->setRound(1);
        $reviewAssignment->setReviewRoundId(1);
        $reviewAssignment->setDateCompleted(Core::getCurrentDate());

        DAORegistry::getDAO('ReviewAssignmentDAO')->insertObject($reviewAssignment);

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
        $finalDecisionCode = SUBMISSION_EDITOR_DECISION_DECLINE;
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
        $finalDecisionCode = SUBMISSION_EDITOR_DECISION_ACCEPT;
        $finalDecision = __('common.accepted', [], $this->locale);
        $finalDecisionDate = '2021-07-07';
        $this->createDecision($this->submissionId, $finalDecisionCode, $finalDecisionDate);

        $articleFactory = new ScieloArticleFactory();
        $scieloArticle = $articleFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloArticle->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloArticle->getFinalDecisionDate());
    }
}
