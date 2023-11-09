<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

use APP\plugins\reports\scieloSubmissionsReport\classes\ClosedDateInterval;
use PHPUnit\Framework\TestCase;

class ClosedDateIntervalTest extends TestCase
{
    private $dateTnterval;
    private $beginningDate = '2020-06-07';
    private $endDate = '2020-07-31';

    public function setUp(): void
    {
        $this->dateInterval = new ClosedDateInterval($this->beginningDate, $this->endDate);
    }

    public function testIntervalBeginning(): void
    {
        $expectedBeginningDate = '2020-06-07 00:00:00';
        $this->assertEquals($expectedBeginningDate, $this->dateInterval->getBeginningDate());
    }

    public function testIntervalEnd(): void
    {
        $expectedEndDate = '2020-07-31 23:59:59';
        $this->assertEquals($expectedEndDate, $this->dateInterval->getEndDate());
    }

    public function testDateInsideIntervalAtMiddle(): void
    {
        $date = '2020-06-30 15:16:20';
        $isInside = $this->dateInterval->isInsideInterval($date);
        $this->assertTrue($isInside);
    }

    public function testDateInsideIntervalAtBeginningDate(): void
    {
        $date = '2020-06-07 05:16:20';
        $isInside = $this->dateInterval->isInsideInterval($date);
        $this->assertTrue($isInside);
    }

    public function testDateInsideIntervalAtEndDate(): void
    {
        $date = '2020-07-31 20:16:20';
        $isInside = $this->dateInterval->isInsideInterval($date);
        $this->assertTrue($isInside);
    }

    public function testDateOutsideInterval(): void
    {
        $date = '2020-11-15 15:11:48';
        $isInside = $this->dateInterval->isInsideInterval($date);
        $this->assertFalse($isInside);
    }
}
