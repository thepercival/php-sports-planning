<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Poule;

class AssignedCounterMap
{
    /**
     * @var array<string, PlaceCounter>
     */
    private array $assignedMap = [];
    /**
     * @var array<string, PlaceCounter>
     */
    private array $homeAssignedMap = [];

    private int $maxNrOfHome;
    private int $maxNrOfGames;

    public function __construct(Poule $poule, AgainstSportVariant $againstSportVariant)
    {
        foreach ($poule->getPlaces() as $place) {
            $this->assignedMap[$place->getLocation()] = new PlaceCounter($place);
            $this->homeAssignedMap[$place->getLocation()] = new PlaceCounter($place);
        }

        $nrOfGamesPerPlaceOneH2H = $againstSportVariant->getTotalNrOfGamesPerPlaceOneH2H($poule->getPlaces()->count());
        $nrOfGamesPerPlace = $againstSportVariant->getTotalNrOfGamesPerPlace($poule->getPlaces()->count());
        if ($nrOfGamesPerPlace > $nrOfGamesPerPlaceOneH2H) {
            $nrOfGamesPerPlace = $nrOfGamesPerPlaceOneH2H;
        }
        $this->maxNrOfGames = $nrOfGamesPerPlace;
        $this->maxNrOfHome = (int)ceil($nrOfGamesPerPlace / 2);
    }

    /**
     * @param PlaceCombination $placeCombination
     * @return int
     */
    public function getNrOfHomeAssigned(PlaceCombination $placeCombination): int
    {
        $nrOfHomeAssigned = 0;
        foreach ($placeCombination->getPlaces() as $place) {
            $nrOfHomeAssigned += $this->homeAssignedMap[$place->getLocation()]->getCounter();
        }
        return $nrOfHomeAssigned;
    }

    /**
     * @param PlaceCombination $placeCombination
     * @return int
     */
    public function getNrOfAssigned(PlaceCombination $placeCombination): int
    {
        $nrOfAssigned = 0;
        foreach ($placeCombination->getPlaces() as $place) {
            $nrOfAssigned += $this->assignedMap[$place->getLocation()]->getCounter();
        }
        return $nrOfAssigned;
    }

    public function createGame(PlaceCombination $combination1, PlaceCombination $combination2): AgainstHomeAway
    {
        if ($this->requiredHome($combination1)) {
            $home = $combination1;
        } elseif ($this->requiredHome($combination2)) {
            $home = $combination2;
        } else {
            $nrOfHomeAssigned1 = $this->getNrOfHomeAssigned($combination1);
            $nrOfHomeAssigned2 = $this->getNrOfHomeAssigned($combination2);
            if ($nrOfHomeAssigned1 === $nrOfHomeAssigned2) {
                $nrAssigned1 = $this->getNrOfAssigned($combination1);
                $nrAssigned2 = $this->getNrOfAssigned($combination2);
                $home = $nrAssigned1 > $nrAssigned2 ? $combination1 : $combination2;
            } else {
                $home = $nrOfHomeAssigned1 < $nrOfHomeAssigned2 ? $combination1 : $combination2;
            }
        }

        $away = $home === $combination1 ? $combination2 : $combination1;
        $this->addAssigned($home, $away);
        return new AgainstHomeAway($home, $away);
    }

    protected function requiredHome(PlaceCombination $combination): bool
    {
        foreach ($combination->getPlaces() as $place) {
            $nrAssigned = $this->assignedMap[$place->getLocation()]->getCounter();
            $nrHomeAssigned = $this->homeAssignedMap[$place->getLocation()]->getCounter();
            $nrUnassiged = $this->maxNrOfGames - $nrAssigned;
            if ($nrUnassiged === $this->maxNrOfHome - $nrHomeAssigned) {
                return true;
            }
        }
        return false;
    }

    protected function addAssigned(PlaceCombination $home, PlaceCombination $away): void
    {
        foreach ($home->getPlaces() as $place) {
            $this->assignedMap[$place->getLocation()]->increment();
            $this->homeAssignedMap[$place->getLocation()]->increment();
        }
        foreach ($away->getPlaces() as $place) {
            $this->assignedMap[$place->getLocation()]->increment();
        }
    }
}
