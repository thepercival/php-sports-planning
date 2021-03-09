<?php


namespace SportsPlanning\Input;

use SportsHelpers\PouleStructure;
use SportsHelpers\SportConfig;
use SportsHelpers\SportConfig\Service as SportConfigService;

class Calculator
{
    protected SportConfigService $sportConfigService;

    public function __construct()
    {
        $this->sportConfigService = new SportConfigService();
    }

    /**
     * dit aantal kan misschien niet gehaal worden, ivm variatie in poulegrootte en sportConfig->nrOfGamePlaces
     *
     * @param PouleStructure $pouleStructure
     * @param array|SportConfig[] $sportConfigs
     * @param bool $selfReferee
     * @return int
     */
    public function getMaxNrOfGamesPerBatch(PouleStructure $pouleStructure, array $sportConfigs, bool $selfReferee): int
    {
        // sort by lowest nrOfGamePlaces first
        uasort($sportConfigs, function (SportConfig $sportConfigA, SportConfig $sportConfigB): int {
            return $sportConfigA->getNrOfGamePlaces() < $sportConfigB->getNrOfGamePlaces() ? -1 : 1;
        });

        $nrOfBatchGames = 0;
        $nrOfPlaces = $pouleStructure->getNrOfPlaces();
        while ($nrOfPlaces > 0 && count($sportConfigs) > 0) {
            $sportConfig = array_shift($sportConfigs);
            $nrOfFields = $sportConfig->getNrOfFields();
            while ($nrOfPlaces > 0 && $nrOfFields-- > 0) {
                $nrOfPlaces -= ($sportConfig->getNrOfGamePlaces() + ($selfReferee ? 1 : 0));
                if ($nrOfPlaces >= 0) {
                    $nrOfBatchGames++;
                }
            }
        }
        return $nrOfBatchGames === 0 ? 1 : $nrOfBatchGames;
    }

    /**
     * @param PouleStructure $pouleStructure
     * @param array|SportConfig[] $sportConfigs
     * @param bool $selfReferee
     * @return int
     */
    public function getMaxNrOfGamesInARow(
        PouleStructure $pouleStructure,
        array $sportConfigs,
        bool $selfReferee
    ): int {
        // if( $gameMode === GameMode::AGAINST ) {
        $nrOfPoulesByNrOfPlaces = $pouleStructure->getNrOfPoulesByNrOfPlaces();
        $nrOfPlaces = key($nrOfPoulesByNrOfPlaces);
        $nrOfPlaces *= $nrOfPoulesByNrOfPlaces[$nrOfPlaces];
        $maxNrOfBatchPlaces = $this->getMaxNrOfPlacesPerBatch($pouleStructure, $sportConfigs, $selfReferee);

        $nrOfRestPlaces = $nrOfPlaces - $maxNrOfBatchPlaces;
        if ($nrOfRestPlaces <= 0) {
            return $this->sportConfigService->getNrOfGamesPerPlace($nrOfPlaces, $sportConfigs);
        }
        return (int)ceil($nrOfPlaces / $nrOfRestPlaces);
        // }

        // in together mode weet ik het niet!!



        // throw new Exception("SHOULD BE IMPLEMENTED AFTER BIG CHANGE");
//        $nrOfPlaces = $this->pouleStructure->getBiggestPoule();
//
//        $this->maxNrOfGamesInARow = (new GameCalculator())->getNrOfGamesPerPlace(
//            $nrOfPlaces,
//            $this->getTeamup(),
//            $this->getSelfReferee() !== self::SELFREFEREE_DISABLED,
//            $this->getNrOfHeadtohead()
//        );
//        if (!$this->getTeamup() && $this->maxNrOfGamesInARow > ($nrOfPlaces * $this->getNrOfHeadtohead())) {
//            $this->maxNrOfGamesInARow = $nrOfPlaces * $this->getNrOfHeadtohead();
//        }
//
//        return $this->maxNrOfGamesInARow;
        //         const sportPlanningConfigService = new SportPlanningConfigService();
        //         const defaultNrOfGames = sportPlanningConfigService.getNrOfCombinationsExt(this.roundNumber);
        //         const nrOfHeadtothead = nrOfGames / defaultNrOfGames;
        //            $nrOfHeadtohead = 2;
        //            if( $nrOfHeadtohead > 1 ) {
        //                $maxNrOfGamesInARow *= 2;
        //            }
    }

    /**
     * dit aantal kan misschien niet gehaal worden, ivm variatie in poulegrootte en sportConfig->nrOfGamePlaces
     *
     * @param PouleStructure $pouleStructure
     * @param array|SportConfig[] $sportConfigs
     * @param bool $selfReferee
     * @return int
     */
    protected function getMaxNrOfPlacesPerBatch(PouleStructure $pouleStructure, array $sportConfigs, bool $selfReferee): int
    {
        // sort by lowest nrOfGamePlaces first
        uasort($sportConfigs, function (SportConfig $sportConfigA, SportConfig $sportConfigB): int {
            return $sportConfigA->getNrOfGamePlaces() < $sportConfigB->getNrOfGamePlaces() ? -1 : 1;
        });

        $nrOfBatchPlaces = 0;
        $nrOfPlaces = $pouleStructure->getNrOfPlaces();
        while ($nrOfPlaces > 0 && count($sportConfigs) > 0) {
            $sportConfig = array_shift($sportConfigs);
            $nrOfFields = $sportConfig->getNrOfFields();
            while ($nrOfPlaces > 0 && $nrOfFields-- > 0) {
                $nrOfGamePlaces = ($sportConfig->getNrOfGamePlaces() + ($selfReferee ? 1 : 0));
                $nrOfPlaces -= $nrOfGamePlaces;
                $nrOfBatchPlaces += $nrOfGamePlaces;
            }
        }
        if ($nrOfPlaces < 0) {
            $nrOfBatchPlaces += $nrOfPlaces;
        }
        return $nrOfBatchPlaces === 0 ? 1 : $nrOfBatchPlaces;
    }
}
