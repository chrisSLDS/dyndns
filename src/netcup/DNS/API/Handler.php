<?php

declare(strict_types=1);

namespace netcup\DNS\API;

require_once __DIR__ . '/Soap.php';
use netcup\DNS\API\Exception\ApiException;
use netcup\DNS\API\Exception\ConfigurationException;
use netcup\DNS\API\Exception\PayloadException;
use netcup\DNS\API\Enum\DnsRecordType;
use RuntimeException;
use SoapFault;

final class Handler
{
    private Payload $payload;

    public function __construct(array $config, array $payload)
    {
        try {
            $this->payload = Payload::fromArray($payload);
        } catch (ConfigurationException|PayloadException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        if (
            Config::getUsername() !== $this->payload->getUser() ||
            Config::getPassword() !== $this->payload->getPassword()
        ) {
            throw PayloadException::invalidCredentials();
        }
    }

    private function doLog(string $msg): self
    {
        // Log to DNS updates log
        Logger::logDnsUpdate($this->payload->getDomain(), $msg);

        Logger::debug(sprintf('[DEBUG] %s %s', $msg, PHP_EOL));

        return $this;
    }

    public function doRun(): self
    {
        try {
            $clientRequestId = md5($this->payload->getDomain() . time());
            $dnsClient = new Soap\DomainWebserviceSoapClient();

            // Login
            $loginResponse = new ApiResponse($dnsClient->login(
                Config::getCustomerId(),
                Config::getApiKey(),
                Config::getApiPassword(),
                $clientRequestId
            ));

            if (!$loginResponse->isSuccess()) {
                throw new RuntimeException(sprintf('api login failed: %s', $loginResponse->getMessage()));
            }
            $this->doLog('api login successful');

            // Get DNS Records
            $dnsResponse = new ApiResponse($dnsClient->infoDnsRecords(
                $this->payload->getHostname(),
                Config::getCustomerId(),
                Config::getApiKey(),
                $loginResponse->getSessionId(),
                $clientRequestId
            ));

            $changes = false;
            $dnsRecords = $dnsResponse->getDnsRecords() ?? [];

            foreach ($dnsRecords as $record) {
                $recordHostnameReal = (!in_array($record->hostname, $this->payload->getMatcher(), true)) 
                    ? $record->hostname . '.' . $this->payload->getHostname() 
                    : $this->payload->getHostname();

                if ($recordHostnameReal === $this->payload->getDomain()) {
                    // Update A Record if exists and IP has changed
                    if (DnsRecordType::A->value === $record->type && $this->payload->getIpv4() &&
                        ($this->payload->isForce() || $record->destination !== $this->payload->getIpv4())
                    ) {
                        $record->destination = $this->payload->getIpv4();
                        $this->doLog(sprintf(
                            'IPv4 for %s set to %s', 
                            $record->hostname . '.' . $this->payload->getHostname(), 
                            $this->payload->getIpv4()
                        ));
                        $changes = true;
                    }

                    // Update AAAA Record if exists and IP has changed
                    if (DnsRecordType::AAAA->value === $record->type && $this->payload->getIpv6() &&
                        ($this->payload->isForce() || $record->destination !== $this->payload->getIpv6())
                    ) {
                        $record->destination = $this->payload->getIpv6();
                        $this->doLog(sprintf(
                            'IPv6 for %s set to %s', 
                            $record->hostname . '.' . $this->payload->getHostname(), 
                            $this->payload->getIpv6()
                        ));
                        $changes = true;
                    }
                }
            }

            // Update DNS records if needed
            if ($changes) {
                $recordSet = new Soap\Dnsrecordset();
                /** @var array<Dnsrecord> $dnsRecords */
                $recordSet->dnsrecords = $dnsRecords;

                $updateResponse = new ApiResponse($dnsClient->updateDnsRecords(
                    $this->payload->getHostname(),
                    Config::getCustomerId(),
                    Config::getApiKey(),
                    $loginResponse->getSessionId(),
                    $clientRequestId,
                    $recordSet
                ));

                if (!$updateResponse->isSuccess()) {
                    throw new RuntimeException(sprintf('dns update failed: %s', $updateResponse->getMessage()));
                }

                $this->doLog('dns recordset updated');
            } else {
                $this->doLog('dns recordset NOT updated (no changes)');
            }

            // Logout
            $logoutResponse = new ApiResponse($dnsClient->logout(
                Config::getCustomerId(),
                Config::getApiKey(),
                $loginResponse->getSessionId(),
                $clientRequestId
            ));

            if (!$logoutResponse->isSuccess()) {
                throw new RuntimeException(sprintf('api logout failed: %s', $logoutResponse->getMessage()));
            }

            $this->doLog('api logout successful');
            
        } catch (SoapFault $e) {
            throw new RuntimeException('SOAP communication error: ' . $e->getMessage(), 0, $e);
        }

        return $this;
    }
}