<?php

declare(strict_types=1);

namespace SportsPlanning\GameGenerator\GameMode;

use DateTimeImmutable;
use Exception;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\GameGenerator\AgainstHomeAway;
use SportsPlanning\GameGenerator\Partial;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\TimeoutException;

class AgainstMixed extends Against
{
    private DateTimeImmutable|null $timeoutDateTime = null;
    protected bool $throwOnTimeout;

    public function __construct(Planning $planning)
    {
        parent::__construct($planning);
        $this->throwOnTimeout = true;
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @return int
     */
    public function generate(Poule $poule, array $sports): int
    {
        $oCurrentDateTime = new DateTimeImmutable();
        $this->timeoutDateTime = $oCurrentDateTime->modify("+" . $this->planning->getTimeoutSeconds() . " seconds");

        foreach ($sports as $sport) {
            $this->defaultField = $sport->getField(1);
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof AgainstSportVariant)) {
                throw new Exception('only against-sport-variant accepted', E_ERROR);
            }
            if (!$sportVariant->isMixed()) {
                continue;
            }
            $homeAways = $this->generateForMixedSportVariant($poule, $sportVariant);
            if (count($homeAways) === 0) {
                return Planning::STATE_FAILED;
            }

            $nrOfPartialsPerH2H = $sportVariant->getNrOfPartialsPerH2H($poule->getPlaces()->count());
            $nrOfH2H = 1;
            $nrOfPartialsToDo = $sportVariant->getNrOfPartials();
            while ( $nrOfPartialsToDo > 0 ) {
                $nrOfPartials = $nrOfPartialsToDo > $nrOfPartialsPerH2H ? $nrOfPartialsPerH2H : $nrOfPartialsToDo;
                $homeAwaysToConvert = array_slice($homeAways, 0, $nrOfPartials);
                $this->toGames($poule, $homeAwaysToConvert, $nrOfH2H++);
                $nrOfPartialsToDo -= $nrOfPartials;
            }
            // if partials exceed nrofhead2head than copy partials for other h2h
            //$this->toGames($poule, $homeAways);
            // voor mixed wil je niet voor elke h2h erdoor
            // bij mixed heeft het een andere betekenis en is de h2h altijd 1
            // en is het nrOfPartials
        }
        return Planning::STATE_SUCCEEDED;
    }

    /**
     * @param Poule $poule
     * @param AgainstSportVariant $sportVariant
     * @return list<AgainstHomeAway>
     */
    public function generateForMixedSportVariant(
        Poule $poule,
        AgainstSportVariant $sportVariant
    ): array {
        $partials = [];

        $maxNrOfPartials = $sportVariant->getNrOfPartials();
        $nrOfPartialsOneH2H = $sportVariant->getNrOfPartialsPerH2H($poule->getPlaces()->count());
        if ($maxNrOfPartials > $nrOfPartialsOneH2H) {
            $maxNrOfPartials = $nrOfPartialsOneH2H;
        }

        $maxNrOfGamesPerPartial = $sportVariant->getNrOfGamesPerPartial($poule->getPlaces()->count());
        $homeAways = $this->generateHomeAways($poule, $sportVariant);
        $succeededPartials = $this->generatePartials($partials, $homeAways, $maxNrOfPartials, $maxNrOfGamesPerPartial);

        $homeAwaysRet = [];
        foreach ($succeededPartials as $succeededPartial) {
            $homeAwaysRet = array_merge($homeAwaysRet, $succeededPartial->getHomeAways());
        }
        // echo $this->outputHomeAways($homeAwaysRet, 'SUCCEEDED');
        return array_values($homeAwaysRet);
    }


    /**
     * @param list<Partial> $partials
     * @param list<AgainstHomeAway> $homeAways
     * @param int $maxNrOfPartials
     * @param int $maxNrOfGamesPerPartial
     * @return list<Partial>
     */
    public function generatePartials(
        array $partials,
        array $homeAways,
        int $maxNrOfPartials,
        int $maxNrOfGamesPerPartial
    ): array {
        $partial = end($partials);
        if (count($partials) === $maxNrOfPartials && $partial !== false && $partial->isComplete()) {
            return $partials;
        }
        if ($partial === false || $partial->isComplete()) {
            $partial = new Partial($maxNrOfGamesPerPartial);
            array_push($partials, $partial);
        }
        if ($this->throwOnTimeout && (new DateTimeImmutable()) > $this->timeoutDateTime) {
            throw new TimeoutException(
                "exceeded maximum duration of " . $this->planning->getTimeoutSeconds() . " seconds",
                E_ERROR
            );
        }
        //echo $this->outputHomeAways($homeAways, 'BEFORE POPPING');
        while ($homeAway = array_pop($homeAways)) {
            if ($partial->canBeAdded($homeAway)) {
                $partial->add($homeAway);
                $succeededPartials = $this->generatePartials($partials, $homeAways, $maxNrOfPartials, $maxNrOfGamesPerPartial);
                if (count($succeededPartials) > 0) {
                    return $succeededPartials;
                }
            }
        }
        return [];
        // gebruik $homeAways om de juiste partials te maken
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
