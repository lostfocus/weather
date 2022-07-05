<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\OpenWeatherMap;

use Lostfocus\Weather\Common\WeatherData;

final class OpenWeatherMapData extends WeatherData
{
    protected ?string $source = 'openweathermap';
}