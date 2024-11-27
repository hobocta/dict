<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace App\Service;

use App\Client\DictClient;
use App\Dto\Api\Response\InterfaceResponseDto;
use App\Dto\Api\Response\LanguagesResponseDto;
use App\Dto\Api\Response\TranslationResponseDto;
use App\Factory\Response\LanguagesResponseDtoFactory;
use App\Factory\Response\TranslationResponseDtoFactory;
use App\Traits\LogException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Class DictService
 * @package App\Service
 */
readonly class DictService
{
    use LogException;

    /**
     * DictService constructor.
     *
     * @param DictClient $dictClient
     * @param LanguagesResponseDtoFactory $languagesResponseDtoFactory
     * @param TranslationResponseDtoFactory $translationResponseDtoFactory
     * @param CacheItemPoolInterface $cacheItemPool
     * @param LoggerInterface $logger
     */
    public function __construct(
        private DictClient $dictClient,
        private LanguagesResponseDtoFactory $languagesResponseDtoFactory,
        private TranslationResponseDtoFactory $translationResponseDtoFactory,
        private CacheItemPoolInterface $cacheItemPool,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @param string $sourceLanguageId
     * @param string $targetLanguageId
     * @param string $wordId
     *
     * @return TranslationResponseDto
     */
    public function getWordDtoCached(
        string $sourceLanguageId,
        string $targetLanguageId,
        string $wordId
    ): TranslationResponseDto {
        try {
            $cacheKey = sprintf('dict.entries.%s-%s.%s', $sourceLanguageId, $targetLanguageId, $wordId);

            $cacheItem = $this->cacheItemPool->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $wordResponseDto = $this->dictClient->requestTranslation($sourceLanguageId, $targetLanguageId, $wordId);

            $this->cacheItemPoolSave($cacheItem, $wordResponseDto);

            return $wordResponseDto;
        } catch (InvalidArgumentException $e) {
            return $this->translationResponseDtoFactory->createError($e->getMessage());
        }
    }

    /**
     * @param CacheItemInterface $cacheItem
     * @param InterfaceResponseDto $responseDto
     *
     * @return void
     */
    private function cacheItemPoolSave(CacheItemInterface $cacheItem, InterfaceResponseDto $responseDto): void
    {
        $this->cacheItemPool->save(
            $cacheItem
                ->set($responseDto)
                ->expiresAfter(
                    $responseDto->isError()
                        ? 3600  // 1 hour
                        : 31536000 // 1 year
                )
        );
    }

    /**
     * @return LanguagesResponseDto
     */
    public function getLanguagesDtoCached(): LanguagesResponseDto
    {
        try {
            $cacheKey = 'dict.entries.languages';

            $cacheItem = $this->cacheItemPool->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $languagesResponseDto = $this->dictClient->requestLanguages();

            $this->cacheItemPoolSave($cacheItem, $languagesResponseDto);

            return $languagesResponseDto;
        } catch (InvalidArgumentException $e) {
            return $this->languagesResponseDtoFactory->createError($e->getMessage());
        }
    }
}
