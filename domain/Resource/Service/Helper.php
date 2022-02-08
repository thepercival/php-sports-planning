<?php

namespace SportsPlanning\Resource\Service;

use Psr\Log\LoggerInterface;
use SportsHelpers\SelfReferee;
use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\Place;
use SportsPlanning\Planning;

class Helper
{
    private const ThresHoldPercentage = 50;
    protected bool $balancedStructure;
    protected int $totalNrOfGames;
    /**
     * @var array<int, array<int, int>>
     */
    protected array $maxNrOfSimultanousGames = [];
    protected Input $input;

    public function __construct(protected Planning $planning, protected LoggerInterface $logger)
    {
        $this->input = $planning->getInput();
        $this->balancedStructure = $this->input->createPouleStructure()->isBalanced();

        $sportVariants = array_values($this->input->createSportVariants()->toArray());
        $this->totalNrOfGames = $this->input->createPouleStructure()->getTotalNrOfGames($sportVariants);
    }

    /**
     * @param Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $previousBatch
     * @param array<TogetherGame|AgainstGame> $gamesForBatchTmp
     */
    public function sortGamesForNextBatch(
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $previousBatch,
        array &$gamesForBatchTmp,
        InfoToAssign $infoToAssign
    ): void {
        uasort(
            $gamesForBatchTmp,
            function (TogetherGame|AgainstGame $gameA, TogetherGame|AgainstGame $gameB) use (
                $previousBatch,
                $infoToAssign
            ): int {
                $mostToAssignA = $this->getMostToAssign($gameA, $infoToAssign);
                $mostToAssignB = $this->getMostToAssign($gameB, $infoToAssign);
                if ($mostToAssignB !== $mostToAssignA) {
                    return $mostToAssignB - $mostToAssignA;
                }
                $sumToAssignA = $this->getSumToAssign($gameA, $infoToAssign);
                $sumToAssignB = $this->getSumToAssign($gameB, $infoToAssign);
                if ($sumToAssignB !== $sumToAssignA) {
                    return $sumToAssignB - $sumToAssignA;
                }
                $amountA = count(
                    $gameA->getPoulePlaces()->filter(function (Place $place) use ($previousBatch): bool {
                        return !$previousBatch->isParticipating($place);
                    })
                );
                $amountB = count(
                    $gameB->getPoulePlaces()->filter(function (Place $place) use ($previousBatch): bool {
                        return !$previousBatch->isParticipating($place);
                    })
                );
                return $amountB - $amountA;
            }
        );

//        uasort(
//            $gamesForBatchTmp,
//            function (TogetherGame|AgainstGame $gameA, TogetherGame|AgainstGame $gameB) use ($previousBatch): int {
//                $amountA = count(
//                    $gameA->getPoulePlaces()->filter(function (Place $place) use ($previousBatch): bool {
//                        return !$previousBatch->isParticipating($place);
//                    })
//                );
//                $amountB = count(
//                    $gameB->getPoulePlaces()->filter(function (Place $place) use ($previousBatch): bool {
//                        return !$previousBatch->isParticipating($place);
//                    })
//                );
//                return $amountB - $amountA;
//            }
//        );
    }

    protected function getMostToAssign(AgainstGame|TogetherGame $game, InfoToAssign $infoToAssign): int
    {
        $mosts = $game->getPoulePlaces()->map(function (Place $place) use ($infoToAssign): int {
            return $infoToAssign->getPlaceInfoMap()[$place->getUniqueIndex()]->getNrOfGames();
        })->toArray();
        return count($mosts) > 0 ? max($mosts) : 0;
    }

    protected function getSumToAssign(AgainstGame|TogetherGame $game, InfoToAssign $infoToAssign): int
    {
        $x = array_sum(
            $game->getPoulePlaces()->map(function (Place $place) use ($infoToAssign): int {
                return $infoToAssign->getPlaceInfoMap()[$place->getUniqueIndex()]->getNrOfGames();
            })->toArray()
        );
        return $x;
    }

    /**
     * @param int $batchNumber
     * @param InfoToAssign $infoToAssign
     * @return bool
     */
    public function canGamesCanBeAssigned(int $batchNumber, InfoToAssign $infoToAssign): bool
    {
        if ($infoToAssign->isEmpty()) {
            return true;
        }
        $maxNrOfBatchesToGo = $this->planning->getMaxNrOfBatches() - $batchNumber;
        if ($this->willMaxNrOfBatchesBeExceeded($maxNrOfBatchesToGo, $infoToAssign)) {
            return false;
        }
        if ($this->willMinNrOfBatchGamesBeReached($maxNrOfBatchesToGo, $infoToAssign)) {
            return true;
        }
        return false;
    }


    public function willMaxNrOfBatchesBeExceeded(int $maxNrOfBatchesToGo, InfoToAssign $infoToAssign): bool
    {
        if ($this->willMaxNrOfBatchesBeExceededForSports($maxNrOfBatchesToGo, $infoToAssign)) {
            return true;
        }
        if ($this->willMaxNrOfBatchesBeExceededForPlaces($maxNrOfBatchesToGo, $infoToAssign)) {
            return true;
        }
        return false;
    }

    public function willMaxNrOfBatchesBeExceededForSports(int $maxNrOfBatchesToGo, InfoToAssign $infoToAssign): bool
    {
        if ($infoToAssign->isEmpty()) {
            return false;
        }

        $simCalculator = new SimCalculator($this->input);

        foreach ($infoToAssign->getSportInfoMap() as $sportInfo) {
            $maxNrOfSportGamesSim = $simCalculator->getMaxNrOfSimultaneousSportGames($sportInfo);
            $minNrOfBatches = (int)ceil($sportInfo->getNrOfGames() / $maxNrOfSportGamesSim);
            if ($minNrOfBatches > $maxNrOfBatchesToGo) {
                return true;
            }
        }

        $maxNrOfGamesSim = $simCalculator->getMaxNrOfGamesPerBatch($infoToAssign);
        $minNrOfBatches = (int)ceil($infoToAssign->getNrOfGames() / $maxNrOfGamesSim);
        return $minNrOfBatches > $maxNrOfBatchesToGo;
    }

    public function willMaxNrOfBatchesBeExceededForPlaces(int $maxNrOfBatchesToGo, InfoToAssign $infoToAssign): bool
    {
        if ($infoToAssign->isEmpty()) {
            return false;
        }
        foreach ($infoToAssign->getPlaceInfoMap() as $placeInfo) {
            if ($placeInfo->getNrOfGames() > $maxNrOfBatchesToGo) {
                return true;
            }
        }


        // //////////////////////
        // per poule en sport kijken als het nog gehaald kunnen worden
        $inputCalculator = new InputCalculator();
//        $simCalculator = new SimCalculator($this->input);
        foreach ($infoToAssign->getSportInfoMap() as $sportInfo) {
            foreach ($sportInfo->getPouleGameCounters() as $pouleGameCounter) {
                // all pouleplaces
                $nrOfPlaces = count($pouleGameCounter->getPoule()->getPlaces());
                $maxNrOfBatchGames = $inputCalculator->getMaxNrOfSportPouleGamesPerBatchByPlaces(
                    $nrOfPlaces,
                    $sportInfo->getVariant(),
                    $this->input->getRefereeInfo()->selfReferee
                );
//                $nrOfSimGames = $simCalculator->getNrOfSimultaneousGamesByPlaces($nrOfPlaces, $sportInfo->getVariant() );
                $nrOfBatchesNeeded = (int)ceil($pouleGameCounter->getNrOfGames() / $maxNrOfBatchGames);
                if ($nrOfBatchesNeeded > $maxNrOfBatchesToGo) {
                    return true;
                }

                // only assigned places
                $nrOfPlaces = $pouleGameCounter->getNrOfDistinctPlacesAssigned();
                $maxNrOfBatchGames = $inputCalculator->getMaxNrOfSportPouleGamesPerBatchByPlaces(
                    $nrOfPlaces,
                    $sportInfo->getVariant(),
                    SelfReferee::Disabled /*$this->input->getRefereeInfo()->selfReferee*/
                );
//                $nrOfSimGames = $simCalculator->getNrOfSimultaneousGamesByPlaces($nrOfPlaces, $sportInfo->getVariant(), SelfReferee::Disabled);
                $nrOfBatchesNeeded = (int)ceil($pouleGameCounter->getNrOfGames() / $maxNrOfBatchGames);
                if ($nrOfBatchesNeeded > $maxNrOfBatchesToGo) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param int $maxNrOfBatchesToGo
     * @param InfoToAssign $infoToAssign
     * @param int $nrOfGamesToAssign
     * @return bool
     */
    public function willMinNrOfBatchGamesBeReached(int $maxNrOfBatchesToGo, InfoToAssign $infoToAssign): bool
    {
        // $sportInfosWithMoreNrOfBatchesNeeded = $this->getSportInfosWithMoreNrOfBatchesNeeded($sportInfoMap);
        $simCalculator = new SimCalculator($this->input);
        $maxNrOfGamesSim = $simCalculator->getMaxNrOfGamesPerBatch($infoToAssign);
        if ($maxNrOfGamesSim < $this->planning->getMinNrOfBatchGames()) {
            return false;
        }

        $sortedSportInfos = $this->getSportInfosSortedByNrOfSimGames($infoToAssign);
        // $sortedSportInfos = $sportInfoMap->getSportInfoMap();

        $nrOfSimultaneousGames = 0;
        while ($nrOfSimultaneousGames < $this->planning->getMinNrOfBatchGames()) {
            $sportInfo = array_shift($sortedSportInfos);
            if ($sportInfo === null) {
                return false;
            }
            $nrOfSimultaneousGames += $simCalculator->getMaxNrOfSimultaneousSportGames($sportInfo);
        }
//        if (count($sortedSportInfos) > 0) { // not all sports needed
//            return true;
//        }
        // als alle sporten nodig zijn, dan mag er max. 1 verschil zitten tussen NrOfBatchesNeeded

//        $sportMath = new SportMath();
//        $lcm = $sportMath->getLeastCommonMultiple($assignableNrOfFieldsInfoMap);
//        $nrOfSportsWithLeastNrOfGames = 0;

// THIS GOES WRONG FOR ExtraTest::test10()  because one sport has 2 FIELDS
//        $leastNrOfBatchesNeeded = null;
//        $mostNrOfBatchesNeeded = null;
//        foreach ($infoToAssign->getSportInfoMap() as $sportInfo) {
//            $nrOfBatchesNeeded = $simCalculator->getMinNrOfBatchesNeeded($sportInfo);
//            if ($leastNrOfBatchesNeeded === null || $nrOfBatchesNeeded < $leastNrOfBatchesNeeded) {
//                $leastNrOfBatchesNeeded = $nrOfBatchesNeeded;
//            }
//            if ($mostNrOfBatchesNeeded === null || $nrOfBatchesNeeded > $mostNrOfBatchesNeeded) {
//                $mostNrOfBatchesNeeded = $nrOfBatchesNeeded;
//            }
//            if (($mostNrOfBatchesNeeded - $leastNrOfBatchesNeeded) > 1) {
//                return false;
//            }
//        }
        return true;
    }

    /**
     * @param InfoToAssign $infoToAssign
     * @return list<SportInfo>
     */
    public function getSportInfosSortedByNrOfSimGames(InfoToAssign $infoToAssign): array
    {
        $simCalculator = new SimCalculator($this->input);
        $sportInfos = $infoToAssign->getSportInfoMap();
        uasort($sportInfos, function (SportInfo $infoA, SportInfo $infoB) use ($simCalculator): int {
            $nrOfSimGamesA = $simCalculator->getMaxNrOfSimultaneousSportGames($infoA);
            $nrOfSimGamesB = $simCalculator->getMaxNrOfSimultaneousSportGames($infoB);
            return $nrOfSimGamesA > $nrOfSimGamesB ? -1 : 1;
        });
        return array_values($sportInfos);
    }

//    /**
//     * @param InfoToAssign $sportInfoMap
//     * @return list<SportInfo>
//     */
//    public function getSportInfosWithMoreNrOfBatchesNeeded(InfoToAssign $infoToAssign): array
//    {
////        /** @var list<int> $assignableNrOfFieldsInfoMap */
////        $assignableNrOfFieldsInfoMap = array_map(function (SportInfo $sportInfo): int {
////            return $sportInfo->getSport()->getNrOfFields();
////        }, $sportsToAssignInfoMap);
//
//        // tel de velden vande sporten van de games op en kijk als dat minder
////        $maxNrOfBatchGamesByFields = array_sum($assignableNrOfFieldsInfoMap);
////        if (!$this->input->hasMultipleSports()) {
////            return $maxNrOfBatchGamesByFields;
////        }
//
//
//        // kijk per veld hoeveel wedstrijden er op gespeeld worden en geef de laagste terug
//        // sport 1 (1 veld) 8 wedstrijden
//        // sport 2 (1 veld) 10 wedstrijden
//        // retourneren 1
//
//        // sport 1 (1 veld) 8 wedstrijden
//        // sport 2 (1 veld) 8 wedstrijden
//        // sport 3 (1 veld) 10 wedstrijden
//        // retourneren 2
//
//        // sport 1 (1 veld)  8 wedstrijden
//        // sport 2 (1 veld)  8 wedstrijden
//        // sport 3 (2 veld) 16 wedstrijden
//        // sport 4 (1 veld) 10 wedstrijden
//        // retourneren 3
//
//        // het aantal sporten met de minste wedstrijden per veld(aantal wedstrijden / aantal velden)
//
//        $simCalculator = new SimCalculator($this->input);
////        $sportMath = new SportMath();
////        $lcm = $sportMath->getLeastCommonMultiple($assignableNrOfFieldsInfoMap);
////        $nrOfSportsWithLeastNrOfGames = 0;
//        $leastNrOfBatchesNeeded = null;
//        foreach ($infoToAssign->getSportInfoMap() as $sportInfo) {
//            $nrOfBatchesNeeded = $simCalculator->getMinNrOfBatchesNeeded($sportInfo);
//            if ($leastNrOfBatchesNeeded === null || $nrOfBatchesNeeded < $leastNrOfBatchesNeeded) {
//                // $sportInfoWithLeastNrOfBatchesNeeded = $sportInfo;
//                $leastNrOfBatchesNeeded = $nrOfBatchesNeeded;
//            }
////            $muliplier = $lcm / $sportInfo->getSport()->getNrOfFields();
////            $nrOfGames = $sportInfo->getNrOfGames() * $muliplier;
////            if ($leastNrOfGames === null || $nrOfGames < $leastNrOfGames) {
////                $leastNrOfGames = $nrOfGames;
////                $nrOfSportsWithLeastNrOfGames = 1;
////            } else {
////                if ($nrOfGames === $leastNrOfGames) {
////                    $nrOfSportsWithLeastNrOfGames++;
////                }
////            }
//        }
//
//        return array_values(array_filter($infoToAssign->getSportInfoMap(), function (SportInfo $sportInfo) use ($simCalculator, $leastNrOfBatchesNeeded): bool {
//            return $simCalculator->getMinNrOfBatchesNeeded($sportInfo) > $leastNrOfBatchesNeeded;
//        }));
//    }
//        $nrOfSportsWithLeastNrOfGames /= $lcm;
    //        return $nrOfSportsWithLeastNrOfGames / aantal velden = aantal batches;
    //        dan moet je weten hoeveel batches je nog mag doen

//        $minNrOfBatchesNeeded = AssignleBatches
//
//        $maxNrOfAssignleBatches = $this->planning->getMaxNrOfBatches() - $currentBatchNr;
//        $ertt = 12;
//        return $maxNrOfBatches;

    // als je weet h


//    /**
//     * @param array<int, SportInfo> $sportsToAssignInfoMap
//     * @return bool
//     */
//    public function biggestPouleGamesStillCanBeAssigned(array $sportsToAssignInfoMap): bool
//    {
//        $biggestPoules = $assignableSportInfoMap->getBiggestPoule;
//        $nrOfPlaces = $biggestPoule->getPlaces()->count();
//        $gamesForPoule = $this->getGamesForPoule($biggestPoule, $games);
//
    ////        $game = reset($games);
    ////        if ($game === false) {
    ////            return true;
    ////        }
//
//        $maxNrOfGamesSim = $this->getMaxNrOfSimultanousPouleGames($game->getSport(), $nrOfPlaces);
//
//        $maxNrOfPouleGamesPerBatch = $this->input->getMaxNrOfBatchGames();
//        if ($maxNrOfGamesSim < $maxNrOfPouleGamesPerBatch) {
//            $maxNrOfPouleGamesPerBatch = $maxNrOfGamesSim;
//        }
//        $nrOfBatchesNeeded = (int)ceil(count($gamesForPoule) / $maxNrOfPouleGamesPerBatch);
//
//        $nrOfOtherPoulesGames = count($games) - count($gamesForPoule);
//        $minNrOfOtherPoulesGamesPerBatch = $this->planning->getMinNrOfBatchGames() - $maxNrOfPouleGamesPerBatch;
//        $nrOfOtherPoulesGamesNeeded = $nrOfBatchesNeeded * $minNrOfOtherPoulesGamesPerBatch;
//        return $nrOfOtherPoulesGames >= $nrOfOtherPoulesGamesNeeded;
//    }
}
