<?php

namespace SportsPlanning\Resource;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Batch;
use SportsPlanning\Batch\Output as BatchOutput;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Output as GameOutput;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Resource\Fields as FieldResources;
use SportsPlanning\Resource\RefereePlace\Predicter;
use SportsPlanning\Resource\Service\Helper;
use SportsPlanning\Sport;
use SportsPlanning\TimeoutException;

class Service
{
    private DateTimeImmutable|null $timeoutDateTime = null;
    private Predicter $refereePlacePredicter;
    protected BatchOutput $batchOutput;
    protected PlanningOutput $planningOutput;
    protected GameOutput $gameOutput;
    protected bool $throwOnTimeout;
    /**
     * @var array<int, AgainstSportVariant|SingleSportVariant>
     */
    protected array $sportVariantMap;
    protected Helper $helper;
    protected Input $input;

    public function __construct(protected Planning $planning, protected LoggerInterface $logger)
    {
        $this->helper = new Helper($planning, $logger);
        $this->input = $planning->getInput();
        $poules = array_values($this->input->getPoules()->toArray());
        $this->refereePlacePredicter = new Predicter($poules);
        $this->batchOutput = new BatchOutput($logger);
        $this->planningOutput = new PlanningOutput($logger);
        $this->gameOutput = new GameOutput($logger);
        $this->initSportVariantMap($planning->getInput());
        $this->throwOnTimeout = true;
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @return PlanningState
     */
    public function assign(array $games): PlanningState
    {
        $oCurrentDateTime = new DateTimeImmutable();
        $this->timeoutDateTime = $oCurrentDateTime->modify("+" . $this->planning->getTimeoutSeconds() . " seconds");
        $batch = new Batch();
        if ($this->input->selfRefereeEnabled()) {
            if ($this->input->getSelfReferee() === SelfReferee::SamePoule) {
                $batch = new SelfRefereeSamePouleBatch($batch);
            } else {
                $poules = array_values($this->input->getPoules()->toArray());
                $batch = new SelfRefereeOtherPouleBatch($poules, $batch);
            }
        }

        try {
            $fieldResources = new FieldResources($this->input);
            $assignedBatch = $this->assignBatch($games, $fieldResources, $batch);
            if ($assignedBatch === null) {
                return PlanningState::Failed;
            }
            if ($assignedBatch instanceof Batch) {
                $refereeService = new RefereeService($this->input);
                $refereeService->assign($assignedBatch->getFirst());
            }

            // $this->batchOutput->output($batch->getFirst(), ' final : iterations = ' . $this->debugIterations );
        } catch (TimeoutException $e) {
            return PlanningState::TimedOut;
        }
        return PlanningState::Succeeded;
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @param FieldResources $fieldResources
     * @param Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch
     * @return Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch|null
     * @throws TimeoutException
     */
    protected function assignBatch(
        array $games,
        FieldResources $fieldResources,
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch
    ): Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch|null {
        if ($this->assignBatchHelper($games, $games, $fieldResources, $batch, $this->planning->getMaxNrOfBatchGames())) {
            return $this->getActiveLeaf($batch->getLeaf());
        }
        return null;
    }

    protected function getActiveLeaf(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch): Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch
    {
        $previousBatch = $batch->getPrevious();
        if ($previousBatch === null) {
            return $batch;
        }
        if (count($previousBatch->getGames()) === $this->planning->getMaxNrOfBatchGames()) {
            return $batch;
        }
        return $this->getActiveLeaf($previousBatch);
    }

    /**
     * @param list<TogetherGame|AgainstGame> $games
     * @param list<TogetherGame|AgainstGame> $gamesForBatch
     * @param FieldResources $fieldResources
     * @param Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch
     * @param int $maxNrOfBatchGames
     * @param int $nrOfGamesTried
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatchHelper(
        array $games,
        array $gamesForBatch,
        FieldResources $fieldResources,
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch,
        int $maxNrOfBatchGames,
        int $nrOfGamesTried = 0
    ): bool {
        if ((count($batch->getGames()) === $maxNrOfBatchGames
                || (count($gamesForBatch) === 0) && count($games) === count($batch->getGames()))
        ) { // batchsuccess
            if (!$this->refereePlacesCanBeAssigned($batch)) {
                return false;
            }

            $this->setGamesBatchNr($batch);

//            $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber() );
//            $this->logger->info('unassinged games: ');
//            $this->batchOutput->outputGames($games);
//            if( $nextBatch->getNumber() === 10 ) {
//                $er = 4;
//            }

            if (count($gamesForBatch) === 0 && count($games) === 0) { // endsuccess
                return true;
            }

            $nextBatch = $this->toNextBatch($batch, $fieldResources, $games);

//            if ($nextBatch->getNumber() === 12) {
//                $er = 12;
//            }

            if (!$this->helper->gamesCanBeAssignedToMinNrOfBatchGames($batch->getNumber(), $games)) {
                return false;
            }

            // ------------- BEGIN: OUTPUT --------------- //
            // 60 wedstrijden , 9 wedstrijden per batch, is 7
//            if ($nextBatch->getNumber() === 12) {
//                $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber());
//                $this->logger->info('unassinged games: ');
//                $this->batchOutput->outputGames($games);
//            }
            // ------------- END: OUTPUT --------------- //

            //            $this->logger->info(' nr of games to process before gamesinarow-filter(max '.$this->planning->getMaxNrOfGamesInARow().') : '  . count($games) );
            //            $this->gameOutput->outputGames($games);

            $gamesForBatchTmp = array_filter(
                $games,
                function (TogetherGame|AgainstGame $game) use ($nextBatch): bool {
                    return $this->areAllPlacesAssignableByGamesInARow($nextBatch, $game);
                }
            );
            $this->helper->sortGamesByNotInPreviousBatch($batch, $gamesForBatchTmp);

//            $this->logger->info(' nr of games to process after gamesinarow-filter(max '.$this->planning->getMaxNrOfGamesInARow().') : '  . count($gamesForBatchTmp) );
//            $this->gameOutput->outputGames($gamesForBatchTmp);
            $gamesList = array_values($gamesForBatchTmp);
            return $this->assignBatchHelper($games, $gamesList, $fieldResources, $nextBatch, $this->planning->getMaxNrOfBatchGames());
        }
        if ($this->throwOnTimeout && (new DateTimeImmutable()) > $this->timeoutDateTime) {
            throw new TimeoutException(
                "exceeded maximum duration of " . $this->planning->getTimeoutSeconds() . " seconds",
                E_ERROR
            );
        }
        if ($nrOfGamesTried === count($gamesForBatch)) {
            return false;
        }
        $game = array_shift($gamesForBatch);
        if ($game === null) {
            return false;
        }
//        if( count($batch->getGames()) === 7 ) {
//            $er = 12;
//        }
        if ($this->isGameAssignable($batch, $game, $fieldResources)) {
            $newFieldResources = clone $fieldResources; // ->copy($this->planning);
            $this->assignGame($batch, $game, $newFieldResources);
            $gamesForBatchTmp = array_values(
                array_filter(
                    $gamesForBatch,
                    function (TogetherGame|AgainstGame $game) use ($batch): bool {
                        return $this->areAllPlacesAssignable($batch, $game);
                    }
                )
            );
            if ($this->assignBatchHelper($games, $gamesForBatchTmp, $newFieldResources, $batch, $maxNrOfBatchGames)) {
                return true;
            }
            $this->releaseGame($batch, $game);
        }
        $gamesForBatch[] = $game;
        ++$nrOfGamesTried;
        if ($this->assignBatchHelper(
            $games,
            $gamesForBatch,
            clone $fieldResources,
            $batch,
            $maxNrOfBatchGames,
            $nrOfGamesTried
        )) {
            return true;
        }
        if ($this->planning->getNrOfBatchGames()->difference() > 0
            && $maxNrOfBatchGames > $this->planning->getMinNrOfBatchGames()) {
            $gamesForBatch[] = $game;
            if ($this->assignBatchHelper(
                $games,
                $gamesForBatch,
                clone $fieldResources,
                $batch,
                $maxNrOfBatchGames - 1
            )) {
                return true;
            }
        }
        return false;
    }

    protected function assignGame(
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch,
        TogetherGame|AgainstGame $game,
        FieldResources $fieldResources
    ): void {
        $fieldResources->assignToGame($game);
        $batch->add($game);
    }

    protected function releaseGame(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch, TogetherGame|AgainstGame $game): void
    {
        $batch->remove($game);
    }

    /**
     * @param Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch
     */
    protected function setGamesBatchNr(
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch
    ): void {
        foreach ($batch->getGames() as $game) {
            $game->setBatchNr($batch->getNumber());
        }
    }

    /**
     * @param Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch
     * @param FieldResources $fieldResources
     * @param list<TogetherGame|AgainstGame> $games
     * @return Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch
     */
    protected function toNextBatch(
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch,
        FieldResources $fieldResources,
        array &$games
    ): Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch {
        $fieldResources->fill();
        foreach ($batch->getGames() as $game) {
            $foundGameIndex = array_search($game, $games, true);
            if ($foundGameIndex !== false) {
                array_splice($games, $foundGameIndex, 1);
            }
        }
        return $batch->createNext();
    }

    private function isGameAssignable(
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch,
        TogetherGame|AgainstGame $game,
        Fields $fieldResources
    ): bool {
        if (!$fieldResources->isSomeFieldAssignable($game->getSport(), $game->getPoule())) {
            return false;
        }
        if (!$this->areAllPlacesAssignable($batch, $game)) {
            return false;
        }
        if (!($batch instanceof SelfRefereeSamePouleBatch)) {
            return true;
        }
        return $this->hasPouleRefereePlaceAvailable($batch, $game);
    }

    // de wedstrijd is assignbaar als
    // 1 alle plekken, van een wedstrijd, nog niet in de batch
    // 2 alle plekken, van een wedstrijd, de sport nog niet vaak genoeg gedaan heeft of alle sporten al gedaan
    private function areAllPlacesAssignable(
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch,
        TogetherGame|AgainstGame $game
    ): bool {
        $maxNrOfGamesInARow = $this->planning->getMaxNrOfGamesInARow();
        foreach ($game->getPoulePlaces() as $place) {
            if ($batch->isParticipating($place)) {
                return false;
            }
            $previousBatch = $batch->getPrevious();
            if ($previousBatch === null) {
                continue;
            }
            $nrOfGamesInARow = $previousBatch->getGamesInARow($place);
            if ($nrOfGamesInARow < $maxNrOfGamesInARow || $maxNrOfGamesInARow <= 0) {
                continue;
            }
            return false;
        }
        return true;
    }

    private function areAllPlacesAssignableByGamesInARow(
        Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch,
        TogetherGame|AgainstGame $game
    ): bool {
        if ($this->planning->getMaxNrOfGamesInARow() === 0) {
            return true;
        }
        foreach ($game->getPoulePlaces() as $place) {
            $previousBatch = $batch->getPrevious();
            if ($previousBatch === null) {
                continue;
            }
            $nrOfGamesInARow = $previousBatch->getGamesInARow($place) + 1;
            if ($nrOfGamesInARow > $this->planning->getMaxNrOfGamesInARow()) {
                return false;
            }
        }
        return true;
    }

    protected function hasPouleRefereePlaceAvailable(
        SelfRefereeSamePouleBatch $batch,
        TogetherGame|AgainstGame $game
    ): bool {
        $poule = $game->getPoule();
        $nrAvailable = $poule->getPlaces()->count() - $batch->getNrOfPlacesParticipating($poule);
        $selfRefereePlace = 1;
        return $nrAvailable >= ($this->getSportVariant($game->getSport())->getNrOfGamePlaces() + $selfRefereePlace);
    }

    protected function refereePlacesCanBeAssigned(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch): bool
    {
        // naast forced refereeplaces and teveel

        if ($batch instanceof Batch\SelfReferee) {
            return $this->refereePlacePredicter->canStillAssign($batch, $this->input->getSelfReferee());
        }
        return true;
    }

    private function initSportVariantMap(Input $input): void
    {
        $this->sportVariantMap = [];
        foreach ($input->getSports() as $sport) {
            $variant = $sport->createVariant();
            if ($variant instanceof AllInOneGameSportVariant) {
                continue;
            }
            $this->sportVariantMap[$sport->getNumber()] = $variant;
        }
    }

    public function getSportVariant(Sport $sport): AgainstSportVariant|SingleSportVariant
    {
        return $this->sportVariantMap[$sport->getNumber()];
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
