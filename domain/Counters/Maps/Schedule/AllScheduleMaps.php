<?php

declare(strict_types=1);

namespace SportsPlanning\Counters\Maps\Schedule;

use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsPlanning\Combinations\CombinationMapper;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\Counters\Maps\PlaceCounterMap;
use SportsPlanning\Poule;

class AllScheduleMaps
{
    protected AmountCounterMap $amountCounterMap;

    protected WithCounterMap $withCounterMap;
    protected AgainstCounterMap $againstCounterMap;
    protected SideCounterMap $homeCounterMap;
    protected SideCounterMap $awayCounterMap;
    protected TogetherCounterMap $togetherCounterMap;

//    /**
//     * @var array<string,array<string,CounterForPlace>>
//     */
//    // protected array $assignedTogetherMap = [];
//    // protected bool $hasAgainstSportWithMultipleSidePlaces;

    /**
     * @param Poule $poule
     * @param list<Single|AllInOneGame|AgainstGpp|AgainstH2h> $sportVariants
     */
    public function __construct(Poule $poule, array $sportVariants)
    {
        $combinationMapper = new CombinationMapper();
        $this->amountCounterMap = new AmountCounterMap($combinationMapper->initPlaceCounterMap($poule));
        $this->withCounterMap = new WithCounterMap($poule, $sportVariants);
        $this->againstCounterMap = new AgainstCounterMap($poule);
        $this->homeCounterMap = new SideCounterMap(Side::Home, $combinationMapper->initPlaceCounterMap($poule));
        $this->awayCounterMap = new SideCounterMap(Side::Away, $combinationMapper->initPlaceCounterMap($poule));
        $this->togetherCounterMap = new TogetherCounterMap($poule);
    }

    /**
     * @param list<HomeAway> $homeAways
     */
    public function addHomeAways(array $homeAways): void
    {
        foreach ($homeAways as $homeAway) {
            $this->addHomeAway($homeAway);
        }
    }

    /**
     * @param HomeAway $homeAway
     */
    public function addHomeAway(HomeAway $homeAway): void
    {
        $this->amountCounterMap->addHomeAway($homeAway);
        $this->withCounterMap->addHomeAway($homeAway);
        $this->againstCounterMap->addHomeAway($homeAway);
        $this->homeCounterMap->addHomeAway($homeAway);
        $this->awayCounterMap->addHomeAway($homeAway);
        $this->togetherCounterMap->addHomeAway($homeAway);
    }

    public function getAmountCounterMap(): AmountCounterMap {
        return $this->amountCounterMap;
    }

    public function getWithCounterMap(): WithCounterMap {
        return $this->withCounterMap;
    }

    public function getAgainstCounterMap(): AgainstCounterMap {
        return $this->againstCounterMap;
    }

    public function getHomeCounterMap(): SideCounterMap {
        return $this->homeCounterMap;
    }

    public function setHomeCounterMap(SideCounterMap $homeCounterMap): void {
        $this->homeCounterMap = $homeCounterMap;
    }

    public function getAwayCounterMap(): SideCounterMap {
        return $this->awayCounterMap;
    }

    public function setAwayCounterMap(SideCounterMap $awayCounterMap): void {
        $this->awayCounterMap = $awayCounterMap;
    }

    public function getTogetherCounterMap(): TogetherCounterMap {
        return $this->togetherCounterMap;
    }

    public function setTogetherCounterMap(TogetherCounterMap $togetherCounterMap): void {
        $this->togetherCounterMap = $togetherCounterMap;
    }

    function __clone()
    {
        $this->amountCounterMap = clone $this->amountCounterMap;
        $this->withCounterMap = clone $this->withCounterMap;
        $this->againstCounterMap = clone $this->againstCounterMap;
        $this->homeCounterMap = clone $this->homeCounterMap;
        $this->awayCounterMap = clone $this->awayCounterMap;
        $this->togetherCounterMap = clone $this->togetherCounterMap;
    }

//    protected function getMapDifference(array $counters): int {
//        $counts = array_map( function(Countable $counter): int {
//            return $counter->count();
//        }, $counters );
//        if( count($counts) === 0 ) {
//            return 0;
//        }
//        return max($counts) - min($counts);
//    }
}
