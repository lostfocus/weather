<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\VisualCrossing;

use Lostfocus\Weather\Common\Source;
use Lostfocus\Weather\Common\WeatherData;

class VisualCrossingData extends WeatherData
{
    public function __construct()
    {
        $this->source = new Source(
            'visualcrossing',
            'Visual Crossing',
            'https://www.visualcrossing.com/'
        );
    }
}