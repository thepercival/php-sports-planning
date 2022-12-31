<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Psr\Log\LoggerInterface;
use SportsHelpers\GameMode;
use SportsHelpers\PouleStructure;
use SportsPlanning\Input;
use SportsPlanning\Referee\Info;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\Creator\AllInOneGame as AllInOneGameCreator;
use SportsPlanning\Schedule\Creator\Against as AgainstCreator;
use SportsPlanning\Schedule\Creator\Single as SingleCreator;
use SportsPlanning\Schedule\Creator\AssignedCounter;
use SportsPlanning\Schedule\Name as ScheduleName;
use SportsPlanning\Sport;
use SportsPlanning\Schedule\Output as ScheduleOutput;

class Creator
{
    public const MAX_ALLOWED_GPP_MARGIN = 10;

    /**
     * @var array<string, AllInOneGameCreator|AgainstCreator|SingleCreator>|null
     */
    protected array|null $generatorMap = null;
    /**
     * @var list<Schedule>|null
     */
    protected array|null $existingSchedules = null;
    protected int $allowedGppMargin = self::MAX_ALLOWED_GPP_MARGIN;


    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Input $input
     * @param int|null $nrOfSecondsBeforeTimeout
     * @return list<Schedule>
     */
    public function createFromInput(Input $input, int|null $nrOfSecondsBeforeTimeout = null): array
    {
        /** @var array<int, Schedule> $schedules */
        $schedules = [];
        $sportVariants = array_values($input->createSportVariants()->toArray());
        $sportConfigsName = new ScheduleName($sportVariants);
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
                $scheduleCreator = $this->getSportScheduleCreator($gameMode);
                $scheduleCreator->createSportSchedules($schedule, $poule, $sports, $assignedCounter, $nrOfSecondsBeforeTimeout);
            }
        }
        return array_values($schedules);
    }

    public function createBetterSchedule(Schedule $schedule, int $nrOfSecondsBeforeTimeout): Schedule
    {
        $input = new Input(
            new PouleStructure( $schedule->getNrOfPlaces() ),
            $schedule->createSportVariantWithFields(),
            new Info(0),
            false
        );
        $sportVariants = array_values($input->createSportVariants()->toArray());
        $newSchedule = new Schedule($schedule->getNrOfPlaces(), $input);
        $newPoule = $newSchedule->getPoule();

        $assignedCounter = new AssignedCounter($newPoule, $sportVariants);
        foreach ([GameMode::AllInOneGame, GameMode::Against, GameMode::Single] as $gameMode) {
            $sports = $this->getSports($input, $gameMode);
            if (count($sports) === 0) {
                continue;
            }
            $scheduleCreator = $this->getSportScheduleCreator($gameMode);
            $scheduleCreator->createSportSchedules($newSchedule, $newPoule, $sports, $assignedCounter, $nrOfSecondsBeforeTimeout);
        }
        return $newSchedule;
    }

    public function setAllowedGppMargin(int $allowedGppMargin): void {
        $this->allowedGppMargin = $allowedGppMargin;
    }

    protected function getSportScheduleCreator(GameMode $gameMode): AllInOneGameCreator|AgainstCreator|SingleCreator {
        $generatorMap = $this->getScheduleCreatorMap();
        return $generatorMap[$gameMode->name];
    }

    /**
     * @return array<string, AllInOneGameCreator|AgainstCreator|SingleCreator>
     */
    protected function getScheduleCreatorMap(): array
    {
        if ($this->generatorMap !== null) {
            return $this->generatorMap;
        }
        $this->generatorMap = [];
        $this->generatorMap[GameMode::AllInOneGame->name] = new AllInOneGameCreator();
        $this->generatorMap[GameMode::Against->name] = new AgainstCreator($this->logger, $this->allowedGppMargin);
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
