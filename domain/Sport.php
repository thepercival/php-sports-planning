<?php
declare(strict_types=1);

namespace SportsPlanning;

use \Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\SportBase;

class Sport extends SportBase
{
    /**
     * @var ArrayCollection<int|string,Field>
     */
    protected ArrayCollection $fields;

    public function __construct(protected Planning $planning, protected int $number, int $gameMode, int $nrOfGamePlaces)
    {
        parent::__construct($gameMode, $nrOfGamePlaces);
        $this->fields = new ArrayCollection();
    }

    public function getPlanning(): Planning
    {
        return $this->planning;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return ArrayCollection<int|string,Field>
     */
    public function getFields(): ArrayCollection
    {
        return $this->fields;
    }
}
