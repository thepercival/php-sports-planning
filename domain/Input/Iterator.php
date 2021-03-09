<?php

namespace SportsPlanning\Input;

use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Input as PlanningInput;
use SportsHelpers\Range;
use SportsPlanning\SelfReferee;
use SportsHelpers\PouleStructure\BalancedIterator as PouleStructureIterator;
use SportsHelpers\Place\Range as PlaceRange;
use SportsPlanning\Input\Service as PlanningInputService;

class Iterator implements \Iterator
{
    protected PouleStructureIterator $structureIterator;
    protected SportsIterator $sportsIterator;
    protected Range $rangeNrOfReferees;
    protected PlanningInputService $planningInputService;
    protected int $nrOfReferees;
    protected int $selfReferee;
    /**
     * @var PlanningInput|null
     */
    protected $current;

    public function __construct(
        PlaceRange $rangePlaces,
        Range $rangePoules,
        Range $rangeNrOfReferees,
        Range $rangeNrOfFields,
        Range $rangeGameAmount
    ) {
        $this->structureIterator = new PouleStructureIterator($rangePlaces, $rangePoules);
        $this->sportsIterator = new SportsIterator($rangeNrOfFields, $rangeGameAmount);
        $this->rangeNrOfReferees = $rangeNrOfReferees;
        $this->planningInputService = new PlanningInputService();
        $this->rewind();
    }

    protected function rewindStructure()
    {
        $this->rewindSports();
    }

    protected function rewindSports()
    {
        $this->sportsIterator->rewind();
        $this->rewindNrOfReferees();
    }

    protected function rewindNrOfReferees()
    {
        $this->nrOfReferees = $this->rangeNrOfReferees->min;
        $this->rewindSelfReferee();
    }

    protected function rewindSelfReferee()
    {
        $this->selfReferee = SelfReferee::DISABLED;
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
        $this->rewindStructure();
        if (!$this->structureIterator->valid()) {
            return;
        }
        $this->current = $this->createInput();
    }

    public function valid() : bool
    {
        return $this->current !== null;
    }

    protected function createInput(): PlanningInput
    {
        return new PlanningInput(
            $this->structureIterator->current(),
            [$this->sportsIterator->current()],
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
        if ($this->nrOfReferees > 0 || $this->selfReferee === SelfReferee::SAMEPOULE) {
            return $this->incrementNrOfReferees();
        }
        $pouleStructure = $this->structureIterator->current();
        $sportConfigs = [$this->sportsIterator->current()];
        $selfRefereeIsAvailable = $this->planningInputService->canSelfRefereeBeAvailable($pouleStructure, $sportConfigs);
        if ($selfRefereeIsAvailable === false) {
            return $this->incrementNrOfReferees();
        }
        if ($this->selfReferee === SelfReferee::DISABLED) {
            if ($this->planningInputService->canSelfRefereeOtherPoulesBeAvailable($pouleStructure)) {
                $this->selfReferee = SelfReferee::OTHERPOULES;
            } else {
                $this->selfReferee = SelfReferee::SAMEPOULE;
            }
        } else {
            $selfRefereeSamePouleAvailable = $this->planningInputService->canSelfRefereeSamePouleBeAvailable(
                $pouleStructure,
                $sportConfigs
            );
            if (!$selfRefereeSamePouleAvailable) {
                return $this->incrementNrOfReferees();
            }
            $this->selfReferee = SelfReferee::SAMEPOULE;
        }
        return true;
    }

    protected function incrementNrOfReferees(): bool
    {
        $maxNrOfReferees = $this->rangeNrOfReferees->max;
        $nrOfPlaces = $this->structureIterator->current()->getNrOfPlaces();
        $maxNrOfRefereesByPlaces = (int)(ceil($nrOfPlaces / 2));
        if ($this->nrOfReferees >= $maxNrOfReferees || $this->nrOfReferees >= $maxNrOfRefereesByPlaces) {
            return $this->incrementSports();
        }
        $this->nrOfReferees++;
        $this->rewindSelfReferee();
        return true;
    }

    protected function incrementSports(): bool
    {
        $this->sportsIterator->next();
        if (!$this->sportsIterator->valid()) {
            return $this->incrementStructure();
        }
        $this->rewindNrOfReferees();
        return true;
    }



    protected function incrementStructure(): bool
    {
        $this->structureIterator->next();
        if (!$this->structureIterator->valid()) {
            return false;
        }
        $this->rewindSports();
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
