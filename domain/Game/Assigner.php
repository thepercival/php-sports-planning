<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Psr\Log\LoggerInterface;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Planning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\Resource\Service as ResourceService;

class Assigner
{
    protected bool $throwOnTimeout;
    protected bool $showHighestCompletedBatchNr = false;

    public function __construct(protected LoggerInterface $logger)
    {
        $this->throwOnTimeout = true;
    }

    public function assignGames(Planning $planning): void
    {
        $games = (new PreAssignSorter())->getGames($planning);
        // (new GameOutput($this->logger))->outputGames($games);

        $resourceService = new ResourceService($planning, $this->logger);
        if (!$this->throwOnTimeout) {
            $resourceService->disableThrowOnTimeout();
        }
        if ($this->showHighestCompletedBatchNr) {
            $resourceService->showHighestCompletedBatchNr();
        }
        $state = $resourceService->assign($games);
        if ($state === PlanningState::Failed || $state === PlanningState::TimedOut) {
            $planning->getAgainstGames()->clear();
            $planning->getTogetherGames()->clear();
            $planning->setState($state);
            $planning->setNrOfBatches(0);
            return;
        }

        $firstBatch = $planning->createFirstBatch();
//        (new BatchOutput())->output($firstBatch, '', null, null, true);
        if ($firstBatch instanceof SelfRefereeBatchOtherPoule || $firstBatch instanceof SelfRefereeBatchSamePoule) {
            $refereePlaceService = new RefereePlaceService($planning);
            if (!$this->throwOnTimeout) {
                $refereePlaceService->disableThrowOnTimeout();
            }
            $state = $refereePlaceService->assign($firstBatch);
            if ($state === PlanningState::Failed || $state === PlanningState::TimedOut) {
                $planning->getAgainstGames()->clear();
                $planning->getTogetherGames()->clear();
                $planning->setState($state);
                $planning->setNrOfBatches(0);
                $this->logger->error('   could not assign refereeplaces (plId:' . (string)$planning->getId() . ')');
                return;
            }
        }
        $planning->setState(PlanningState::Succeeded);
        $planning->setNrOfBatches($firstBatch->getLeaf()->getNumber());
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }

    public function showHighestCompletedBatchNr(): void
    {
        $this->showHighestCompletedBatchNr = true;
    }
}
