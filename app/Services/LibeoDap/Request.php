<?php

namespace NeoClocking\Services\LibeoDap;

use GuzzleHttp\Client;
use Illuminate\Foundation\Application;
use NeoClocking\Exceptions\UnsupportedFormatException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Request
{
    const JSON = 'json';

    protected $outputFormat = self::JSON;

    protected $supportedFormat = [self::JSON];

    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function execute($uri, $method = 'GET')
    {
        if (!in_array($this->outputFormat, $this->supportedFormat)) {
            throw new UnsupportedFormatException("The [{$this->outputFormat}] is not supported.");
        }

        $client = $this->createClient();
        $params = $this->prepareParams();

        $response = $this->parseResponse($client->request($method, $uri, $params));

        return new Response($response);
    }

    protected function parseResponse(ResponseInterface $response)
    {
        $method = 'parse' . ucfirst($this->outputFormat);

        return $this->$method($response);
    }

    protected function parseJson(ResponseInterface $response)
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    protected function createClient()
    {
        return new Client([
            'base_uri' => env('LDAP_WEB_HOST'),
        ]);
    }

    protected function prepareParams()
    {
        return [
            'query' => $this->buildQuery(),
            'headers' => $this->buildHeaders(),
            'verify' => $this->shouldVerify(),
        ];
    }

    protected function buildQuery()
    {
        return [
            'format' => $this->outputFormat,
        ];
    }

    protected function buildHeaders()
    {
        return [
            'Authorization' => 'Token token="' . env('LDAP_WEB_TOKEN') . '"',
        ];
    }

    protected function shouldVerify()
    {
        return $this->app->environment('production');
    }
}
