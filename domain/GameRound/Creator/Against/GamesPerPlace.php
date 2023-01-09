<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator\Against;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Combinations\HomeAway;
use SportsPlanning\Combinations\HomeAwayBalancer;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\Mapper;
use SportsPlanning\Combinations\PlaceCombinationCounterMap;
use SportsPlanning\Combinations\PlaceCounterMap;
use SportsPlanning\Combinations\StatisticsCalculator\Against\GamesPerPlace as GppStatisticsCalculator;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\GameRound\Creator\Against as AgainstCreator;
use SportsPlanning\PlaceCounter;
use SportsPlanning\Poule;
use SportsPlanning\Schedule\CreatorHelpers\AgainstGppDifference;
use SportsPlanning\Schedule\CreatorHelpers\AgainstGppDifferenceManager;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\TimeoutException;

class GamesPerPlace extends AgainstCreator
{
    protected int $highestGameRoundNumberCompleted = 0;
    protected int $nrOfGamesPerGameRound = 0;
    protected \DateTimeImmutable|null $timeoutDateTime = null;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function createGameRound(
        Poule $poule,
        AgainstGpp $againstGpp,
        GppHomeAwayCreator $homeAwayCreator,
        AssignedCounter $assignedCounter,
        AgainstGppDifference $difference,
        int|null $nrOfSecondsBeforeTimeout
    ): AgainstGameRound {
        if( $nrOfSecondsBeforeTimeout > 0 ) {
            $this->timeoutDateTime = (new \DateTimeImmutable())->add(new \DateInterval('PT' . $nrOfSecondsBeforeTimeout . 'S'));
        }
        $variantWithPoule = new AgainstGppWithPoule($poule, $againstGpp);
        $mapper = new Mapper();
        $gameRound = new AgainstGameRound();
        $this->highestGameRoundNumberCompleted = 0;
        $this->nrOfGamesPerGameRound = $variantWithPoule->getNrOfGamesSimultaneously();

        $homeAways = $this->createHomeAways($homeAwayCreator, $poule, $againstGpp);
        $homeAways = $this->initHomeAways($homeAways);

        $assignedHomeMap = $assignedCounter->getAssignedHomeMap();

        $statisticsCalculator = new GppStatisticsCalculator(
            $variantWithPoule,
            $assignedHomeMap,
            0,
            new PlaceCounterMap( array_values( $mapper->getPlaceMap($poule) ) ),
            new PlaceCounterMap( array_values($assignedCounter->getAssignedMap() ) ),
            new PlaceCombinationCounterMap( array_values($assignedCounter->getAssignedWithMap() ) ),
            new PlaceCombinationCounterMap( array_values($assignedCounter->getAssignedAgainstMap() ) ),
            $assignedCounter->assignAgainstGppSportsEqually(),
            $difference,
            $this->logger
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
        $homeAwayBalancer = new HomeAwayBalancer($this->logger);

        $maxDiff = $variantWithPoule->allPlacesSameNrOfOfSidePlacesAssignable(Side::Home) ? 0 : 1;
        $reversedHomeAways = $homeAwayBalancer->balance($assignedHomeMap, $gameRound->getAllHomeAways(), $maxDiff);
        $this->updateWithReversedHomeAways($gameRound, $reversedHomeAways);


        return $gameRound;
    }

    /**
     * @param AgainstGppWithPoule $againstWithPoule
     * @param list<HomeAway> $homeAwaysForGameRound
     * @param list<HomeAway> $homeAways
     * @param GppStatisticsCalculator $statisticsCalculator
     * @param AgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignGameRound(
        AgainstGppWithPoule $againstWithPoule,
        array $homeAwaysForGameRound,
        array $homeAways,
        GppStatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $depth = 0
    ): bool {

        if( $againstWithPoule->getTotalNrOfGames() === $statisticsCalculator->getNrOfHomeAwaysAssigned() ) {
            if( $statisticsCalculator->allAssigned() ) {
//                $this->gameRoundOutput->output($gameRound, false, 'ASSIGNED HOMEAWAYS GR' . $gameRound->getNumber());
//                $statisticsCalculator->output();
                return true;
            }
//            $this->gameRoundOutput->output($gameRound, false, 'ASSIGNED HOMEAWAYS GR' . $gameRound->getNumber());
//            $statisticsCalculator->output();
            return false;
        }

        if ($this->timeoutDateTime !== null && (new DateTimeImmutable()) > $this->timeoutDateTime) {
            throw new TimeoutException('exceeded maximum duration', E_ERROR);
        }

        if ($this->isGameRoundCompleted($againstWithPoule, $gameRound)) {
            $nextGameRound = $this->toNextGameRound($gameRound, $homeAways);

            if (!$statisticsCalculator->minimalSportCanStillBeAssigned()) {
                return false;
            }

//            if (!$statisticsCalculator->minimalAgainstCanStillBeAssigned(null)) {
//                return false;
//            }
//            if (!$statisticsCalculator->minimalWithCanStillBeAssigned(null)) {
//                return false;
//            }

//            if( $gameRound->getNumber() === 7 ) {
//                $this->gameRoundOutput->output($gameRound, false, 'ASSIGNED HOMEAWAYS GR' . $gameRound->getNumber());
//                $statisticsCalculator->output();
//                $er = 12;
//            }

            $filteredHomeAways = $statisticsCalculator->filterBeforeGameRound($homeAways);
            if ($gameRound->getNumber() > $this->highestGameRoundNumberCompleted) {
                $this->highestGameRoundNumberCompleted = $gameRound->getNumber();
//                 $this->logger->info('highestGameRoundNumberCompleted: ' . $gameRound->getNumber());
//                $this->gameRoundOutput->output($gameRound, true, 'ASSIGNED HOMEAWAYS');

//                $this->gameRoundOutput->outputHomeAways($filteredHomeAways, null, 'UNASSIGNED HOMEAWAYS BEFORE SORT');
                $filteredHomeAways = $statisticsCalculator->sortHomeAways($filteredHomeAways, $this->logger);
//                $this->gameRoundOutput->outputHomeAways($filteredHomeAways, null, 'UNASSIGNED HOMEAWAYS AFTER SORT');
            }
            // $this->logger->info('gr ' . $gameRound->getNumber() . ' completed ( ' . count($homeAways) . ' => ' . count($filteredHomeAways) .  ' )');

            if( count($filteredHomeAways) < $this->nrOfGamesPerGameRound ) {
                return false;
            }
            if( $this->assignGameRound(
                $againstWithPoule,
                $filteredHomeAways,
                $homeAways,
                $statisticsCalculator,
                $nextGameRound,
                $depth + 1
            ) ) {
                return true;
            }
            else {
                if( $gameRound->getNumber() === 1 ) {
                    $this->logger->info('return to gr  : ' . $gameRound->getNumber() );
                }
            }
        }
        // $this->logger->info('gr ' . $gameRound->getNumber() . ' trying.. ( grgames ' . count($gameRound->getHomeAways()) . ', haGr ' . count($homeAwaysForGameRound) .  ' )');

        return $this->assignSingleGameRound(
            $againstWithPoule,
            $homeAwaysForGameRound,
            $homeAways,
            $statisticsCalculator,
            $gameRound,
            $depth + 1
        );
    }

    /**
     * @param AgainstGppWithPoule $againstWithPoule
     * @param list<HomeAway> $homeAwaysForGameRound
     * @param list<HomeAway> $homeAways
     * @param GppStatisticsCalculator $statisticsCalculator,
     * @param AgainstGameRound $gameRound
     * @param int $depth
     * @return bool
     */
    protected function assignSingleGameRound(
        AgainstGppWithPoule $againstWithPoule,
        array $homeAwaysForGameRound,
        array $homeAways,
        GppStatisticsCalculator $statisticsCalculator,
        AgainstGameRound $gameRound,
        int $depth = 0
    ): bool {

        $triedHomeAways = [];
        while($homeAway = array_shift($homeAwaysForGameRound)) {
            if (!$this->isHomeAwayAssignable($homeAway, $statisticsCalculator)) {
                array_push($triedHomeAways, $homeAway);
                continue;
            }

//            if( $gameRound->getNumber() === 13 ) {
//                $this->gameRoundOutput->output($gameRound, false, 'ASSIGNED HOMEAWAYS GR' . $gameRound->getNumber());
//                $statisticsCalculator->output($this->logger, true, true);
//                $er = 12; // die();
//            }

            $gameRound->add($homeAway);

            $homeAwaysForGameRoundTmp = array_values(
                array_filter(
                    array_merge( $homeAwaysForGameRound, $triedHomeAways),
                    function (HomeAway $homeAway) use ($gameRound): bool {
                        return !$gameRound->isHomeAwayPlaceParticipating($homeAway);
                    }
                )
            );

            if (count($homeAwaysForGameRoundTmp) >= ($this->nrOfGamesPerGameRound - count($gameRound->getHomeAways()))
                && $this->assignGameRound(
                    $againstWithPoule,
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
     * @return list<HomeAway>
     */
    protected function createHomeAways(
        GppHomeAwayCreator $homeAwayCreator,
        Poule $poule,
        AgainstGpp $sportVariant): array
    {
        $variantWithPoule = (new AgainstGppWithPoule($poule, $sportVariant));
        $totalNrOfGames = $variantWithPoule->getTotalNrOfGames();
        $homeAways = [];
        while ( count($homeAways) < $totalNrOfGames ) {
            $homeAways = array_merge($homeAways, $homeAwayCreator->create($variantWithPoule));
        }
        return $homeAways;
    }

    protected function isHomeAwayAssignable(
        HomeAway $homeAway, GppStatisticsCalculator $statisticsCalculator
    ): bool {

//        if( $statisticsCalculator->overAllowedAgainstDifference($homeAway) ) {
////            $this->logger->info('HA : ' . $homeAway);
////            $statisticsCalculator->output();
//            return false;
//        }
//        if( $statisticsCalculator->overAllowedWithDifference($homeAway) ) {
//            return false;
//        }

//        if ($statisticsCalculator->againstWillBeOverAssigned($homeAway)) {
//            return false;
//        }

        // als aantal met minimum kleiner of gelijk is aan het aantal gameplaces, dan ook ok

//        if ($this->nrOfHomeAwaysAssigned < $this->againstGppWithPoule->getTotalNrOfGames()) {
//            return false;
//        }
//
//        $allowedDifference = $this->assignAgainstGppSportsEqually ? 0 : 1;
//        if( $this->assignedMap->getMaxDifference() > $allowedDifference ) {
//            return false;
//        }
//
//        if( $this->assignedAgainstMap->getMaxDifference() > $this->difference->allowedAgainstCumDiff ) {
//            return false;
//        }
//        if( $this->assignedWithMap->getMaxDifference() > $this->difference->allowedWithCumDiff ) {
//            return false;
//        }

//        if( $statisticsCalculator->againstWillBeTooMuchDiffAssigned($homeAway) ) {
//            return false;
//        }

//        if( !$statisticsCalculator->minimalAgainstCanStillBeAssigned($homeAway) ) {
//            return false;
//        }
//        if( $statisticsCalculator->againstWillBeOverAssigned($homeAway) ) {
//            return false;
//        }
        foreach ($homeAway->getPlaces() as $place) {
            if ( $statisticsCalculator->sportWillBeOverAssigned($place) ) {
                return false;
            }
        }

        $statisticsCalculator = $statisticsCalculator->addHomeAway($homeAway);
        if( !$statisticsCalculator->againstWithinMarginDuring() ) {
            return false;
        }

        if( !$statisticsCalculator->withWithinMarginDuring() ) {
            return false;
        }

//        if( !$statisticsCalculator->minimalWithCanStillBeAssigned($homeAway) ) {
//            return false;
//        }
//        if( $statisticsCalculator->withWillBeOverAssigned($homeAway) ) {
//            return false;
//        }
        return true;
    }

    /**
     * @param AgainstGppWithPoule $againstWithPoule
     * @param int $currentGameRoundNumber
     * @param list<HomeAway> $homeAways
     * @return bool
     */
    protected function isOverAssigned(
        AgainstGppWithPoule $againstWithPoule,
        int $currentGameRoundNumber,
        array $homeAways
    ): bool {
        $poule = $againstWithPoule->getPoule();
        $unassignedMap = [];
        foreach ($poule->getPlaces() as $place) {
            $unassignedMap[$place->getNumber()] = new PlaceCounter($place);
        }
        foreach ($homeAways as $homeAway) {
            foreach ($homeAway->getPlaces() as $place) {
                $unassignedMap[$place->getNumber()]->increment();
            }
        }

        $maxNrOfGameGroups = $againstWithPoule->getNrOfGameGroups();
        foreach ($poule->getPlaces() as $place) {
            if ($currentGameRoundNumber + $unassignedMap[$place->getNumber()]->count() > $maxNrOfGameGroups) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param list<HomeAway> $homeAways
     * @return list<HomeAway>
     */
    private function initHomeAways(array $homeAways): array {
        /** @var list<HomeAway> $newHomeAways */
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
     * @param AgainstGameRound $gameRound
     * @param list<HomeAway> $reversedHomeAways
     * @return void
     */
    protected function updateWithReversedHomeAways(AgainstGameRound $gameRound, array $reversedHomeAways): void {
        foreach( $reversedHomeAways as $reversedHomeAway ) {
            $gameRoundIt = $gameRound;
            while($gameRoundIt && !$gameRoundIt->reverseSidesOfHomeAway($reversedHomeAway)) {
                $gameRoundIt = $gameRoundIt->getNext();
            }
        }
    }
}
