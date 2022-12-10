<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\Indirect\Map as IndirectMap;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;

class StatisticsCalculator
{
    protected int $withShortage = 0;
    protected int $againstShortage = 0;

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param array<int, PlaceCounter> $assignedSportMap
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithMap
     * @param IndirectMap $assignedAgainstMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     */
    public function __construct(
        protected VariantWithPoule $variantWithPoule,
        protected array $assignedSportMap,
        protected array $assignedMap,
        protected array $assignedWithMap,
        protected IndirectMap $assignedAgainstMap,
        protected array $assignedHomeMap
    )
    {
        $this->initAgainstShortage();
        $this->initWithShortage();
    }

    private function initAgainstShortage(): void {
        $counters = $this->assignedAgainstMap->getCopiedCounters();
        $minAgainstAmount = $this->getMinAgainstSinglePlace($this->variantWithPoule);
        $nrOfOpponents = count($this->variantWithPoule->getPoule()->getPlaces()) - 1;
        foreach( $this->variantWithPoule->getPoule()->getPlaces() as $place ) {
            if( !array_key_exists($place->getNumber(), $counters) ) {
                $this->againstShortage += $minAgainstAmount * $nrOfOpponents;
                continue;
            }
            $placeCounter = $counters[$place->getNumber()];

            foreach( $this->variantWithPoule->getPoule()->getPlaces() as $againstPlace ) {
                if( $place === $againstPlace) {
                    continue;
                }
                $nrOfAgainst = $placeCounter->count($againstPlace);

                if( $nrOfAgainst < $minAgainstAmount ) {
                    $this->againstShortage += $minAgainstAmount - $nrOfAgainst;
                }
            }
        }
    }

    private function initWithShortage(): void {
        $minWithAmount = $this->getMinWithAmount($this->variantWithPoule);
        foreach( $this->assignedWithMap as $placeCombinationCounter ) {
            if( $placeCombinationCounter->count() < $minWithAmount ) {
                $this->withShortage += $minWithAmount - $placeCombinationCounter->count();
            }
        }
    }

    public function addHomeAway(AgainstHomeAway $homeAway): self
    {
        $assignedSportMap = $this->assignedSportMap;
        $assignedMap = $this->assignedMap;
        foreach ($homeAway->getPlaces() as $place) {
            $assignedSportMap[$place->getNumber()]->increment();
            $assignedMap[$place->getNumber()]->increment();
        }

        $assignedWithMap = $this->assignedWithMap;
        $assignedWithMap[$homeAway->getHome()->getNumber()]->increment();
        $assignedWithMap[$homeAway->getAway()->getNumber()]->increment();

        $assignedHomeMap = $this->assignedHomeMap;
        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            $assignedHomeMap[$homePlace->getNumber()]->increment();
        }

        return new self(
            $this->variantWithPoule,
            $assignedSportMap,
            $assignedMap,
            $assignedWithMap,
            $this->assignedAgainstMap->addHomeAway($homeAway),
            $assignedHomeMap
        );
    }

    public function sportWillBeOverAssigned(Place $place): bool
    {
        $i = (memory_get_usage() / (1024*1024)) . 'MB';
        $totalNrOfGamesPerPlace = $this->variantWithPoule->getTotalNrOfGamesPerPlace();
        $sportVariant = $this->variantWithPoule->getSportVariant();
        $nrOfPlaces = $this->variantWithPoule->getNrOfPlaces();
        $notAllPlacesPlaySameNrOfGames = $sportVariant instanceof AgainstGpp
            && !$sportVariant->allPlacesPlaySameNrOfGames($nrOfPlaces);
        $totalNrOfGamesPerPlace = $totalNrOfGamesPerPlace + ($notAllPlacesPlaySameNrOfGames ? 1 : 0);
        if( $this->assignedSportMap[$place->getNumber()]->count() > $totalNrOfGamesPerPlace ) {
            $j = (memory_get_usage() / (1024*1024)) . 'MB';
            return true;
        }
        $k = (memory_get_usage() / (1024*1024)) . 'MB';
        return false;
    }

    public function minimalWithCanStillBeAssigned(AgainstGameRound $gameRound, AgainstHomeAway $homeAway): bool {

        $nrOfGamesToGo = $this->variantWithPoule->getTotalNrOfGames() - $gameRound->getNrOfHomeAwaysRecursive();

        $maxHomeAwayWiths = 2;
        if( ($this->withShortage - $maxHomeAwayWiths) <= ($nrOfGamesToGo * 2) ) {
            return true;
        }
        $withShortageIncludingHomeAway = $this->getWithShortageIncludingHomeAway($homeAway);
        return $withShortageIncludingHomeAway <= ($nrOfGamesToGo * 2);
    }

    private function getWithShortageIncludingHomeAway(AgainstHomeAway $homeAway): int {
        $minWithAmount = $this->getMinWithAmount($this->variantWithPoule);
        $withShortage = $this->withShortage;

        $homeAmount = 0;
        if( array_key_exists($homeAway->getHome()->getNumber(), $this->assignedWithMap)) {
            $homeAmount = $this->assignedWithMap[$homeAway->getHome()->getNumber()];
        }
        if( $homeAmount < $minWithAmount ) {
            $withShortage--;
        }

        $awayAmount = 0;
        if( array_key_exists($homeAway->getAway()->getNumber(), $this->assignedWithMap)) {
            $awayAmount = $this->assignedWithMap[$homeAway->getAway()->getNumber()];
        }
        if( $awayAmount < $minWithAmount ) {
            $withShortage--;
        }
        return $withShortage;
    }

    public function minimalAgainstCanStillBeAssigned(AgainstGameRound $gameRound, AgainstHomeAway $homeAway): bool {

        $nrOfCombinationsPerGame = $this->getSportVariant($this->variantWithPoule)->getNrOfHomeAwayCombinations() * 2;
        // 8
        $nrOfGamesToGo = $this->variantWithPoule->getTotalNrOfGames() - $gameRound->getNrOfHomeAwaysRecursive();

        if( ($this->againstShortage - $nrOfCombinationsPerGame) <= ($nrOfGamesToGo * $nrOfCombinationsPerGame) ) {
            return true;
        }
        $againstShortageIncludingHomeAway = $this->getAgainstShortageIncludingHomeAway($homeAway);
        return $againstShortageIncludingHomeAway <= ($nrOfGamesToGo * $nrOfCombinationsPerGame);
    }

    private function getAgainstShortageIncludingHomeAway(AgainstHomeAway $homeAway): int {
        $minAgainstAmount = $this->getMinAgainstSinglePlace($this->variantWithPoule);
        $placeAgainstCounters = $this->assignedAgainstMap->getCopiedCounters();
        $againstShortage = $this->againstShortage;

        foreach( [Side::Home,Side::Away] as $side ) {
            $placeCombination = $homeAway->get($side);

            foreach( $placeCombination->getPlaces() as $place ) {
                if( !array_key_exists($place->getNumber(), $placeAgainstCounters ) ) {
                    $againstShortage -= 2;
                    continue;
                }
                $opponentCounters = $placeAgainstCounters[$place->getNumber()]->getCopiedPlaceCounters();

                $opponents = $homeAway->get($side->getOpposite());

                foreach( $opponents->getPlaces() as $opponent ) {
                    if( !array_key_exists($opponent->getNumber(), $opponentCounters ) ) {
                        $againstShortage --;
                        continue;
                    }
                    $amount = $opponentCounters[$opponent->getNumber()]->count();
                    if( $amount < $minAgainstAmount ) {
                        $againstShortage--;
                    }
                }
            }
        }
        return $againstShortage;
    }

    public function withWillBeOverAssigned(AgainstHomeAway $homeAway): bool
    {
        $homeWithAmount = $this->assignedWithMap[$homeAway->getHome()->getNumber()]->count();
        $awayWithAmount = $this->assignedWithMap[$homeAway->getAway()->getNumber()]->count();

        $maxWithAmount = $this->getMaxWithAmount($this->variantWithPoule);
        return ($homeWithAmount + 1) > $maxWithAmount || ($awayWithAmount + 1) > $maxWithAmount;
    }

    public function againstWillBeOverAssigned(AgainstHomeAway $homeAway): bool
    {
        $counters = $this->assignedAgainstMap->getCopiedCounters();
        $maxAgainstAmount = $this->getMaxAgainstSinglePlace($this->variantWithPoule);
        foreach( $homeAway->getHome()->getPlaces() as $homePlace ) {
            if( empty($counters[$homePlace->getNumber()]) ) {
                continue;
            }
            $homePlaceCounter = $counters[$homePlace->getNumber()];
            foreach( $homeAway->getAway()->getPlaces() as $awayPlace ) {
                $amount = $homePlaceCounter->count($awayPlace);
                if( ($amount + 1) > $maxAgainstAmount ) {
                    return true;
                    // continue;
                }
            }
        }
        return false;

    }

    private function minimalWithIsAssigned(): bool {
        $minWithAmount = $this->getMinWithAmount($this->variantWithPoule);

        foreach( $this->assignedWithMap as $placeCombinationCounter ) {
            if( $placeCombinationCounter->count() < $minWithAmount ) {
                return false;
            }
        }
        return true;
    }

    public function allAssigned(): bool
    {
        $nrOfIncompletePlaces = 0;
        foreach ($this->assignedMap as $assignedCounter) {
            if ($assignedCounter->count() < $this->variantWithPoule->getTotalNrOfGamesPerPlace()) {
                $nrOfIncompletePlaces++;
            }

            if ($nrOfIncompletePlaces >= $this->variantWithPoule->getNrOfGamePlaces()) {
                return false;
            }
        }
        return $this->minimalWithIsAssigned();
    }

    protected function getMaxWithAmount(VariantWithPoule $variantWithPoule): int {
        return (int)ceil($this->getWithAmount($variantWithPoule));
    }

    protected function getMinWithAmount(VariantWithPoule $variantWithPoule): int {
        return (int)floor($this->getWithAmount($variantWithPoule));
    }

    private function getWithAmount(VariantWithPoule $variantWithPoule): float {
        $sportVariant = $this->getSportVariant($variantWithPoule);
        $maxNrOfSidePlaces = max( $sportVariant->getNrOfHomePlaces(), $sportVariant->getNrOfAwayPlaces() );
        return $sportVariant->getNrOfGamesPerPlace() / ( ($variantWithPoule->getNrOfPlaces() - 1) * ($maxNrOfSidePlaces - 1) );
    }

    protected function getMinAgainstSinglePlace(VariantWithPoule $variantWithPoule): int {
        return (int)floor($this->getAgainstSinglePlace($variantWithPoule));
    }

    protected function getMaxAgainstSinglePlace(VariantWithPoule $variantWithPoule): int {
        return (int)ceil($this->getAgainstSinglePlace($variantWithPoule));
    }

    private function getAgainstSinglePlace(VariantWithPoule $variantWithPoule): float {
        $sportVariant = $this->getSportVariant($variantWithPoule);
        $maxNrOfSidePlaces = max( $sportVariant->getNrOfHomePlaces(), $sportVariant->getNrOfAwayPlaces() );
        return ($sportVariant->getNrOfGamesPerPlace() * $maxNrOfSidePlaces) / ($variantWithPoule->getNrOfPlaces() - 1);
    }

    protected function getSportVariant(VariantWithPoule $variantWithPoule): AgainstGpp {
        $sportVariant = $variantWithPoule->getSportVariant();
        if( !($sportVariant instanceof AgainstGpp) ) {
            throw new \Exception('incorrect sportvariant', E_ERROR );
        }
        return $sportVariant;
    }

    public function output(LoggerInterface $logger, bool $againstTotals, bool $withTotals): void {
        if( $againstTotals ) {
            $this->outputAgainstTotals($logger);
        }
        if( $withTotals ) {
            $this->outputWithTotals($logger);
        }
    }

    public function outputAgainstTotals(LoggerInterface $logger): void {
        foreach( $this->variantWithPoule->getPoule()->getPlaces() as $place ) {
            $this->outputAgainstPlaceTotals($logger, $place);
        }
    }

    private function outputAgainstPlaceTotals(LoggerInterface $logger, Place $place): void {
        $placeNr = $place->getNumber() < 10 ? '0' . $place->getNumber() : $place->getNumber();
        $out = '    ' . $placeNr . " => ";
        foreach( $this->variantWithPoule->getPoule()->getPlaces() as $opponent ) {
            $opponentNr = $opponent->getNumber() < 10 ? '0' . $opponent->getNumber() : $opponent->getNumber();
            $out .= '' . $opponentNr . ':' . $this->getAmount($place, $opponent) . ',';
        }
        $logger->info($out);
    }

    private function getAmount(Place $place, Place $opponent): string {
        if( $place === $opponent) {
            return '  ';
        }
        $placeCounters = $this->assignedAgainstMap->getCopiedCounters();
        if( !array_key_exists($place->getNumber(), $placeCounters)) {
            return '0x';
        }
        $opponentCounters = $placeCounters[$place->getNumber()]->getCopiedPlaceCounters();
        if( !array_key_exists($opponent->getNumber(), $opponentCounters)) {
            return '0x';
        }
        return $opponentCounters[$opponent->getNumber()]->count() . 'x';

    }

    public function outputWithTotals(LoggerInterface $logger): void {

    }


}
