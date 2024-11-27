<?php

declare(strict_types=1);

namespace App\Dto\Api\Response;

interface InterfaceResponseDto
{
    public function isError(): bool;

    public function getError(): string;

    public function getResults(): array;
}
