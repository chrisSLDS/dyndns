<?php

declare(strict_types=1);

namespace netcup\DNS\API\Enum;

enum DnsMode: string
{
    case BOTH = 'both';
    case WILDCARD = '*';
    case ROOT = '@';
}