<?php

namespace App\Dto\Request;

/**
 * Class WordDto
 * @package App\Dto\Request
 */
class WordDto
{
    /**
     * @var string|null
     */
    protected ?string $word;

    /**
     * @return string|null
     */
    public function getWord(): ?string
    {
        return $this->word;
    }

    /**
     * @param string|null $word
     *
     * @return $this
     */
    public function setWord(?string $word): self
    {
        $this->word = $word;

        return $this;
    }
}
