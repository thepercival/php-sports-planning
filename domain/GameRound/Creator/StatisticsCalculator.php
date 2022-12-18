<?php

declare(strict_types=1);

namespace SportsPlanning\GameRound\Creator;

use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\AgainstHomeAway;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\Combinations\PlaceCombinationCounter;
use SportsHelpers\Sport\Variant\Against;
use SportsPlanning\Place;
use SportsPlanning\PlaceCounter;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;

class StatisticsCalculator
{
    protected int $withShortage = 0;
    protected int $againstShortage = 0;
    protected int $amountOverAgainstAmountForAllPlaces = 0;
    protected int $leastNrOfAgainstAssigned = 0;
    protected int $amountLeastAgainstAssigned = 0;
    protected bool $useWith;
    protected int $leastNrOfWithAssigned = 0;
    protected int $amountLeastWithAssigned = 0;

    /**
     * @param VariantWithPoule $variantWithPoule
     * @param array<int, PlaceCounter> $assignedSportMap
     * @param array<int, PlaceCounter> $assignedMap
     * @param array<int, PlaceCombinationCounter> $assignedWithSportMap
     * @param array<int, PlaceCombinationCounter> $assignedAgainstSportMap
     * @param array<int, PlaceCounter> $assignedHomeMap
     * @param array<int, PlaceCombination> $leastAgainstAssigned
     * @param int $margin
     * @param int $nrOfHomeAwaysAsigned
     */
    public function __construct(
        protected VariantWithPoule $variantWithPoule,
        protected array $assignedSportMap,
        protected array $assignedMap,
        protected array $assignedWithSportMap,
        protected array $assignedAgainstSportMap,
        protected array $assignedHomeMap,
        protected array $leastAgainstAssigned,
        private readonly int $margin,
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

        $minAgainstAmountPerPlace = $this->getMinAgainstAmountPerPlace($this->variantWithPoule);
        $maxAgainstAmountPerPlace = $this->getMaxAgainstAmountPerPlace($this->variantWithPoule);
        foreach( $this->assignedAgainstSportMap as $placeCombinationCounter ) {
            $nrOfAgainst = $placeCombinationCounter->count();

            if( $nrOfAgainst < ($minAgainstAmountPerPlace - $this->margin) ) {
                $this->againstShortage += $minAgainstAmountPerPlace - $nrOfAgainst;
            }
            if( $nrOfAgainst > ($maxAgainstAmountPerPlace + $this->margin) ) {
                $this->amountOverAgainstAmountForAllPlaces += $nrOfAgainst - $maxAgainstAmountPerPlace;
            }
            if( $nrOfAgainst <= $this->leastNrOfAgainstAssigned ) {
                $this->leastNrOfAgainstAssigned = $nrOfAgainst;
                $this->amountLeastAgainstAssigned++;
            }
        }
    }

    private function initWithSportShortage(int $nrOfPlaces, AgainstGpp $sportVariaant): void {
        $minWithAmount = $this->getMinWithAmount($nrOfPlaces, $sportVariaant) - $this->margin;
        foreach( $this->assignedWithSportMap as $placeCombinationCounter ) {
            $nrOfWith = $placeCombinationCounter->count();
            if( $nrOfWith < $minWithAmount ) {
                $this->withShortage += $minWithAmount - $nrOfWith;
            }
            if( $nrOfWith <= $this->leastNrOfWithAssigned ) {
                $this->leastNrOfWithAssigned = $nrOfWith;
                $this->amountLeastWithAssigned++;
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

        $assignedAgainstMap = $this->copyPlaceCombinationCounterMap($this->assignedAgainstSportMap);
        foreach( $homeAway->getAgainstPlaceCombinations() as $placeCombination) {
            $assignedAgainstMap[$placeCombination->getNumber()]->increment();
        }
        $assignedWithMap = $this->copyPlaceCombinationCounterMap($this->assignedWithSportMap);
        if( $this->useWith) {
            if(count($homeAway->getHome()->getPlaces()) > 1 ) {
                $assignedWithMap[$homeAway->getHome()->getNumber()]->increment();
            }
            $assignedWithMap[$homeAway->getAway()->getNumber()]->increment();
        }

        $assignedHomeMap = $this->copyPlaceCounterMap($this->assignedHomeMap);
        foreach ($homeAway->getHome()->getPlaces() as $homePlace) {
            $assignedHomeMap[$homePlace->getNumber()]->increment();
        }

        $leastAgainstAssigned = $this->leastAgainstAssigned;
//        $unsetForNewLeastAssigned = [];
//        foreach($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
//            if (array_key_exists($againstPlaceCombination->getNumber(), $leastAgainstAssigned)) {
//                unset($leastAgainstAssigned[$againstPlaceCombination->getNumber()]);
//            } else {
//                $unsetForNewLeastAssigned[] = $againstPlaceCombination;
//            }
//        }
//        if( count($leastAgainstAssigned) === 0) {
//            $leastAgainstAssigned = $this->convertToPlaceCombinationMap($this->assignedAgainstSportMap);
//            foreach($unsetForNewLeastAssigned as $againstPlaceCombination ) {
//                unset($leastAgainstAssigned[$againstPlaceCombination->getNumber()]);
//            }
//        }

        return new self(
            $this->variantWithPoule,
            $assignedSportMap,
            $assignedMap,
            $assignedWithMap,
            $assignedAgainstMap,
            $assignedHomeMap,
            $leastAgainstAssigned,
            $this->margin,
            $this->nrOfHomeAwaysAsigned + 1
        );
    }

    public function againstWillBeTooMuchDiffAssigned(AgainstHomeAway $homeAway): bool
    {
        return false;
//        $againstPlaceCombinations = $homeAway->getAgainstPlaceCombinations();
//        $nrOfAgainstCombinations = count($againstPlaceCombinations);
//
//        $nrOfOverAssigned = 0;
//        foreach($againstPlaceCombinations as $againstPlaceCombination ) {
//            if (!array_key_exists($againstPlaceCombination->getNumber(), $this->assignedAgainstSportMap)) {
//                continue;
//            }
//            $nrAssigned = $this->assignedAgainstSportMap[$againstPlaceCombination->getNumber()]->count();
//            if( $nrAssigned - $this->leastNrOfAgainstAssigned > (0 + $this->margin)) {
//                $nrOfOverAssigned++;
//                if( $this->amountLeastAgainstAssigned >= $nrOfAgainstCombinations) {
//                    return true;
//                }
//            }
//        }
//
//        if( $nrOfOverAssigned > $this->amountLeastAgainstAssigned) {
//            return true;
//        }
//        return false;
    }

    public function withWillBeTooMuchDiffAssigned(AgainstHomeAway $homeAway): bool
    {
        if( !$this->useWith ) {
            return false;
        }
        $withPlaceCombinations = $homeAway->getWithPlaceCombinations();
        $nrOfWithCombinations = count($withPlaceCombinations);
        if( $this->amountLeastWithAssigned < $nrOfWithCombinations ) {
            return false;
        }
        foreach($withPlaceCombinations as $withPlaceCombination ) {
            if (!array_key_exists($withPlaceCombination->getNumber(), $this->assignedWithSportMap)) {
                continue;
            }
            $nrAssigned = $this->assignedWithSportMap[$withPlaceCombination->getNumber()]->count();
            if( $nrAssigned - $this->leastNrOfWithAssigned > 0) {
                return true;
            }
        }
//        if( $nrOfAgainstCombinations === count($leastAgainstAssigned)
//        || count($leastAgainstAssigned) === count($this->leastAgainstAssigned)) {
//            return true;
//        }
        return false;
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

    /**
     * @param array<int, PlaceCombinationCounter> $map
     * @return array<int, PlaceCombination>
     */
    private function convertToPlaceCombinationMap(array $map): array {
        $newMap = [];
        foreach( $map as $idx => $counter ) {
            $newMap[$idx] = $counter->getPlaceCombination();
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
        if( ($this->assignedSportMap[$place->getNumber()]->count() + $delta) > $this->getMaxNrOfGamesPerPlace() ) {
            return true;
        }
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

    public function minimalWithCanStillBeAssigned(AgainstHomeAway|null $homeAway): bool {

        $nrOfGamesToGo = $this->variantWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAsigned;

        $nrOfWithCombinationsTogo = $nrOfGamesToGo * 2;
        if( ($this->withShortage) <= $nrOfWithCombinationsTogo ) {
            return true;
        }
        if( $homeAway === null) {
            return false;
        }
        $withShortageIncludingHomeAway = $this->getWithShortageIncludingHomeAway($homeAway);
        return $withShortageIncludingHomeAway <= $nrOfWithCombinationsTogo;
    }

    private function getWithShortageIncludingHomeAway(AgainstHomeAway $homeAway): int {
        $sportVariant = $this->variantWithPoule->getSportVariant();
        if( !($sportVariant instanceof AgainstGpp)) {
            return 0;
        }
        $minWithAmount = $this->getMinWithAmount($this->variantWithPoule->getNrOfPlaces(), $sportVariant) - $this->margin;
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
                return !$this->withWillBeOverAssigned($homeAway) && !$this->againstWillBeOverAssigned($homeAway)
                    && !$this->againstWillBeTooMuchDiffAssigned($homeAway)
                    && !$this->withWillBeTooMuchDiffAssigned($homeAway);
            }
        );
        return array_values($homeAways);
    }

    public function minimalAgainstCanStillBeAssigned(AgainstHomeAway|null $homeAway): bool {

        $nrOfCombinationsPerGame = $this->getSportVariant($this->variantWithPoule)->getNrOfHomeAwayCombinations();
        $nrOfGamesToGo = $this->variantWithPoule->getTotalNrOfGames() - $this->nrOfHomeAwaysAsigned;
        $nrOfAgainstCombinationsTogo = $nrOfGamesToGo * $nrOfCombinationsPerGame;

        if( ($this->againstShortage) <= $nrOfAgainstCombinationsTogo ) {
            return true;
        }
        if( $homeAway === null ) {
            return false;
        }
        $againstShortageIncludingHomeAway = $this->getAgainstShortageIncludingHomeAway($homeAway);
        return $againstShortageIncludingHomeAway <= $nrOfAgainstCombinationsTogo;
    }

    private function getAgainstShortageIncludingHomeAway(AgainstHomeAway $homeAway): int {
        $minAgainstAmountPerPlace = $this->getMinAgainstAmountPerPlace($this->variantWithPoule);
        $againstShortage = $this->againstShortage;

        foreach( $homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
            if( !array_key_exists($againstPlaceCombination->getNumber(), $this->assignedAgainstSportMap ) ) {
                $againstShortage -= ($minAgainstAmountPerPlace - $this->margin);
                continue;
            }
            $amount = $this->assignedAgainstSportMap[$againstPlaceCombination->getNumber()]->count();
            if( $amount < ($minAgainstAmountPerPlace - $this->margin) ) {
                $againstShortage--;
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

        $homeWithAmount = $this->getHomeWithAmountAssigned($homeAway);
        $awayWithAmount = $this->assignedWithSportMap[$homeAway->getAway()->getNumber()]->count();

        $nrOfPlaces = $this->variantWithPoule->getNrOfPlaces();

        $maxWithAmount = $this->getMaxWithAmount($nrOfPlaces, $againstGppSportVariant) + $this->margin;
        return ($homeWithAmount + 1) > $maxWithAmount || ($awayWithAmount + 1) > $maxWithAmount;
    }

    public function againstWillBeOverAssigned(AgainstHomeAway $homeAway): bool
    {
        $maxAgainstAmountPerPlace = $this->getMaxAgainstAmountPerPlace($this->variantWithPoule) + $this->margin;
        $amountOverAgainstPerPlace = 0;
        foreach( $homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
            if( !array_key_exists($againstPlaceCombination->getNumber(), $this->assignedAgainstSportMap) ) {
                continue;
            }
            $newAmount = $this->assignedAgainstSportMap[$againstPlaceCombination->getNumber()]->count() + 1;

            if( $newAmount <= $maxAgainstAmountPerPlace ) {
                continue;
            }
            if( $newAmount === ($maxAgainstAmountPerPlace + 1 ) ) {
                $amountOverAgainstPerPlace++;
                continue;
            }
            return true;
        }
        return ($this->amountOverAgainstAmountForAllPlaces + $amountOverAgainstPerPlace) > $this->getMaxAmountOverMaxAgainstAmountForAllPlaces($this->variantWithPoule);
    }

    private function minimalWithIsAssigned(): bool {
        $sportVariant = $this->variantWithPoule->getSportVariant();
        if( !($sportVariant instanceof AgainstGpp)) {
            return true;
        }

        $minWithAmount = $this->getMinWithAmount($this->variantWithPoule->getNrOfPlaces(), $sportVariant) - $this->margin;

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
        $maxAgainst = $this->getMaxNrOfAgainstPlacesForPlace($variantWithPoule) + $this->margin;
        $rest = $maxAgainst % ($variantWithPoule->getNrOfPlaces() - 1);
        return ($rest * $variantWithPoule->getNrOfPlaces() );
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
        $header = 'nrOfHomeAwaysAsigned: ' . $this->nrOfHomeAwaysAsigned;
        $nrOfPlaces = $this->variantWithPoule->getNrOfPlaces();
        $againstSportVariant = $this->getSportVariant($this->variantWithPoule);
        if( $againstSportVariant instanceof AgainstGpp) {
            $minWith = $this->getMinWithAmount($nrOfPlaces, $againstSportVariant);
            $maxWith = $this->getMaxWithAmount($nrOfPlaces, $againstSportVariant);
            $minAgainst = $this->getMinAgainstAmountPerPlace($this->variantWithPoule);
            $maxAgainst = $this->getMaxAgainstAmountPerPlace($this->variantWithPoule);
            $header .= ', withRange: ' . $minWith . ' - ' . $maxWith;
            $header .= ', againstRange ' . $minAgainst . ' - ' . $maxAgainst;
        }
        if( $this->margin > 0 ) {
            $header .= ', margin ' . $this->margin;
        }
        $logger->info($header);
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
            $placeCombination = new PlaceCombination([$place, $opponent]);
            $out .= '' . $opponentNr . ':' . $this->getAmount($placeCombination) . ',';
        }
        $logger->info($out);
    }

    private function getAmount(PlaceCombination $placeCombination): string {
        if( !array_key_exists($placeCombination->getNumber(), $this->assignedAgainstSportMap)) {
            return '0x';
        }
        return $this->assignedAgainstSportMap[$placeCombination->getNumber()]->count() . 'x';

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

//    /**
//     * @param list<AgainstHomeAway> $homeAways
//     * @return list<AgainstHomeAway>
//     */
//    public function sortHomeAways(array $homeAways): array {
//        uasort($homeAways, function (AgainstHomeAway $ha1, AgainstHomeAway $ha2) {
//            return $this->getHomeAwayAssigned($ha1) - $this->getHomeAwayAssigned($ha2);
//        });
//        return array_values($homeAways);
//    }

    /**
     * @param list<AgainstHomeAway> $homeAways
     * @param LoggerInterface $logger
     * @return list<AgainstHomeAway>
     */
    public function sortHomeAways(array $homeAways, LoggerInterface $logger): array {
//        $time_start = microtime(true);

        if(count($homeAways) > 10000 ) {
            $splitted = true;
            /** @var list<AgainstHomeAway> $firstPartHomeAways */
            $firstPartHomeAways = array_splice($homeAways, 0, 10000);
        } else {
            $splitted = false;
            $firstPartHomeAways = $homeAways;
        }

        $leastAmountAssigned = [];
        foreach($firstPartHomeAways as $homeAway ) {
            $leastAmountAssigned[(string)$homeAway] = $this->getLeastAmountAssigned($homeAway);
        }

        uasort($firstPartHomeAways, function (
            AgainstHomeAway $homeAwayA,
            AgainstHomeAway $homeAwayB
        ) use($leastAmountAssigned): int {

            list($amountA, $nrOfPlacesA) = $leastAmountAssigned[(string)$homeAwayA];
            list($amountB, $nrOfPlacesB) = $leastAmountAssigned[(string)$homeAwayB];
            if ($amountA !== $amountB) {
                return $amountA - $amountB;
            }
            if ($nrOfPlacesA !== $nrOfPlacesB) {
                return $nrOfPlacesB - $nrOfPlacesA;
            }
            $amountAgainstA = $this->getAgainstAmountAssigned($homeAwayA);
            $amountAgainstB = $this->getAgainstAmountAssigned($homeAwayB);
            if ($amountAgainstA !== $amountAgainstB) {
                return $amountAgainstA - $amountAgainstB;
            }
            if( $this->useWith ) {
                $amountWithA = $this->getWithAmountAssigned($homeAwayA);
                $amountWithB = $this->getWithAmountAssigned($homeAwayB);
                if ($amountWithA !== $amountWithB) {
                    return $amountWithA - $amountWithB;
                }
            }


//            list($amountHomeA, $nrOfPlacesHomeA) = $this->getLeastHomeAmountAssigned($homeAwayA, $assignedHomeMap);
//            list($amountHomeB, $nrOfPlacesHomeB) = $this->getLeastHomeAmountAssigned($homeAwayB, $assignedHomeMap);
//            if ($amountHomeA !== $amountHomeB) {
//                return $amountHomeA - $amountHomeB;
//            }
//            if ($nrOfPlacesHomeA !== $nrOfPlacesHomeB) {
//                return $nrOfPlacesHomeA - $nrOfPlacesHomeB;
//            }
            return 0; // $nrOfPlacesHomeA - $nrOfPlacesHomeB;
        });
        //        $logger->info("sorting homeaways .. " . (microtime(true) - $time_start));
        if( $splitted ) {
            return array_merge($firstPartHomeAways, $homeAways);
        }
        return array_values($firstPartHomeAways);
    }

    // ALL BENEATH SHOULD PERFORM BETTER
    private function getAgainstAmountAssigned(AgainstHomeAway $homeAway): int {
        $amount = 0;
        foreach($homeAway->getAgainstPlaceCombinations() as $againstPlaceCombination ) {
            if( !array_key_exists($againstPlaceCombination->getNumber(), $this->assignedAgainstSportMap ) ) {
                continue;
            }
            $amount += $this->assignedAgainstSportMap[$againstPlaceCombination->getNumber()]->count();
        }
        return $amount;
    }

    /**
     * @param AgainstHomeAway $homeAway
     * @return list<int>
     */
    public function getLeastAmountAssigned(AgainstHomeAway $homeAway): array
    {
        $leastAmount = -1;
        $nrOfPlaces = 0;
        foreach ($homeAway->getPlaces() as $place) {
            $amountAssigned = $this->assignedMap[$place->getNumber()]->count();
            if ($leastAmount === -1 || $amountAssigned < $leastAmount) {
                $leastAmount = $amountAssigned;
                $nrOfPlaces = 0;
            }
            if ($amountAssigned === $leastAmount) {
                $nrOfPlaces++;
            }
        }
        return [$leastAmount, $nrOfPlaces];
    }

    protected function getWithAmountAssigned(AgainstHomeAway $homeAway): int
    {
        $awayWithAmountAssigned = $this->assignedWithSportMap[$homeAway->getAway()->getNumber()]->count();
        return $this->getHomeWithAmountAssigned($homeAway) + $awayWithAmountAssigned;
    }

    protected function getHomeWithAmountAssigned(AgainstHomeAway $homeAway): int
    {
        if( $this->getSportVariant($this->variantWithPoule)->getNrOfHomePlaces() > 1 ) {
            return $this->assignedWithSportMap[$homeAway->getHome()->getNumber()]->count();
        }
        return 0;
    }

//    /**
//     * @param AgainstHomeAway $homeAway
//     * @param array<int, AgainstCounter> $assignedAgainstMap
//     * @return int
//     */
//    protected function getAgainstAmountAssigned(AgainstHomeAway $homeAway, array $assignedAgainstMap): int
//    {
//        $home = $homeAway->getHome();
//        $away = $homeAway->getAway();
//        $homeAgainstAmountAssigned = $assignedAgainstMap[$home->getNumber()]->count($away);
//        $awayAgainstAmountAssigned = $assignedAgainstMap[$away->getNumber()]->count($home);
//        return $homeAgainstAmountAssigned + $awayAgainstAmountAssigned;
//    }

}
