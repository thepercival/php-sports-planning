<?php

declare(strict_types=1);

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\GamePlaceCalculator;
use SportsHelpers\Sport\Variant as SportVariant;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Input;
use SportsPlanning\Sport;

class Calculator
{
    protected GamePlaceCalculator $sportGamePlaceCalculator;

    public function __construct()
    {
        $this->sportGamePlaceCalculator = new GamePlaceCalculator();
    }

    /**
     * dit aantal kan misschien niet gehaal worden, ivm variatie in poulegrootte en sportConfig->nrOfGamePlaces
     *
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param bool $selfReferee
     * @return int
     */
    public function getMaxNrOfGamesPerBatch(
        PouleStructure $pouleStructure,
        array $sportVariantsWithFields,
        int $nrOfReferees,
        bool $selfReferee
    ): int {
        $singleSport = count($sportVariantsWithFields) === 1;
        // sort by lowest nrOfGamePlaces first
        uasort(
            $sportVariantsWithFields,
            function (SportVariantWithFields $a, SportVariantWithFields $b) use ($pouleStructure): int {
                $nrOfGamePlacesA = $this->getNrOfGamePlaces($pouleStructure, $a->getSportVariant());
                $nrOfGamePlacesB = $this->getNrOfGamePlaces($pouleStructure, $b->getSportVariant());
                return $nrOfGamePlacesA < $nrOfGamePlacesB ? -1 : 1;
            }
        );

        $nrOfBatchGames = 0;
        $nrOfPlaces = $pouleStructure->getNrOfPlaces();
        $doRefereeCheck = !$selfReferee && $nrOfReferees > 0;
        $sportVariantWithFields = array_shift($sportVariantsWithFields);
        $singleSportVariantWithFields = null;
        if ($singleSport && $sportVariantWithFields !== null) {
            $singleSportVariantWithFields = $sportVariantWithFields;
        }
        while ($nrOfPlaces > 0 && $sportVariantWithFields !== null && (!$doRefereeCheck || $nrOfReferees > 0)) {
            $nrOfFields = $sportVariantWithFields->getNrOfFields();
            $nrOfGamePlaces = $this->getNrOfGamePlaces($pouleStructure, $sportVariantWithFields->getSportVariant());
            $nrOfGamePlaces += ($selfReferee ? 1 : 0);

            while ($nrOfPlaces > 0 && $nrOfFields-- > 0 && (!$doRefereeCheck || $nrOfReferees-- > 0)) {
                $nrOfPlaces -= $nrOfGamePlaces;
                if ($nrOfPlaces >= 0) {
                    $nrOfBatchGames++;
                }
            }
            $sportVariantWithFields = array_shift($sportVariantsWithFields);
        }
        if ($nrOfBatchGames === 0) {
            return 1;
        }

        if ($singleSport && $pouleStructure->isBalanced() && $singleSportVariantWithFields !== null) {
            $nrOfGamePlaces = $this->getNrOfGamePlaces(
                $pouleStructure,
                $singleSportVariantWithFields->getSportVariant()
            );
            $nrOfGamePlaces += ($selfReferee ? 1 : 0);
            $maxNrOfGamesPerBatchPerPoule = (int)floor($pouleStructure->getBiggestPoule() / $nrOfGamePlaces);
            if ($maxNrOfGamesPerBatchPerPoule === 0) {
                $maxNrOfGamesPerBatchPerPoule = 1;
            }
            // pak maximale aantal wedstrijden per poule tegelijk * het aantal poules
            $maxNrOfBatchGames = $maxNrOfGamesPerBatchPerPoule * $pouleStructure->getNrOfPoules();
            if ($nrOfBatchGames > $maxNrOfBatchGames) {
                $nrOfBatchGames = $maxNrOfBatchGames;
            }
        }

        return $nrOfBatchGames;
    }

    protected function getNrOfGamePlaces(PouleStructure $pouleStructure, SportVariant $sportVariant): int
    {
        if ($sportVariant instanceof SingleSportVariant || $sportVariant instanceof AgainstSportVariant) {
            return $sportVariant->getNrOfGamePlaces();
        }
        return $pouleStructure->getBiggestPoule();
    }

    public function getMaxNrOfGamesInARow(Input $input, bool $selfReferee): int
    {
        $pouleStructure = $input->createPouleStructure();
        $nrOfPoulesByNrOfPlaces = $pouleStructure->getNrOfPoulesByNrOfPlaces();
        $nrOfPlaces = key($nrOfPoulesByNrOfPlaces);
        $nrOfPlaces *= $nrOfPoulesByNrOfPlaces[$nrOfPlaces];
        $maxNrOfBatchPlaces = $this->getMaxNrOfPlacesPerBatch($input, $selfReferee);

        $nrOfRestPlaces = $nrOfPlaces - $maxNrOfBatchPlaces;
        if ($nrOfRestPlaces <= 0) {
            $sportVariants = array_values($input->createSportVariants()->toArray());
            return $this->sportGamePlaceCalculator->getMaxNrOfGamesPerPlace($nrOfPlaces, $sportVariants);
        }
        return (int)ceil($nrOfPlaces / $nrOfRestPlaces);
    }

    /**
     * dit aantal kan misschien niet gehaal worden, ivm variatie in poulegrootte en sportConfig->nrOfGamePlaces
     */
    protected function getMaxNrOfPlacesPerBatch(Input $input, bool $selfReferee): int
    {
        $sports = $input->getSports()->toArray();
        // sort by lowest nrOfGamePlaces first
        uasort($sports, function (Sport $sportA, Sport $sportB): int {
            return $sportA->getNrOfGamePlaces() < $sportB->getNrOfGamePlaces() ? -1 : 1;
        });

        $nrOfBatchPlaces = 0;
        $nrOfPlaces = $input->getNrOfPlaces();
        while ($nrOfPlaces > 0 && count($sports) > 0) {
            $sport = array_shift($sports);
            $sportVariant = $sport->createVariant();
            if ($sportVariant instanceof AllInOneGameSportVariant) {
                return $input->getPoule(1)->getPlaces()->count();
            }
            $sportNrOfGamePlaces = $sportVariant->getNrOfGamePlaces() + ($selfReferee ? 1 : 0);
            $nrOfFields = $sport->getNrOfFields();
            while ($nrOfPlaces > 0 && $nrOfFields-- > 0) {
                $nrOfGamePlaces = $sportNrOfGamePlaces + ($selfReferee ? 1 : 0);
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
