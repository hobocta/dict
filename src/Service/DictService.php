<?php

declare(strict_types=1);

namespace App\Service;

use App\Client\DictClient;
use App\Dto\Response\WordDto;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * Class DictService
 * @package App\Service
 */
readonly class DictService
{
    /**
     * DictService constructor.
     *
     * @param CacheItemPoolInterface $cacheItemPool
     * @param DictClient $dictClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CacheItemPoolInterface $cacheItemPool,
        private DictClient $dictClient,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param string $word
     *
     * @return WordDto
     */
    public function getWordDtoCached(string $word): WordDto
    {
        try {
            $cacheKey = sprintf('dict.entries.%s', $word);

            $cacheItem = $this->cacheItemPool->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $wordDto = $this->getWordDto($word);

            $this->cacheItemPool->save(
                $cacheItem
                    ->set($wordDto)
                    ->expiresAfter(
                        !empty($wordDto->getError())
                            ? 3600  // 1 hour
                            : 31536000 // 1 year
                    )
            );
        } catch (InvalidArgumentException $e) {
            $wordDto = $this->createWordDtoError($e->getMessage());
        }

        return $wordDto;
    }

    /**
     * @param string $word
     *
     * @return WordDto
     */
    public function getWordDto(string $word): WordDto
    {
        try {
            [$statusCode, $content] = $this->dictClient->requestEntries($word);

            return $this->createWordDto($statusCode, $content);
        } catch (ExceptionInterface $e) {
            $this->logException($e);

            return $this->createWordDtoError($e->getMessage());
        }
    }

    /**
     * @param int $statusCode
     * @param string $content
     *
     * @return WordDto
     */
    private function createWordDto(int $statusCode, string $content): WordDto
    {
        if ($statusCode === 404) {
            return $this->createWordDtoError('Word not found');
        }

        if ($statusCode !== 200) {
            return $this->createWordDtoError('Http code is ' . $statusCode);
        }

        $responseJson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->createWordDtoError('Incorrect gateway response');
        }

        if (empty($responseJson['results'])) {
            return $this->createWordDtoError('Empty results');
        }

        if (!is_array($responseJson['results'])) {
            return $this->createWordDtoError('Results is not array');
        }

        return $this->createWordDtoSuccess($responseJson['results']);
    }

    /**
     * @param string $error
     *
     * @return WordDto
     */
    private function createWordDtoError(string $error): WordDto
    {
        return $this->createEmptyWordDto()->setError($error);
    }

    /**
     * @return WordDto
     */
    private function createEmptyWordDto(): WordDto
    {
        return (new WordDto());
    }

    /**
     * @param array $results
     *
     * @return WordDto
     */
    private function createWordDtoSuccess(array $results): WordDto
    {
        return $this->createEmptyWordDto()->setResults($results);
    }

    /**
     * @param Exception $e
     *
     * @return void
     */
    private function logException(Exception $e): void
    {
        $this->logger->error(
            $e->getMessage(),
            [
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]
        );
    }
}
