<?php
import('lib.pkp.tests.DatabaseTestCase');
import('classes.journal.Section');
import('classes.journal.SectionDAO');
import('classes.submission.Submission');
import('classes.submission.SubmissionDAO');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReportFactory');

class ScieloSubmissionsReportFactoryTest extends DatabaseTestCase {

    protected function getAffectedTables() {
		return array("sections", "section_settings", "submissions", "submission_settings");
	}

    public function testReportHasSections() : void {
        $locale =  "en_US";
        $contextId = 1;
        $section1 = new Section();
        $section1->setTitle("Biological Sciences", $locale);
        $section2 = new Section();
        $section2->setTitle("Math", $locale);
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $firstSectionId = $sectionDao->insertObject($section1);
        $secondSectionId = $sectionDao->insertObject($section2);

        $factory = new ScieloSubmissionsReportFactory();
        
        $report = $factory->createReport($contextId, [$firstSectionId, $secondSectionId], $locale);

        $expectedSections = [$firstSectionId => "Biological Sciences", $secondSectionId => "Math"];
        $this->assertEquals($expectedSections, $report->getSections());
    }
    
    public function testReportHasSubmission() {
        $locale = 'en_US';
        $contextId = 1;
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');

        $submission = new Submission();
        $submission->setData('contextId', $contextId);
        $submissionId = $submissionDao->insertObject($submission);

        $reportFactory = new ScieloSubmissionsReportFactory();
        $report = $reportFactory->createReport($contextId, [], $locale);

        $expectedSubmissions = [$submissionId];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }

    public function testReportFilterBySection() {
        return true;
    }
    
    public function testReportFilterBySubmissionDate() {
        return true;
    }
    
    public function testReportFilterByFinalDecisionDate() {
        return true;
    }
}

?>