<?php
declare(strict_types=1);

namespace SportsPlanning\Resource\RefereePlace;

use SportsHelpers\SelfReferee;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Poule;
use SportsPlanning\PouleCounter;

class Predicter
{
    private const SAME_POULE_MAX_DELTA = 1;

    /**
     * @param list<Poule> $poules
     */
    public function __construct(protected array $poules)
    {
    }

    public function canStillAssign(SelfRefereeBatch $batch, SelfReferee $selfReferee): bool
    {
        if ($selfReferee === SelfReferee::DISABLED) {
            return true;
        }
        if ($selfReferee === SelfReferee::SAMEPOULE) {
            return $this->validatePouleAssignmentsSamePoule($batch) && $this->validateTooMuchForcedAssignmentDiffernce(
                    $batch
                );
        }
        return $this->validatePouleAssignmentsOtherPoules($batch) && $this->validateTooMuchForcedAssignmentDiffernce(
                $batch
            );
    }

    protected function validatePouleAssignmentsSamePoule(SelfRefereeBatch $batch): bool
    {
        $pouleCounterMap = $this->createPouleCounterMap();
        $this->addGamesToPouleCounterMap($pouleCounterMap, $batch);

        foreach ($pouleCounterMap as $pouleCounter) {
            $nrOfPlacesAvailable = $this->getNrOfPlacesAvailable([$pouleCounter]);
            if ($nrOfPlacesAvailable < $pouleCounter->getNrOfGames()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array<int,PouleCounter>
     */
    protected function createPouleCounterMap(): array
    {
        $pouleCounterMap = [];
        foreach ($this->poules as $poule) {
            $pouleCounterMap[$poule->getNumber()] = new PouleCounter($poule);
        }
        return $pouleCounterMap;
    }

    /**
     * @param array<int,PouleCounter> $pouleCounterMap
     * @param SelfRefereeBatch $batch
     */
    protected function addGamesToPouleCounterMap(array $pouleCounterMap, SelfRefereeBatch $batch): void
    {
        foreach ($batch->getBase()->getGames() as $game) {
            $pouleCounterMap[$game->getPoule()->getNumber()]->add($game->getPlaces()->count());
        }
    }

    protected function validatePouleAssignmentsOtherPoules(SelfRefereeBatch $batch): bool
    {
        $pouleCounterMap = $this->createPouleCounterMap();
        $this->addGamesToPouleCounterMap($pouleCounterMap, $batch);

        foreach ($pouleCounterMap as $pouleCounter) {
            $otherPouleCounters = array_values(array_filter(
                $pouleCounterMap,
                function (PouleCounter $pouleCounterIt) use ($pouleCounter) : bool {
                    return $pouleCounter !== $pouleCounterIt;
                }
            ));
            $nrOfPlacesAvailable = $this->getNrOfPlacesAvailable($otherPouleCounters);
            if ($pouleCounter->getNrOfGames() > $nrOfPlacesAvailable) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param list<PouleCounter> $pouleCounters
     * @return int
     */
    protected function getNrOfPlacesAvailable(array $pouleCounters): int
    {
        $nrOfPlacesAvailable = 0;
        foreach ($pouleCounters as $pouleCounter) {
            $nrOfPlaces = $pouleCounter->getPoule()->getPlaces()->count();
            $nrOfPlacesAvailable += ($nrOfPlaces - $pouleCounter->getNrOfPlacesAssigned());
        }
        return $nrOfPlacesAvailable;
    }

    /**
     * voor selfref = samepoule , per plek kijken hoevaak deze verplicht is als scheidsrechter
     * dit mag max. het gemiddelde + 1.500000001 zijn
     */
    protected function validateTooMuchForcedAssignmentDiffernce(SelfRefereeBatch $batch): bool
    {
        $totalNrOfForcedRefereePlaces = $batch->getTotalNrOfForcedRefereePlaces();
        $totalPouleCounters = $batch->getTotalPouleCounters();

        $pouleHasForcedRefereePlaces = function (Poule $poule) use ($totalNrOfForcedRefereePlaces) : bool {
            foreach ($poule->getPlaces() as $place) {
                if (array_key_exists($place->getLocation(), $totalNrOfForcedRefereePlaces)) {
                    return true;
                }
            }
            return false;
        };

        foreach ($this->poules as $poule) {
            if (count($totalNrOfForcedRefereePlaces) === 0 || !$pouleHasForcedRefereePlaces($poule)) {
                continue;
            }
            /** @var int|null $maxNrOfForcedRefereePlaces */
            $maxNrOfForcedRefereePlaces = null;
            /** @var int|null $minNrOfForcedRefereePlaces */
            $minNrOfForcedRefereePlaces = null;
            $avgNrOfGamesForRefereePlace = $totalPouleCounters[$poule->getNumber()]->getNrOfGames() / $poule->getPlaces()->count();
            $pouleMax = $avgNrOfGamesForRefereePlace + self::SAME_POULE_MAX_DELTA;
            // $pouleMin = $avgNrOfGamesForRefereePlace - self::SAME_POULE_MAX_DELTA;

            // naast de forced referee assignments heb je ook dat places niet beschikbaar zijn, omdat ze zelf moeten
            // place met laagste nrOfForcedAssignment moet minimaal 1x beschikbaar zijn
            foreach ($poule->getPlaces() as $place) {
                $nrOfForcedRefereePlaces = 0;
                if (array_key_exists($place->getLocation(), $totalNrOfForcedRefereePlaces)) {
                    $nrOfForcedRefereePlaces = $totalNrOfForcedRefereePlaces[$place->getLocation()];
                }
                if ($nrOfForcedRefereePlaces >= $pouleMax /*|| $nrOfForcedRefereePlaces <= $pouleMin*/) {
                    return false;
                }
                if ($minNrOfForcedRefereePlaces === null || $nrOfForcedRefereePlaces < $minNrOfForcedRefereePlaces) {
                    $minNrOfForcedRefereePlaces = $nrOfForcedRefereePlaces;
                }
                if ($maxNrOfForcedRefereePlaces === null || $nrOfForcedRefereePlaces > $maxNrOfForcedRefereePlaces) {
                    $maxNrOfForcedRefereePlaces = $nrOfForcedRefereePlaces;
                }
            }
            if ($maxNrOfForcedRefereePlaces !== null && $minNrOfForcedRefereePlaces !== null
                && ($maxNrOfForcedRefereePlaces - $minNrOfForcedRefereePlaces) > 1) {
                return false;
            }
        }
        return true;
    }
}
