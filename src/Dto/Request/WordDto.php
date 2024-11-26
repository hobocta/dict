<?php

declare(strict_types=1);

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class WordDto
 * @package App\Dto\Response
 */
class WordDto
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Length(min: 1, max: 45)]
    #[Assert\Regex('/^[A-z]+$/')]
    protected string $word;

    /**
     * @return string
     */
    public function getWord(): string
    {
        return $this->word;
    }

    /**
     * @param string $word
     *
     * @return $this
     * @noinspection PhpUnused
     */
    public function setWord(string $word): self
    {
        $this->word = mb_strtolower($word);

        return $this;
    }
}
