<?php

declare(strict_types=1);

namespace SportsPlanning\SerializationHandler;

use JMS\Serializer\Handler\HandlerRegistry;
use SportsPlanning\SerializationHandler\PlanningHandler as PlanningHandler;

class Subscriber
{
    public function __construct(/*protected DummyCreator $dummyCreator*/)
    {
    }

    public function subscribeHandlers(HandlerRegistry $registry): void
    {
        $registry->registerSubscribingHandler(new PlanningHandler());
    }
}
