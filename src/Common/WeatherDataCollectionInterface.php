<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Common;

use Countable;
use Iterator;
use JsonSerializable;

interface WeatherDataCollectionInterface extends Countable, Iterator, JsonSerializable
{
    public function add(WeatherDataInterface $weatherData): self;
}