<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;

class Name implements \Stringable
{
    protected string|null $name = null;
    /**
     * @param list<AgainstSportVariant|AllInOneGameSportVariant|SingleSportVariant> $sportVariants
     */
    public function __construct(protected array $sportVariants)
    {
    }

    public function __toString()
    {
        if ($this->name !== null) {
            return $this->name;
        }
        $names = [];
        foreach ($this->sportVariants as $sportVariant) {

            if ($sportVariant instanceof AgainstSportVariant) {
                $name = [
                    'nrOfHomePlaces' => $sportVariant->getNrOfHomePlaces(),
                    'nrOfAwayPlaces' => $sportVariant->getNrOfAwayPlaces()
                ];
                if ($sportVariant->getNrOfH2H() > 0) {
                    $name['nrOfH2H'] = $sportVariant->getNrOfH2H();
                } else {
                    $name['nrOfGamesPerPlace'] = $sportVariant->getNrOfGamePlaces();
                }
            } else {
                $name = ['nrOfGamesPerPlace' => $sportVariant->getNrOfGamesPerPlace()];
                if ($sportVariant instanceof SingleSportVariant) {
                    $name['nrOfGamePlaces'] = $sportVariant->getNrOfGamePlaces();
                }
            }
            $names[] = $name;
        }
        $json = json_encode($names);
        $this->name = $json === false ? '?' : $json;
        return $this->name;
    }
}
