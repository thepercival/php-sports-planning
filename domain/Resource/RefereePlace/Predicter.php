<?php


namespace SportsPlanning\Resource\RefereePlace;

use Doctrine\Common\Collections\Collection;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\Input;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\PouleCounter;

class Predicter
{
    /**
     * @var array|Poule[]
     */
    private array $poules;

    private const SAME_POULE_MAX_DELTA = 1;

    /**
     * @param array|Poule[] $poules
     */
    public function __construct(array $poules)
    {
        $this->poules = $poules;
    }

    public function canStillAssign(SelfRefereeBatch $batch, int $selfReferee)
    {
        if ($selfReferee === Input::SELFREFEREE_DISABLED) {
            return true;
        }
        if ($selfReferee === Input::SELFREFEREE_SAMEPOULE) {
            return $this->validatePouleAssignmentsSamePoule($batch) && $this->validateTooMuchForcedAssignmentDiffernce($batch);
        }
        return $this->validatePouleAssignmentsOtherPoules($batch);
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
     * @return array|PouleCounter[]
     */
    protected function createPouleCounterMap(): array {
        $pouleCounterMap = [];
        foreach ($this->poules as $poule) {
            $pouleCounterMap[$poule->getNumber()] = new PouleCounter($poule);
        }
        return $pouleCounterMap;
    }

    protected function addGamesToPouleCounterMap(array $pouleCounterMap, SelfRefereeBatch $batch) {
        foreach ($batch->getBase()->getGames() as $game) {
            $pouleCounterMap[$game->getPoule()->getNumber()]->add($game->getPlaces()->count());
        }
    }

    protected function validatePouleAssignmentsOtherPoules(SelfRefereeBatch $batch): bool
    {
        $pouleCounterMap = $this->createPouleCounterMap();
        $this->addGamesToPouleCounterMap( $pouleCounterMap, $batch );

        foreach ($pouleCounterMap as $pouleCounter) {
            $otherPouleCounters = array_filter(
                $pouleCounterMap,
                function (PouleCounter $pouleCounterIt) use ($pouleCounter) : bool {
                    return $pouleCounter !== $pouleCounterIt;
                }
            );
            $nrOfPlacesAvailable = $this->getNrOfPlacesAvailable($otherPouleCounters);
            if ($pouleCounter->getNrOfGames() > $nrOfPlacesAvailable) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array|PouleCounter[] $pouleCounters
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
    protected function validateTooMuchForcedAssignmentDiffernce(SelfRefereeBatch $batch): bool {

        $totalNrOfForcedRefereePlaces = $batch->getTotalNrOfForcedRefereePlaces();
        $totalPouleCounters = $batch->getTotalPouleCounters();

        $pouleHasForcedRefereePlaces = function( Poule $poule) use( $totalNrOfForcedRefereePlaces ) : bool {
            foreach( $poule->getPlaces() as $place ) {
                if( array_key_exists( $place->getLocation(), $totalNrOfForcedRefereePlaces  ) ) {
                    return true;
                }
            }
            return false;
        };

        foreach( $this->poules as $poule ) {
            if( count($totalNrOfForcedRefereePlaces) === 0 || !$pouleHasForcedRefereePlaces($poule) ) {
                continue;
            }
            $avgNrOfGamesForRefereePlace = $totalPouleCounters[$poule->getNumber()]->getNrOfGames() / $poule->getPlaces()->count();
            $pouleMax = $avgNrOfGamesForRefereePlace + self::SAME_POULE_MAX_DELTA;
            $pouleMin = $avgNrOfGamesForRefereePlace - ( self::SAME_POULE_MAX_DELTA * 3 );
            foreach( $poule->getPlaces() as $place ) {
                $nrOfForcedRefereePlaces = 0;
                if( array_key_exists( $place->getLocation(), $totalNrOfForcedRefereePlaces ) ) {
                    $nrOfForcedRefereePlaces = $totalNrOfForcedRefereePlaces[$place->getLocation()];
                }
                if ( $nrOfForcedRefereePlaces > $pouleMax || $nrOfForcedRefereePlaces < $pouleMin ) {
                    return false;
                }
            }
        }
        return true;
    }
}