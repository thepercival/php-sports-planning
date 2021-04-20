<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\GameMode;
use SportsPlanning\GameGenerator\GameMode as GameModeGenerator;
use SportsHelpers\SportMath;

class GameGenerator
{
    protected SportMath $math;

    public function __construct()
    {
        $this->math = new SportMath();
    }

    public function generateGames(Planning $planning): void
    {
        foreach ($planning->getInput()->getPoules() as $poule) {
            $generatorMap = $this->getGenerators($planning);
            foreach ([GameMode::ALL_IN_ONE_GAME, GameMode::AGAINST, GameMode::SINGLE] as $gameMode) {
                $sports = $this->getSports($planning, $gameMode);
                $generatorMap[$gameMode]->generate($poule, $sports);
            }
        }

        // hier moeten de games gegenereerd worden, op basis van creationstrategy
//        public const StaticPouleSize = 1;
//        public const StaticManual = 2;
//        public const IncrementalRandom = 3;
//        public const IncrementalRanking = 4;
    }

    /**
     * @param Planning $planning
     * @return array<int, GameModeGenerator>
     */
    protected function getGenerators(Planning $planning): array
    {
        $generatorMap = [];
        $generatorMap[GameMode::ALL_IN_ONE_GAME] = new GameGenerator\GameMode\AllInOneGame($planning);
        $generatorMap[GameMode::AGAINST] = new GameGenerator\GameMode\Against($planning);
        $generatorMap[GameMode::SINGLE] = new GameGenerator\GameMode\Single($planning);
        return $generatorMap;
    }

    // veld, sportsvariants

    /**
     * @param Planning $planning
     * @param int $gameMode
     * @return list<Sport>
     */
    protected function getSports(Planning $planning, int $gameMode): array
    {
        return array_values($planning->getInput()->getSports()->filter(function (Sport $sport) use ($gameMode): bool {
            return $sport->getGameMode() === $gameMode;
        })->toArray());
    }
}
