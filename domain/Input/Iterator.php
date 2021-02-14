<?php

namespace SportsPlanning\Input;

use Exception;
use SportsHelpers\GameMode;
use SportsHelpers\SportBase;
use SportsHelpers\SportConfig;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Input as PlanningInput;
use SportsHelpers\Range;
use SportsHelpers\PouleStructure\BalancedIterator as PouleStructureIterator;
use SportsHelpers\Place\Range as PlaceRange;
use SportsPlanning\Input\Service as PlanningInputService;

class Iterator implements \Iterator
{
    private const DEFAULTNROFGAMEPLACES = 2;
    protected PouleStructureIterator $structureIterator;
    protected Range $rangeGameAmount;
    protected Range $rangeNrOfSports;
    protected Range $rangeNrOfReferees;
    protected Range $rangeNrOfFields;
    protected int $maxFieldsMultipleSports = 6;
    /**
     * @var PlanningInputService
     */
    protected $planningInputService;
    protected $nrOfPoules;
    protected int $nrOfSports;
    protected int $nrOfReferees;
    protected int $nrOfFields;
    protected int $gameMode;
    protected int $selfReferee;
    protected int $nrOfGamesPlaces;
    protected int $gameAmountNumber;
    /**
     * @var PlanningInput|null
     */
    protected $current;

    public function __construct(
        PlaceRange $rangePlaces,
        Range $rangePoules,
        Range $rangeNrOfSports,
        Range $rangeNrOfFields,
        Range $rangeNrOfReferees,
        Range $rangeGameAmount
    ) {
        $this->structureIterator = new PouleStructureIterator($rangePlaces, $rangePoules);
        $this->rangeNrOfSports = $rangeNrOfSports;
        $this->rangeNrOfFields = $rangeNrOfFields;
        $this->rangeNrOfReferees = $rangeNrOfReferees;
        $this->rangeGameAmount = $rangeGameAmount;
        $this->gameMode = GameMode::AGAINST;
        $this->maxFieldsMultipleSports = 6;

        $this->planningInputService = new PlanningInputService();

        $this->nrOfGamesPlaces = self::DEFAULTNROFGAMEPLACES; // @TODO SHOULD BE IN ITERATION

        $this->init();
    }

    /**
     * @param int $nrOfSports
     * @param int $nrOfFields
     * @param int $gameAmountNumber
     * @return array|SportConfig[]]
     */
    protected function createSportConfigs(int $nrOfSports, int $nrOfFields, int $gameAmountNumber): array
    {
        $sports = [];
        $nrOfFieldsPerSport = (int)ceil($nrOfFields / $nrOfSports);
        for ($sportNr = 1; $sportNr <= $nrOfSports; $sportNr++) {
            $sport = new SportBase(self::DEFAULTNROFGAMEPLACES, );
            $sports[] = new SportConfig($sport, $nrOfFieldsPerSport, $gameAmountNumber);
            $nrOfFields -= $nrOfFieldsPerSport;
            if (($nrOfFieldsPerSport * ($nrOfSports - $sportNr)) > $nrOfFields) {
                $nrOfFieldsPerSport--;
            }
        }
        return $sports;
    }

    protected function init()
    {
        $this->initStructure();
        if (!$this->structureIterator->valid()) {
            return;
        }
        $this->current = $this->createInput();
    }

    protected function initStructure()
    {
        $this->initNrOfSports();
    }

    protected function initNrOfSports()
    {
        $this->nrOfSports = $this->rangeNrOfSports->min;
        $this->initNrOfFields();
    }

    protected function initNrOfFields()
    {
        if ($this->rangeNrOfFields->min >= $this->nrOfSports) {
            $this->nrOfFields = $this->rangeNrOfFields->min;
        } else {
            $this->nrOfFields = $this->nrOfSports;
        }
        $this->initNrOfReferees();
    }

    protected function initNrOfReferees()
    {
        $this->nrOfReferees = $this->rangeNrOfReferees->min;
        $this->initGameAmount();
    }

    protected function initGameAmount()
    {
        $this->gameAmountNumber = $this->rangeGameAmount->min;

        $this->initSelfReferee();
    }

    protected function initSelfReferee()
    {
        $this->selfReferee = PlanningInput::SELFREFEREE_DISABLED;
    }

    public function current() : ?PlanningInput
    {
        return $this->current;
    }

    public function key() : string
    {
        $planningInputOutput = new PlanningOutput();
        return $planningInputOutput->getInputAsString($this->current);
    }

    public function next()
    {
        if ($this->current === null) {
            return;
        }

        if ($this->incrementValue() === false) {
            $this->current = null;
            return;
        }

        $this->current = $this->createInput();

//        $maxNrOfRefereesInPlanning = $planningInput->getMaxNrOfBatchGames(
//            Resources::FIELDS + Resources::PLACES
//        );
//        if ($this->nrOfReferees < $this->nrOfFields && $this->nrOfReferees > $maxNrOfRefereesInPlanning) {
//            if ($this->incrementNrOfFields() === false) {
//                return;
//            }
//            $this->current = $this->createInput();
//        }
//
//        $maxNrOfFieldsInPlanning = $planningInput->getMaxNrOfBatchGames(
//            Resources::REFEREES + Resources::PLACES
//        );
//        if ($this->nrOfFields < $this->nrOfReferees && $this->nrOfFields > $maxNrOfFieldsInPlanning) {
//            if ($this->incrementNrOfSports() === false) {
//                return;
//            }
//            $this->current = $this->createInput();
//        }
    }

    public function rewind()
    {
        throw new Exception("rewind is not implemented", E_ERROR);
    }

    public function valid() : bool
    {
        return $this->current !== null;
    }

    protected function createInput(): PlanningInput
    {
        $sportConfigs = $this->createSportConfigs($this->nrOfSports, $this->nrOfFields, $this->gameAmountNumber);
        return new PlanningInput(
            $this->structureIterator->current(),
            $sportConfigs,
            $this->gameMode,
            $this->nrOfReferees,
            $this->selfReferee
        );
    }

    protected function incrementValue(): bool
    {
        return $this->incrementSelfReferee();
    }

    protected function incrementSelfReferee(): bool
    {
        if ($this->nrOfReferees > 0 || $this->selfReferee === PlanningInput::SELFREFEREE_SAMEPOULE) {
            return $this->incrementGameAmount();
        }

        // $nrOfGamePlaces = (new GameCalculator())->getNrOfGamePlaces($this->nrOfGamesPlaces, $this->teamup, false);
        $nrOfGamePlaces = self::DEFAULTNROFGAMEPLACES;
        $pouleStructure = $this->structureIterator->current();
        $sportConfigs = $this->createSportConfigs($this->nrOfSports, $this->nrOfFields, $this->gameAmountNumber);
        $selfRefereeIsAvailable = $this->planningInputService->canSelfRefereeBeAvailable($pouleStructure, $sportConfigs);
        if ($selfRefereeIsAvailable === false) {
            return $this->incrementGameAmount();
        }
        if ($this->selfReferee === PlanningInput::SELFREFEREE_DISABLED) {
            if ($this->planningInputService->canSelfRefereeOtherPoulesBeAvailable($pouleStructure)) {
                $this->selfReferee = PlanningInput::SELFREFEREE_OTHERPOULES;
            } else {
                $this->selfReferee = PlanningInput::SELFREFEREE_SAMEPOULE;
            }
        } else {
            $selfRefereeSamePouleAvailable = $this->planningInputService->canSelfRefereeSamePouleBeAvailable(
                $pouleStructure,
                $sportConfigs
            );
            if (!$selfRefereeSamePouleAvailable) {
                return $this->incrementGameAmount();
            }
            $this->selfReferee = PlanningInput::SELFREFEREE_SAMEPOULE;
        }
        return true;
    }



//    protected function incrementTeamup(): bool
//    {
//        if ($this->teamup === true) {
//            return $this->incrementNrOfHeadtohead();
//        }
//        $pouleStructure = $this->structureIterator->current();
//        $sportConfigs = $this->createSportConfigs($this->nrOfSports, $this->nrOfFields);
//        $teamupAvailable = $this->planningInputService->canTeamupBeAvailable($pouleStructure, $sportConfigs);
//        if ($teamupAvailable === false) {
//            return $this->incrementNrOfHeadtohead();
//        }
//        $this->teamup = true;
//        $this->initSelfReferee();
//        return true;
//    }

    protected function incrementGameAmount(): bool
    {
        if ($this->gameAmountNumber === $this->rangeGameAmount->max) {
            return $this->incrementNrOfReferees();
        }
        $this->gameAmountNumber++;
        $this->initSelfReferee();
        return true;
    }

    protected function incrementNrOfReferees(): bool
    {
        $maxNrOfReferees = $this->rangeNrOfReferees->max;
        $nrOfPlaces = $this->structureIterator->current()->getNrOfPlaces();
        $maxNrOfRefereesByPlaces = (int)(ceil($nrOfPlaces / 2));
        if ($this->nrOfReferees >= $maxNrOfReferees || $this->nrOfReferees >= $maxNrOfRefereesByPlaces) {
            return $this->incrementNrOfFields();
        }
        $this->nrOfReferees++;
        $this->initGameAmount();
        return true;
    }

    protected function incrementNrOfFields(): bool
    {
        $maxNrOfFields = $this->rangeNrOfFields->max;
        $nrOfPlaces = $this->structureIterator->current()->getNrOfPlaces();
        $maxNrOfFieldsByPlaces = (int)(ceil($nrOfPlaces / 2));
        if ($this->nrOfFields >= $maxNrOfFields || $this->nrOfFields >= $maxNrOfFieldsByPlaces) {
            return $this->incrementNrOfSports();
        }
        $this->nrOfFields++;
        $this->initNrOfReferees();
        return true;
    }

    protected function incrementNrOfSports(): bool
    {
        if ($this->nrOfSports === $this->rangeNrOfSports->max) {
            return $this->incrementStructure();
        }
        $this->nrOfSports++;
        $this->initNrOfFields();
        return true;
    }

    protected function incrementStructure(): bool
    {
        $this->structureIterator->next();
        if (!$this->structureIterator->valid()) {
            return false;
        }
        $this->initNrOfSports();
        return true;
    }

    /*if ($nrOfCompetitors === 6 && $nrOfPoules === 1 && $nrOfSports === 1 && $nrOfFields === 2
        && $nrOfReferees === 0 && $nrOfHeadtohead === 1 && $teamup === false && $selfReferee === false ) {
        $w1 = 1;
    } else*/ /*if ($nrOfCompetitors === 12 && $nrOfPoules === 2 && $nrOfSports === 1 && $nrOfFields === 4
            && $nrOfReferees === 0 && $nrOfHeadtohead === 1 && $teamup === false && $selfReferee === false ) {
            $w1 = 1;
        } else {
            continue;
        }*/

//        $multipleSports = count($sportConfig) > 1;
//        $newNrOfHeadtohead = $nrOfHeadtohead;
//        if ($multipleSports) {
//            //                                    if( count($sportConfig) === 4 && $sportConfig[0]["nrOfFields"] == 1 && $sportConfig[1]["nrOfFields"] == 1
//            //                                        && $sportConfig[2]["nrOfFields"] == 1 && $sportConfig[3]["nrOfFields"] == 1
//            //                                        && $teamup === false && $selfReferee === false && $nrOfHeadtohead === 1 && $structureConfig == [3]  ) {
//            //                                        $e = 2;
//            //                                    }
//            $newNrOfHeadtohead = $this->planningInputSerivce->getSufficientNrOfHeadtohead(
//                $nrOfHeadtohead,
//                min($structureConfig),
//                $teamup,
//                $selfReferee,
//                $sportConfig
//            );
//        }

//        $planningInput = new PlanningInput(
//            $structureConfig,
//            $sportConfig,
//            $nrOfReferees,
//            $teamup,
//            $selfReferee,
//            $newNrOfHeadtohead
//        );
//
//        if (!$multipleSports) {
//            $maxNrOfFieldsInPlanning = $planningInput->getMaxNrOfBatchGames(
//                Resources::REFEREES + Resources::PLACES
//            );
//            if ($nrOfFields > $maxNrOfFieldsInPlanning) {
//                return;
//            }
//        } else {
//            if ($nrOfFields > self::MAXNROFFIELDS_FOR_MULTIPLESPORTS) {
//                return;
//            }
//        }
}
