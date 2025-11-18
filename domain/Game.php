<?php

declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\Collection;
use SportsHelpers\PlaceLocationInterface;

abstract class Game extends Identifiable
{
    protected int $batchNr = 0;
    protected Place|null $refereePlace = null;
    protected Referee|null $referee = null;
    /**
     * @var Collection<int|string,Place>|null
     */
    protected Collection|null $poulePlaces = null;

    public const int ORDER_BY_BATCH = 1;
    // public const ORDER_BY_GAMENUMBER = 2;

    public function __construct(protected Planning $planning, protected Poule $poule, protected Field $field)
    {
    }

    public function getPlanning(): Planning
    {
        return $this->planning;
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getPouleNr(): int
    {
        return $this->poule->getNumber();
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

    public function getRefereePlaceLocation(): string|null
    {
        if( $this->refereePlace instanceof PlaceLocationInterface ) {
            return $this->refereePlace->getUniqueIndex();
        }
        return null;
    }

    public function setRefereePlace(Place|null $refereePlace): void
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

    public function getFieldUniqueIndex(): string {
        return $this->getField()->getUniqueIndex();
    }
}
