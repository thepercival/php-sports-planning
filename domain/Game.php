<?php

declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\Collection;
use SportsHelpers\Identifiable;
use SportsPlanning\Game\Place\AgainstEachOther as AgainstEachOtherGamePlace;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;

abstract class Game extends Identifiable
{
    protected Poule $poule;
    /**
     * @var Field|null
     */
    protected $field;
    /**
     * @var int
     */
    protected $batchNr;
    /**
     * @var Place|null
     */
    protected $refereePlace;
    /**
     * @var Referee|null
     */
    protected $referee;
    /**
     * @var Collection|Place[]|null
     */
    protected $poulePlaces;

    public const ORDER_BY_BATCH = 1;
    // public const ORDER_BY_GAMENUMBER = 2;

    public function __construct(Poule $poule)
    {
        $this->poule = $poule;
        $this->batchNr = 0;
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function getBatchNr(): int
    {
        return $this->batchNr;
    }

    public function setBatchNr(int $batchNr)
    {
        $this->batchNr = $batchNr;
    }

    public function getRefereePlace(): ?Place
    {
        return $this->refereePlace;
    }

    public function setRefereePlace(Place $refereePlace = null )
    {
        $this->refereePlace = $refereePlace;
    }

    public function getReferee(): ?Referee
    {
        return $this->referee;
    }

    public function setReferee(Referee $referee)
    {
        $this->referee = $referee;
    }

    public function emptyReferee()
    {
        $this->referee = null;
    }

    public function getField(): ?Field
    {
        return $this->field;
    }

    public function setField(Field $field)
    {
        $this->field = $field;
    }

    public function emptyField()
    {
        $this->field = null;
    }
}
