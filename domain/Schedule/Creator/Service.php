<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\GameMode;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Input;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Name as ScheduleName;
use SportsPlanning\Sport;

class Service
{
    /**
     * @var array<string, CreatorInterface>|null
     */
    protected array|null $generatorMap = null;
    /**
     * @var list<Schedule>|null
     */
    protected array|null $existingSchedules = null;

    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Input $input
     * @return list<Schedule>
     */
    public function createSchedules(Input $input): array
    {
        /** @var array<int, Schedule> $schedules */
        $schedules = [];
        $gamePlaceStrategy = $input->getGamePlaceStrategy();
        $sportConfigsName = new ScheduleName(array_values($input->createSportVariants()->toArray()));
        $sportVariants = array_values($input->createSportVariants()->toArray());
        foreach ($input->getPoules() as $poule) {
            $nrOfPlaces = $poule->getPlaces()->count();
            if ($this->isScheduleAlreadyCreated($nrOfPlaces, $gamePlaceStrategy, (string)$sportConfigsName)) {
                continue;
            }
            if (array_key_exists($nrOfPlaces, $schedules)) {
                continue;
            }
            $schedule = new Schedule($nrOfPlaces, $poule->getInput());
            $schedules[$nrOfPlaces] = $schedule;

            $assignedCounter = new AssignedCounter($poule, $sportVariants);
            foreach ([GameMode::ALL_IN_ONE_GAME, GameMode::AGAINST, GameMode::SINGLE] as $gameMode) {
                $sports = $this->getSports($input, $gameMode);
                if (count($sports) === 0) {
                    continue;
                }
                $scheduleCreator = $this->getScheduleCreator($input, $gameMode);
                $scheduleCreator->createSportSchedules($schedule, $poule, $sports, $assignedCounter);
            }
        }
        return array_values($schedules);
    }

    /**
     * @param Input $input
     * @param GameMode $gameMode
     * @return CreatorInterface
     */
    protected function getScheduleCreator(Input $input, GameMode $gameMode): CreatorInterface
    {
        $generatorMap = $this->getScheduleCreatorMap($input);
        return $generatorMap[$gameMode->name];
    }

    /**
     * @param Input $input
     * @return array<string, CreatorInterface>
     */
    protected function getScheduleCreatorMap(Input $input): array
    {
        if ($this->generatorMap !== null) {
            return $this->generatorMap;
        }
        $this->generatorMap = [];
        $this->generatorMap[GameMode::ALL_IN_ONE_GAME->name] = new AllInOneGame();
        $this->generatorMap[GameMode::AGAINST->name] = new Against($this->logger);
        $this->generatorMap[GameMode::SINGLE->name] = new Single($this->logger);
        return $this->generatorMap;
    }

    /**
     * @param Input $input
     * @param GameMode $gameMode
     * @return list<Sport>
     */
    protected function getSports(Input $input, GameMode $gameMode): array
    {
        return array_values(
            $input->getSports()->filter(function (Sport $sport) use ($gameMode): bool {
                return $sport->getGameMode() === $gameMode;
            })->toArray()
        );
    }

    /**
     * @param list<Schedule> $existingSchedules
     */
    public function setExistingSchedules(array $existingSchedules): void
    {
        $this->existingSchedules = $existingSchedules;
    }

    public function isScheduleAlreadyCreated(
        int $nrOfPlaces,
        GamePlaceStrategy $gamePlaceStrategy,
        string $sportConfigsName
    ): bool {
        if ($this->existingSchedules === null) {
            return false;
        }
        foreach ($this->existingSchedules as $existingSchedule) {
            if ($nrOfPlaces === $existingSchedule->getNrOfPlaces()
                && $gamePlaceStrategy === $existingSchedule->getGamePlaceStrategy()
                && $sportConfigsName === $existingSchedule->getSportsConfigName()) {
                return true;
            }
        }
        return false;
    }
}
