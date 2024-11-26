<?php

declare(strict_types=1);

namespace App\Client;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class DictClient
 * @package App\Client
 */
readonly class DictClient
{
    /**
     * DictClient constructor.
     *
     * @param string $sourceLang
     * @param string $appId
     * @param string $appKey
     * @param HttpClientInterface $httpClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        private string $sourceLang,
        private string $appId,
        private string $appKey,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param string $word
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function requestEntries(string $word): array
    {
        $url = $this->getEntriesUrl($word);
        $options = $this->getEntriesOptions();

        $this->logger->debug('Request', ['GET' . $url, 'options' => $options]);

        $response = $this->httpClient->request('GET', $url, $options);

        $statusCode = $response->getStatusCode();
        $content = $response->getContent();
        $headers = $response->getHeaders();

        $this->logger->debug(
            'Response',
            ['statusCode' => $statusCode, 'content' => $content, 'headers' => $headers]
        );

        return [$statusCode, $content];
    }

    /**
     * @param string $word
     *
     * @return string
     */
    private function getEntriesUrl(string $word): string
    {
        return sprintf(
            'https://od-api-sandbox.oxforddictionaries.com/api/v2/entries/%s/%s',
            $this->sourceLang,
            $word
        );
    }

    /**
     * @return array
     */
    private function getEntriesOptions(): array
    {
        return [
            'headers' => [
                'Accept: application/json',
                'app_id: ' . $this->appId,
                'app_key: ' . $this->appKey,
            ],
            'timeout' => 15,
        ];
    }
}
