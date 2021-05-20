<?php

declare(strict_types=1);

namespace SportsPlanning;

use SportsHelpers\GameMode;
use SportsPlanning\GameGenerator\GameMode as GameModeGenerator;
use SportsPlanning\GameGenerator\GameMode\SingleHelper;

class GameGenerator
{
    public function __construct()
    {
    }

    public function generateUnassignedGames(Planning $planning): void
    {
        $singleHelper = new SingleHelper($planning);
        foreach ($planning->getInput()->getPoules() as $poule) {
            $generatorMap = $this->getGenerators($planning, $singleHelper);
            foreach ([GameMode::ALL_IN_ONE_GAME, GameMode::AGAINST, GameMode::SINGLE] as $gameMode) {
                $sports = $this->getSports($planning, $gameMode);
                $generatorMap[$gameMode]->generate($poule, $sports);
            }
        }
    }

    /**
     * @param Planning $planning
     * @param SingleHelper $singleHelper
     * @return array<int, GameModeGenerator>
     */
    protected function getGenerators(Planning $planning, SingleHelper $singleHelper): array
    {
        $generatorMap = [];
        $generatorMap[GameMode::ALL_IN_ONE_GAME] = new GameGenerator\GameMode\AllInOneGame($planning);
        $generatorMap[GameMode::AGAINST] = new GameGenerator\GameMode\Against($planning);
        $generatorMap[GameMode::SINGLE] = new GameGenerator\GameMode\Single($planning, $singleHelper);
        return $generatorMap;
    }

    /**
     * @param Planning $planning
     * @param int $gameMode
     * @return list<Sport>
     */
    protected function getSports(Planning $planning, int $gameMode): array
    {
        if ($gameMode === 0) {
            $gameMode = GameMode::AGAINST;
        }
        return array_values($planning->getInput()->getSports()->filter(function (Sport $sport) use ($gameMode): bool {
            return $sport->getGameMode() === $gameMode;
        })->toArray());
    }
}
