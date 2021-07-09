<?php

class ClosedDateInterval
{
    private $beginningDate;
    private $endDate;

    public function __construct(string $beginningDate, string $endDate)
    {
        $this->beginningDate = new DateTime($beginningDate . " 00:00:00");
        $this->endDate = new DateTime($endDate . " 23:59:59");
    }

    public function getBeginningDate(): string
    {
        return $this->beginningDate->format('Y-m-d H:i:s');
    }

    public function getEndDate(): string
    {
        return $this->endDate->format('Y-m-d H:i:s');
    }

    public function isInsideInterval(string $date): bool
    {
        $datetime = new DateTime($date);

        return ($this->beginningDate <= $datetime) && ($datetime <= $this->endDate);
    }

    public function isValid(): bool
    {
        return ($this->beginningDate <= $this->endDate);
    }
}
