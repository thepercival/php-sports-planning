<?php
declare(strict_types=1);

namespace SportsPlanning;

use Psr\Log\LoggerInterface;
use SportsHelpers\GameMode;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\GameGenerator\AssignedCounter;
use SportsPlanning\GameGenerator\Helper as GameGeneratorHelper;

class GameGenerator
{
    /**
     * @var array<int, GameGeneratorHelper>|null
     */
    protected array|null $generatorMap = null;

    public function __construct(protected LoggerInterface $logger)
    {
    }

    public function generateUnassignedGames(Planning $planning): void
    {
        $gamePlaceStrategy = $planning->getInput()->getGamePlaceStrategy();
        $sportVariants = array_values($planning->getInput()->createSportVariants()->toArray());
        foreach ($planning->getInput()->getPoules() as $poule) {
            $assignedCounter = new AssignedCounter($poule, $sportVariants);
            foreach ([GameMode::ALL_IN_ONE_GAME, GameMode::AGAINST, GameMode::SINGLE] as $gameMode) {
                $sports = $this->getSports($planning, $gameMode);
                $generator = $this->getGenerator($planning, $gameMode, $gamePlaceStrategy);
                $generator->generate($poule, $sports, $assignedCounter);
            }
        }
    }

    /**
     * @param Planning $planning
     * @param int $gameMode
     * @param int $gamePlaceStrategy
     * @return GameGeneratorHelper
     */
    protected function getGenerator(Planning $planning, int $gameMode, int $gamePlaceStrategy): GameGeneratorHelper
    {
        $generatorMap = $this->getGeneratorMap($planning);
        return $generatorMap[$gameMode];
    }

    /**
     * @param Planning $planning
     * @return array<int, GameGeneratorHelper>
     */
    protected function getGeneratorMap(Planning $planning): array {
        if($this->generatorMap !== null) {
            return $this->generatorMap;
        }
        $this->generatorMap = [];
        $this->generatorMap[GameMode::ALL_IN_ONE_GAME] = new GameGenerator\Helper\AllInOneGame($planning);
        $this->generatorMap[GameMode::AGAINST] = new GameGenerator\Helper\Against($planning, $this->logger);
        $this->generatorMap[GameMode::SINGLE] = new GameGenerator\Helper\Single($planning, $this->logger);
        return $this->generatorMap;
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
