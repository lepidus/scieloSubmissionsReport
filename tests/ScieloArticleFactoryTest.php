<?php
import('lib.pkp.tests.DatabaseTestCase');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('classes.journal.Section');
import('classes.article.Author');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');
import('classes.workflow.EditorDecisionActionsManager');

class ScieloArticleFactoryTest extends DatabaseTestCase {
    private $application = 'ojs';
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

    public function setUp() : void {
        parent::setUp();
        $sectionId = $this->createSection();
        $this->submissionId = $this->createSubmission();
        $this->publicationId = $this->createPublication($sectionId);
        $this->submissionAuthors = $this->createAuthors();
        $this->statusMessage = __('submission.status.published', [], 'en_US');
        $this->addCurrentPublicationToSubmission();
    }

    protected function getAffectedTables() {
        return ['notes', 'submissions', 'submission_settings', 'publications', 'publication_settings', 'users', 'user_groups', 'user_settings', 'user_group_settings', 'user_user_groups', 'event_log', 'sections', 'section_settings', 'authors', 'author_settings', 'edit_decisions', 'stage_assignments', 'user_group_stage'];
    }

    private function createSubmission() : int {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = new Submission();
        $submission->setData('contextId', $this->contextId);
        $submission->setData('dateSubmitted', $this->dateSubmitted);
        $submission->setData('status', $this->statusCode);
        $submission->setData('locale', $this->locale);
        $submission->setData('dateLastActivity', $this->dateLastActivity);
        
        return $submissionDao->insertObject($submission);
    }

    private function createPublication($sectionId) : int {
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

    private function createEditorUserGroup() : int {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $editorUserGroupLocalizedNames = [
            'en_US'=>'editor',
            'pt_BR'=>'editor',
            'es_ES'=>'editor'];
        $editorsUserGroup = new UserGroup();
        $editorsUserGroup->setData('name', $editorUserGroupLocalizedNames);
        $editorsUserGroup->setData('roleId', ROLE_ID_MANAGER);
        $editorsUserGroup->setData('contextId', $this->contextId);
        return $userGroupDao->insertObject($editorsUserGroup);
    }

    private function createSection() : int {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $section = new Section();
        $section->setTitle($this->sectionName, $this->locale);
        $sectionId = $sectionDao->insertObject($section);

        return $sectionId;
    }

    private function createAuthors() : array {
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

    private function addCurrentPublicationToSubmission() : void {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($this->submissionId);
        $submission->setData('currentPublicationId', $this->publicationId);
        $submissionDao->updateObject($submission);
    }

    private function createUsers() : array {
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

    private function createFinalDecision($submissionId, $decision, $dateDecided) : void {
        $editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
        $editDecisionDao->updateEditorDecision($submissionId, ['editDecisionId' => null, 'decision' => $decision, 'dateDecided' => $dateDecided, 'editorId' => 1]);
    }

    private function createStageAssignments(array $userIds, $groupId) : void {
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

    public function testSubmissionGetsFinalDecisionWithDateAcceptInOJS() : void {
        $finalDecisionCode = SUBMISSION_EDITOR_DECISION_ACCEPT;
        $finalDecision = __('common.accepted', [], $this->locale);
        $finalDecisionDate = '2021-06-30 17:31:00';
        $this->createFinalDecision($this->submissionId, $finalDecisionCode, $finalDecisionDate);

        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloSubmission->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloSubmission->getFinalDecisionDate());
    }

    public function testSubmissionIsArticleInOJS() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertTrue($scieloSubmission instanceof ScieloArticle);
    }
}
?>