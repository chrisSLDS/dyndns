<?php

declare(strict_types=1);

namespace netcup\DNS\API\Interface;

interface LoggerInterface
{
    public function log(string $message): void;
    public function debug(string $message): void;
    public function error(string $message): void;
}