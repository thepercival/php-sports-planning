<?php

namespace SportsPlanning;

class Field implements Resource
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    protected $number;
    /**
     * @var Sport
     */
    protected $sport;

    public function __construct(int $number, Sport $sport)
    {
        $this->number = $number;
        $this->sport = $sport;
        $sport->getFields()->add($this);
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getSport(): Sport
    {
        return $this->sport;
    }
}
