<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\DarkSky;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Http\Client\HttpClient;
use JsonException;
use Lostfocus\Weather\Common\AbstractProvider;
use Lostfocus\Weather\Common\WeatherDataCollectionInterface;
use Lostfocus\Weather\Common\WeatherDataInterface;
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
        $darkSkyUnits = ($units === self::UNIT_METRIC) ? 'si' : 'us';

        $querystring = sprintf(
            "https://api.darksky.net/forecast/%s/%s,%s,%s?lang=%s&units=%s&exclude=minutely,hourly,daily",
            $this->key,
            $latitude,
            $longitude,
            time(),
            $lang,
            $darkSkyUnits
        );

        $request = $this->getRequest('GET', $querystring);

        $response = $this->getParsedResponse($request);

        try {
            $weatherRawData = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new WeatherException($e->getMessage(), $e->getCode(), $e);
        }

        $type = WeatherDataInterface::CURRENT;

        $weatherData = new DarkSkyData();
        $weatherData->setType($type);
        if (array_key_exists('latitude', $weatherRawData)) {
            $weatherData->setLatitude($weatherRawData['latitude']);
        }
        if (array_key_exists('longitude', $weatherRawData)) {
            $weatherData->setLongitude($weatherRawData['longitude']);
        }

        if ($weatherData->getLatitude() === null) {
            $weatherData->setLatitude($latitude);
        }
        if ($weatherData->getLongitude() === null) {
            $weatherData->setLongitude($longitude);
        }

        if (array_key_exists('currently', $weatherRawData) && is_array($weatherRawData['currently'])) {
            if (array_key_exists('temperature', $weatherRawData['currently'])) {
                $weatherData->setTemperature($weatherRawData['currently']['temperature']);
            }
            if (array_key_exists('apparentTemperature', $weatherRawData['currently'])) {
                $weatherData->setFeelsLike($weatherRawData['currently']['apparentTemperature']);
            }
            if (array_key_exists('humidity', $weatherRawData['currently'])) {
                $weatherData->setHumidity($weatherRawData['currently']['humidity']);
            }
            if (array_key_exists('pressure', $weatherRawData['currently'])) {
                $weatherData->setPressure($weatherRawData['currently']['pressure']);
            }
            if (array_key_exists('windSpeed', $weatherRawData['currently'])) {
                $weatherData->setWindSpeed($weatherRawData['currently']['windSpeed']);
            }
            if (array_key_exists('windBearing', $weatherRawData['currently'])) {
                $weatherData->setWindDirection($weatherRawData['currently']['windBearing']);
            }
            if (array_key_exists('precipIntensity', $weatherRawData['currently'])) {
                $weatherData->setPrecipitation($weatherRawData['currently']['precipIntensity']);
            }
            if (array_key_exists('cloudCover', $weatherRawData['currently'])) {
                $weatherData->setCloudCover($weatherRawData['currently']['cloudCover']);
            }
        }

        $dateTime = (new DateTime())->setTimezone(new DateTimeZone('UTC'));
        if (array_key_exists('currently', $weatherRawData) &&
            is_array($weatherRawData['currently']) &&
            array_key_exists('time', $weatherRawData['currently'])
        ) {
            $dateTime->setTimestamp($weatherRawData['currently']['time']);
        }
        $weatherData->setUtcDateTime($dateTime);

        return $weatherData;
    }

    public function getForecast(
        float $latitude,
        float $longitude,
        DateTimeInterface $dateTime,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): ?WeatherDataInterface {
        // TODO: Implement getForecast() method.
    }

    public function getForecastCollection(
        float $latitude,
        float $longitude,
        string $units = self::UNIT_METRIC,
        string $lang = 'en'
    ): WeatherDataCollectionInterface {
        // TODO: Implement getForecastCollection() method.
    }
}