<?php

declare(strict_types=1);

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructure\Balanced as BalancedPouleStructure;
use SportsHelpers\PouleStructure\BalancedIterator as PouleStructureIterator;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Input as PlanningInput;
use SportsPlanning\Input\Service as PlanningInputService;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Referee\Info as RefereeInfo;

/**
 * @template TKey
 * @template TValue
 * @implements \Iterator<TKey, TValue>
 */
class Iterator implements \Iterator
{
    protected PouleStructureIterator $structureIterator;
    protected AgainstSportsIterator $sportsIterator;
    protected SportRange $rangeNrOfReferees;
    protected PlanningInputService $planningInputService;
    protected int $nrOfReferees;
    protected SelfReferee $selfReferee;
    protected PlanningInput|null $current = null;

    public function __construct(
        SportRange $rangePlaces,
        SportRange $rangePlacesPerPoule,
        SportRange $rangePoules,
        SportRange $rangeNrOfReferees,
        SportRange $rangeNrOfFields,
        SportRange $rangeGameAmount
    ) {
        $this->structureIterator = new PouleStructureIterator($rangePlaces, $rangePlacesPerPoule, $rangePoules);
        $this->sportsIterator = new AgainstSportsIterator($rangeNrOfFields, $rangeGameAmount);
        $this->rangeNrOfReferees = $rangeNrOfReferees;
        $this->planningInputService = new PlanningInputService();
        $this->rewind();
    }

    protected function rewindStructure(): void
    {
        $this->rewindSports();
    }

    protected function rewindSports(): void
    {
        $this->sportsIterator->rewind();
        $this->rewindNrOfReferees();
    }

    protected function rewindNrOfReferees(): void
    {
        $this->nrOfReferees = $this->rangeNrOfReferees->getMin();
        $this->rewindSelfReferee();
    }

    protected function rewindSelfReferee(): void
    {
        $this->selfReferee = SelfReferee::Disabled;
    }

    public function current(): ?PlanningInput
    {
        return $this->current;
    }

    public function key(): string
    {
        $planningInputOutput = new PlanningOutput();
        if ($this->current === null) {
            return 'no current value';
        }
        return $planningInputOutput->getInputAsString($this->current);
    }

    public function next(): void
    {
        if ($this->current === null) {
            return;
        }

        if ($this->incrementValue() === false) {
            $this->current = null;
            return;
        }

        $pouleStructure = $this->structureIterator->current();
        $sportVariantWithFields = $this->sportsIterator->current();
        if ($pouleStructure === null || $sportVariantWithFields === null) {
            return;
        }
        $this->current = $this->createInput($pouleStructure, $sportVariantWithFields);

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

    public function rewind(): void
    {
        $this->rewindStructure();
        $pouleStructure = $this->structureIterator->current();
        $sportVariant = $this->sportsIterator->current();

        if ($pouleStructure === null || $sportVariant === null) {
            return;
        }
        $this->current = $this->createInput($pouleStructure, $sportVariant);
    }

    public function valid(): bool
    {
        return $this->current !== null;
    }

    protected function createInput(
        BalancedPouleStructure $pouleStructure,
        SportVariantWithFields  $sportVariantWithFields
    ): PlanningInput {
        return new PlanningInput(
            $pouleStructure,
            [$sportVariantWithFields],
            new RefereeInfo($this->selfReferee === SelfReferee::Disabled ? $this->nrOfReferees : $this->selfReferee)
        );
    }

    protected function incrementValue(): bool
    {
        return $this->incrementSelfReferee();
    }

    protected function incrementSelfReferee(): bool
    {
        if ($this->nrOfReferees > 0 || $this->selfReferee === SelfReferee::SamePoule) {
            return $this->incrementNrOfReferees();
        }
        $pouleStructure = $this->structureIterator->current();
        $sportVariantWithFields = $this->sportsIterator->current();
        if ($pouleStructure === null || $sportVariantWithFields === null) {
            return $this->incrementNrOfReferees();
        }
        $selfRefereeIsAvailable = $this->planningInputService->canSelfRefereeBeAvailable(
            $pouleStructure,
            [$sportVariantWithFields->getSportVariant()]
        );
        if ($selfRefereeIsAvailable === false) {
            return $this->incrementNrOfReferees();
        }
        if ($this->selfReferee === SelfReferee::Disabled) {
            if ($this->planningInputService->canSelfRefereeOtherPoulesBeAvailable($pouleStructure)) {
                $this->selfReferee = SelfReferee::OtherPoules;
            } else {
                $this->selfReferee = SelfReferee::SamePoule;
            }
        } else {
            $selfRefereeSamePouleAvailable = $this->planningInputService->canSelfRefereeSamePouleBeAvailable(
                $pouleStructure,
                [$sportVariantWithFields->getSportVariant()]
            );
            if (!$selfRefereeSamePouleAvailable) {
                return $this->incrementNrOfReferees();
            }
            $this->selfReferee = SelfReferee::SamePoule;
        }
        return true;
    }

    protected function incrementNrOfReferees(): bool
    {
        $maxNrOfReferees = $this->rangeNrOfReferees->getMax();
        $pouleStructure = $this->structureIterator->current();
        if ($pouleStructure === null) {
            return $this->incrementSports();
        }
        $nrOfPlaces = $pouleStructure->getNrOfPlaces();
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
        $sportVariantWithFields = $this->sportsIterator->current();
        if ($sportVariantWithFields === null) {
            return $this->incrementStructure();
        }
        $sportVariant = $sportVariantWithFields->getSportVariant();
        $pouleStructure = $this->structureIterator->current();
        if ($pouleStructure === null) {
            return $this->incrementStructure();
        }
        if (($sportVariant instanceof AgainstSportVariant || $sportVariant instanceof SingleSportVariant)
            && $sportVariant->getNrOfGamePlaces() > $pouleStructure->getSmallestPoule()) {
            return $this->incrementSports();
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
