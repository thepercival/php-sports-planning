<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator\Against;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SportsHelpers\Output;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\GameRound\Creator\StatisticsCalculator;
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
use SportsPlanning\TimeoutException;

class GamesPerPlace extends AgainstCreator
{
    protected int $highestGameRoundNumberCompleted = 0;
    protected int $nrOfGamesPerGameRound = 0;
    protected \DateTimeImmutable|null $timeoutDateTime = null;

    public function __construct(
        LoggerInterface $logger,
        protected int $margin,
        protected int|null $nrOfSecondsBeforeTimeout)
    {
        parent::__construct($logger);
    }

    public function createGameRound(
        Poule $poule,
        AgainstGpp $sportVariant,
        GppHomeAwayCreator $homeAwayCreator,
        AssignedCounter $assignedCounter,
    ): AgainstGameRound {
        if( $this->nrOfSecondsBeforeTimeout > 0 ) {
            $this->timeoutDateTime = (new \DateTimeImmutable())->add(new \DateInterval('PT' . $this->nrOfSecondsBeforeTimeout . 'S'));
        }
        $variantWithPoule = new VariantWithPoule($sportVariant, $poule);
//        $assignedCounterEmpty = new AssignedCounter($poule, [$sportVariant]);
        $gameRound = new AgainstGameRound();
        $assignedMap = $assignedCounter->getAssignedMap();
        $this->highestGameRoundNumberCompleted = 0;
        $this->nrOfGamesPerGameRound = $variantWithPoule->getNrOfGamesSimultaneously();
        // Over all Sports
//        $assignedWithMap = $assignedCounter->getAssignedWithMap();
        $assignedAgainstMap = $this->getPlaceCombinationMap($poule); //$assignedCounter->getAssignedAgainstMap();

        $assignedHomeMap = $assignedCounter->getAssignedHomeMap();

        $homeAways = $this->createHomeAways($homeAwayCreator, $poule, $sportVariant);
        $homeAways = $this->initHomeAways($homeAways);

        $statisticsCalculator = new StatisticsCalculator(
            $variantWithPoule,
            $this->getAssignedSportCounters($poule),
            $assignedMap,
            $this->getPlaceCombinationMap($poule)/*$assignedWithMap*/,
            $assignedAgainstMap,
            $assignedHomeMap,
            $this->convertToPlaceCombinationMap($assignedAgainstMap),
            $this->margin
        );

        $homeAways = $statisticsCalculator->sortHomeAways($homeAways, $this->logger);
        if ($this->assignGameRound(
                $variantWithPoule,
                $homeAways,
                $homeAways,
                $statisticsCalculator,
                $gameRound
            ) === false) {
            throw new \Exception('creation of homeaway can not be false', E_ERROR);
        }
        return $gameRound;
    }

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param list<AgainstHomeAway> $homeAwaysForGameRound,
     * @param list<AgainstHomeAway> $homeAways
     * @param StatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignGameRound(
        VariantWithPoule $variantWithPoule,
        array $homeAwaysForGameRound,
        array $homeAways,
        StatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $depth = 0
    ): bool {

        if( $variantWithPoule->getTotalNrOfGames() === $statisticsCalculator->getNrOfHomeAwaysAsigned() ) {
            if( $statisticsCalculator->allAssigned() ) {
//                $this->gameRoundOutput->output($gameRound, false, 'ASSIGNED HOMEAWAYS GR' . $gameRound->getNumber());
//                $statisticsCalculator->output($this->logger, true, true);
                return true;
            }
            return false;
        }

        if ($this->nrOfSecondsBeforeTimeout && (new DateTimeImmutable()) > $this->timeoutDateTime) {
            throw new TimeoutException('exceeded maximum duration of ' . $this->nrOfSecondsBeforeTimeout . ' seconds', E_ERROR);
        }

        if ($this->isGameRoundCompleted($variantWithPoule, $gameRound)) {
            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);

            if (!$statisticsCalculator->minimalSportCanStillBeAssigned()) {
                return false;
            }
            if (!$statisticsCalculator->minimalAgainstCanStillBeAssigned(null)) {
                return false;
            }
            if (!$statisticsCalculator->minimalWithCanStillBeAssigned(null)) {
                return false;
            }

//            if( $gameRound->getNumber() === 7 ) {
//                $this->gameRoundOutput->output($gameRound, false, 'ASSIGNED HOMEAWAYS GR' . $gameRound->getNumber());
//                $statisticsCalculator->output($this->logger, true, true);
//                $er = 12; // die();
//            }

            $filteredHomeAways = $statisticsCalculator->filter($homeAways);
            if ($gameRound->getNumber() > $this->highestGameRoundNumberCompleted) {
                $this->highestGameRoundNumberCompleted = $gameRound->getNumber();
                //$this->logger->info('highestGameRoundNumberCompleted: ' . $gameRound->getNumber());
                $filteredHomeAways = $statisticsCalculator->sortHomeAways($filteredHomeAways, $this->logger);
            }
            // $this->logger->info('gr ' . $gameRound->getNumber() . ' completed ( ' . count($homeAways) . ' => ' . count($filteredHomeAways) .  ' )');

            if( count($filteredHomeAways) < $this->nrOfGamesPerGameRound ) {
                return false;
            }
            if( $this->assignGameRound(
                $variantWithPoule,
                $filteredHomeAways,
                $homeAways,
                $statisticsCalculator,
                $nextGameRound,
                $depth + 1
            ) ) {
                return true;
            }
//            else {
//                $this->logger->info('return to gr  : ' . $gameRound->getNumber() );
//            }
        }
        // $this->logger->info('gr ' . $gameRound->getNumber() . ' trying.. ( grgames ' . count($gameRound->getHomeAways()) . ', haGr ' . count($homeAwaysForGameRound) .  ' )');

        return $this->assignSingleGameRound(
            $variantWithPoule,
            $homeAwaysForGameRound,
            $homeAways,
            $statisticsCalculator,
            $gameRound,
            $depth + 1
        );
    }

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param list<AgainstHomeAway> $homeAwaysForGameRound
     * @param list<AgainstHomeAway> $homeAways
     * @param StatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignSingleGameRound(
        VariantWithPoule $variantWithPoule,
        array $homeAwaysForGameRound,
        array $homeAways,
        StatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $depth = 0
    ): bool {

        $triedHomeAways = [];
        while($homeAway = array_shift($homeAwaysForGameRound)) {

            if (!$this->isHomeAwayAssignable($homeAway, $statisticsCalculator)) {
                array_push($triedHomeAways, $homeAway);
                continue;
            }
            $gameRound->add($homeAway);

            $homeAwaysForGameRoundTmp = array_values(
                array_filter(
                    array_merge( $homeAwaysForGameRound, $triedHomeAways),
                    function (AgainstHomeAway $homeAway) use ($gameRound): bool {
                        return !$gameRound->isHomeAwayPlaceParticipating($homeAway);
                    }
                )
            );

            if (count($homeAwaysForGameRoundTmp) >= ($this->nrOfGamesPerGameRound - count($gameRound->getHomeAways()))
                && $this->assignGameRound(
                    $variantWithPoule,
                    $homeAwaysForGameRoundTmp,
                    $homeAways,
                    $statisticsCalculator->addHomeAway($homeAway),
                    $gameRound,
                    $depth + 1
            )) {
                return true;
            }
            $this->releaseHomeAway($gameRound, $homeAway);
            array_push($triedHomeAways, $homeAway);

        }
        return false;
    }

    /**
     * @param GppHomeAwayCreator $homeAwayCreator
     * @param Poule $poule
     * @param AgainstGpp $sportVariant
     * @return list<AgainstHomeAway>
     */
    protected function createHomeAways(
        GppHomeAwayCreator $homeAwayCreator,
        Poule $poule,
        AgainstGpp $sportVariant): array
    {
        $variantWithPoule = new VariantWithPoule($sportVariant, $poule);
        $totalNrOfGames = $variantWithPoule->getTotalNrOfGames();
        $homeAways = [];
        while ( count($homeAways) < $totalNrOfGames ) {
            $homeAways = array_merge($homeAways, $homeAwayCreator->create($sportVariant));
        }
        return $homeAways;
    }

    protected function isHomeAwayAssignable(
        AgainstHomeAway $homeAway, StatisticsCalculator $statisticsCalculator
    ): bool {

        if( $statisticsCalculator->againstWillBeTooMuchDiffAssigned($homeAway) ) {
            return false;
        }

        if( !$statisticsCalculator->minimalAgainstCanStillBeAssigned($homeAway) ) {
            return false;
        }
        if( $statisticsCalculator->againstWillBeOverAssigned($homeAway) ) {
            return false;
        }
        foreach ($homeAway->getPlaces() as $place) {
            if ( $statisticsCalculator->sportWillBeOverAssigned($place, 1) ) {
                return false;
            }
        }
        if( !$statisticsCalculator->useWith()) {
            return true;
        }
        if( !$statisticsCalculator->minimalWithCanStillBeAssigned($homeAway) ) {
            return false;
        }
        if( $statisticsCalculator->withWillBeOverAssigned($homeAway) ) {
            return false;
        }
        return true;
    }



    /**
     * @param VariantWithPoule $variantWithPoule
     * @param int $currentGameRoundNumber
     * @param list<AgainstHomeAway> $homeAways
     * @return bool
     */
    protected function isOverAssigned(
        VariantWithPoule $variantWithPoule,
        int $currentGameRoundNumber,
        array $homeAways
    ): bool {
        $poule = $variantWithPoule->getPoule();
        $unassignedMap = [];
        foreach ($poule->getPlaces() as $place) {
            $unassignedMap[$place->getNumber()] = new PlaceCounter($place);
        }
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaces() as $place) {
                $unassignedMap[$place->getNumber()]->increment();
            }
        }

        $maxNrOfGameGroups = $variantWithPoule->getNrOfGameGroups();
        foreach ($poule->getPlaces() as $place) {
            if ($currentGameRoundNumber + $unassignedMap[$place->getNumber()]->count() > $maxNrOfGameGroups) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @return list<AgainstHomeAway>
     */
    private function initHomeAways(array $homeAways): array {
        /** @var list<AgainstHomeAway> $newHomeAways */
        $newHomeAways = [];
        while( $homeAway = array_shift($homeAways) ) {
            if( (count($homeAways) % 2) === 0 ) {
                array_unshift($newHomeAways, $homeAway);
            } else {
                array_push($newHomeAways, $homeAway);
            }
        }

//        while( count($homeAways) > 0 ) {
//            if( (count($homeAways) % 2) === 0 ) {
//                $homeAway = array_shift($homeAways);
//            } else {
//                $homeAway = array_pop($homeAways);
//            }
//            array_push($newHomeAways, $homeAway);
//        }

        return $newHomeAways;
    }

    /**
     * @param Poule $poule
     * @return array<int, PlaceCombinationCounter>
     */
    public function getPlaceCombinationMap(Poule $poule): array
    {
        $map = [];
        foreach ($poule->getPlaces() as $place) {
            foreach ($poule->getPlaces() as $placeIt) {
                if( $placeIt->getNumber() >= $place->getNumber() ) {
                    break;
                }
                $placeCombination = new PlaceCombination([$placeIt, $place]);
                $map[$placeCombination->getNumber()] = new PlaceCombinationCounter($placeCombination);
            }
        }
        return $map;
    }



}
