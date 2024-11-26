<?php

declare(strict_types=1);

namespace App\Dto\Response;

/**
 * Class WordDto
 * @package App\Dto\Request
 */
class WordDto
{
    protected string $error = '';

    protected array $results = [];

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param string $error
     *
     * @return $this
     */
    public function setError(string $error): static
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param array $results
     *
     * @return $this
     */
    public function setResults(array $results): static
    {
        $this->results = $results;

        return $this;
    }
}
