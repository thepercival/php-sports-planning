<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule;

use Psr\Log\LoggerInterface;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\WithPoule\Against\EquallyAssignCalculator;
use SportsPlanning\Combinations\AssignedCounter;
use SportsPlanning\Input;
use SportsPlanning\Poule;
use SportsPlanning\Referee\Info;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\CreatorHelpers\Against\H2h as AgainstH2hCreatorHelper;
use SportsPlanning\Schedule\CreatorHelpers\Against\GamesPerPlace as AgainstGppCreatorHelper;
use SportsPlanning\Combinations\Amount\Range as AmountRange;
use SportsPlanning\Schedule\CreatorHelpers\AgainstDifferenceManager;
use SportsPlanning\Schedule\CreatorHelpers\AllInOneGame as AllInOneGameCreatorHelper;
use SportsPlanning\Schedule\CreatorHelpers\Single as SingleCreatorHelper;
use SportsPlanning\Schedule\Name as ScheduleName;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;

class Creator
{
    /**
     * @var list<Schedule>|null
     */
    protected array|null $existingSchedules = null;


    public function __construct(protected LoggerInterface $logger)
    {
    }

    /**
     * @param Input $input
     * @param int $allowedGppMargin
     * @param int|null $nrOfSecondsBeforeTimeout
     * @return list<Schedule>
     */
    public function createFromInput(
        Input $input,
        int $allowedGppMargin,
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

            $againstVariantMap = $this->getAgainstSportVariantMap($input, $poule);
            if( count($againstVariantMap) > 0) {
                $differenceManager = new AgainstDifferenceManager(
                    $poule,
                    $againstVariantMap,
                    $allowedGppMargin,
                    $this->logger);

                $againstH2hMap = $this->getAgainstH2hSportVariantMap($input);
                if( count($againstH2hMap) > 0 ) {
                    $againstH2hHelper = new AgainstH2hCreatorHelper($this->logger);
                    $againstH2hHelper->createSportSchedules(
                        $schedule,
                        $poule,
                        $againstH2hMap,
                        $assignedCounter,
                        $differenceManager);
                }
                $againstGppMap = $this->getAgainstGppSportVariantMap($input, $poule);
                if( count($againstGppMap) > 0) {
                    $againstGppHelper = new AgainstGppCreatorHelper($this->logger);
                    $againstGppHelper->createSportSchedules(
                        $schedule,
                        $poule,
                        $againstGppMap,
                        $assignedCounter,
                        $differenceManager,
                        $nrOfSecondsBeforeTimeout);
                }
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

        $againstVariantMap = $this->getAgainstSportVariantMap($input, $newPoule);

        if( count($againstVariantMap) > 0) {
            $differenceManager = new AgainstDifferenceManager($newPoule, $againstVariantMap, $allowedGppMargin, $this->logger);

            $againstH2hMap = $this->getAgainstH2hSportVariantMap($input);
            if( count($againstH2hMap) > 0 ) {
                $againstH2hHelper = new AgainstH2hCreatorHelper($this->logger);
                $againstH2hHelper->createSportSchedules(
                    $newSchedule, $newPoule, $againstH2hMap, $assignedCounter, $differenceManager);
            }
            $againstGppMap = $this->getAgainstGppSportVariantMap($input, $newPoule);
            if( count($againstGppMap) > 0 ) {
                $againstGppHelper = new AgainstGppCreatorHelper($this->logger);
                $againstGppHelper->createSportSchedules(
                    $newSchedule, $newPoule, $againstGppMap,
                    $assignedCounter, $differenceManager, $nrOfSecondsBeforeTimeout);
            }
        }

        return $newSchedule;
    }

    public function getMaxGppMargin(Input $input, Poule $poule): int {
        $maxAgainstMargin = 0;
        $maxWithMargin = 0;
        {
            $againstGppMap = $this->getAgainstGppSportVariantMap($input, $poule);
            if( count($againstGppMap) > 0 ) {
                list($maxWithMargin,$maxAgainstMargin) = $this->getMargins($poule, $againstGppMap);
            }
        }

        {
            $singleSportVariantMap = $this->getSingleSportVariantMap($input);
            if( count($singleSportVariantMap) > 0 ) {
                $maxWithMargin = max(1, $maxWithMargin);
            }
        }

        return max($maxAgainstMargin, $maxWithMargin);
    }

    /**
     * @param Poule $poule
     * @param array<int, AgainstGpp> $againstGppMap
     * @return list<int>
     */
    private function getMargins(Poule $poule, array $againstGppMap): array {
        $nrOfPlaces = $poule->getPlaces()->count();
        $allowedAgainstAmountCum = 0;
        $nrOfAgainstCombinationsCumulative = 0;
        $allowedWithAmountCum = 0;
        $nrOfWithCombinationsCumulative = 0;
        foreach ($againstGppMap as $againstGpp) {
            $againstGppWithPoule = new AgainstGppWithPoule($nrOfPlaces, $againstGpp);
            $nrOfSportGames = $againstGppWithPoule->getTotalNrOfGames();
            // against
            {
                $nrOfAgainstCombinationsSport = $againstGpp->getNrOfAgainstCombinationsPerGame() * $nrOfSportGames;
                $nrOfAgainstCombinationsCumulative += $nrOfAgainstCombinationsSport;
                $allowedAgainstAmountCum += (new EquallyAssignCalculator())->getMaxAmount(
                    $nrOfAgainstCombinationsCumulative,
                    $againstGppWithPoule->getNrOfPossibleAgainstCombinations()
                );
            }
            // with
            {
                $nrOfWithCombinationsSport = $againstGpp->getNrOfWithCombinationsPerGame() * $nrOfSportGames;
                $nrOfWithCombinationsCumulative += $nrOfWithCombinationsSport;
                $allowedWithAmountCum += (new EquallyAssignCalculator())->getMaxAmount(
                    $nrOfWithCombinationsCumulative,
                    $againstGppWithPoule->getNrOfPossibleWithCombinations()
                );
            }
        }
        return [$allowedAgainstAmountCum,$allowedWithAmountCum];
    }

    /**
     * @param array<int, Single> $singleVariantMap
     * @return int
     */
    private function getTotalNrOfWithPerPlace(array $singleVariantMap): int {
        $nrOfWithPerPlace = 0;
        foreach ($singleVariantMap as $singleVariant) {
            $nrOfWithPerPlace += $singleVariant->getNrOfGamesPerPlace();
        }
        return $nrOfWithPerPlace;
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
     * @param Input $input
     * @param Poule $poule
     * @return array<int, AgainstH2h|AgainstGpp>
     */
    protected function getAgainstSportVariantMap(Input $input, Poule $poule): array
    {
        $againstVariantMap = [];
        foreach( $this->getAgainstH2hSportVariantMap($input) as $sportNr => $againstH2h) {
            $againstVariantMap[$sportNr] = $againstH2h;
        }
        foreach( $this->getAgainstGppSportVariantMap($input, $poule) as $sportNr => $againstGpp) {
            $againstVariantMap[$sportNr] = $againstGpp;
        }
        return $againstVariantMap;
    }

    /**
     * @param Poule $poule
     * @param array<int, AgainstGpp> $sportVariantMap
     * @return array<int, AgainstGpp>
     */
    protected function sortByEquallyAssigned(Poule $poule, array $sportVariantMap): array
    {
        $nrOfPlaces = $poule->getPlaces()->count();
        uasort($sportVariantMap, function (AgainstGpp $sportVariantA, AgainstGpp $sportVariantB) use($nrOfPlaces): int {
            $sportVariantWithPouleA = new AgainstGppWithPoule($nrOfPlaces, $sportVariantA );
            $sportVariantWithPouleB = new AgainstGppWithPoule($nrOfPlaces, $sportVariantB );
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
