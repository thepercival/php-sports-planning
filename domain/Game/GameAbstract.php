<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use SportsPlanning\Field;
use SportsPlanning\Place;
use SportsPlanning\Poule;

abstract class GameAbstract
{
    protected int $batchNr = 0;
    protected string|null $refereePlaceUniqueIndex = null;
    protected int|null $refereeNr = null;

    public function __construct(public readonly Poule $poule, protected Field $field)
    {
    }

    public function getBatchNr(): int
    {
        return $this->batchNr;
    }

    /**
     * @return void
     */
    public function setBatchNr(int $batchNr)
    {
        $this->batchNr = $batchNr;
    }

    public function getRefereePlaceUniqueIndex(): string|null
    {
        return $this->refereePlaceUniqueIndex;
    }

//    public function getRefereePlaceLocation(): string|null
//    {
//        if( $this->refereePlace instanceof PlaceLocationInterface ) {
//            return $this->refereePlace->getUniqueIndex();
//        }
//        return null;
//    }

    public function setRefereePlaceUniqueIndex(string|null $refereePlaceUniqueIndex): void
    {
        $this->refereePlaceUniqueIndex = $refereePlaceUniqueIndex;
    }

    public function getRefereeNr(): int|null
    {
        return $this->refereeNr;
    }

    public function setRefereeNr(int|null $refereeNr): void
    {
        $this->refereeNr = $refereeNr;
    }

    public function getField(): Field
    {
        return $this->field;
    }

    public function setField(Field $field): void
    {
        $this->field = $field;
    }
}
