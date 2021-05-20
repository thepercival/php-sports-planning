<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Place;
use SportsPlanning\Poule;

class AgainstSerie
{
    /**
     * @var list<AgainstPartial>
     */
    protected array $partials = [];

    public function __construct(protected Poule $poule, protected AgainstSportVariant $sportVariant)
    {
    }

    /**
     * @param int $nrOfHomeAways
     * @return list<AgainstHomeAway>
     */
    public function getHomeAways(int $nrOfHomeAways): array
    {
        $homeAways = [];
        $nrOfHomeAways = $this->getValidNrOfHomeAways($nrOfHomeAways);
        $partials = $this->partials;
        while ($nrOfHomeAways > 0 && count($partials) > 0) {
            $partialHomeAways = $this->getHomeAwaysFromPartial(array_shift($partials), $nrOfHomeAways);
            $homeAways = array_merge($homeAways, $partialHomeAways);
            $nrOfHomeAways -= count($partialHomeAways);
        }
        return $homeAways;
    }

    protected function getValidNrOfHomeAways(int $nrOfHomeAways): int
    {
        $maxNrOfHomeAways = $this->sportVariant->getNrOfGamesOneSerie($this->poule->getPlaces()->count());
        if ($nrOfHomeAways > $maxNrOfHomeAways) {
            return $maxNrOfHomeAways;
        }
        return $nrOfHomeAways;
    }

    /**
     * @param AgainstPartial|null $partial
     * @param int $maxNrOfHomeAways
     * @return list<AgainstHomeAway>
     */
    protected function getHomeAwaysFromPartial(AgainstPartial|null $partial, int $maxNrOfHomeAways): array
    {
        if ($partial === null) {
            return [];
        }
        $homeAways = $partial->getHomeAways();
        /** @var array<int, AgainstHomeAway> $splicedHomeAway */
        $splicedHomeAway = array_splice($homeAways, 0, $maxNrOfHomeAways);
        return array_values($splicedHomeAway);
    }

    /**
     * @param list<Place> $places
     * @return list<int>
     */
    protected function getPlaceNrs(array $places): array
    {
        return array_values(array_map(function (Place $place): int {
            return $place->getNumber();
        }, $places));
    }
}
