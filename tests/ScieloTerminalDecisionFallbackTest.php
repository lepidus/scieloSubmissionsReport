<?php

import('lib.pkp.tests.DatabaseTestCase');
import('classes.submission.Submission');
import('classes.log.SubmissionEventLogEntry');
import('classes.workflow.EditorDecisionActionsManager');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloArticlesDAO');

use Illuminate\Database\Capsule\Manager as Capsule;

class ScieloTerminalDecisionFallbackTest extends DatabaseTestCase
{
    private $locale = 'en_US';
    private $contextId = 1;
    private $submissionId;

    public function setUp(): void
    {
        parent::setUp();
        $this->submissionId = $this->createSubmission(STATUS_DECLINED);
    }

    protected function getAffectedTables()
    {
        return [
            'edit_decisions',
            'event_log',
            'submissions'
        ];
    }

    private function createSubmission(
        int $status,
        int $stageId = WORKFLOW_STAGE_ID_SUBMISSION,
        int $submissionProgress = 0
    ): int {
        $submission = new Submission();
        $submission->setData('contextId', $this->contextId);
        $submission->setData('status', $status);
        $submission->setData('locale', $this->locale);
        $submission->setData('stageId', $stageId);
        $submission->setData('submissionProgress', $submissionProgress);

        $submissionId = DAORegistry::getDAO('SubmissionDAO')->insertObject($submission);
        Capsule::table('event_log')
            ->where('assoc_type', ASSOC_TYPE_SUBMISSION)
            ->where('assoc_id', $submissionId)
            ->delete();
        Capsule::table('edit_decisions')
            ->where('submission_id', $submissionId)
            ->delete();

        return $submissionId;
    }

    private function createArchivedEvent(string $dateLogged): void
    {
        $this->createEvent(
            SUBMISSION_LOG_EDITOR_ARCHIVE,
            'log.editor.archived',
            $dateLogged
        );
    }

    private function createEvent(int $eventType, string $message, string $dateLogged): void
    {
        Capsule::table('event_log')->insert([
            'assoc_type' => ASSOC_TYPE_SUBMISSION,
            'assoc_id' => $this->submissionId,
            'user_id' => 1,
            'date_logged' => $dateLogged,
            'event_type' => $eventType,
            'message' => $message,
            'is_translated' => 0
        ]);
    }

    private function createDecision(int $decision, string $dateDecided): void
    {
        DAORegistry::getDAO('EditDecisionDAO')->updateEditorDecision(
            $this->submissionId,
            [
                'editDecisionId' => null,
                'decision' => $decision,
                'dateDecided' => $dateDecided,
                'editorId' => 1
            ]
        );
    }

    private function getSubmissionDecisions(): array
    {
        $dao = new ScieloArticlesDAO();
        $finalDecision = $dao->getFinalDecisionWithDate($this->submissionId, $this->locale);

        return [$dao, $finalDecision];
    }

    /**
     * @group OJS
     */
    public function testArchivedDeclinedSubmissionWithoutDecisionsGetsDecisionFallback(): void
    {
        $this->createArchivedEvent('2021-04-19 12:00:00');
        $this->createArchivedEvent('2021-04-20 12:36:39');

        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $this->assertEquals(__('common.declined', [], $this->locale), $finalDecision->getDecision());
        $this->assertEquals('2021-04-20', $finalDecision->getDateDecided());
        $this->assertEquals(
            __('editor.submission.decision.decline'),
            $dao->getLastDecision($this->submissionId)
        );
    }

    /**
     * @group OJS
     */
    public function testArchivedSubmissionDoesNotUseFallbackWhenItIsNotDeclined(): void
    {
        $this->submissionId = $this->createSubmission(STATUS_PUBLISHED);
        $this->createArchivedEvent('2021-04-20 12:36:39');

        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $this->assertNull($finalDecision);
        $this->assertEquals('', $dao->getLastDecision($this->submissionId));
    }

    /**
     * @group OJS
     */
    public function testDeclinedSubmissionDoesNotUseFallbackWithoutArchivedEvent(): void
    {
        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $this->assertNull($finalDecision);
        $this->assertEquals('', $dao->getLastDecision($this->submissionId));
    }

    /**
     * @group OJS
     */
    public function testStructuredFinalDecisionTakesPrecedenceOverArchivedFallback(): void
    {
        $this->createArchivedEvent('2021-04-20 12:36:39');
        $this->createDecision(SUBMISSION_EDITOR_DECISION_ACCEPT, '2021-05-29');

        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $this->assertEquals(__('common.accepted', [], $this->locale), $finalDecision->getDecision());
        $this->assertEquals('2021-05-29', $finalDecision->getDateDecided());
        $this->assertEquals(
            __('editor.submission.decision.accept'),
            $dao->getLastDecision($this->submissionId)
        );
    }

    /**
     * @group OJS
     */
    public function testNonFinalStructuredDecisionDoesNotPreventArchivedFallback(): void
    {
        $this->createArchivedEvent('2021-04-20 12:36:39');
        $this->createDecision(SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS, '2021-04-19');

        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $this->assertEquals(__('common.declined', [], $this->locale), $finalDecision->getDecision());
        $this->assertEquals('2021-04-20', $finalDecision->getDateDecided());
        $this->assertEquals(
            __('editor.submission.decision.requestRevisions'),
            $dao->getLastDecision($this->submissionId)
        );
    }

    /**
     * @group OJS
     */
    public function testSendToProductionIsAnAcceptedFinalDecision(): void
    {
        $this->createDecision(SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION, '2021-07-07');

        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $this->assertEquals(__('common.accepted', [], $this->locale), $finalDecision->getDecision());
        $this->assertEquals('2021-07-07', $finalDecision->getDateDecided());
        $this->assertEquals(
            __('editor.submission.decision.sendToProduction'),
            $dao->getLastDecision($this->submissionId)
        );
    }

    /**
     * @group OJS
     */
    public function testAcceptDecisionTakesPrecedenceOverLaterSendToProduction(): void
    {
        $this->createDecision(SUBMISSION_EDITOR_DECISION_ACCEPT, '2021-01-07');
        $this->createDecision(SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION, '2021-03-07');

        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $this->assertEquals(__('common.accepted', [], $this->locale), $finalDecision->getDecision());
        $this->assertEquals('2021-01-07', $finalDecision->getDateDecided());
        $this->assertEquals(
            __('editor.submission.decision.sendToProduction'),
            $dao->getLastDecision($this->submissionId)
        );
    }

    /**
     * @group OJS
     */
    public function testPublishedSubmissionWithoutWorkflowUsesQuickSubmissionOrImportFallback(): void
    {
        $this->submissionId = $this->createSubmission(STATUS_PUBLISHED, WORKFLOW_STAGE_ID_PRODUCTION);
        $this->createEvent(
            SUBMISSION_LOG_METADATA_PUBLISH,
            'publication.event.scheduled',
            '2021-04-19 12:36:39'
        );
        $this->createEvent(
            SUBMISSION_LOG_METADATA_PUBLISH,
            'publication.event.versionPublished',
            '2021-04-21 12:36:39'
        );
        $this->createEvent(
            SUBMISSION_LOG_METADATA_PUBLISH,
            'publication.event.published',
            '2021-04-20 12:36:39'
        );

        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $expectedDecision = __(
            'plugins.reports.scieloSubmissionsReport.decision.quickSubmissionOrImport',
            [],
            $this->locale
        );
        $this->assertEquals($expectedDecision, $finalDecision->getDecision());
        $this->assertEquals('2021-04-20', $finalDecision->getDateDecided());
        $this->assertEquals($expectedDecision, $dao->getLastDecision($this->submissionId));
    }

    /**
     * @group OJS
     */
    public function testQuickSubmissionOrImportWithoutPublicationEventKeepsDateEmpty(): void
    {
        $this->submissionId = $this->createSubmission(STATUS_PUBLISHED, WORKFLOW_STAGE_ID_PRODUCTION);

        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $expectedDecision = __(
            'plugins.reports.scieloSubmissionsReport.decision.quickSubmissionOrImport',
            [],
            $this->locale
        );
        $this->assertEquals($expectedDecision, $finalDecision->getDecision());
        $this->assertSame('', $finalDecision->getDateDecided());
        $this->assertEquals($expectedDecision, $dao->getLastDecision($this->submissionId));
    }

    /**
     * @group OJS
     */
    public function testPublishedSubmissionWithNormalSubmitEventDoesNotUseImportFallback(): void
    {
        $this->submissionId = $this->createSubmission(STATUS_PUBLISHED, WORKFLOW_STAGE_ID_PRODUCTION);
        $this->createEvent(
            SUBMISSION_LOG_SUBMISSION_SUBMIT,
            'submission.event.submitted',
            '2021-04-19 12:36:39'
        );

        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $this->assertNull($finalDecision);
        $this->assertSame('', $dao->getLastDecision($this->submissionId));
    }

    /**
     * @group OJS
     */
    public function testPublishedSubmissionWithEditorialDecisionDoesNotUseImportFallback(): void
    {
        $this->submissionId = $this->createSubmission(STATUS_PUBLISHED, WORKFLOW_STAGE_ID_PRODUCTION);
        $this->createDecision(SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS, '2021-04-19');

        list($dao, $finalDecision) = $this->getSubmissionDecisions();

        $this->assertNull($finalDecision);
        $this->assertEquals(
            __('editor.submission.decision.requestRevisions'),
            $dao->getLastDecision($this->submissionId)
        );
    }
}
