<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsHelpers\Against\Side as AgainstSide;

class Partial
{
    /**
     * @var array<string, PlaceCounter>
     */
    private array $placeCounterMap = [];
    /**
     * @var list<AgainstHomeAway>
     */
    private array $homeAways = [];

    public function __construct(
        private int $maxNrOfGames
    ) {
    }

    public function canBeAdded(AgainstHomeAway $homeAway): bool
    {
        if ($this->isComplete()) {
            return false;
        }
        foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $side) {
            foreach ($homeAway->get($side)->getPlaces() as $place) {
                if (!isset($this->placeCounterMap[$place->getLocation()])) {
                    continue;
                }
                if ($this->placeCounterMap[$place->getLocation()]->getCounter() === $this->maxNrOfGames) {
                    return false;
                }
            }
        }

        return true;
    }

    public function add(AgainstHomeAway $homeAway): void
    {
        foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $side) {
            foreach ($homeAway->get($side)->getPlaces() as $place) {
                if (!isset($this->placeCounterMap[$place->getLocation()])) {
                    $this->placeCounterMap[$place->getLocation()] = new PlaceCounter($place);
                }
                $this->placeCounterMap[$place->getLocation()]->increment();
            }
        }
        array_push($this->homeAways, $homeAway);
    }

    /**
     * @return list<AgainstHomeAway>
     */
    public function getHomeAways(): array
    {
        return $this->homeAways;
    }

    public function isComplete(): bool
    {
        return count($this->homeAways) === $this->maxNrOfGames;
    }
}
