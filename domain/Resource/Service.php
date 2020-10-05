<?php

namespace SportsPlanning\Resource;

use DateTimeImmutable;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

use SportsHelpers\GameCalculator;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Planning;
use SportsPlanning\Game;
use SportsPlanning\Input as PlanningInput;
use SportsPlanning\Place;
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
    /**
     * @var Planning
     */
    private $planning;
    /**
     * @var int
     */
    private $nrOfPoules;
    /**
     * @var int
     */
    private $nrOfSports;
    /**
     * @var bool
     */
    private $tryShuffledFields = false;
    /**
     * @var DateTimeImmutable
     */
    private $timeoutDateTime;
    /**
     * @var Predicter
     */
    private $refereePlacePredicter;

    protected LoggerInterface $logger;
    protected BatchOutput $batchOutput;
    protected PlanningOutput $planningOutput;
    protected GameOutput $gameOutput;

    protected $debugIterations;

    public function __construct(Planning $planning, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->planning = $planning;
        $this->nrOfPoules = $this->planning->getPoules()->count();
        $this->refereePlacePredicter = new Predicter($this->planning->getPoules()->toArray());
        $this->batchOutput = new BatchOutput($logger);
        $this->planningOutput = new PlanningOutput($logger);
        $this->gameOutput = new GameOutput($logger);
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    protected function init()
    {
        if ($this->planning->getInput()->hasMultipleSports()) {
            $this->tryShuffledFields = true;
        }
    }

    /**
     * @return array|SportCounter[]
     */
    protected function getSportCounters(): array
    {
        $sports = $this->planning->getSports()->toArray();
        $teamup = $this->getInput()->getTeamup();
        $selfReferee = $this->getInput()->getSelfReferee();
        $nrOfHeadtohead = $this->getInput()->getNrOfHeadtohead();

        $sportsNrFields = $this->convertSports($sports);
        $nrOfGamesDoneMap = [];
        foreach ($sportsNrFields as $sportNrFields) {
            $nrOfGamesDoneMap[$sportNrFields->getSportNr()] = 0;
        }

        $sportCounters = [];
        foreach ($this->planning->getPoules() as $poule) {
            $pouleNrOfPlaces = $poule->getPlaces()->count();
            $nrOfGamesToGo = (new GameCalculator())->getNrOfGamesPerPlace(
                $pouleNrOfPlaces,
                $teamup,
                false,
                $nrOfHeadtohead
            );

            // $sportsNrFieldsGames = $sportService->getPlanningMinNrOfGames($sportsNrFields, $pouleNrOfPlaces, $teamup, $selfReferee, $nrOfHeadtohead );
            // hier moet de $sportsNrFieldsGames puur berekent worden op basis van aantal sporten
            $minNrOfGamesMap = $this->convertToMap($sportsNrFields/*$sportsNrFieldsGames*/);
            /** @var Place $placeIt */
            foreach ($poule->getPlaces() as $placeIt) {
                $sportCounters[$placeIt->getLocation()] = new SportCounter(
                    $nrOfGamesToGo,
                    $minNrOfGamesMap,
                    $nrOfGamesDoneMap
                );
            }
        }
        return $sportCounters;
    }

    /**
     * @param array $sports |Sport[]
     * @return array|SportNrFields[]
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
     * @param array|SportNrFields[] $sportsNrFields
     * @return array
     */
    protected function convertToMap(array $sportsNrFields): array
    {
        $minNrOfGamesMap = [];
        /** @var SportNrFields $sportNrFields */
        foreach ($sportsNrFields as $sportNrFields) {
            $minNrOfGamesMap[$sportNrFields->getSportNr()] = $sportNrFields->getNrOfFields();
        }
        return $minNrOfGamesMap;
    }

    public function assign(array $games): int
    {
        $this->debugIterations = 0;
        $oCurrentDateTime = new DateTimeImmutable();
        $this->timeoutDateTime = $oCurrentDateTime->modify("+" . $this->planning->getTimeoutSeconds() . " seconds");
        $this->init();
        $batch = new Batch();
        if( $this->getInput()->selfRefereeEnabled() ) {
            if( $this->getInput()->getSelfReferee() === PlanningInput::SELFREFEREE_SAMEPOULE) {
                $batch = new SelfRefereeSamePouleBatch( $batch );
            } else {
                $batch = new SelfRefereeOtherPouleBatch( $this->planning->getPoules()->toArray(), $batch );
            }
        }

        try {
            $fields = $this->planning->getFields()->toArray();

            $resources = new Resources($fields/*, $this->getSportCounters()*/);
            $batch = $this->assignBatch($games, $resources, $batch);
            if ($batch === null) {
                return Planning::STATE_FAILED;
            }
            if (!$this->getInput()->selfRefereeEnabled() && $this->getInput()->getNrOfReferees() > 0 ) {
                $refereeService = new RefereeService($this->planning);
                $refereeService->assign($batch->getFirst());
            }

           // $this->batchOutput->output($batch->getFirst(), ' final : iterations = ' . $this->debugIterations );


        } catch (TimeoutException $e) {
            return Planning::STATE_TIMEDOUT;
        }
        return Planning::STATE_SUCCEEDED;
    }

    /**
     * @param array $games
     * @param Resources $resources
     * @param Batch|SelfRefereeBatch $batch
     * @return Batch|SelfRefereeBatch|null
     * @throws TimeoutException
     */
    protected function assignBatch(array $games, Resources $resources, $batch)
    {
        if ($this->assignBatchHelper($games, $games, $resources, $batch, $this->planning->getMaxNrOfBatchGames())) {
            return $this->getActiveLeaf($batch->getLeaf());
        }
        return null;
    }

    /**
     * @param Batch|SelfRefereeBatch $batch
     * @return Batch|SelfRefereeBatch
     */
    protected function getActiveLeaf($batch)
    {
        if ($batch->hasPrevious() === false) {
            return $batch;
        }
        if (count($batch->getPrevious()->getGames()) === $this->planning->getMaxNrOfBatchGames()) {
            return $batch;
        }
        return $this->getActiveLeaf($batch->getPrevious());
    }

    // uasort( $games, function( Game $gameA, Game $gameB ) use ( $continueResources6 ) {
    //                $this->outputBatch->outputGames( $gameA, null, 'gameA: ' );
    //                $this->outputBatch->outputGames( $gameB, null, 'gameB: ' );
//                $nrOfSportsToGoA = $continueResources6->getGameNrOfSportsToGo($gameA);
//                $nrOfSportsToGoB = $continueResources6->getGameNrOfSportsToGo($gameB);
//                return $nrOfSportsToGoA >= $nrOfSportsToGoB ? -1 : 1;
//            });

    /**
     * @param array $games
     * @param Resources $resources
     * @param Batch|SelfRefereeBatch $batch
     * @param int $nrOfGamesTried
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatchHelper(
        array $games,
        array $gamesForBatch,
        Resources $resources,
        $batch,
        int $maxNrOfBatchGames,
        int $nrOfGamesTried = 0
    ): bool
    {
        if ((count($batch->getGames()) === $maxNrOfBatchGames
                || (count($gamesForBatch) === 0) && count($games) === count($batch->getGames()))
        ) { // batchsuccess
            if( !$this->refereePlacesCanBeAssigned($batch)) {
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
                function (Game $game) use ($nextBatch): bool {
                    return $this->areAllPlacesAssignableByGamesInARow($nextBatch, $game);
                }
            );
//            $this->logger->info(' nr of games to process after gamesinarow-filter(max '.$this->planning->getMaxNrOfGamesInARow().') : '  . count($gamesForBatchTmp) );
//            $this->gameOutput->outputGames($gamesForBatchTmp);
            return $this->assignBatchHelper($games, $gamesForBatchTmp, $resources, $nextBatch, $this->planning->getMaxNrOfBatchGames(), 0);
        }
        if ((new DateTimeImmutable()) > $this->timeoutDateTime) { // @FREDDY
            throw new TimeoutException(
                "exceeded maximum duration of " . $this->planning->getTimeoutSeconds() . " seconds", E_ERROR
            );
        }
        if ($nrOfGamesTried === count($gamesForBatch)) {
            return false;
        }
//        $this->debugIterations++;
//        echo "iteration " . $this->debugIterations . " (27489) (".$this->convert(memory_get_usage(true))." / ".ini_get('memory_limit').")" . PHP_EOL;

        $game = array_shift($gamesForBatch);
        if ($this->isGameAssignable($batch, $game, $resources)) {
            $resourcesAssign = $resources->copy();
            $this->assignGame($batch, $game, $resourcesAssign);
            $gamesForBatchTmp = array_filter(
                $gamesForBatch,
                function (Game $game) use ($batch): bool {
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

    protected function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * @param Batch|SelfRefereeBatch $batch
     * @param Game $game
     * @param Resources $resources
     */
    protected function assignGame($batch, Game $game, Resources $resources)
    {
        $this->assignField($game, $resources);
        $batch->add($game);
        $resources->assignSport($game, $game->getField()->getSport());
    }

    /**
     * @param Batch|SelfRefereeBatch $batch
     * @param Game $game
     */
    protected function releaseGame($batch, Game $game)
    {
        $batch->remove($game);
        // $this->releaseSport($game, $game->getField()->getSport());
        // $this->releaseField($game);
    }

    /**
     * @param Batch|SelfRefereeBatch $batch
     * @param Resources $resources
     * @return Batch|SelfRefereeBatch
     */
    protected function toNextBatch($batch, Resources $resources, array &$games)
    {
        foreach ($batch->getGames() as $game) {
            $game->setBatchNr($batch->getNumber());
            // hier alle velden toevoegen die er nog niet in staan
            if (array_search($game->getField(), $resources->getFields(), true) === false) {
                $resources->addField($game->getField());
            }
            $foundGameIndex = array_search($game, $games, true);
            if ($foundGameIndex !== false) {
                unset($games[$foundGameIndex]);
            }
        }
        $nextBatch = $batch->createNext();

        return $nextBatch;
    }

    /**
     * @param Batch|SelfRefereeBatch $batch
     * @param Game $game
     * @param Resources $resources
     * @return bool
     */
    private function isGameAssignable($batch, Game $game, Resources $resources): bool
    {
        if (!$this->isSomeFieldAssignable($game, $resources)) {
            return false;
        }
        return $this->areAllPlacesAssignable($batch, $game);
    }

    /**
     * de wedstrijd is assignbaar als
     * 1 alle plekken, van een wedstrijd, nog niet in de batch
     * 2 alle plekken, van een wedstrijd, de sport nog niet vaak genoeg gedaan heeft of alle sporten al gedaan
     *
     * @param Batch|SelfRefereeBatch $batch
     * @param Game $game
     * @return bool
     */
    private function areAllPlacesAssignable($batch, Game $game, bool $checkGamesInARow = true): bool
    {
        $maxNrOfGamesInARow = $this->getInput()->getMaxNrOfGamesInARow();
        foreach ($game->getPoulePlaces() as $place) {
            if ($batch->isParticipating($place)) {
                return false;
            }
            $nrOfGamesInARow = $batch->hasPrevious() ? ($batch->getPrevious()->getGamesInARow($place)) : 0;
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

    /**
     * @param Batch|SelfRefereeBatch $batch
     * @param Game $game
     * @return bool
     */
    private function areAllPlacesAssignableByGamesInARow($batch, Game $game): bool
    {
        foreach ($game->getPoulePlaces() as $place) {
            $nrOfGamesInARow = ($batch->hasPrevious() ? ($batch->getPrevious()->getGamesInARow($place)) : 0) + 1;
            if ($this->planning->getMaxNrOfGamesInARow() > 0 && $nrOfGamesInARow > $this->planning->getMaxNrOfGamesInARow()) {
                return false;
            }
        }
        return true;
    }

    private function isSomeFieldAssignable(Game $game, Resources $resources): bool
    {
        foreach ($resources->getFields() as $fieldIt) {
            if ($resources->isSportAssignable($game, $fieldIt->getSport())) {
                return true;
            }
        }
        return false;
    }

    private function releaseField(Game $game/*, Resources $resources*/)
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

    private function assignField(Game $game, Resources $resources)
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
            $removedField = $resources->removeField($fieldIndex);
            $resources->setFieldIndex($fieldIndex);
            $game->setField($removedField);
        }
    }

    /**
     * @param Batch|SelfRefereeBatch $batch
     * @return bool
     */
    protected function refereePlacesCanBeAssigned($batch): bool
    {
        // naast forced refereeplaces and teveel

        if( $batch instanceof Batch\SelfReferee ) {
            return $this->refereePlacePredicter->canStillAssign($batch, $this->getInput()->getSelfReferee());
        }
        return true;
    }
}
