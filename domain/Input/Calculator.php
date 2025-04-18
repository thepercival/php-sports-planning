<?php

declare(strict_types=1);

namespace SportsPlanning\Input;

use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\GamePlaceCalculator;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Creator as VariantCreator;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\H2h as AgainstH2hWithNrOfPlaces;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\AllInOneGame as AllInOneGameWithNrOfPlaces;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Single as SingleWithNrOfPlaces;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

class Calculator
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
