<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Provider\OpenWeatherMap;

use DateTime;
use DateTimeZone;
use Http\Client\HttpClient;
use JsonException;
use Lostfocus\Weather\Common\AbstractProvider;
use Lostfocus\Weather\Common\WeatherDataInterface;
use Lostfocus\Weather\Exceptions\WeatherException;
use Psr\Http\Message\RequestFactoryInterface;

class OpenWeatherMap extends AbstractProvider
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

        $querystring = sprintf(
            "https://api.openweathermap.org/data/2.5/weather?lat=%s&lon=%s&appid=%s&units=%s&lang=%s",
            $latitude,
            $longitude,
            $this->key,
            $units,
            $lang
        );

        $request = $this->getRequest('GET', $querystring);

        $response = $this->getParsedResponse($request);

        try {
            $weatherRawData = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new WeatherException($e->getMessage(), $e->getCode(), $e);
        }

        $weatherData = new OpenWeatherMapData();
        $weatherData->setType(WeatherDataInterface::CURRENT);

        if (array_key_exists('coord', $weatherRawData) && is_array($weatherRawData['coord'])) {
            if (array_key_exists('lat', $weatherRawData['coord'])) {
                $weatherData->setLatitude($weatherRawData['coord']['lat']);
            } else {
                $weatherData->setLatitude($latitude);
            }
            if (array_key_exists('lon', $weatherRawData['coord'])) {
                $weatherData->setLongitude($weatherRawData['coord']['lon']);
            } else {
                $weatherData->setLongitude($longitude);
            }
        }

        if (array_key_exists('main', $weatherRawData) && is_array($weatherRawData['main'])) {
            if (array_key_exists('temp', $weatherRawData['main'])) {
                $weatherData->setTemperature($weatherRawData['main']['temp']);
            }
            if (array_key_exists('feels_like', $weatherRawData['main'])) {
                $weatherData->setFeelsLike($weatherRawData['main']['feels_like']);
            }
            if (array_key_exists('temp_min', $weatherRawData['main'])) {
                $weatherData->setTemperatureMin($weatherRawData['main']['temp_min']);
            }
            if (array_key_exists('temp_max', $weatherRawData['main'])) {
                $weatherData->setTemperatureMax($weatherRawData['main']['temp_max']);
            }
            if (array_key_exists('pressure', $weatherRawData['main'])) {
                $weatherData->setPressure($weatherRawData['main']['pressure']);
            }
            if (array_key_exists('humidity', $weatherRawData['main'])) {
                $weatherData->setHumidity($weatherRawData['main']['humidity'] / 100);
            }
        }

        if (array_key_exists('wind', $weatherRawData) && is_array($weatherRawData['wind'])) {
            if (array_key_exists('speed', $weatherRawData['wind'])) {
                $weatherData->setWindSpeed($weatherRawData['wind']['speed']);
            }
            if (array_key_exists('deg', $weatherRawData['wind'])) {
                $weatherData->setWindDirection($weatherRawData['wind']['deg']);
            }
        }

        $dateTime = (new DateTime())->setTimezone(new DateTimeZone('UTC'));
        if (array_key_exists('dt', $weatherRawData)) {
            $dateTime->setTimestamp($weatherRawData['dt']);
        }
        $weatherData->setUtcDateTime($dateTime);

        return $weatherData;
    }
}