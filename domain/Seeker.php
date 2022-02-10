<?php

declare(strict_types=1);

namespace SportsPlanning;

use Exception;
use Psr\Log\LoggerInterface;
use SportsHelpers\Output\Color;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Assigner as GameAssigner;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Input as InputBase;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\Input\Repository as InputRepository;
use SportsPlanning\Input\Service as InputService;
use SportsPlanning\Planning as PlanningBase;
use SportsPlanning\Planning\Filter as PlanningFilter;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Planning\Repository as PlanningRepository;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\Type as PlanningType;
use SportsPlanning\Schedule\Repository as ScheduleRepository;

class Seeker
{
    use Color;

    protected InputService $inputService;
    protected PlanningOutput $planningOutput;
    // protected EqualBatchGamesPostProcessor $equalbatchGamesPostProcessor;
    protected bool $throwOnTimeout;
    protected bool $showHighestCompletedBatchNr = false;

    public function __construct(
        protected LoggerInterface $logger,
        protected InputRepository $inputRepos,
        protected PlanningRepository $planningRepos,
        protected ScheduleRepository $scheduleRepos
    ) {
        $this->planningOutput = new PlanningOutput($this->logger);
        $this->inputService = new InputService();
        // $this->equalbatchGamesPostProcessor = new EqualBatchGamesPostProcessor($this->logger, $this->planningRepos);
        $this->throwOnTimeout = true;
    }

    public function showHighestCompletedBatchNr(): void
    {
        $this->showHighestCompletedBatchNr = true;
    }

    /**
     * @param InputBase $input
     * @param list<Schedule> $schedules
     * @throws Exception
     */
    public function processInput(Input $input, array $schedules): void
    {
        try {
            $this->planningOutput->outputInput($input, 'processing input(' . ((string)$input->getId()) . '): ', " ..");
            $this->inputRepos->removePlannings($input);

//            $plannings = $this->createBatchGamesPlannings($input);
            $bestEqualBatchGamePlanning = $this->processEqualBatchGamesPlannings($input, $schedules);
//             $this->processEqualBatchGamesPlannings($input, $schedules);
            $this->processUnequalBatchGamesPlannings($input, $schedules, $bestEqualBatchGamePlanning);

            // $this->processGamesInARowPlannings($input->getBestPlanning(), $schedules);
            $this->processGamesInARowPlannings($input, $schedules);
            // $this->inputRepos->save($input);
        } catch (Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
    }

    /**
     * @param InputBase $input
     * @param list<Schedule> $schedules
     * @return Planning
     * @throws Exception
     */
    protected function processEqualBatchGamesPlannings(InputBase $input, array $schedules): Planning
    {
        $nrOfBatches = $input->getMaxNrOfBatchGames();


//        $calculator = new NextEqualBatchGamesPlanningCalculator($input, $this->maxTimeoutSeconds);
        $this->logger->info('       -- ---------- start processing batchGames-plannings ----------');

        $planning = null;
        $nrOfPlanningsCreated = 0;
        while ($nrOfBatches >= 1) {
            $planning = new Planning($input, new SportRange($nrOfBatches, $nrOfBatches), 0);
            $nrOfPlanningsCreated++;
            $this->planningRepos->save($planning, true);
            $this->processPlanningHelper($planning, $schedules);
            $this->updateSeekingPercentage($input, new SportRange(0, 33), $nrOfPlanningsCreated);
            if ($planning->getState() === PlanningState::Succeeded) {
                // $this->equalBatchGamesPostProcessor->processSucceededPlanning($planningIt);
                break;
            }
            $nrOfBatches--;
        }
        if ($planning === null) {
            throw new \Exception('there should always be a successful equal-batchgames-planning', E_ERROR);
        }
        return $planning;
    }

    /**
     * @param Input $input
     * @param list<Schedule> $schedules
     * @param Planning $bestEqualBatchGamesPlanning
     * @throws Exception
     */
    protected function processUnequalBatchGamesPlannings(
        Input $input,
        array $schedules,
        Planning $bestEqualBatchGamesPlanning
    ): void {
        $this->logger->info('       -- ---------- start processing unequal-batchGames-plannings ----------');

        $range = new SportRange(
            $this->getMinNrOfBatchGames($bestEqualBatchGamesPlanning),
            $input->getMaxNrOfBatchGames()
        );
//        $calculator = new NextUnequalBatchGamesPlanningCalculator($input, $batchGamesRange, $this->maxTimeoutSeconds);
        $nrOfPlanningsCreated = 0;
        for ($maxNrOfBatches = $range->getMax(); $maxNrOfBatches > $range->getMin(); $maxNrOfBatches--) {
            for ($minNrOfBatches = $range->getMax() - 1; $minNrOfBatches >= $range->getMin(); $minNrOfBatches--) {
                if ($minNrOfBatches >= $maxNrOfBatches) {
                    continue;
                }
                $planning = new Planning($input, new SportRange($minNrOfBatches, $maxNrOfBatches), 0);
                $nrOfPlanningsCreated++;
                $this->planningRepos->save($planning, true);
                $this->updateSeekingPercentage($input, new SportRange(33, 66), $nrOfPlanningsCreated);
                $this->processPlanningHelper($planning, $schedules);
                if ($planning->getState() === PlanningState::Succeeded) {
                    return;
                }
            }
        }
    }

    /**
     *
     *
     * @param Planning $batchGamesPlanning
     * @param list<Schedule> $schedules
     * @throws Exception
     */
    public function processGamesInARowPlannings(Input $input, array $schedules): void
    {
        try {
            $this->logger->info('       -- ---------- remove all gamesInARow-plannings ----------');
            $this->removeGamesInARowPlannings($input);

            $this->logger->info('       -- ---------- start processing gamesInARow-plannings ----------');
            $bestBatchGamePlanning = $input->getBestPlanning(PlanningType::BatchGames);
            $nrOfPlanningsCreated = 0;
            $maxNrOfGamesInARowInput = $input->getMaxNrOfGamesInARow();
            for ($gamesInARow = 1; $gamesInARow <= $maxNrOfGamesInARowInput; $gamesInARow++) {
                $planning = new PlanningBase($input, $bestBatchGamePlanning->getNrOfBatchGames(), $gamesInARow);
                $nrOfPlanningsCreated++;
                $this->planningRepos->save($planning);
                $this->updateSeekingPercentage($input, new SportRange(66, 100), $nrOfPlanningsCreated);
                $this->processPlanningHelper($planning, $schedules);
                if ($planning->getState() === PlanningState::Succeeded || $planning->getState(
                    ) === PlanningState::Failed) {
                    break;
                }
            }
            $this->updateSeekingPercentage($input, new SportRange(100, 100));
        } catch (Exception $e) {
            $this->logger->error('   ' . '   ' . " => " . $e->getMessage());
        }
    }

    protected function updateSeekingPercentage(Input $input, SportRange $percentageRange, int|null $amount = null): void
    {
        if ($amount === null || $amount < 1) {
            $amount = 1;
        }
        $delta = ($amount - 1) / $amount;
        $delta *= ($percentageRange->getMax() - $percentageRange->getMin());

        $input->setSeekingPercentage((int)($percentageRange->getMin() + $delta));
        $this->inputRepos->save($input, true);
    }

    protected function removeGamesInARowPlannings(Input $input): void
    {
        $plannings = $input->getPlannings()->filter(function (Planning $planning): bool {
            return !$planning->isBatchGames();
        })->toArray();
//        $bestMaxNrOfGamesInARowPlanning = null;
//        foreach( $plannings as $planning ) {
//            if ($bestMaxNrOfGamesInARowPlanning === null
//                || $planning->getMaxNrOfGamesInARow() < $bestMaxNrOfGamesInARowPlanning->getMaxNrOfGamesInARow()) {
//                $bestMaxNrOfGamesInARowPlanning = $planning;
//            }
//        }

        while (count($plannings) > 0) {
            $planning = array_pop($plannings);
//            if( $planning->getMaxNrOfGamesInARow() < $bestMaxNrOfGamesInARowPlanning->getMaxNrOfGamesInARow() ) {
//                continue;
//            }
            $planning->getInput()->getPlannings()->removeElement($planning);
            $this->planningRepos->remove($planning);
        }
    }

    public function getMinNrOfBatchGames(Planning $bestEqualBatchGamesPlanning): int
    {
        $input = $bestEqualBatchGamesPlanning->getInput();

        $pouleStructure = $input->createPouleStructure();
        if (!$pouleStructure->isBalanced()) {
            $poules = $pouleStructure->toArray();
            $firstPouleNrOfPlaces = array_shift($poules);
            $secondPouleNrOfPlaces = array_shift($poules);
            if ($firstPouleNrOfPlaces !== null && $secondPouleNrOfPlaces !== null && $firstPouleNrOfPlaces > $secondPouleNrOfPlaces) {
                array_unshift($poules, $secondPouleNrOfPlaces);
                $pouleStructure = new PouleStructure(...$poules);
            }
        }
        $calculator = new InputCalculator();
        /** @var non-empty-list<SportVariantWithFields> $sportVariantsWithFields */
        $sportVariantsWithFields = array_values($input->createSportVariantsWithFields()->toArray());
        return $calculator->getMinNrOfGamesPerBatch(
            $pouleStructure,
            $sportVariantsWithFields,
            $input->getRefereeInfo()
        );
    }

    /**
     * @param Input $input
     * @param list<Schedule> $schedules
     * @param PlanningFilter $filter
     * @return Planning|null
     * @throws Exception
     */
    public function processFilter(Input $input, array $schedules, PlanningFilter $filter): Planning|null
    {
        $this->logger->info('       -- ---------- start processing planning-filter (only debug) -------');
        foreach ($input->getPlannings() as $planning) {
            if (!$filter->equals($planning)) {
                continue;
            }
            $this->planningRepos->resetPlanning($planning, PlanningState::ToBeProcessed);
            $this->processPlanningHelper($planning, $schedules);
            return $planning;
        }
        return null;
    }

    /**
     * @param Planning $planning
     * @param list<Schedule> $schedules
     * @throws Exception
     */
    protected function processPlanningHelper(Planning $planning, array $schedules): void
    {
        $this->planningOutput->output($planning, false, '   ', " trying .. ");

        $gameCreator = new GameCreator($this->logger);
        $gameCreator->createGames($planning, $schedules);
        // $this->planningRepos->save($planning);

        $gameAssigner = new GameAssigner($this->logger);
        if (!$this->throwOnTimeout) {
            $gameAssigner->disableThrowOnTimeout();
        }
        if ($this->showHighestCompletedBatchNr) {
            $gameAssigner->showHighestCompletedBatchNr();
        }
        $gameAssigner->assignGames($planning);
        $this->planningRepos->save($planning);

//        $planningOutput = new PlanningOutput($this->logger);
//        $planningOutput->outputWithGames($planning, false);
//        $planningOutput->outputWithTotals($planning, false);

        $stateDescription = $planning->getState() === PlanningState::Failed ? "failed" :
            ($planning->getState() === PlanningState::TimedOut ? "timeout(" . $planning->getTimeoutSeconds(
                ) . ")" : "success");

        $this->logger->info('   ' . '   ' . " => " . $stateDescription);
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
