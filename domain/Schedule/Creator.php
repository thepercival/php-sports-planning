<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\Referee\Info;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\CreatorHelpers\Against\H2h as AgainstH2hCreatorHelper;
use SportsPlanning\Schedule\CreatorHelpers\Against\GamesPerPlace as AgainstGppCreatorHelper;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Schedule\CreatorHelpers\AgainstGppDifferenceManager;
use SportsPlanning\Schedule\CreatorHelpers\AllInOneGame as AllInOneGameCreatorHelper;
use SportsPlanning\Schedule\CreatorHelpers\Single as SingleCreatorHelper;
use SportsPlanning\Schedule\Name as ScheduleName;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;

class Creator
{
    public const MAX_ALLOWED_GPP_MARGIN = 4;

    /**
     * @var list<Schedule>|null
     */
    protected array|null $existingSchedules = null;


    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Input $input
     * @param int|null $nrOfSecondsBeforeTimeout
     * @return list<Schedule>
     */
    public function createFromInput(
        Input $input,
        int $allowedGppMargin = self::MAX_ALLOWED_GPP_MARGIN,
        int|null $nrOfSecondsBeforeTimeout = null): array
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

            $allInOneGameSportVariantMap = $this->getAllInOneGameSportVariantMap($input);
            (new AllInOneGameCreatorHelper())->createSportSchedules($schedule, $poule, $allInOneGameSportVariantMap);

            $singleSportVariantMap = $this->getSingleSportVariantMap($input);
            $singleHelper = new SingleCreatorHelper($this->logger);
            $singleHelper->createSportSchedules($schedule, $poule, $singleSportVariantMap, $assignedCounter);

            $againstH2hSportVariantMap = $this->getAgainstH2hSportVariantMap($input);
            $againstH2hHelper = new AgainstH2hCreatorHelper($this->logger);
            $againstH2hHelper->createSportSchedules($schedule, $poule, $againstH2hSportVariantMap, $assignedCounter);

            $againstGppVariantMap = $this->getAgainstGppSportVariantMap($input, $poule);
            if( count($againstGppVariantMap) > 0) {
                $differenceManager = new AgainstGppDifferenceManager(
                    $poule,
                    $againstGppVariantMap,
                    $allowedGppMargin,
                    $this->logger);
                $againstGppHelper = new AgainstGppCreatorHelper($this->logger);
                $againstGppHelper->createSportSchedules(
                    $schedule,
                    $poule,
                    $againstGppVariantMap,
                    $assignedCounter,
                    $differenceManager,
                    $nrOfSecondsBeforeTimeout);
            }
//            try {
//            } catch(LessThanMinimumAgainstDifferenceException $e) {
//
//            }
        }
        return array_values($schedules);
    }

    public function createBetterSchedule(
        Schedule $schedule,
        int $allowedGppMargin,
        int $nrOfSecondsBeforeTimeout): Schedule
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

        $allInOneGameSportVariantMap = $this->getAllInOneGameSportVariantMap($input);
        (new AllInOneGameCreatorHelper())->createSportSchedules($newSchedule, $newPoule, $allInOneGameSportVariantMap);

        $singleSportVariantMap = $this->getSingleSportVariantMap($input);
        $singleHelper = new SingleCreatorHelper($this->logger);
        $singleHelper->createSportSchedules($newSchedule, $newPoule, $singleSportVariantMap, $assignedCounter);

        $againstH2hSportVariantMap = $this->getAgainstH2hSportVariantMap($input);
        $againstH2hHelper = new AgainstH2hCreatorHelper($this->logger);
        $againstH2hHelper->createSportSchedules($newSchedule, $newPoule, $againstH2hSportVariantMap, $assignedCounter);

        $againstGppSportVariantMap = $this->getAgainstGppSportVariantMap($input, $newPoule);
        if( count($againstGppSportVariantMap) > 0) {
            $differenceManager = new AgainstGppDifferenceManager($newPoule, $againstGppSportVariantMap, $allowedGppMargin, $this->logger);
            $againstGppHelper = new AgainstGppCreatorHelper($this->logger);
            $againstGppHelper->createSportSchedules(
                $newSchedule, $newPoule, $againstGppSportVariantMap,
                $assignedCounter, $differenceManager, $nrOfSecondsBeforeTimeout);
        }

        return $newSchedule;
    }

    /**
     * @param Input $input
     * @return array<int, AllInOneGame>
     */
    protected function getAllInOneGameSportVariantMap(Input $input): array
    {
        $map = [];
        foreach( $input->getSports() as $sport) {
            $sportVariant = $sport->createVariant();
            if( $sportVariant instanceof AllInOneGame) {
                $map[$sport->getNumber()] = $sportVariant;
            }
        }
        return $map;
    }

    /**
     * @param Input $input
     * @return array<int, Single>
     */
    protected function getSingleSportVariantMap(Input $input): array
    {
        $map = [];
        foreach( $input->getSports() as $sport) {
            $sportVariant = $sport->createVariant();
            if( $sportVariant instanceof Single) {
                $map[$sport->getNumber()] = $sportVariant;
            }
        }
        return $map;
    }

    /**
     * @param Input $input
     * @return array<int, AgainstH2h>
     */
    protected function getAgainstH2hSportVariantMap(Input $input): array
    {
        $map = [];
        foreach( $input->getSports() as $sport) {
            $sportVariant = $sport->createVariant();
            if( $sportVariant instanceof AgainstH2h) {
                $map[$sport->getNumber()] = $sportVariant;
            }
        }
        return $map;
    }

    /**
     * @param Input $input
     * @param Poule $poule
     * @return array<int, AgainstGpp>
     */
    protected function getAgainstGppSportVariantMap(Input $input, Poule $poule): array
    {
        $map = [];
        foreach( $input->getSports() as $sport) {
            $sportVariant = $sport->createVariant();
            if( $sportVariant instanceof AgainstGpp) {
                $map[$sport->getNumber()] = $sportVariant;
            }
        }
        return $this->sortByEquallyAssigned($poule, $map);
    }

    /**
     * @param Poule $poule
     * @param array<int, AgainstGpp> $sportVariantMap
     * @return array<int, AgainstGpp>
     */
    protected function sortByEquallyAssigned(Poule $poule, array $sportVariantMap): array
    {
        uasort($sportVariantMap, function (AgainstGpp $sportVariantA, AgainstGpp $sportVariantB) use($poule): int {
            $sportVariantWithPouleA = new AgainstGppWithPoule($poule, $sportVariantA );
            $sportVariantWithPouleB = new AgainstGppWithPoule($poule, $sportVariantB );
            $allPlacesSameNrOfGamesA = $sportVariantWithPouleA->allPlacesSameNrOfGamesAssignable();
            $allPlacesSameNrOfGamesB = $sportVariantWithPouleB->allPlacesSameNrOfGamesAssignable();
            if (($allPlacesSameNrOfGamesA && $allPlacesSameNrOfGamesB)
                || (!$allPlacesSameNrOfGamesA && !$allPlacesSameNrOfGamesB)) {
                return 0;
            }
            return $allPlacesSameNrOfGamesA ? -1 : 1;
        });
        return $sportVariantMap;
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
