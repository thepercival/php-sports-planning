<?php

declare(strict_types=1);

namespace SportsPlanning\Input;

use SportsHelpers\Sport\GamePlaceCalculator;

final class Calculator
{
    // protected GamePlaceCalculator $sportGamePlaceCalculator;

    public function __construct()
    {
        // $this->sportGamePlaceCalculator = new GamePlaceCalculator();
    }


//    protected function getNrOfGamePlaces(AgainstH2h|AgainstGpp|Single|AllInOneGame $sportVariant, int $nrOfPlaces): int
//    {
//        $variantWithPoule = (new VariantCreator())->createWithPoule($nrOfPlaces, $sportVariant);
//        if( $variantWithPoule instanceof AllInOneGameWithPoule) {
//            return $variantWithPoule->getNrOfGamePlaces();
//        }
//        return $variantWithPoule->getSportVariant()->getNrOfGamePlaces();
//    }







}
