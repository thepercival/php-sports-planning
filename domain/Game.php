<?php

declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\Identifiable;

abstract class Game extends Identifiable
{
    protected int $batchNr = 0;
    protected Place|null $refereePlace = null;
    protected Referee|null $referee = null;
    /**
     * @var ArrayCollection<int|string,Place>|null
     */
    protected ArrayCollection|null $poulePlaces = null;

    public const ORDER_BY_BATCH = 1;
    // public const ORDER_BY_GAMENUMBER = 2;

    public function __construct(protected Poule $poule, protected Field $field)
    {
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getSport(): Sport
    {
        return $this->field->getSport();
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

    public function getRefereePlace(): ?Place
    {
        return $this->refereePlace;
    }

    public function setRefereePlace(Place $refereePlace = null): void
    {
        $this->refereePlace = $refereePlace;
    }

    public function getReferee(): ?Referee
    {
        return $this->referee;
    }

    public function setReferee(Referee $referee): void
    {
        $this->referee = $referee;
    }

    public function emptyReferee(): void
    {
        $this->referee = null;
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
