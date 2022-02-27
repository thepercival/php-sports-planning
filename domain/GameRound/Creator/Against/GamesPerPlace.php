<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator\Against;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\MultipleCombinationsCounter\Against as AgainstCounter;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\Output\GameRound as GameRoundOutput;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against as AgainstCreator;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsPlanning\Schedule\Creator\AssignedCounter;

class GamesPerPlace extends AgainstCreator
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createGameRound(
        Poule $poule,
        AgainstGpp $sportVariant,
        AssignedCounter $assignedCounter
    ): AgainstGameRound {
        $variantWithPoule = new VariantWithPoule($sportVariant, $poule);

        $gameRound = new AgainstGameRound();
        $assignedMap = $assignedCounter->getAssignedMap();
        $assignedWithMap = $assignedCounter->getAssignedWithMap();
        $assignedAgainstMap = $assignedCounter->getAssignedAgainstMap();
        $assignedHomeMap = $assignedCounter->getAssignedHomeMap();
        $homeAwayCreator = $this->getHomeAwayCreator($poule, $sportVariant);
        $homeAways = $homeAwayCreator->create();
        $sortedHomeAways = $this->sortHomeAways(
            $homeAways,
            $assignedMap,
            $assignedWithMap,
            $assignedAgainstMap,
            $assignedHomeMap
        );

        // $this->outputUnassignedHomeAways($homeAways);
        if ($this->assignGameRound(
                $variantWithPoule,
                $homeAwayCreator,
                $sortedHomeAways,
                $sortedHomeAways,
                $this->getAssignedSportCounters($poule),
                $assignedMap,
                $assignedWithMap,
                $assignedAgainstMap,
                $assignedHomeMap,
                $gameRound
            ) === false) {
            throw new \Exception('creation of homeaway can not be false', E_ERROR);
        }
        return $gameRound;
    }

    protected function getHomeAwayCreator(Poule $poule, AgainstGpp $sportVariant): GppHomeAwayCreator
    {
        return new GppHomeAwayCreator($poule, $sportVariant);
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @param array<int, AgainstCounter> $assignedAgainstMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     * @return list<AgainstHomeAway>
     */
    protected function sortHomeAways(
        array $homeAways,
        array $assignedMap,
        array $assignedWithMap,
        array $assignedAgainstMap,
        array $assignedHomeMap
    ): array {
        uasort($homeAways, function (
            AgainstHomeAway $homeAwayA,
            AgainstHomeAway $homeAwayB
        ) use ($assignedMap, $assignedWithMap, $assignedAgainstMap, $assignedHomeMap): int {
            list($amountA, $nrOfPlacesA) = $this->getLeastAmountAssigned($homeAwayA, $assignedMap);
            list($amountB, $nrOfPlacesB) = $this->getLeastAmountAssigned($homeAwayB, $assignedMap);
            if ($amountA !== $amountB) {
                return $amountA - $amountB;
            }
            if ($nrOfPlacesA !== $nrOfPlacesB) {
                return $nrOfPlacesB - $nrOfPlacesA;
            }
            $amountWithA = $this->getWithAmountAssigned($homeAwayA, $assignedWithMap);
            $amountWithB = $this->getWithAmountAssigned($homeAwayB, $assignedWithMap);
            if ($amountWithA !== $amountWithB) {
                return $amountWithA - $amountWithB;
            }
            $amountAgainstA = $this->getAgainstAmountAssigned($homeAwayA, $assignedAgainstMap);
            $amountAgainstB = $this->getAgainstAmountAssigned($homeAwayB, $assignedAgainstMap);
            if ($amountAgainstA !== $amountAgainstB) {
                return $amountAgainstA - $amountAgainstB;
            }
            list($amountHomeA, $nrOfPlacesHomeA) = $this->getLeastAmountAssigned(
                $homeAwayA,
                $assignedHomeMap
            );
            list($amountHomeB, $nrOfPlacesHomeB) = $this->getLeastAmountAssigned(
                $homeAwayB,
                $assignedHomeMap
            );
            if ($amountHomeA !== $amountHomeB) {
                return $amountHomeA - $amountHomeB;
            }
            return $nrOfPlacesHomeA - $nrOfPlacesHomeB;
        });
        return array_values($homeAways);
    }
}
