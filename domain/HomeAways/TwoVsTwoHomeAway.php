<?php

declare(strict_types=1);

namespace SportsPlanning\HomeAways;

use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Place;

readonly class TwoVsTwoHomeAway extends HomeAwayAbstract implements HomeAwayInterface
{

    public function __construct(private DuoPlaceNr $home, private DuoPlaceNr $away)
    {
        parent::__construct( $home . ' vs ' . $away);
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

//    /**
//     * @param AgainstSide|null $side
//     * @return list<Place>
//     */
//    public function getPlaces(AgainstSide|null $side = null): array
//    {
//        if( $side === null ) {
//            return array_merge($this->home->getPlaces(), $this->away->getPlaces());
//        }
//        return $this->get($side)->getPlaces();
//    }

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
        return ($homeAway->getAway()->getIndex() === $this->getHome()->getIndex()
                || $homeAway->getHome()->getIndex() === $this->getHome()->getIndex())
            && ($homeAway->getAway()->getIndex() === $this->getAway()->getIndex()
                || $homeAway->getHome()->getIndex() === $this->getAway()->getIndex());
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
     * @return list<int>
     */
    public function convertToPlaceNrs(): array {
        return array_merge($this->home->getPlaceNrs(), $this->away->getPlaceNrs());
    }
}
