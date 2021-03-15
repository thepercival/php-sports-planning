<?php

namespace SportsPlanning\Resource;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Planning;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\SelfReferee;
use SportsPlanning\Sport;
use SportsPlanning\Resources as Resources;
use SportsPlanning\Input;
use SportsPlanning\Sport\Counter as SportCounter;
use SportsPlanning\Sport\NrFields as SportNrFields;
use SportsPlanning\Batch;
use SportsPlanning\Batch\Output as BatchOutput;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Game\Output as GameOutput;
use SportsPlanning\Resource\RefereePlace\Predicter;
use SportsPlanning\TimeoutException;

class Service
{
    private int $nrOfPoules;
    private bool $tryShuffledFields = false;
    private DateTimeImmutable|null $timeoutDateTime = null;
    private Predicter $refereePlacePredicter;
    protected BatchOutput $batchOutput;
    protected PlanningOutput $planningOutput;
    protected GameOutput $gameOutput;
    protected bool $throwOnTimeout;
    protected int $debugIterations = 0;

    public function __construct(protected Planning $planning, protected LoggerInterface $logger)
    {
        $this->nrOfPoules = $this->planning->getPoules()->count();
        $this->refereePlacePredicter = new Predicter($this->planning->getPoules()->toArray());
        $this->batchOutput = new BatchOutput($logger);
        $this->planningOutput = new PlanningOutput($logger);
        $this->gameOutput = new GameOutput($logger);
        $this->throwOnTimeout = true;
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    protected function init(): void
    {
        if ($this->planning->getInput()->hasMultipleSports()) {
            $this->tryShuffledFields = true;
        }
    }

    /**
     * @return array<SportCounter>
     */
    protected function getSportCounters(): array
    {
        $sports = $this->planning->getSports()->toArray();
//        $sportConfigs = $this->getInput()->getSportConfigs();
//        $selfReferee = $this->getInput()->getSelfReferee();
//        $nrOfHeadtohead = $this->getInput()->getNrOfHeadtohead();

        $sportsNrFields = $this->convertSports($sports);
        $nrOfGamesDoneMap = [];
        foreach ($sportsNrFields as $sportNrFields) {
            $nrOfGamesDoneMap[$sportNrFields->getSportNr()] = 0;
        }

        $sportCounters = [];
        foreach ($this->planning->getPoules() as $poule) {
            throw new \Exception("TODO DEPRECATED", E_ERROR);
//            $pouleNrOfPlaces = $poule->getPlaces()->count();
//            $nrOfGamesToGo = (new GameCalculator())->getNrOfGamesPerPlace(
//                $pouleNrOfPlaces,
//                $sportConfigs,
//                false,
//                $nrOfHeadtohead
//            );
//
//            // $sportsNrFieldsGames = $sportService->getPlanningMinNrOfGames($sportsNrFields, $pouleNrOfPlaces, $teamup, $selfReferee, $nrOfHeadtohead );
//            // hier moet de $sportsNrFieldsGames puur berekent worden op basis van aantal sporten
//            $minNrOfGamesMap = $this->convertToMap($sportsNrFields/*$sportsNrFieldsGames*/);
//            /** @var Place $placeIt */
//            foreach ($poule->getPlaces() as $placeIt) {
//                $sportCounters[$placeIt->getLocation()] = new SportCounter(
//                    $nrOfGamesToGo,
//                    $minNrOfGamesMap,
//                    $nrOfGamesDoneMap
//                );
//            }
        }
        return $sportCounters;
    }

    /**
     * @param array<Sport> $sports
     * @return array<SportNrFields>
     */
    protected function convertSports(array $sports): array
    {
        return array_map(
            function (Sport $sport): SportNrFields {
                return new SportNrFields(
                    $sport->getNumber(),
                    $sport->getFields()->count(),
                    $sport->getNrOfGamePlaces()
                );
            },
            $sports
        );
    }

    /**
     * @param array<SportNrFields> $sportsNrFields
     * @return array<int,int>
     */
    protected function convertToMap(array $sportsNrFields): array
    {
        $minNrOfGamesMap = [];
        foreach ($sportsNrFields as $sportNrFields) {
            $minNrOfGamesMap[$sportNrFields->getSportNr()] = $sportNrFields->getNrOfFields();
        }
        return $minNrOfGamesMap;
    }

    /**
     * @param array<TogetherGame|AgainstGame> $games
     * @return int
     */
    public function assign(array $games): int
    {
        $this->debugIterations = 0;
        $oCurrentDateTime = new DateTimeImmutable();
        $this->timeoutDateTime = $oCurrentDateTime->modify("+" . $this->planning->getTimeoutSeconds() . " seconds");
        $this->init();
        $batch = new Batch();
        if ($this->getInput()->selfRefereeEnabled()) {
            if ($this->getInput()->getSelfReferee() === SelfReferee::SAMEPOULE) {
                $batch = new SelfRefereeSamePouleBatch($batch);
            } else {
                $batch = new SelfRefereeOtherPouleBatch($this->planning->getPoules()->toArray(), $batch);
            }
        }

        try {
            $fields = $this->planning->getFields()->toArray();

            $resources = new Resources($fields/*, $this->getSportCounters()*/);
            $assignedBatch = $this->assignBatch($games, $resources, $batch);
            if ($assignedBatch === null) {
                return Planning::STATE_FAILED;
            }
            if (!$this->getInput()->selfRefereeEnabled() && $this->getInput()->getNrOfReferees() > 0) {
                $refereeService = new RefereeService($this->planning);
                $refereeService->assign($assignedBatch->getFirst());
            }

            // $this->batchOutput->output($batch->getFirst(), ' final : iterations = ' . $this->debugIterations );
        } catch (TimeoutException $e) {
            return Planning::STATE_TIMEDOUT;
        }
        return Planning::STATE_SUCCEEDED;
    }

    /**
     * @param array<TogetherGame|AgainstGame> $games
     * @param Resources $resources
     * @param Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch
     * @return Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch|null
     * @throws TimeoutException
     */
    protected function assignBatch(array $games, Resources $resources, Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch): Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch|null
    {
        if ($this->assignBatchHelper($games, $games, $resources, $batch, $this->planning->getMaxNrOfBatchGames())) {
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

    // uasort( $games, function( Game $gameA, Game $gameB ) use ( $continueResources6 ) {
    //                $this->outputBatch->outputGames( $gameA, null, 'gameA: ' );
    //                $this->outputBatch->outputGames( $gameB, null, 'gameB: ' );
//                $nrOfSportsToGoA = $continueResources6->getGameNrOfSportsToGo($gameA);
//                $nrOfSportsToGoB = $continueResources6->getGameNrOfSportsToGo($gameB);
//                return $nrOfSportsToGoA >= $nrOfSportsToGoB ? -1 : 1;
//            });

    /**
     * @param array<TogetherGame|AgainstGame> $games
     * @param array<TogetherGame|AgainstGame> $gamesForBatch
     * @param Resources $resources
     * @param Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch
     * @param int $maxNrOfBatchGames
     * @param int $nrOfGamesTried
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatchHelper(
        array $games,
        array $gamesForBatch,
        Resources $resources,
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

            $nextBatch = $this->toNextBatch($batch, $resources, $games);

//            $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber() );

            if (count($gamesForBatch) === 0 && count($games) === 0) { // endsuccess
                return true;
            }
//            $this->logger->info(' nr of games to process before gamesinarow-filter(max '.$this->planning->getMaxNrOfGamesInARow().') : '  . count($games) );
//            $this->gameOutput->outputGames($games);

            $gamesForBatchTmp = array_filter(
                $games,
                function (TogetherGame|AgainstGame $game) use ($nextBatch): bool {
                    return $this->areAllPlacesAssignableByGamesInARow($nextBatch, $game);
                }
            );
//            $this->logger->info(' nr of games to process after gamesinarow-filter(max '.$this->planning->getMaxNrOfGamesInARow().') : '  . count($gamesForBatchTmp) );
//            $this->gameOutput->outputGames($gamesForBatchTmp);
            return $this->assignBatchHelper($games, $gamesForBatchTmp, $resources, $nextBatch, $this->planning->getMaxNrOfBatchGames(), 0);
        }
        if ($this->throwOnTimeout && (new DateTimeImmutable()) > $this->timeoutDateTime) { // @FREDDY
            throw new TimeoutException(
                "exceeded maximum duration of " . $this->planning->getTimeoutSeconds() . " seconds",
                E_ERROR
            );
        }
        if ($nrOfGamesTried === count($gamesForBatch)) {
            return false;
        }
        $game = array_shift($gamesForBatch);
        if ($this->isGameAssignable($batch, $game, $resources)) {
            $resourcesAssign = $resources->copy();
            $this->assignGame($batch, $game, $resourcesAssign);
            $gamesForBatchTmp = array_filter(
                $gamesForBatch,
                function (TogetherGame|AgainstGame $game) use ($batch): bool {
                    return $this->areAllPlacesAssignable($batch, $game);
                }
            );
            if ($this->assignBatchHelper($games, $gamesForBatchTmp, $resourcesAssign, $batch, $maxNrOfBatchGames)) {
                return true;
            }
            $this->releaseGame($batch, $game);
        }
        $gamesForBatch[] = $game;
        if ($this->assignBatchHelper(
            $games,
            $gamesForBatch,
            $resources->copy(),
            $batch,
            $maxNrOfBatchGames,
            ++$nrOfGamesTried
        )) {
            return true;
        }

//        $resourcesSwitchFields = $resources->copy();
//        while ($resourcesSwitchFields->switchFields()) {
//            if ($this->assignBatchHelper($games, $gamesForBatch, $resourcesSwitchFields, $batch, $maxNrOfBatchGames)) {
//                return true;
//            }
//        }

        if ($maxNrOfBatchGames === $this->planning->getMaxNrOfBatchGames() && $this->planning->getNrOfBatchGames(
            )->difference() > 0) {
            if ($this->assignBatchHelper($games, $gamesForBatch, $resources->copy(), $batch, $maxNrOfBatchGames - 1)) {
                return true;
            }
        }
        return false;
    }

    protected function assignGame(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch, TogetherGame|AgainstGame $game, Resources $resources): void
    {
        $this->assignField($game, $resources);
        $batch->add($game);
        $resources->assignSport($game);
    }

    protected function releaseGame(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch, TogetherGame|AgainstGame $game): void
    {
        $batch->remove($game);
        // $this->releaseSport($game, $game->getField()->getSport());
        // $this->releaseField($game);
    }

    /**
     * @param Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch
     * @param Resources $resources
     * @param array<TogetherGame|AgainstGame> $games
     * @return Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch
     */
    protected function toNextBatch(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch, Resources $resources, array &$games): Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch
    {
        foreach ($batch->getGames() as $game) {
            $game->setBatchNr($batch->getNumber());
            // hier alle velden toevoegen die er nog niet in staan
            $field = $game->getField();
            if ($field !== null && array_search($field, $resources->getFields(), true) === false) {
                $resources->addField($field);
            }
            $foundGameIndex = array_search($game, $games, true);
            if ($foundGameIndex !== false) {
                unset($games[$foundGameIndex]);
            }
        }
        return $batch->createNext();
    }

    private function isGameAssignable(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch, TogetherGame|AgainstGame $game, Resources $resources): bool
    {
        if (!$this->isSomeFieldAssignable($game, $resources)) {
            return false;
        }
        return $this->areAllPlacesAssignable($batch, $game);
    }

    // de wedstrijd is assignbaar als
    // 1 alle plekken, van een wedstrijd, nog niet in de batch
    // 2 alle plekken, van een wedstrijd, de sport nog niet vaak genoeg gedaan heeft of alle sporten al gedaan
    private function areAllPlacesAssignable(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch, TogetherGame|AgainstGame $game, bool $checkGamesInARow = true): bool
    {
        $maxNrOfGamesInARow = $this->getInput()->getMaxNrOfGamesInARow();
        foreach ($game->getPoulePlaces() as $place) {
            if ($batch->isParticipating($place)) {
                return false;
            }
            $previousBatch = $batch->getPrevious();
            if ($previousBatch === null) {
                continue;
            }
            $nrOfGamesInARow = $previousBatch->getGamesInARow($place);
            if ($nrOfGamesInARow < $maxNrOfGamesInARow || $maxNrOfGamesInARow === -1) {
                continue;
            }
            return false;
        }
        return true;

//        $nrOfPlacesNotInBatch = 0; @FREDDY
//        foreach( $this->getPlaces($game) as $place ) {
//            if (!$batch->hasPlace($place)) {
//                $nrOfPlacesNotInBatch++;
//            }
//        }
//        $enoughPlacesFree = ( ($batch->getNrOfPlaces() + $nrOfPlacesNotInBatch) <= 4 );
//
//        foreach( $this->getPlaces($game) as $place ) {
//            if( !$batch->hasPlace($place) && !$enoughPlacesFree ) {
//                return false;
//            }
//            if( $batch->getNrOfGames($place) === 3 ) {
//                return false;
//            }
//        }
//        return true;
    }

    private function areAllPlacesAssignableByGamesInARow(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch, TogetherGame|AgainstGame $game): bool
    {
        foreach ($game->getPoulePlaces() as $place) {
            $previousBatch = $batch->getPrevious();
            if ($previousBatch === null) {
                continue;
            }
            $nrOfGamesInARow = $previousBatch->getGamesInARow($place);
            $nrOfGamesInARow += 1;
            if ($this->planning->getMaxNrOfGamesInARow() > 0 && $nrOfGamesInARow > $this->planning->getMaxNrOfGamesInARow()) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param TogetherGame|AgainstGame $game
     * @param Resources $resources
     * @return bool
     */
    private function isSomeFieldAssignable($game, Resources $resources): bool
    {
        foreach ($resources->getFields() as $fieldIt) {
            if ($resources->isSportAssignable($game, $fieldIt->getSport())) {
                return true;
            }
        }
        return false;
    }

    private function releaseField(TogetherGame|AgainstGame $game/*, Resources $resources*/): void
    {
//        if ($resources->getFieldIndex() !== null) {
//            $fieldIndex = array_search($game->getField(), $resources->getFields() );
//            if ($fieldIndex === false) {
//                $resources->unshiftField( $game->getField() );
//            }
//            $resources->resetFieldIndex();
//        }
        $game->emptyField();
    }

    private function assignField(TogetherGame|AgainstGame $game, Resources $resources): void
    {
        $fields = array_filter(
            $resources->getFields(),
            function ($fieldIt) use ($game, $resources): bool {
                return $resources->isSportAssignable($game, $fieldIt->getSport());
            }
        );
        if (count($fields) >= 1) {
            $field = reset($fields);
            $fieldIndex = array_search($field, $resources->getFields(), true);
            if ($fieldIndex !== false) {
                $removedField = $resources->removeField($fieldIndex);
                $resources->setFieldIndex($fieldIndex);
                $game->setField($removedField);
            }
        }
    }

    protected function refereePlacesCanBeAssigned(Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch $batch): bool
    {
        // naast forced refereeplaces and teveel

        if ($batch instanceof Batch\SelfReferee) {
            return $this->refereePlacePredicter->canStillAssign($batch, $this->getInput()->getSelfReferee());
        }
        return true;
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
