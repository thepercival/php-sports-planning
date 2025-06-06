<?php

declare(strict_types=1);

namespace SportsPlanning\HomeAways;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Place;

final readonly class TwoVsTwoHomeAway extends HomeAwayAbstract
{

    public function __construct(private DuoPlaceNr $home, private DuoPlaceNr $away)
    {
        parent::__construct( (string)$home . ' vs ' . (string)$away);
    }

    public function get(AgainstSide $side): DuoPlaceNr
    {
        return $side === AgainstSide::Home ? $this->home : $this->away;
    }

    public function getHome(): DuoPlaceNr
    {
        return $this->home;
    }

    public function getAway(): DuoPlaceNr
    {
        return $this->away;
    }

    public function hasPlaceNr(int $placeNr, AgainstSide $side = null): bool
    {
        if( $placeNr < 1) {
            throw new \Exception('placeNr should be at least 1');
        }

        $inHome = $this->home->has($placeNr);
        if( ($side === AgainstSide::Home || $side === null) && $inHome ) {
            return true;
        }
        $inAway = $this->away->has($placeNr);
        return ($side === AgainstSide::Away || $side === null) && $inAway;
    }

    public function playsAgainst(int $placeNr, int $againstPlaceNr): bool {
        return ($this->hasPlaceNr($placeNr,AgainstSide::Home) && $this->hasPlaceNr($againstPlaceNr,AgainstSide::Away))
            || ($this->hasPlaceNr($againstPlaceNr,AgainstSide::Home) && $this->hasPlaceNr($placeNr,AgainstSide::Away));
    }

    /**
     * @return list<DuoPlaceNr>
     */
    public function createAgainstDuoPlaceNrs(): array {

        $duoPlaceNrs = [];
        foreach($this->home->getPlaceNrs() as $homePlaceNr) {
            foreach($this->away->getPlaceNrs() as $awayPlaceNr) {
                $duoPlaceNrs[] = new DuoPlaceNr($homePlaceNr, $awayPlaceNr);
            }
        }
        return $duoPlaceNrs;
    }

    /**
     * @return list<DuoPlaceNr>
     */
    public function createWithDuoPlaceNrs(): array {

        return [$this->home, $this->away];
    }

    /**
     * @return list<DuoPlaceNr>
     */
    public function createTogetherDuoPlaceNrs(): array {

        return array_merge(
            $this->createAgainstDuoPlaceNrs(),
            $this->createWithDuoPlaceNrs(),
        );
    }

    public function equals(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): bool
    {
        if( !($homeAway instanceof TwoVsTwoHomeAway)) {
            return false;
        }
        return ($homeAway->getAway()->createUniqueNumber() === $this->getHome()->createUniqueNumber()
                || $homeAway->getHome()->createUniqueNumber() === $this->getHome()->createUniqueNumber())
            && ($homeAway->getAway()->createUniqueNumber() === $this->getAway()->createUniqueNumber()
                || $homeAway->getHome()->createUniqueNumber() === $this->getAway()->createUniqueNumber());
    }

    public function hasOverlap(TwoVsTwoHomeAway $game): bool
    {
        return $game->getAway()->hasOverlap($this->getHome())
            || $game->getAway()->hasOverlap($this->getAway())
            || $game->getHome()->hasOverlap($this->getHome())
            || $game->getHome()->hasOverlap($this->getAway());
    }

    public function swap(): self
    {
        return new TwoVsTwoHomeAway($this->getAway(), $this->getHome());
    }


    /**
     * @param AgainstSide|null $side
     * @return list<int>
     */
    #[\Override]
    public function convertToPlaceNrs(AgainstSide|null $side = null): array {
        if( $side === AgainstSide::Home ) {
            return $this->home->getPlaceNrs();
        } else if( $side === AgainstSide::Away ) {
            return $this->away->getPlaceNrs();
        }
        return array_merge($this->home->getPlaceNrs(), $this->away->getPlaceNrs());
    }
}
