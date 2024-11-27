<?php

declare(strict_types=1);

namespace App\Traits;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Trait LogException
 * @package App\Traits
 */
trait LogException
{
    private readonly LoggerInterface $logger;

    /**
     * @param Throwable $e
     *
     * @return void
     */
    protected function logException(Throwable $e): void
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
