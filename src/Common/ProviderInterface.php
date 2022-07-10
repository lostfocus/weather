<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Common;

interface ProviderInterface
{
    public const UNIT_METRIC = 'metric';
    public const UNIT_IMPERIAL = 'imperial';

    public function getCurrentWeatherData(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataInterface;

    public function getForecastCollection(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataCollectionInterface;
}