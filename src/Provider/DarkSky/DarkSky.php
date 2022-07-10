<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\DarkSky;

use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Http\Client\HttpClient;
use Lostfocus\Weather\Common\AbstractProvider;
use Lostfocus\Weather\Common\WeatherDataCollection;
use Lostfocus\Weather\Common\WeatherDataCollectionInterface;
use Lostfocus\Weather\Common\WeatherDataInterface;
use Lostfocus\Weather\Exceptions\DateNotInTheFutureException;
use Lostfocus\Weather\Exceptions\ForecastNoMaxDateException;
use Lostfocus\Weather\Exceptions\ForecastNotPossibleException;
use Lostfocus\Weather\Exceptions\WeatherException;
use Psr\Http\Message\RequestFactoryInterface;

class DarkSky extends AbstractProvider
{
    private string $key;

    /**
     * @noinspection PhpOptionalBeforeRequiredParametersInspection
     */
    public function __construct(HttpClient $client, ?RequestFactoryInterface $requestFactory = null, string $key)
    {
        $this->key = $key;

        parent::__construct($client, $requestFactory);
    }

    /**
     * @throws WeatherException
     */
    public function getCurrentWeatherData(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataInterface {
        return $this->getAtDateTime(
            $latitude,
            $longitude,
            new DateTime(),
            $units,
            $lang,
            WeatherDataInterface::CURRENT
        );
    }

    /**
     * @throws WeatherException
     */
    public function getHistorical(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {
        return $this->getAtDateTime(
            $latitude,
            $longitude,
            $dateTime,
            $units,
            $lang
        );
    }

    /**
     * @throws WeatherException
     */
    public function getForecast(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {
        if ($dateTime < new DateTime()) {
            throw new DateNotInTheFutureException();
        }

        $forecastCollection = $this->getForecastCollection($latitude, $longitude, $units, $lang);

        $maxForecastDate = $forecastCollection->getMaxDate();
        if ($maxForecastDate === null) {
            throw new ForecastNoMaxDateException();
        }

        $limit = $maxForecastDate->add(new DateInterval('PT12H'));

        if ($dateTime > $limit) {
            throw new ForecastNotPossibleException();
        }

        return $forecastCollection->getClosest($dateTime);
    }

    /**
     * @throws WeatherException
     */
    public function getForecastCollection(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataCollectionInterface {
        $querystring = sprintf(
            "https://api.darksky.net/forecast/%s/%s,%s?lang=%s&units=%s",
            $this->key,
            $latitude,
            $longitude,
            $lang,
            $this->mapUnits($units)
        );

        $weatherRawData = $this->getArrayFromQueryString($querystring);

        $weatherDataLatitude = $latitude;
        $weatherDataLongitude = $longitude;
        if (array_key_exists('latitude', $weatherRawData)) {
            $weatherDataLatitude = $weatherRawData['latitude'];
        }
        if (array_key_exists('longitude', $weatherRawData)) {
            $weatherDataLongitude = $weatherRawData['longitude'];
        }

        $weatherData = new WeatherDataCollection();

        if (array_key_exists('currently', $weatherRawData)) {
            $weatherData->add(
                $this->mapWeatherData(
                    WeatherDataInterface::CURRENT,
                    $weatherRawData['currently'],
                    $weatherDataLatitude,
                    $weatherDataLongitude
                )
            );
        }

        $keys = ['minutely', 'hourly', 'daily'];

        foreach ($keys as $key) {
            if (
                array_key_exists($key, $weatherRawData) &&
                is_array($weatherRawData[$key]) &&
                array_key_exists('data', $weatherRawData[$key]) &&
                is_array($weatherRawData[$key]['data'])
            ) {
                foreach ($weatherRawData[$key]['data'] as $weatherRawDataItem) {
                    $weatherData->add(
                        $this->mapWeatherData(
                            WeatherDataInterface::FORECAST,
                            $weatherRawDataItem,
                            $weatherDataLatitude,
                            $weatherDataLongitude
                        )
                    );
                }
            }
        }

        return $weatherData;
    }

    /**
     * @param  string  $type
     * @param  array  $weatherRawData
     * @param  float  $latitude
     * @param  float  $longitude
     * @return DarkSkyData
     */
    private function mapWeatherData(string $type, array $weatherRawData, float $latitude, float $longitude): DarkSkyData
    {
        $weatherData = new DarkSkyData();
        $weatherData->setType($type);

        if ($weatherData->getLatitude() === null) {
            $weatherData->setLatitude($latitude);
        }
        if ($weatherData->getLongitude() === null) {
            $weatherData->setLongitude($longitude);
        }

        if (array_key_exists('temperature', $weatherRawData)) {
            $weatherData->setTemperature($weatherRawData['temperature']);
        }
        if (array_key_exists('temperatureMin', $weatherRawData)) {
            $weatherData->setTemperatureMin($weatherRawData['temperatureMin']);
        }
        if (array_key_exists('temperatureMax', $weatherRawData)) {
            $weatherData->setTemperatureMax($weatherRawData['temperatureMax']);
        }
        if (array_key_exists('apparentTemperature', $weatherRawData)) {
            $weatherData->setFeelsLike($weatherRawData['apparentTemperature']);
        }
        if (array_key_exists('humidity', $weatherRawData)) {
            $weatherData->setHumidity($weatherRawData['humidity']);
        }
        if (array_key_exists('pressure', $weatherRawData)) {
            $weatherData->setPressure($weatherRawData['pressure']);
        }
        if (array_key_exists('windSpeed', $weatherRawData)) {
            $weatherData->setWindSpeed($weatherRawData['windSpeed']);
        }
        if (array_key_exists('windBearing', $weatherRawData)) {
            $weatherData->setWindDirection($weatherRawData['windBearing']);
        }
        if (array_key_exists('precipIntensity', $weatherRawData)) {
            $weatherData->setPrecipitation($weatherRawData['precipIntensity']);
        }
        if (array_key_exists('precipProbability', $weatherRawData)) {
            $weatherData->setPrecipitationProbability($weatherRawData['precipProbability']);
        }
        if (array_key_exists('cloudCover', $weatherRawData)) {
            $weatherData->setCloudCover($weatherRawData['cloudCover']);
        }

        $dateTime = (new DateTime())->setTimezone(new DateTimeZone('UTC'));
        if (array_key_exists('time', $weatherRawData)) {
            $dateTime->setTimestamp($weatherRawData['time']);
        }
        $weatherData->setUtcDateTime($dateTime);

        return $weatherData;
    }

    /**
     * @param  string  $units
     * @return string
     */
    private function mapUnits(string $units): string
    {
        return ($units === self::UNIT_METRIC) ? 'si' : 'us';
    }

    /**
     * @throws WeatherException
     */
    public function getAtDateTime(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en',
        string $type = WeatherDataInterface::HISTORICAL
    ): ?WeatherDataInterface {
        $querystring = sprintf(
            "https://api.darksky.net/forecast/%s/%s,%s,%s?lang=%s&units=%s&exclude=minutely,hourly,daily",
            $this->key,
            $latitude,
            $longitude,
            $dateTime->getTimestamp(),
            $lang,
            $this->mapUnits($units)
        );

        $weatherRawData = $this->getArrayFromQueryString($querystring);

        $weatherDataLatitude = $latitude;
        $weatherDataLongitude = $longitude;
        if (array_key_exists('latitude', $weatherRawData)) {
            $weatherDataLatitude = $weatherRawData['latitude'];
        }
        if (array_key_exists('longitude', $weatherRawData)) {
            $weatherDataLongitude = $weatherRawData['longitude'];
        }


        return $this->mapWeatherData(
            $type,
            $weatherRawData['currently'],
            $weatherDataLatitude,
            $weatherDataLongitude
        );
    }

}