<?php

declare(strict_types=1);

namespace SportsPlanning\HomeAways;

use SportsHelpers\Against\Side;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Place;

readonly class OneVsOneHomeAway extends HomeAwayAbstract implements HomeAwayInterface
{

    public function __construct(private int $home, private int $away)
    {
        parent::__construct( $home . ' vs ' . $away);
    }

    public function get(AgainstSide $side): int
    {
        return $side === AgainstSide::Home ? $this->home : $this->away;
    }

    public function getHome(): int
    {
        return $this->home;
    }

    public function getAway(): int
    {
        return $this->away;
    }

    public function hasPlaceNr(int $placeNr, AgainstSide $side = null): bool
    {
        $inHome = $this->home === $placeNr;
        if( ($side === AgainstSide::Home || $side === null) && $inHome ) {
            return true;
        }
        $inAway = $this->away === $placeNr;
        return ($side === AgainstSide::Away || $side === null) && $inAway;
    }

    public function playsAgainst(int $placeNr, int $againstPlaceNr): bool {
        return ($this->hasPlaceNr($placeNr,AgainstSide::Home) && $this->hasPlaceNr($againstPlaceNr,AgainstSide::Away))
            || ($this->hasPlaceNr($againstPlaceNr,AgainstSide::Home) && $this->hasPlaceNr($placeNr,AgainstSide::Away));
    }

    public function createDuoPlaceNr(): DuoPlaceNr {
        return new DuoPlaceNr($this->home, $this->away);
    }

//    public function getOtherSidePlace(Place $place): Place
//    {
//        foreach([AgainstSide::Home, AgainstSide::Away] as $side) {
//            if( $this->get($side)->has($place)) {
//                foreach( $this->get($side)->getPlaces() as $placeIt) {
//                    if( $placeIt !== $place) {
//                        return $placeIt;
//                    }
//                }
//            }
//        }
//        throw new \Exception('place should be found');
//    }





    public function equals(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): bool
    {
        if( !($homeAway instanceof OneVsOneHomeAway)) {
            return false;
        }
        return ($homeAway->getAway() === $this->getHome()
                || $homeAway->getHome() === $this->getHome())
            && ($homeAway->getAway() === $this->getAway()
                || $homeAway->getHome() === $this->getAway());
    }

    public function hasOverlap(self $homeAway): bool
    {
        return $homeAway->getAway() === $this->away
            || $homeAway->getHome() === $this->away
            || $homeAway->getHome() === $this->home
            || $homeAway->getAway() === $this->home;
    }

    public function swap(): self
    {
        return new OneVsOneHomeAway($this->getAway(), $this->getHome());
    }

    /**
     * @param AgainstSide|null $side
     * @return list<int>
     */
    public function convertToPlaceNrs(AgainstSide|null $side = null): array {
        if( $side === AgainstSide::Home ) {
            return [$this->home];
        } else if( $side === AgainstSide::Away ) {
            return [$this->away];
        }
        return [$this->home, $this->away];
    }
}