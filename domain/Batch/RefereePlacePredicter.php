<?php


namespace SportsPlanning\Batch;

use Doctrine\Common\Collections\Collection;
use SportsPlanning\Poule;
use SportsPlanning\Input;
use SportsPlanning\Batch;

class RefereePlacePredicter
{
    /**
     * @var array|PouleCounter[]
     */
    private array $pouleCounters;

    /**
     * @param Collection|Poule[] $poules
     */
    public function __construct(Collection $poules)
    {
        foreach ($poules as $poule) {
            $this->pouleCounters[$poule->getNumber()] = new PouleCounter($poule);
        }
    }

    public function canStillAssign(Batch $batch, int $selfReferee)
    {
        if ($selfReferee === Input::SELFREFEREE_DISABLED) {
            return true;
        }
        foreach ($this->pouleCounters as $pouleCounter) {
            $pouleCounter->reset();
        }
        foreach ($batch->getGames() as $game) {
            $this->pouleCounters[$game->getPoule()->getNumber()]->add($game->getPlaces()->count());
        }
        if ($selfReferee === Input::SELFREFEREE_SAMEPOULE) {
            return $this->validatePouleAssignmentsSamePoule();
        }
        return $this->validatePouleAssignmentsOtherPoules();
    }

    /**
     * @return bool
     */
    protected function validatePouleAssignmentsSamePoule(): bool
    {
        foreach ($this->pouleCounters as $pouleCounter) {
            $nrOfPlacesAvailable = $this->getNrOfPlacesAvailable([$pouleCounter]);
            if ($nrOfPlacesAvailable < $pouleCounter->getNrOfGames()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    protected function validatePouleAssignmentsOtherPoules(): bool
    {
        foreach ($this->pouleCounters as $pouleCounter) {
            $otherPouleCounters = array_filter(
                $this->pouleCounters,
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
}