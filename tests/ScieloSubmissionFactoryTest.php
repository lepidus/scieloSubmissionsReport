<?php
import('lib.pkp.tests.DatabaseTestCase');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('classes.journal.Section');
import('classes.article.Author');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');
import('classes.workflow.EditorDecisionActionsManager');

class ScieloSubmissionFactoryTest extends DatabaseTestCase {
    
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
        return ['notes', 'submissions', 'submission_settings', 'publications', 'publication_settings', 'users', 'user_groups', 'user_settings', 'user_group_settings', 'user_user_groups', 'event_log', 'sections', 'section_settings', 'authors', 'author_settings', 'edit_decisions', 'user_group_stage', 'stage_assignments'];
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

    private function createFinalDecision($submissionId, $decision, $dateDecided) : void {
        $editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
        $editDecisionDao->updateEditorDecision($submissionId, ['editDecisionId' => null, 'decision' => $decision, 'dateDecided' => $dateDecided, 'editorId' => 1]);
    }

    private function addCurrentPublicationToSubmission() : void {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($this->submissionId);
        $submission->setData('currentPublicationId', $this->publicationId);
        $submissionDao->updateObject($submission);
    }

    private function createSectionModeratorUserGroup() {
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

    private function createModeratorUsers() : array {
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

    private function createModeratorsStageAssignments(array $moderatorsIds, $moderatorGroupId) : void {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $stageAssignmentsIds = array();

        foreach ($moderatorsIds as $moderatorId) {
            $stageAssignment = new StageAssignment();
            $stageAssignment->setSubmissionId($this->submissionId);
            $stageAssignment->setUserId($moderatorId);
            $stageAssignment->setUserGroupId($moderatorGroupId);
            $stageAssignment->setStageId(5);            
            $stageAssignmentDao->insertObject($stageAssignment);
        }
    }

    private function updateModeratorUserGroup($moderatorGroupId) : void {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $moderatorUserGroupLocalizedNames = [
            'en_US'=>'moderator',
            'pt_BR'=>'moderador',
            'es_ES'=>'moderador'];
        $moderatorUserGroup = $userGroupDao->getById($moderatorGroupId);
        $moderatorUserGroup->setData("name", $moderatorUserGroupLocalizedNames);
        $userGroupDao->updateObject($moderatorUserGroup);
    }
    
    public function testSubmissionGetsTitle() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($this->title, $scieloSubmission->getTitle());
    }

    public function testSubmissionGetsSubmitter() : void {
        $submissionEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $userSubmitter = new User();
        $userSubmitter->setUsername('the_godfather');
        $userSubmitter->setEmail('donvito@corleone.com');
        $userSubmitter->setPassword('miaumiau');
        $userSubmitter->setGivenName("Don", $this->locale);
        $userSubmitter->setFamilyName("Vito Corleone", $this->locale);
        $userSubmitterId = $userDao->insertObject($userSubmitter);

        $submissionEvent = $submissionEventLogDao->newDataObject();
        $submissionEvent->setSubmissionId($this->submissionId);
        $submissionEvent->setEventType(SUBMISSION_LOG_SUBMISSION_SUBMIT);
        $submissionEvent->setUserId($userSubmitterId);
        $submissionEvent->setDateLogged('2021-05-28 15:19:24');
        $submissionEventLogDao->insertObject($submissionEvent);

        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application,$this->submissionId, $this->locale);

        $this->assertEquals($this->submitter, $scieloSubmission->getSubmitter());
    }

    public function testSubmissionGetsDateSubmitted() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($this->dateSubmitted, $scieloSubmission->getDateSubmitted());
    }

    public function testSubmissionGetsStatus() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($this->statusMessage, $scieloSubmission->getStatus());
    }

    public function testSubmissionGetsSectionName() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($this->sectionName, $scieloSubmission->getSection());
    }

    public function testSubmissionGetsLanguage() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($this->locale, $scieloSubmission->getLanguage());
    }

    public function testSubmissionsGetsDaysUntilStatusChange() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $dateSubmitted = new DateTime($this->dateSubmitted);
        $dateLastActivity = new DateTime($this->dateLastActivity);
        $daysUntilStatusChange = $dateLastActivity->diff($dateSubmitted)->format('%a');

        $this->assertEquals($daysUntilStatusChange, $scieloSubmission->getDaysUntilStatusChange());
    }

    public function testSubmissionGetsSubmissionAuthors() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($this->submissionAuthors, $scieloSubmission->getAuthors());
    }

    public function testSubmissionGetsFinalDecisionWithDateInitialDecline() : void {

        $finalDecisionCode = SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE;
        $finalDecision = __('common.declined', [], $this->locale);
        $finalDecisionDate = '2021-05-29 15:00:00';
        $this->createFinalDecision($this->submissionId, $finalDecisionCode, $finalDecisionDate);

        $this->application = 'ops';
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloSubmission->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloSubmission->getFinalDecisionDate());
    }

    public function testSubmissionGetsFinalDecisionWithDateDecline() : void {
        $finalDecisionCode = SUBMISSION_EDITOR_DECISION_DECLINE;
        $finalDecision = __('common.declined', [], $this->locale);
        $finalDecisionDate = '2021-04-21 15:00:00';
        $this->createFinalDecision($this->submissionId, $finalDecisionCode, $finalDecisionDate);

        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloSubmission->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloSubmission->getFinalDecisionDate());
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

    public function testSubmissionGetsFinalDecisionWithDatePostedInOPS() : void {
        $finalDecision = __('common.accepted', [], $this->locale);
        $finalDecisionDate = '2021-07-31';

        $publicationDao = DAORegistry::getDAO('PublicationDAO');
        $publication = $publicationDao->getById($this->publicationId);
        $publication->setData('datePublished', $finalDecisionDate);
        $publicationDao->updateObject($publication);

        $this->application = 'ops';
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($finalDecision, $scieloSubmission->getFinalDecision());
        $this->assertEquals($finalDecisionDate, $scieloSubmission->getFinalDecisionDate());
    }

    public function testSubmissionGetsPublicationStatusInOPS() : void {
        $this->application = 'ops';
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloPreprint = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($this->statusCode, $scieloPreprint->getPublicationStatus());
    }

    public function testSubmissionGetsPublicationDOIInOPS() : void {
        $this->application = 'ops';
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertEquals($this->doi, $scieloSubmission->getPublicationDOI());
    }

    public function testSubmissionWithoutPublicationDOIInOPS() : void {
        $publicationDao = DAORegistry::getDAO('PublicationDAO');
        $publication = $publicationDao->getById($this->publicationId);
        $publication->setData('vorDoi', NULL);
        $publicationDao->updateObject($publication);

        $this->application = 'ops';
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);
        $expectedResult = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI");

        $this->assertEquals($expectedResult, $scieloSubmission->getPublicationDOI());
    }

    public function testSubmissionIsPreprintInOPS() : void {
        $this->application = 'ops';
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);

        $this->assertTrue($scieloSubmission instanceof ScieloPreprint);
    }
    
    public function testSubmissionModeratorsInOPS() : void {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $moderatorGroupId = 3;
        $this->updateModeratorUserGroup($moderatorGroupId);
        
        $moderatorUsers = $this->createModeratorUsers();
        $firstModeratorId = $userDao->insertObject($moderatorUsers[0]);
        $secondModeratorId = $userDao->insertObject($moderatorUsers[1]);
        
        $userGroupDao->assignUserToGroup($firstModeratorId, $moderatorGroupId);
        $userGroupDao->assignUserToGroup($secondModeratorId, $moderatorGroupId);
        
        $this->createModeratorsStageAssignments([$firstModeratorId, $secondModeratorId], $moderatorGroupId);
        
        $this->application = 'ops';
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);
        
        $expectedModerators = $moderatorUsers[0]->getFullName() . "," . $moderatorUsers[1]->getFullName();
        
        $this->assertEquals($expectedModerators, $scieloSubmission->getModerators());
    }

    public function testSubmissionSectionModeratorInOPS() {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $userSectionModerator = $this->createModeratorUsers()[0];
        $userSectionModeratorId = $userDao->insertObject($userSectionModerator);
        $sectionModeratorUserGroupId = $this->createSectionModeratorUserGroup();
        
        $userGroupDao->assignUserToGroup($userSectionModeratorId, $sectionModeratorUserGroupId);
        
        $this->createModeratorsStageAssignments([$userSectionModeratorId], $sectionModeratorUserGroupId);
        
        $userGroupDao->assignGroupToStage($this->contextId, $sectionModeratorUserGroupId, 5);
        
        $this->application = 'ops';
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);
        
        $this->assertEquals($userSectionModerator->getFullName(), $scieloSubmission->getSectionModerator());
    }

    public function testSubmissionNotesInOPS() {
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

        $this->application = 'ops';
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);
        $expectedResult = "Note: Um breve resumo sobre a inteligência computacional. Note: Algoritmos Genéticos: Implementação no jogo do dino";

        $this->assertEquals($expectedResult, $scieloSubmission->getNotes());
    }
    
    public function testSubmissionDoesNotHaveNotesInOPS() {
        $this->application = 'ops';
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->application, $this->submissionId, $this->locale);
        
        $this->assertEquals(__('plugins.reports.scieloSubmissionsReport.warning.noNotes'), $scieloSubmission->getNotes());
    }
}

?>