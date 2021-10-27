<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\GameMode;
use SportsPlanning\Schedule;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Sport;

class Service
{
    /**
     * @var array<int, CreatorInterface>|null
     */
    protected array|null $generatorMap = null;

    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Input $input
     * @return list<Schedule>
     */
    public function createSchedules(Input $input): array
    {
        /** @var array<int, list<Schedule>> $schedules */
        $schedules = [];
        $gamePlaceStrategy = $input->getGamePlaceStrategy();
        $sportVariants = array_values($input->createSportVariants()->toArray());
        foreach ($input->getPoules() as $poule) {
            $nrOfPlaces = $poule->getPlaces()->count();
            if (array_key_exists($nrOfPlaces, $schedules)) {
                continue;
            }
            $schedules[$nrOfPlaces] = [];
            $assignedCounter = new AssignedCounter($poule, $sportVariants);
            $schedulesForSportVariant = [];
            foreach ([GameMode::ALL_IN_ONE_GAME, GameMode::AGAINST, GameMode::SINGLE] as $gameMode) {
                $sports = $this->getSports($input, $gameMode);
                if (count($sports) === 0) {
                    continue;
                }
                $scheduleCreator = $this->getScheduleCreator($input, $gameMode, $gamePlaceStrategy);
                $schedulesForSportVariant[] = $scheduleCreator->create($poule, $sports, $assignedCounter);
            }
            foreach ($schedulesForSportVariant as $scheduleForSportVariant) {
                $schedules[$nrOfPlaces][] = $scheduleForSportVariant;
            }
        }
        // flatten
        $flattenSchedules = [];
        foreach ($schedules as $schedulesPerNrOfPlaces) {
            foreach ($schedulesPerNrOfPlaces as $schedulePerNrOfPlaces) {
                $flattenSchedules[] = $schedulePerNrOfPlaces;
            }
        }
        return $flattenSchedules;
    }

    /**
     * @param Input $input
     * @param int $gameMode
     * @param int $gamePlaceStrategy
     * @return CreatorInterface
     */
    protected function getScheduleCreator(Input $input, int $gameMode, int $gamePlaceStrategy): CreatorInterface
    {
        $generatorMap = $this->getScheduleCreatorMap($input);
        return $generatorMap[$gameMode];
    }

    /**
     * @param Input $input
     * @return array<int, CreatorInterface>
     */
    protected function getScheduleCreatorMap(Input $input): array
    {
        if ($this->generatorMap !== null) {
            return $this->generatorMap;
        }
        $this->generatorMap = [];
        $this->generatorMap[GameMode::ALL_IN_ONE_GAME] = new AllInOneGame();
        $this->generatorMap[GameMode::AGAINST] = new Against($this->logger);
        $this->generatorMap[GameMode::SINGLE] = new Single($this->logger);
        return $this->generatorMap;
    }

    /**
     * @param Input $input
     * @param int $gameMode
     * @return list<Sport>
     */
    protected function getSports(Input $input, int $gameMode): array
    {
        if ($gameMode === 0) {
            $gameMode = GameMode::AGAINST;
        }
        return array_values($input->getSports()->filter(function (Sport $sport) use ($gameMode): bool {
            return $sport->getGameMode() === $gameMode;
        })->toArray());
    }
}
