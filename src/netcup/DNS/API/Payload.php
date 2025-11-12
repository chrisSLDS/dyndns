<?php

declare(strict_types=1);

namespace netcup\DNS\API;

use netcup\DNS\API\Enum\DnsMode;
use netcup\DNS\API\Exception\PayloadException;

readonly class Payload
{
    private ?DnsMode $mode;

    public function __construct(
        private string $user,
        private string $password,
        private string $domain,
        ?string $mode = null,
        private ?string $ipv4 = null,
        private ?string $ipv6 = null,
        private bool $force = false,
    ) {
        $this->mode = $mode ? DnsMode::from($mode) : null;
        $this->validate();
    }

    /**
     * Create a Payload instance from an array
     * 
     * @param array<string, mixed> $payload
     * @throws PayloadException
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            user: (string) ($payload['user'] ?? ''),
            password: (string) ($payload['password'] ?? ''),
            domain: (string) ($payload['domain'] ?? ''),
            mode: isset($payload['mode']) ? (string) $payload['mode'] : null,
            ipv4: isset($payload['ipv4']) ? (string) $payload['ipv4'] : null,
            ipv6: isset($payload['ipv6']) ? (string) $payload['ipv6'] : null,
            force: (bool) ($payload['force'] ?? false),
        );
    }

    /**
     * @throws PayloadException
     */
    private function validate(): void
    {
        if (empty($this->user)) {
            throw PayloadException::missingField('user');
        }
        if (empty($this->password)) {
            throw PayloadException::missingField('password');
        }
        if (empty($this->domain)) {
            throw PayloadException::missingField('domain');
        }
        if (empty($this->ipv4) && empty($this->ipv6)) {
            throw PayloadException::missingField('ipv4 or ipv6');
        }
        if (!empty($this->ipv4) && !$this->isValidIpv4()) {
            throw PayloadException::invalidField('ipv4', 'Invalid IPv4 address');
        }
        if (!empty($this->ipv6) && !$this->isValidIpv6()) {
            throw PayloadException::invalidField('ipv6', 'Invalid IPv6 address');
        }
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @return array{0: string}|array{0: string, 1: string}
     */
    public function getMatcher(): array
    {
        return match($this->mode) {
            DnsMode::BOTH => ['@', '*'],
            DnsMode::WILDCARD => ['*'],
            default => ['@'],
        };
    }

    /**
     * Get the registrable domain name
     * 
     * There is no good way to get the correct "registrable" Domain without external libs!
     * @see https://github.com/jeremykendall/php-domain-parser
     *
     * This method is still tricky, because:
     * - works: nas.tld.com
     * - works: nas.tld.de
     * - works: tld.com
     * - failed: nas.tld.co.uk
     * - failed: nas.home.tld.de
     */
    public function getHostname(): string
    {
        // hack if top level domain are used for dynDNS
        if (1 === substr_count($this->domain, '.')) {
            return $this->domain;
        }

        $domainParts = explode('.', $this->domain);
        array_shift($domainParts); // remove sub domain
        return implode('.', $domainParts);
    }

    public function getIpv4(): ?string
    {
        return $this->ipv4;
    }

    private function isValidIpv4(): bool
    {
        return !empty($this->ipv4) && 
            filter_var($this->ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function getIpv6(): ?string
    {
        return $this->ipv6;
    }

    private function isValidIpv6(): bool
    {
        return !empty($this->ipv6) && 
            filter_var($this->ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function isForce(): bool
    {
        return $this->force;
    }
}