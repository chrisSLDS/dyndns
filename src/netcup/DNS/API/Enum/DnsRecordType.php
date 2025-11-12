<?php

declare(strict_types=1);

namespace netcup\DNS\API\Enum;

enum DnsRecordType: string
{
    case A = 'A';
    case AAAA = 'AAAA';
    case CNAME = 'CNAME';
    case MX = 'MX';
    case TXT = 'TXT';
    case NS = 'NS';
    case SRV = 'SRV';
    case PTR = 'PTR';
    case CAA = 'CAA';
}