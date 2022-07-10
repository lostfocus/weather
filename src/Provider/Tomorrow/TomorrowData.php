<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\Tomorrow;

use Lostfocus\Weather\Common\Source;
use Lostfocus\Weather\Common\WeatherData;

class TomorrowData extends WeatherData
{
    public function __construct()
    {
        $this->source = new Source(
            'tomorrow',
            'tomorrow.io',
            'https://www.tomorrow.io/'
        );
    }
}