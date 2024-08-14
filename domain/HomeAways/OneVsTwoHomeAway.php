<?php

declare(strict_types=1);

namespace SportsPlanning\HomeAways;

use SportsHelpers\Against\Side;
use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Place;

readonly class OneVsTwoHomeAway extends HomeAwayAbstract implements HomeAwayInterface
{

    public function __construct(private int $home, private DuoPlaceNr $away)
    {
        parent::__construct( $home . ' vs ' . $away);
    }

    public function get(AgainstSide $side): int|DuoPlaceNr
    {
        return $side === AgainstSide::Home ? $this->home : $this->away;
    }

    public function getHome(): int
    {
        return $this->home;
    }

    public function getAway(): DuoPlaceNr
    {
        return $this->away;
    }

    public function hasPlaceNr(int $placeNr, AgainstSide $side = null): bool
    {
        $inHome = $this->home === $placeNr;
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

    /**
     * @return list<DuoPlaceNr>
     */
    public function createAgainstDuoPlaceNrs(): array {

        $duoPlaces = [];
        foreach($this->away->getPlaceNrs() as $awayPlaceNr) {
            $duoPlaces[] = new DuoPlaceNr($this->home, $awayPlaceNr);
        }
        return $duoPlaces;
    }

    /**
     * @return DuoPlaceNr
     */
    public function getWithDuoPlaceNr(): DuoPlaceNr {

        return $this->away;
    }

    /**
     * @return list<DuoPlaceNr>
     */
    public function createTogetherDuoPlaceNrs(): array {

        return array_merge(
            $this->createAgainstDuoPlaceNrs(),
            [$this->getWithDuoPlaceNr()],
        );
    }

    public function equals(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): bool
    {
        if( !($homeAway instanceof OneVsTwoHomeAway)) {
            return false;
        }
        return ($homeAway->getAway()->getIndex() === $this->getAway()->getIndex())
            && ($homeAway->getHome() === $this->getHome());
    }

    public function hasOverlap(OneVsTwoHomeAway $homeAway): bool
    {
        return $homeAway->hasPlaceNr($this->getHome(), AgainstSide::Home)
            || $homeAway->hasPlaceNr($this->getHome(), AgainstSide::Away)
            || $this->hasPlaceNr($homeAway->getHome(), AgainstSide::Home)
            || $this->hasPlaceNr($homeAway->getHome(), AgainstSide::Away);
    }


    /**
     * @param AgainstSide|null $side
     * @return list<int>
     */
    public function convertToPlaceNrs(AgainstSide|null $side = null): array {
        if( $side === AgainstSide::Home ) {
            return [$this->home];
        } else if( $side === AgainstSide::Away ) {
            return $this->away->getPlaceNrs();
        }
        return array_merge([$this->home], $this->away->getPlaceNrs());
    }
}
