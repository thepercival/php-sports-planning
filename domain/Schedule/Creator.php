<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Psr\Log\LoggerInterface;
use SportsHelpers\GameMode;
use SportsPlanning\Input;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Creator\AllInOneGame as AllInOneGameCreator;
use SportsPlanning\Schedule\Creator\Against as AgainstCreator;
use SportsPlanning\Schedule\Creator\Single as SingleCreator;
use SportsPlanning\Schedule\Creator\AssignedCounter;
use SportsPlanning\Schedule\Name as ScheduleName;
use SportsPlanning\Sport;

class Creator
{
    /**
     * @var array<string, AllInOneGameCreator|AgainstCreator|SingleCreator>|null
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
    public function createFromInput(Input $input): array
    {
        /** @var array<int, Schedule> $schedules */
        $schedules = [];
        $sportConfigsName = new ScheduleName(array_values($input->createSportVariants()->toArray()));
        $sportVariants = array_values($input->createSportVariants()->toArray());
        foreach ($input->getPoules() as $poule) {
            $nrOfPlaces = $poule->getPlaces()->count();
            if ($this->isScheduleAlreadyCreated($nrOfPlaces, (string)$sportConfigsName)) {
                continue;
            }
            if (array_key_exists($nrOfPlaces, $schedules)) {
                continue;
            }
            $schedule = new Schedule($nrOfPlaces, $poule->getInput());
            $schedules[$nrOfPlaces] = $schedule;

            $assignedCounter = new AssignedCounter($poule, $sportVariants);
            foreach ([GameMode::AllInOneGame, GameMode::Against, GameMode::Single] as $gameMode) {
                $sports = $this->getSports($input, $gameMode);
                if (count($sports) === 0) {
                    continue;
                }
                $scheduleCreator = $this->getSportScheduleCreator($input, $gameMode);
                $scheduleCreator->createSportSchedules($schedule, $poule, $sports, $assignedCounter);
            }
        }
        return array_values($schedules);
    }

    /**
     * @param Input $input
     * @param GameMode $gameMode
     * @return AllInOneGameCreator|AgainstCreator|SingleCreator
     */
    protected function getSportScheduleCreator(
        Input $input,
        GameMode $gameMode
    ): AllInOneGameCreator|AgainstCreator|SingleCreator {
        $generatorMap = $this->getScheduleCreatorMap($input);
        return $generatorMap[$gameMode->name];
    }

    /**
     * @param Input $input
     * @return array<string, AllInOneGameCreator|AgainstCreator|SingleCreator>
     */
    protected function getScheduleCreatorMap(Input $input): array
    {
        if ($this->generatorMap !== null) {
            return $this->generatorMap;
        }
        $this->generatorMap = [];
        $this->generatorMap[GameMode::AllInOneGame->name] = new AllInOneGameCreator();
        $this->generatorMap[GameMode::Against->name] = new AgainstCreator($this->logger);
        $this->generatorMap[GameMode::Single->name] = new SingleCreator($this->logger);
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

    public function isScheduleAlreadyCreated(int $nrOfPlaces, string $sportConfigsName): bool
    {
        if ($this->existingSchedules === null) {
            return false;
        }
        foreach ($this->existingSchedules as $existingSchedule) {
            if ($nrOfPlaces === $existingSchedule->getNrOfPlaces()
                && $sportConfigsName === $existingSchedule->getSportsConfigName()) {
                return true;
            }
        }
        return false;
    }
}
