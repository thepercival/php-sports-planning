<?php

namespace SportsPlanning\Output\PlanningOutput;

enum  Extra: int
{
    case Input = 1;
    case Games = 2;
    case Totals = 4;
    case NrOfBatchGamesRange = 8;
    case MaxNrOfGamesInARow = 16;
}