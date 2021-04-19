<?php
use PHPUnit\Framework\TestCase;

final class SubmissionAuthorTest extends TestCase {
    private $author;
    private $fullName = "Atila Iamarino";
    private $country = "Brasil";
    private $affiliation = "Universidade de São Paulo";
    
    public function setUp() : void {
        $this->author = new SubmissionAuthor($this->fullName, $this->country, $this->affiliation);
    }

    public function testHasFullName() : void {
        $this->assertEquals($this->fullName, $this->author->getFullName());
    }

    public function testHasCountry() : void {
        $this->assertEquals($this->country, $this->author->getCountry());
    }

    public function testHasAffiliation() : void {
        $this->assertEquals($this->affiliation, $this->author->getAffiliation());
    }

    public function testAsRecord() : void {
        $this->assertEquals("Atila Iamarino, Brasil, Universidade de São Paulo", $this->author->asRecord());
    }
}