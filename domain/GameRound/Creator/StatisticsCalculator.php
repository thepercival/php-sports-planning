<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as GppHomeAwayCreator;
use SportsPlanning\Combinations\Indirect\Map as IndirectMap;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsPlanning\GameRound\Against as AgainstGameRound;
use SportsHelpers\Sport\Variant\Against;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;

class StatisticsCalculator
{
    protected int $withShortage = 0;
    protected int $againstShortage = 0;
    protected int $amountOverAgainstAmountForAllPlaces = 0;
    protected bool $useWith;

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param array<int, PlaceCounter> $assignedSportMap
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithSportMap
     * @param IndirectMap $assignedAgainstSportMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     * @param int $margin
     * @param int $nrOfHomeAwaysAsigned
     */
    public function __construct(
        protected VariantWithPoule $variantWithPoule,
        protected array $assignedSportMap,
        protected array $assignedMap,
        protected array $assignedWithSportMap,
        protected IndirectMap $assignedAgainstSportMap,
        protected array $assignedHomeMap,
        private int $margin,
        protected int $nrOfHomeAwaysAsigned = 0
    )
    {
        $this->initAgainstSportShortage();
        $sporVariant = $this->variantWithPoule->getSportVariant();
        if( $sporVariant instanceof AgainstGpp) {
            $this->initWithSportShortage($variantWithPoule->getNrOfPlaces(), $sporVariant);
        }
        $this->useWith = $sporVariant instanceof Against && $sporVariant->hasMultipleSidePlaces();
    }

    public function useWith(): bool {
        return $this->useWith;
    }


    public function getNrOfHomeAwaysAsigned(): int {
        return $this->nrOfHomeAwaysAsigned;
    }

    private function initAgainstSportShortage(): void {

        $counters = $this->assignedAgainstSportMap->getCopiedCounters();
        $minAgainstAmountPerPlace = $this->getMinAgainstAmountPerPlace($this->variantWithPoule);
        $maxAgainstAmountPerPlace = $this->getMaxAgainstAmountPerPlace($this->variantWithPoule);
        $nrOfOpponents = count($this->variantWithPoule->getPoule()->getPlaces()) - 1;
        foreach( $this->variantWithPoule->getPoule()->getPlaces() as $place ) {
            if( !array_key_exists($place->getNumber(), $counters) ) {
                $this->againstShortage += $minAgainstAmountPerPlace * $nrOfOpponents;
                continue;
            }
            $placeCounter = $counters[$place->getNumber()];

            foreach( $this->variantWithPoule->getPoule()->getPlaces() as $againstPlace ) {
                if( $place === $againstPlace) {
                    continue;
                }
                $nrOfAgainst = $placeCounter->count($againstPlace);

                if( $nrOfAgainst < ($minAgainstAmountPerPlace - $this->margin) ) {
                    $this->againstShortage += $minAgainstAmountPerPlace - $nrOfAgainst;
                }
                if( $nrOfAgainst > ($maxAgainstAmountPerPlace + $this->margin) ) {
                    $this->amountOverAgainstAmountForAllPlaces += $nrOfAgainst - $maxAgainstAmountPerPlace;
                }
            }
        }
    }

    private function initWithSportShortage(int $nrOfPlaces, AgainstGpp $sportVariaant): void {
        $minWithAmount = $this->getMinWithAmount($nrOfPlaces, $sportVariaant) - $this->margin;
        foreach( $this->assignedWithSportMap as $placeCombinationCounter ) {
            if( $placeCombinationCounter->count() < $minWithAmount ) {
                $this->withShortage += $minWithAmount - $placeCombinationCounter->count();
            }
        }
    }

    public function addHomeAway(AgainstHomeAway $homeAway): self
    {
        $assignedSportMap = $this->copyPlaceCounterMap($this->assignedSportMap);
        $assignedMap = $this->copyPlaceCounterMap($this->assignedMap);
        foreach ($homeAway->getPlaces() as $place) {
            $assignedSportMap[$place->getNumber()]->increment();
            $assignedMap[$place->getNumber()]->increment();
        }

        $assignedWithMap = $this->copyPlaceCombinationCounterMap($this->assignedWithSportMap);
        $assignedWithMap[$homeAway->getHome()->getNumber()]->increment();
        $assignedWithMap[$homeAway->getAway()->getNumber()]->increment();

        $assignedHomeMap = $this->copyPlaceCounterMap($this->assignedHomeMap);
        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            $assignedHomeMap[$homePlace->getNumber()]->increment();
        }

        return new self(
            $this->variantWithPoule,
            $assignedSportMap,
            $assignedMap,
            $assignedWithMap,
            $this->assignedAgainstSportMap->addHomeAway($homeAway),
            $assignedHomeMap,
            $this->margin,
            $this->nrOfHomeAwaysAsigned + 1
        );
    }

    /**
     * @param array<int, PlaceCounter> $map
     * @return array<int, PlaceCounter>
     */
    private function copyPlaceCounterMap(array $map): array {
        $newMap = [];
        foreach( $map as $idx => $counter ) {
            $newMap[$idx] = new PlaceCounter($counter->getPlace(), $counter->count());
        }
        return $newMap;
    }

    /**
     * @param array<int, PlaceCombinationCounter> $map
     * @return array<int, PlaceCombinationCounter>
     */
    private function copyPlaceCombinationCounterMap(array $map): array {
        $newMap = [];
        foreach( $map as $idx => $counter ) {
            $newMap[$idx] = new PlaceCombinationCounter($counter->getPlaceCombination(), $counter->count());
        }
        return $newMap;
    }

    public function minimalSportCanStillBeAssigned(): bool {
        $nrOfGamesToGo = $this->variantWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAsigned;
        $minNrOfGamesPerPlace = $this->getMinNrOfGamesPerPlace();

        foreach( $this->variantWithPoule->getPoule()->getPlaces() as $place ) {
            if( ($this->assignedSportMap[$place->getNumber()]->count() + $nrOfGamesToGo) < $minNrOfGamesPerPlace ) {
                return false;
            }
        }
        return true;
    }

    public function sportWillBeOverAssigned(Place $place, int $delta): bool
    {
        // $i = (memory_get_usage() / (1024*1024)) . 'MB';

        if( ($this->assignedSportMap[$place->getNumber()]->count() + $delta) > $this->getMaxNrOfGamesPerPlace() ) {
            // $j = (memory_get_usage() / (1024*1024)) . 'MB';
            return true;
        }
        // $k = (memory_get_usage() / (1024*1024)) . 'MB';
        return false;
    }

    private function getMinNrOfGamesPerPlace(): int {
        $totalNrOfGamesPerPlace = $this->variantWithPoule->getTotalNrOfGamesPerPlace();
        $notAllPlacesPlaySameNrOfGames = !$this->variantWithPoule->allPlacesPlaySameNrOfGames();
        return $totalNrOfGamesPerPlace - ($notAllPlacesPlaySameNrOfGames ? 1 : 0);
    }

    private function getMaxNrOfGamesPerPlace(): int {
        return $this->variantWithPoule->getTotalNrOfGamesPerPlace();
    }

    public function minimalWithCanStillBeAssigned(AgainstHomeAway $homeAway): bool {

        $nrOfGamesToGo = $this->variantWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAsigned;

        // $maxHomeAwayWiths = 2;
        if( ($this->withShortage /*- $maxHomeAwayWiths*/) <= ($nrOfGamesToGo * 2) ) {
            return true;
        }
        $withShortageIncludingHomeAway = $this->getWithShortageIncludingHomeAway($homeAway);
        return $withShortageIncludingHomeAway <= ($nrOfGamesToGo * 2);
    }

    private function getWithShortageIncludingHomeAway(AgainstHomeAway $homeAway): int {
        $sportVariant = $this->variantWithPoule->getSportVariant();
        if( !($sportVariant instanceof AgainstGpp)) {
            return 0;
        }
        $minWithAmount = $this->getMinWithAmount($this->variantWithPoule->getNrOfPlaces(), $sportVariant);
        $withShortage = $this->withShortage;

        $homeAmount = 0;
        if( array_key_exists($homeAway->getHome()->getNumber(), $this->assignedWithSportMap)) {
            $homeAmount = $this->assignedWithSportMap[$homeAway->getHome()->getNumber()]->count();
        }
        if( $homeAmount < $minWithAmount ) {
            $withShortage--;
        }

        $awayAmount = 0;
        if( array_key_exists($homeAway->getAway()->getNumber(), $this->assignedWithSportMap)) {
            $awayAmount = $this->assignedWithSportMap[$homeAway->getAway()->getNumber()]->count();
        }
        if( $awayAmount < $minWithAmount ) {
            $withShortage--;
        }
        return $withShortage;
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @return list<AgainstHomeAway>
     */
    public function filter(array $homeAways): array {
        $homeAways = array_filter(
            $homeAways,
            function (AgainstHomeAway $homeAway): bool {
                return !$this->withWillBeOverAssigned($homeAway) && !$this->againstWillBeOverAssigned($homeAway);
            }
        );
        return array_values($homeAways);
    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @return list<AgainstHomeAway>
     */
    public function sortHomeAways(array $homeAways): array {
        uasort($homeAways, function (AgainstHomeAway $ha1, AgainstHomeAway $ha2) {
            return $this->getHomeAwayAssigned($ha1) - $this->getHomeAwayAssigned($ha2);
        });
        return array_values($homeAways);
    }

    public function minimalAgainstCanStillBeAssigned(AgainstHomeAway|null $homeAway): bool {

        $nrOfCombinationsPerGame = $this->getSportVariant($this->variantWithPoule)->getNrOfHomeAwayCombinations() * 2;
        // 8
        $nrOfGamesToGo = $this->variantWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAsigned;
        // $nrOfGamesToGo--;

        if( ($this->againstShortage/* - $nrOfCombinationsPerGame*/) <= ($nrOfGamesToGo * $nrOfCombinationsPerGame) ) {
            return true;
        }
        if( $homeAway === null ) {
            return false;
        }
        $againstShortageIncludingHomeAway = $this->getAgainstShortageIncludingHomeAway($homeAway);
        return $againstShortageIncludingHomeAway <= ($nrOfGamesToGo * $nrOfCombinationsPerGame);
    }

    private function getAgainstShortageIncludingHomeAway(AgainstHomeAway $homeAway): int {
        $minAgainstAmountPerPlace = $this->getMinAgainstAmountPerPlace($this->variantWithPoule);
        $placeAgainstCounters = $this->assignedAgainstSportMap->getCopiedCounters();
        $againstShortage = $this->againstShortage;

        foreach( [Side::Home,Side::Away] as $side ) {
            $placeCombination = $homeAway->get($side);

            foreach( $placeCombination->getPlaces() as $place ) {
                $opponents = $homeAway->get($side->getOpposite());
                if( !array_key_exists($place->getNumber(), $placeAgainstCounters ) ) {
                    $againstShortage -= count($opponents->getPlaces());
                    continue;
                }
                $opponentCounters = $placeAgainstCounters[$place->getNumber()]->getCopiedPlaceCounters();

                foreach( $opponents->getPlaces() as $opponent ) {
                    if( !array_key_exists($opponent->getNumber(), $opponentCounters ) ) {
                        $againstShortage--;
                        continue;
                    }
                    $amount = $opponentCounters[$opponent->getNumber()]->count();
                    if( $amount < ($minAgainstAmountPerPlace - $this->margin) ) {
                        $againstShortage--;
                    }
                }
            }
        }
        return $againstShortage;
    }

    public function withWillBeOverAssigned(AgainstHomeAway $homeAway): bool
    {
        if( !$this->useWith) {
            return false;
        }
        $againstGppSportVariant = $this->variantWithPoule->getSportVariant();
        if( !($againstGppSportVariant instanceof AgainstGpp)) {
            return false;
        }
        $homeWithAmount = $this->assignedWithSportMap[$homeAway->getHome()->getNumber()]->count();
        $awayWithAmount = $this->assignedWithSportMap[$homeAway->getAway()->getNumber()]->count();

        $nrOfPlaces = $this->variantWithPoule->getNrOfPlaces();

        $maxWithAmount = $this->getMaxWithAmount($nrOfPlaces, $againstGppSportVariant);
        return ($homeWithAmount + 1) > $maxWithAmount || ($awayWithAmount + 1) > $maxWithAmount;
    }

    public function againstWillBeOverAssigned(AgainstHomeAway $homeAway): bool
    {
        // if( $this->variantWithPoule->e)
        $counters = $this->assignedAgainstSportMap->getCopiedCounters();
        $maxAgainstAmountPerPlace = $this->getMaxAgainstAmountPerPlace($this->variantWithPoule);
        $amountOverAgainstPerPlace = 0;
        foreach( $homeAway->getHome()->getPlaces() as $homePlace ) {
            if( empty($counters[$homePlace->getNumber()]) ) {
                continue;
            }
            $homePlaceCounter = $counters[$homePlace->getNumber()];
            foreach( $homeAway->getAway()->getPlaces() as $awayPlace ) {
                $newAmount = $homePlaceCounter->count($awayPlace) + 1;
                if( $newAmount <= $maxAgainstAmountPerPlace ) {
                    continue;
                }
                if( $newAmount === ($maxAgainstAmountPerPlace + 1 ) ) {
                    $amountOverAgainstPerPlace += (1 * 2);
                    continue;
                }
                return true;
            }
        }
        return ($this->amountOverAgainstAmountForAllPlaces + $amountOverAgainstPerPlace) > $this->getMaxAmountOverMaxAgainstAmountForAllPlaces($this->variantWithPoule);
    }

    private function minimalWithIsAssigned(): bool {
        $sportVariant = $this->variantWithPoule->getSportVariant();
        if( !($sportVariant instanceof AgainstGpp)) {
            return true;
        }

        $minWithAmount = $this->getMinWithAmount($this->variantWithPoule->getNrOfPlaces(), $sportVariant);

        foreach( $this->assignedWithSportMap as $placeCombinationCounter ) {
            if( $placeCombinationCounter->count() < $minWithAmount ) {
                return false;
            }
        }
        return true;
    }

    private function getHomeAwayAssigned(AgainstHomeAway $ha): int {
        $assigned = 0;
        foreach( $ha->getPlaces() as $place) {
            if( !array_key_exists($place->getNumber(), $this->assignedSportMap)) {
               continue;
            }
            $assigned += $this->assignedSportMap[$place->getNumber()]->count();
            if( !array_key_exists($place->getNumber(), $this->assignedMap)) {
                continue;
            }
            $assigned += $this->assignedMap[$place->getNumber()]->count();
        }
        return $assigned;
    }

    public function allAssigned(): bool
    {
        if ( $this->nrOfHomeAwaysAsigned < $this->variantWithPoule->getTotalNrOfGames() ) {
            return false;
        }
        if( !$this->minimalSportCanStillBeAssigned() ) {
            return false;
        }
        $nrOfIncompletePlaces = 0;
        foreach ($this->assignedMap as $assignedCounter) {
            if ($assignedCounter->count() < $this->variantWithPoule->getTotalNrOfGamesPerPlace()) {
                $nrOfIncompletePlaces++;
            }

            if ($nrOfIncompletePlaces >= $this->variantWithPoule->getNrOfGamePlaces()) {
                return false;
            }
        }
        if( !$this->useWith() ) {
            return true;
        }
        if( !$this->minimalAgainstCanStillBeAssigned(null) ) {
            return false;
        }
        return $this->minimalWithIsAssigned();
    }

    protected function getMaxWithAmount(int $nrOfPlaces, AgainstGpp $sportVariant): int {
        $maxNrOfSidePlaces = max( $sportVariant->getNrOfHomePlaces(), $sportVariant->getNrOfAwayPlaces() );
        return (int)ceil($sportVariant->getNrOfGamesPerPlace() / ( ($nrOfPlaces - 1) * ($maxNrOfSidePlaces - 1) ));
    }

    protected function getMinWithAmount(int $nrOfPlaces, AgainstGpp $sportVariant): int {
        $minNrOfSidePlaces = min( $sportVariant->getNrOfHomePlaces(), $sportVariant->getNrOfAwayPlaces() );
        if( $minNrOfSidePlaces === 1) {
            return 0;
        }
        return (int)floor($sportVariant->getNrOfGamesPerPlace() / ( ($nrOfPlaces - 1) * ($minNrOfSidePlaces - 1) ));
    }

    protected function getMaxAmountOverMaxAgainstAmountForAllPlaces(VariantWithPoule $variantWithPoule): int {

        $rest = $this->getMaxNrOfAgainstPlacesForPlace($variantWithPoule) % ($variantWithPoule->getNrOfPlaces() - 1);
        return ($rest * $variantWithPoule->getNrOfPlaces() );
        // (int)ceil(($sportVariant->getNrOfGamesPerPlace() * $maxNrOfSidePlaces) / ($variantWithPoule->getNrOfPlaces() - 1));
    }

    protected function getMinAgainstAmountPerPlace(VariantWithPoule $variantWithPoule): int {
        return (int)floor($this->getMinNrOfAgainstPlacesForPlace($variantWithPoule) / ($variantWithPoule->getNrOfPlaces() - 1));
    }

    protected function getMaxAgainstAmountPerPlace(VariantWithPoule $variantWithPoule): int {
        return (int)ceil($this->getMaxNrOfAgainstPlacesForPlace($variantWithPoule) / ($variantWithPoule->getNrOfPlaces() - 1));
    }

    protected function getMinNrOfAgainstPlacesForPlace(VariantWithPoule $variantWithPoule): float {
        $sportVariant = $this->getSportVariant($variantWithPoule);
        $minNrOfSidePlaces = min( $sportVariant->getNrOfHomePlaces(), $sportVariant->getNrOfAwayPlaces() );
        return $this->getNrOfGamesPerPlace($sportVariant, $this->variantWithPoule->getNrOfPlaces()) * $minNrOfSidePlaces;
    }

    protected function getMaxNrOfAgainstPlacesForPlace(VariantWithPoule $variantWithPoule): float {
        $sportVariant = $this->getSportVariant($variantWithPoule);
        $minNrOfSidePlaces = max( $sportVariant->getNrOfHomePlaces(), $sportVariant->getNrOfAwayPlaces() );
        return $this->getNrOfGamesPerPlace($sportVariant, $this->variantWithPoule->getNrOfPlaces()) * $minNrOfSidePlaces;
    }

    protected function getNrOfGamesPerPlace(AgainstGpp|AgainstH2h $sportVariant, int $nrOfPlaces): float {
        if( $sportVariant instanceof AgainstGpp ) {
            return $sportVariant->getNrOfGamesPerPlace();
        }
        return $sportVariant->getTotalNrOfGamesPerPlace($nrOfPlaces);
    }

    protected function getSportVariant(VariantWithPoule $variantWithPoule): AgainstGpp|AgainstH2h {
        $sportVariant = $variantWithPoule->getSportVariant();
        if( !($sportVariant instanceof AgainstGpp || $sportVariant instanceof AgainstH2h) ) {
            throw new \Exception('incorrect sportvariant', E_ERROR );
        }
        return $sportVariant;
    }

    public function output(LoggerInterface $logger, bool $againstTotals, bool $withTotals): void {
        $logger->info('nrOfHomeAwaysAsigned: ' . $this->nrOfHomeAwaysAsigned);
        if( $againstTotals ) {
            $this->outputAgainstTotals($logger);
        }
        if( $withTotals ) {
            $this->outputWithTotals($logger);
        }
    }

    public function outputAgainstTotals(LoggerInterface $logger): void {
        $header = 'AgainstTotals ( ' . $this->againstShortage . ' againstShortage, amount overAssigned is ' . $this->amountOverAgainstAmountForAllPlaces . ')';
        $logger->info($header);
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
        $placeCounters = $this->assignedAgainstSportMap->getCopiedCounters();
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
        $header = 'WithTotals ( ' . $this->withShortage . ' withShortage )';
        $logger->info($header);

        $amountPerLine = 4; $counter = 0; $line = '';
        foreach( $this->assignedWithSportMap as $counterIt ) {
            $line .= $counterIt->getPlaceCombination() . ' ' . $counterIt->count() . 'x, ';
            if( ++$counter === $amountPerLine ) {
                $logger->info('    ' . $line);
                $counter = 0;
                $line = '';
            }
        }
        if( strlen($line) > 0 ) {
            $logger->info('    ' . $line);
        }
    }


}
