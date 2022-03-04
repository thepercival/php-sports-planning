<?php

namespace SportsPlanning\Resource\Service;

use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant as SportVariant;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Input;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\Resource\UniquePlacesCounter;
use SportsPlanning\Referee\Info as RefereeInfo;

class SimCalculator
{
    private RefereeInfo $refereeInfo;
    private InputCalculator $inputCalculator;
    private PouleStructure $pouleStructure;

    public function __construct(Input $input)
    {
        $this->inputCalculator = new InputCalculator();
        $this->refereeInfo = $input->getRefereeInfo();
        $this->pouleStructure = $input->createPouleStructure();
        // $this->balancedStructure = $this->input->createPouleStructure()->isBalanced();
        // $sportVariants = array_values($this->input->createSportVariants()->toArray());
        // $this->totalNrOfGames = $this->input->createPouleStructure()->getTotalNrOfGames($sportVariants);
    }

    public function getMaxNrOfGamesPerBatch(InfoToAssign $infoToAssign): int
    {
        $maxNrOfGamesPerBatch = 0;
        foreach ($infoToAssign->getSportInfoMap() as $sportInfo) {
            $maxNrOfGamesPerBatch += $this->getMaxNrOfSimultaneousSportGames($sportInfo);
        }
        return $maxNrOfGamesPerBatch;
//        $maxNrOfGamesPerBatch = $this->inputCalculator->reduceByReferees($maxNrOfGamesPerBatch, $this->refereeInfo);
//        return $this->reduceByPlaces($maxNrOfGamesPerBatch, $infoToAssign);
    }

    public function getMaxNrOfSimultaneousSportGames(SportInfo $sportInfo): int
    {
        $pouleStructure = $this->getPouleStructureFromPoulesToAssign($sportInfo);
        $sportVariant = new SportVariantWithFields($sportInfo->getVariant(), $sportInfo->getSport()->getNrOfFields());
        $maxNrOfGamesPerBatch = $this->inputCalculator->getMaxNrOfSportGamesPerBatchByPlaces(
            $pouleStructure,
            $sportVariant,
            $this->refereeInfo
        );

        return $this->inputCalculator->reduceByFields($maxNrOfGamesPerBatch, $sportVariant->getNrOfFields());
    }

    /**
     * @param SportInfo $sportInfo
     * @return PouleStructure
     */
    protected function getPouleStructureFromPoulesToAssign(SportInfo $sportInfo): PouleStructure
    {
        /** @var list<int> $nrOfPlacesPerPoule */
        $nrOfPlacesPerPoule = array_map(function (UniquePlacesCounter $uniquePlacesCounter): int {
            return count($uniquePlacesCounter->getPoule()->getPlaces());
        }, $sportInfo->getUniquePlacesCounters());
        return new PouleStructure(...$nrOfPlacesPerPoule);
    }

    public function reduceByPlaces(int $maxNrOfGamesPerBatch, InfoToAssign $infoToAssign): int
    {
        $nrOfGamesPerBatch = 0;
        foreach ($this->pouleStructure->toArray() as $nrOfPlaces) {
            foreach ($infoToAssign->getSportInfoMap() as $sportInfo) {
                $nrOfGamePlaces = $this->getNrOfGamePlaces($sportInfo->getSport()->createVariant(), $nrOfPlaces);
                if ($nrOfGamePlaces <= $nrOfPlaces) {
                    $nrOfGamesPerBatch++;
                    $nrOfPlaces -= $nrOfGamePlaces;
                }
            }
        }
        return $nrOfGamesPerBatch < $maxNrOfGamesPerBatch ? $nrOfGamesPerBatch : $maxNrOfGamesPerBatch;
    }

    protected function getNrOfGamePlaces(SportVariant $sportVariant, int $nrOfPlaces): int
    {
        if ($sportVariant instanceof SingleSportVariant || $sportVariant instanceof AgainstSportVariant) {
            return $sportVariant->getNrOfGamePlaces();
        }
        return $nrOfPlaces;
    }

//
//    // uitgaan van het aantal wedstrijden en velden per sport en scheidsrechters
//    // aantal pouleplekken niet, want je kunt verschillende poules hebben met
//    // verschillende aantallen

    public function getMinNrOfBatchesNeeded(SportInfo $sportInfoToAssign): int
    {
        $maxNrOfSimultaneousGames = $this->getMaxNrOfSimultaneousSportGames($sportInfoToAssign);
        return (int)ceil($sportInfoToAssign->getNrOfGames() / $maxNrOfSimultaneousGames);
    }




//    /**
//     * @param array<int, SportInfo> $gameCounters
//     * @return list<PouleCounter>
//     */
//    protected function getGameCountersByLeastNrOfPoulePlaces(array $gameCounters): array
//    {
//        uasort(
//            $gameCounters,
//            function (PouleCounter $counterA, PouleCounter $counterB): int {
//                $nrOfPoulePlacesA = count($counterA->getPoule()->getPlaces());
//                $nrOfPoulePlacesB = count($counterB->getPoule()->getPlaces());
//                return $nrOfPoulePlacesA < $nrOfPoulePlacesB ? -1 : 1;
//            }
//        );
//        return array_values($gameCounters);
//    }

//    // @TODO CDK HOUD REKENING MET SELFREFERE OTHER POULE
//    protected function getMaxNrOfSimultaneousPouleGames(Sport $sport, int $nrOfPlaces): int
//    {
//        $nrOfGamesOneGameRound = $sport->createVariant()->getNrOfGamesOneGameRound($nrOfPlaces);
////        if (!array_key_exists($sport->getNumber(), $this->maxNrOfSimultanousGames)) {
////            $this->maxNrOfSimultanousGames[$sport->getNumber()] = [];
////        }
////        if (array_key_exists($nrOfPlaces, $this->maxNrOfSimultanousGames[$sport->getNumber()])) {
////            return $this->maxNrOfSimultanousGames[$sport->getNumber()][$nrOfPlaces];
////        }
//
//        $max = $this->getMaxNrOfSimultanousGamesHelper($sport, $nrOfPlaces);
//        $this->maxNrOfSimultanousGames[$sport->getNumber()][$nrOfPlaces] = $max;
//        return $max;
//    }
//
//    protected function getMaxNrOfSimultanousGamesForNrOfPlaces(Sport $sport, int $nrOfPlaces): int
//    {
//        // aantal wedstrijden per batch
//        $selfRefereeSamePoule = $this->selfReferee === SelfReferee::SamePoule;
//        $sportVariant = $sport->createVariant();
//        $nrOfGamePlaces = $this->getNrOfGamePlaces($sportVariant, $nrOfPlaces, $selfRefereeSamePoule);
//
//        $maxGames = (int)floor($nrOfPlaces / $nrOfGamePlaces);
//        if ($sport->getFields()->count() < $maxGames) {
//            $maxGames = $sport->getFields()->count();
//        }
//
//        return $maxGames;
//    }
//
//    public function getNrOfGamePlaces(
//        SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant $sportVariant,
//        int $nrOfPlaces,
//        bool $selfRefereeSamePoule
//    ): int {
//        if ($sportVariant instanceof AgainstSportVariant) {
//            return $sportVariant->getNrOfGamePlaces() + ($selfRefereeSamePoule ? 1 : 0);
//        } elseif ($sportVariant instanceof SingleSportVariant) {
//            return $sportVariant->getNrOfGamePlaces() + ($selfRefereeSamePoule ? 1 : 0);
//        }
//        return $nrOfPlaces;
//    }
}
