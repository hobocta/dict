<?php

declare(strict_types=1);

namespace App\Dto\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class TranslationRequestDto
 * @package App\Dto\Response
 */
class TranslationRequestDto
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(min: 2, max: 3)]
    #[Assert\Regex('/^[a-z]+$/')]
    protected string $sourceLanguageId;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(min: 2, max: 3)]
    #[Assert\Regex('/^[a-z]+$/')]
    protected string $targetLanguageId;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(min: 1, max: 45)]
    protected string $wordId;

    /**
     * @return string
     */
    public function getSourceLanguageId(): string
    {
        return $this->sourceLanguageId;
    }

    /**
     * @param string $sourceLanguageId
     *
     * @return $this
     * @noinspection PhpUnused
     */
    public function setSourceLanguageId(string $sourceLanguageId): self
    {
        $this->sourceLanguageId = mb_strtolower($sourceLanguageId);

        return $this;
    }

    /**
     * @return string
     */
    public function getTargetLanguageId(): string
    {
        return $this->targetLanguageId;
    }

    /**
     * @param string $targetLanguageId
     *
     * @return $this
     * @noinspection PhpUnused
     */
    public function setTargetLanguageId(string $targetLanguageId): self
    {
        $this->targetLanguageId = mb_strtolower($targetLanguageId);

        return $this;
    }

    /**
     * @return string
     */
    public function getWordId(): string
    {
        return $this->wordId;
    }

    /**
     * @param string $wordId
     *
     * @return $this
     * @noinspection PhpUnused
     */
    public function setWordId(string $wordId): self
    {
        $this->wordId = $wordId;

        return $this;
    }
}
