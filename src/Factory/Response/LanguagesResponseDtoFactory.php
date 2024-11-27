<?php

declare(strict_types=1);

namespace App\Factory\Response;

use App\Dto\Api\Response\LanguagesResponseDto;

/**
 * Class LanguagesResponseDtoFactory
 * @package App\Factory\Response
 */
class LanguagesResponseDtoFactory
{
    /**
     * @param string $error
     *
     * @return LanguagesResponseDto
     */
    public function createError(string $error): LanguagesResponseDto
    {
        return $this->createEmpty()->setError($error);
    }

    /**
     * @return LanguagesResponseDto
     */
    private function createEmpty(): LanguagesResponseDto
    {
        return (new LanguagesResponseDto());
    }

    /**
     * @param array $results
     *
     * @return LanguagesResponseDto
     */
    public function createSuccess(array $results): LanguagesResponseDto
    {
        return $this->createEmpty()->setResults($results);
    }
}
