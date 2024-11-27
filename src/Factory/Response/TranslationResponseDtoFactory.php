<?php

declare(strict_types=1);

namespace App\Factory\Response;

use App\Dto\Api\Response\TranslationResponseDto;

/**
 * Class TranslationResponseDtoFactory
 * @package App\Factory\Response
 */
class TranslationResponseDtoFactory
{
    /**
     * @param string $error
     *
     * @return TranslationResponseDto
     */
    public function createError(string $error): TranslationResponseDto
    {
        return $this->createEmpty()->setError($error);
    }

    /**
     * @return TranslationResponseDto
     */
    private function createEmpty(): TranslationResponseDto
    {
        return (new TranslationResponseDto());
    }

    /**
     * @param array $results
     *
     * @return TranslationResponseDto
     */
    public function createSuccess(array $results): TranslationResponseDto
    {
        return $this->createEmpty()->setResults($results);
    }
}
