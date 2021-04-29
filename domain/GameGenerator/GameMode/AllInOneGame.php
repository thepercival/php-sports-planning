<?php

namespace SportsPlanning\GameGenerator\GameMode;

use Exception;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsPlanning\Field;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;
use SportsPlanning\GameGenerator\GameMode as GameModeGameGenerator;

class AllInOneGame implements GameModeGameGenerator
{
    protected Field|null $defaultField = null;

    public function __construct(protected Planning $planning)
    {
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @return int
     */
    public function generate(Poule $poule, array $sports): int
    {
        foreach ($sports as $sport) {
            $this->defaultField = $sport->getField(1);
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof AllInOneGameSportVariant)) {
                throw new \Exception('only allinonegame-sport-variant accepted', E_ERROR);
            }
            $this->generateForSportVariant($poule, $sportVariant);
        }
        return Planning::STATE_SUCCEEDED;
    }

    /**
     * @param Poule $poule
     * @param AllInOneGameSportVariant $sportVariant
     */
    protected function generateForSportVariant(Poule $poule, AllInOneGameSportVariant $sportVariant): void
    {
        for ($gameRoundNumber = 1; $gameRoundNumber <= $sportVariant->getNrOfGames() ; $gameRoundNumber++) {
            $game = new TogetherGame($this->planning, $poule, $this->getDefaultField());
            foreach ($poule->getPlaces() as $place) {
                new TogetherGamePlace($game, $place, $gameRoundNumber);
            }
        }
    }

    protected function getDefaultField(): Field
    {
        if ($this->defaultField === null) {
            throw new Exception('geen standaard veld gedefinieerd', E_ERROR);
        }
        return $this->defaultField;
    }
}
