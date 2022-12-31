<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations\StatisticsCalculator\Against;

use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\Combinations\PlaceCombinationCounterMap;
use SportsPlanning\Combinations\StatisticsCalculator;
use SportsPlanning\PlaceCounter;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;

class H2h extends StatisticsCalculator
{

    /**
     * @param AgainstH2hWithPoule $againstH2hWithPoule
     * @param array<int, PlaceCounter> $assignedSportMap
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<string, PlaceCombinationCounter> $assignedWithSportMap
     * @param array<string, PlaceCombinationCounter> $assignedAgainstSportMap
     * @param array<string, PlaceCombinationCounter> $assignedAgainstPreviousSportsMap
     * @param PlaceCombinationCounterMap $assignedHomeMap
     * @param array<string, PlaceCombination> $leastAgainstAssigned
     * @param int $allowedMargin
     * @param int $nrOfHomeAwaysAssigned
     */
    public function __construct(
        protected Againsth2hWithPoule $againstH2hWithPoule,
        array $assignedSportMap,
        array $assignedMap,
        array $assignedWithSportMap,
        array $assignedAgainstSportMap,
        array $assignedAgainstPreviousSportsMap,
        PlaceCombinationCounterMap $assignedHomeMap,
        array $leastAgainstAssigned,
        int $allowedMargin,
        int $nrOfHomeAwaysAssigned = 0
    )
    {
        parent::__construct(
            $againstH2hWithPoule,
            $assignedSportMap,
            $assignedMap,
            $assignedWithSportMap,
            $assignedAgainstSportMap,
            $assignedAgainstPreviousSportsMap,
            $assignedHomeMap,
            $leastAgainstAssigned,
            $allowedMargin,
            $nrOfHomeAwaysAssigned
        );
    }

    public function addHomeAway(HomeAway $homeAway): self
    {
        $assignedSportMap = $this->copyPlaceCounterMap($this->assignedSportMap);
        $assignedMap = $this->copyPlaceCounterMap($this->assignedMap);
        foreach ($homeAway->getPlaces() as $place) {
            $assignedSportMap[$place->getNumber()]->increment();
            $assignedMap[$place->getNumber()]->increment();
        }

        $assignedAgainstSportMap = $this->copyPlaceCombinationCounterMap($this->assignedAgainstSportMap);
        foreach ($homeAway->getAgainstPlaceCombinations() as $placeCombination) {
            $assignedAgainstSportMap[$placeCombination->getIndex()]->increment();
        }
        $assignedWithMap = $this->copyPlaceCombinationCounterMap($this->assignedWithSportMap);
        if ($this->useWith) {
            if (count($homeAway->getHome()->getPlaces()) > 1) {
                $assignedWithMap[$homeAway->getHome()->getIndex()]->increment();
            }
            $assignedWithMap[$homeAway->getAway()->getIndex()]->increment();
        }

        $assignedHomeMap = $this->assignedHomeMap->addPlaceCombination($homeAway->getHome());

        $leastAgainstAssigned = $this->leastAgainstAssigned;
//        $unsetForNewLeastAssigned = [];
//        foreach($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
//            if (array_key_exists($againstPlaceCombination->getNumber(), $leastAgainstAssigned)) {
//                unset($leastAgainstAssigned[$againstPlaceCombination->getNumber()]);
//            } else {
//                $unsetForNewLeastAssigned[] = $againstPlaceCombination;
//            }
//        }
//        if( count($leastAgainstAssigned) === 0) {
//            $leastAgainstAssigned = $this->convertToPlaceCombinationMap($this->assignedAgainstSportMap);
//            foreach($unsetForNewLeastAssigned as $againstPlaceCombination ) {
//                unset($leastAgainstAssigned[$againstPlaceCombination->getNumber()]);
//            }
//        }

        return new self(
            $this->againstH2hWithPoule,
            $assignedSportMap,
            $assignedMap,
            $assignedWithMap,
            $assignedAgainstSportMap,
            $this->assignedAgainstPreviousSportsMap,
            $assignedHomeMap,
            $leastAgainstAssigned,
            $this->allowedMargin,
            $this->nrOfHomeAwaysAssigned + 1
        );
    }

    public function outputAgainstTotals(LoggerInterface $logger): void {
        $header = 'AgainstTotals';
        $logger->info($header);
        parent::outputAgainstTotals($logger);
    }

    public function outputWithTotals(LoggerInterface $logger): void
    {
        $header = 'WithTotals';
        $logger->info($header);
        parent::outputWithTotals($logger);
    }
}
