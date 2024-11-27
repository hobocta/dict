<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace App\Client;

use App\Dto\Api\Response\LanguagesResponseDto;
use App\Dto\Api\Response\TranslationResponseDto;
use App\Exception\ClientResponseException;
use App\Factory\Response\LanguagesResponseDtoFactory;
use App\Factory\Response\TranslationResponseDtoFactory;
use App\Traits\LogException;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Class DictClient
 * @package App\Client
 */
readonly class DictClient
{
    use LogException;

    /**
     * DictClient constructor.
     *
     * @param string $appId
     * @param string $appKey
     * @param string $apiUrl
     * @param string $apiTimeout
     * @param LanguagesResponseDtoFactory $languagesResponseDtoFactory
     * @param TranslationResponseDtoFactory $translationResponseDtoFactory
     * @param HttpClientInterface $httpClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        private string $appId,
        private string $appKey,
        private string $apiUrl,
        private string $apiTimeout,
        private LanguagesResponseDtoFactory $languagesResponseDtoFactory,
        private TranslationResponseDtoFactory $translationResponseDtoFactory,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return LanguagesResponseDto
     */
    public function requestLanguages(): LanguagesResponseDto
    {
        try {
            $results = $this->request('GET', $this->getLanguagesUrl());

            return $this->languagesResponseDtoFactory->createSuccess($results);
        } catch (ExceptionInterface|ClientResponseException $e) {
            $this->logException($e);

            return $this->languagesResponseDtoFactory->createError($e->getMessage());
        }
    }

    /**
     * @param string $method
     * @param string $url
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws ClientResponseException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function request(string $method, string $url): array
    {
        $options = $this->getRequestOptions();

        $this->logger->debug('Request', [$method . ' ' . $url, 'options' => $options]);

        $response = $this->httpClient->request($method, $url, $options);

        $this->logger->debug(
            'Response',
            [
                'statusCode' => $response->getStatusCode(),
                'content' => $response->getContent(),
                'headers' => $response->getHeaders(),
            ]
        );

        $this->validateResponseDto($response);

        return $this->getResultsFromResponseDto($response);
    }

    /**
     * @return array
     */
    private function getRequestOptions(): array
    {
        return [
            'headers' => [
                'Accept: application/json',
                'app_id: ' . $this->appId,
                'app_key: ' . $this->appKey,
            ],
            'timeout' => $this->apiTimeout,
        ];
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     * @throws ClientResponseException
     * @throws TransportExceptionInterface
     */
    private function validateResponseDto(ResponseInterface $response): void
    {
        if ($response->getStatusCode() === 404) {
            throw new ClientResponseException('Not found');
        }

        if ($response->getStatusCode() !== 200) {
            throw new ClientResponseException('Http code is ' . $response->getStatusCode());
        }
    }

    /**
     * @param ResponseInterface $response
     *
     * @return array
     * @throws ClientExceptionInterface
     * @throws ClientResponseException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function getResultsFromResponseDto(ResponseInterface $response): array
    {
        try {
            $responseData = json_decode(
                $response->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            $this->logException($e);

            throw new ClientResponseException('Unable to decode response json');
        }

        if (empty($responseData['results'])) {
            throw new ClientResponseException('Empty results');
        }

        if (!is_array($responseData['results'])) {
            throw new ClientResponseException('Results is not array');
        }

        return $responseData['results'];
    }

    /**
     * @return string
     */
    private function getLanguagesUrl(): string
    {
        return $this->apiUrl . 'languages';
    }

    /**
     * @param string $sourceLanguageId
     * @param string $targetLanguageId
     * @param string $wordId
     *
     * @return TranslationResponseDto
     */
    public function requestTranslation(
        string $sourceLanguageId,
        string $targetLanguageId,
        string $wordId
    ): TranslationResponseDto {
        try {
            $results = $this->request(
                'GET',
                $this->getTranslationUrl($sourceLanguageId, $targetLanguageId, $wordId)
            );

            return $this->translationResponseDtoFactory->createSuccess($results);
        } catch (ExceptionInterface|ClientResponseException $e) {
            $this->logException($e);

            return $this->translationResponseDtoFactory->createError($e->getMessage());
        }
    }

    /**
     * @param string $sourceLanguageId
     * @param string $targetLanguageId
     * @param string $wordId
     *
     * @return string
     */
    private function getTranslationUrl(
        string $sourceLanguageId,
        string $targetLanguageId,
        string $wordId
    ): string {
        return $this->apiUrl . 'translations/' . $sourceLanguageId . '/' . $targetLanguageId . '/' . $wordId;
    }
}
