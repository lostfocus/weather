<?php
declare(strict_types=1);

namespace Lostfocus\Weather\Common;

use Http\Client\HttpClient;
use Http\Discovery\Psr17FactoryDiscovery;
use JsonException;
use Lostfocus\Weather\Exceptions\InvalidCredentials;
use Lostfocus\Weather\Exceptions\QuotaExceeded;
use Lostfocus\Weather\Exceptions\ServerException;
use Lostfocus\Weather\Exceptions\WeatherException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected HttpClient $client;
    protected RequestFactoryInterface $requestFactory;

    /**
     * @param  HttpClient  $client
     * @param  RequestFactoryInterface|null  $requestFactory
     */
    public function __construct(HttpClient $client, ?RequestFactoryInterface $requestFactory = null)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory ?: Psr17FactoryDiscovery::findRequestFactory();
    }

    protected function getRequest(string $method, string $url): RequestInterface
    {
        return $this->requestFactory->createRequest($method, $url);
    }

    /**
     * @param  string  $querystring
     * @return array
     * @throws WeatherException
     */
    protected function getArrayFromQueryString(string $querystring): array
    {
        $request = $this->getRequest('GET', $querystring);

        $response = $this->getParsedResponse($request);

        try {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new WeatherException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws WeatherException
     */
    protected function getParsedResponse(RequestInterface $request): string
    {
        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new WeatherException($e->getMessage(), $e->getCode(), $e);
        }

        $statusCode = $response->getStatusCode();
        if (401 === $statusCode || 403 === $statusCode) {
            throw new InvalidCredentials();
        }

        if (429 === $statusCode) {
            throw new QuotaExceeded();
        }

        if ($statusCode >= 300) {
            throw new ServerException();
        }

        $body = (string)$response->getBody();
        if ('' === $body) {
            throw new ServerException();
        }

        return $body;
    }
}