<?php
declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use SportsHelpers\SportBase;
use SportsHelpers\Sport\Helper as SportHelper;
use SportsHelpers\Sport\HelperTrait as SportHelperTrait;

class Sport extends SportBase implements SportHelper
{
    /**
     * @var ArrayCollection<int|string,Field>
     */
    protected ArrayCollection $fields;

    use SportHelperTrait;

    public function __construct(
        protected Planning $planning,
        protected int $number,
        int $gameMode,
        int $nrOfGamePlaces,
        protected int $gameAmount
    ) {
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

    public function getField(int $number): Field
    {
        foreach ($this->getFields() as $field) {
            if ($field->getNumber() === $number) {
                return $field;
            }
        }
        throw new Exception('het veld kan niet gevonden worden', E_ERROR);
    }
}
