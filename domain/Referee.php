<?php

namespace SportsPlanning;

class Referee extends Identifiable implements Resource
{
    protected int $number;
    protected int $priority;

    public function __construct(protected Input $input)
    {
        $this->number = $input->getReferees()->count() + 1;
        $input->getReferees()->add($this);
        $this->priority = 1;
    }

    public function getInput(): Input
    {
        return $this->input;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getUniqueIndex(): string
    {
        return (string)$this->getNumber();
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }
}
