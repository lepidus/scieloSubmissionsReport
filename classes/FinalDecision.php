<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes;

class FinalDecision
{
    private $decision;
    private $dateDecided;

    public function __construct(string $decision, string $dateDecided)
    {
        $this->decision = $decision;
        $this->dateDecided = $dateDecided;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function getDateDecided(): string
    {
        return $this->dateDecided;
    }
}
