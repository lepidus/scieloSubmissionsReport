<?php
import('lib.pkp.tests.DatabaseTestCase');
import('classes.journal.Section');
import('classes.journal.SectionDAO');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReportFactory');

class ScieloSubmissionsReportFactoryTest extends DatabaseTestCase {

    protected function getAffectedTables() {
		return array("sections", "section_settings");
	}

    public function testReportHasSections() : void {
        $locale =  "en_US";
        $section1 = new Section();
        $section1->setTitle("Biological Sciences", $locale);
        $section2 = new Section();
        $section2->setTitle("Math", $locale);
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $firstSectionId = $sectionDao->insertObject($section1);
        $secondSectionId = $sectionDao->insertObject($section2);

        $factory = new ScieloSubmissionsReportFactory();
        
        $report = $factory->createReport([$firstSectionId, $secondSectionId], $locale);

        $expectedSections = [$firstSectionId => "Biological Sciences", $secondSectionId => "Math"];
        $this->assertEquals($expectedSections, $report->getSections());
    }

}

?>