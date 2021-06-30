<?php
declare(strict_types=1);

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\GamePlaceCalculator;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsPlanning\Input;
use SportsPlanning\Sport;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\Sport\Variant as SportVariant;

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
        // sort by lowest nrOfGamePlaces first
        uasort($sportVariantsWithFields, function (SportVariantWithFields $a, SportVariantWithFields $b) use ($pouleStructure): int {
            $nrOfGamePlacesA = $this->getNrOfGamePlaces($pouleStructure, $a->getSportVariant());
            $nrOfGamePlacesB = $this->getNrOfGamePlaces($pouleStructure, $b->getSportVariant());
            return $nrOfGamePlacesA < $nrOfGamePlacesB ? -1 : 1;
        });

        $nrOfBatchGames = 0;
        $nrOfPlaces = $pouleStructure->getNrOfPlaces();
        $doRefereeCheck = $nrOfReferees > 0;
        while ($nrOfPlaces > 0 && count($sportVariantsWithFields) > 0 && (!$doRefereeCheck || $nrOfReferees > 0)) {
            $sportVariantWithFields = array_shift($sportVariantsWithFields);
            $nrOfFields = $sportVariantWithFields->getNrOfFields();
            $nrOfGamePlaces = $this->getNrOfGamePlaces($pouleStructure, $sportVariantWithFields->getSportVariant());
            $nrOfGamePlaces += ($selfReferee ? 1 : 0);

            while ($nrOfPlaces > 0 && $nrOfFields-- > 0 && (!$doRefereeCheck || $nrOfReferees-- > 0)) {
                $nrOfPlaces -= $nrOfGamePlaces;
                if ($nrOfPlaces >= 0) {
                    $nrOfBatchGames++;
                }
            }
        }
        return $nrOfBatchGames === 0 ? 1 : $nrOfBatchGames;
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
        return $nrOfBatchPlaces === 0 ? 1 : (int)$nrOfBatchPlaces;
    }
}
