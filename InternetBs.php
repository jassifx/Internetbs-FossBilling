<?php
declare(strict_types=1);

namespace FOSSBilling\Registrar\Adapter;

use FOSSBilling\Registrar\AdapterAbstract;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;

class InternetBs extends AdapterAbstract
{
    protected string $apiKey;
    protected string $password;
    protected string $endpoint;
    protected HttpClientInterface $client;

    /**
     * Module configuration metadata and form definition
     */
    public static function getConfig(): array
    {
        return [
            'meta' => [
                'name' => 'internetbs',
                'label' => 'InternetBs Registrar',
                'description' => 'Integration module for Internet.bs domain registrar',
                'author' => 'Jaspreet Singh',
                'version' => '1.0.0',
            ],
            'form' => [
                'apiKey' => [
                    'type' => 'text',
                    'label' => 'API Key',
                    'required' => true,
                ],
                'password' => [
                    'type' => 'password',
                    'label' => 'API Password',
                    'required' => true,
                ],
                'testMode' => [
                    'type' => 'boolean',
                    'label' => 'Test Mode',
                    'description' => 'Use test API endpoint',
                    'default' => true,
                ],
            ],
        ];
    }

    /**
     * Constructor sets credentials and HTTP client
     */
    public function __construct(protected array $config)
    {
        $this->apiKey = (string)($config['apiKey'] ?? '');
        $this->password = (string)($config['password'] ?? '');
        $this->endpoint = ($config['testMode'] ?? true)
            ? 'https://testapi.internet.bs'
            : 'https://api.internet.bs';
        $this->client = HttpClient::create([
            'base_uri' => $this->endpoint,
            'timeout' => 30,
        ]);
    }

    /**
     * Internal helper to perform API requests and return decoded JSON
     */
    protected function request(string $resource, array $params = []): array
    {
        $params = array_merge($params, [
            'ApiKey' => $this->apiKey,
            'Password' => $this->password,
            'ResponseFormat' => 'JSON',
        ]);

        $response = $this->client->request('GET', $resource, ['query' => $params]);
        $data = $response->toArray(false);

        if (!isset($data['STATUS'])) {
            throw new \RuntimeException('Invalid API response');
        }
        if ($data['STATUS'] === 'FAILURE') {
            $msg = $data['STATUS_MESSAGE'] ?? 'Unknown error';
            throw new \RuntimeException("API Error: {$msg}");
        }
        return $data;
    }

    // -------------------------------------------------------------------------
    // TLDs
    // -------------------------------------------------------------------------

    public function getTlds(): array
    {
        $data = $this->request('/Account/PriceList/Get', ['version' => 2]);
        $tlds = [];
        foreach ($data as $key => $item) {
            if (is_array($item) && isset($item['type'])) {
                $ext = ltrim($item['type'], '.');
                if ($ext && !in_array($ext, $tlds, true)) {
                    $tlds[] = $ext;
                }
            }
        }
        return $tlds;
    }

    // -------------------------------------------------------------------------
    // Domain operations
    // -------------------------------------------------------------------------

    public function checkAvailability(string $domain): bool
    {
        $data = $this->request('/Domain/Check', ['Domain' => $domain]);
        return $data['STATUS'] === 'AVAILABLE';
    }

    public function registerDomain(string $domain, array $params): array
    {
        $params['Domain'] = $domain;
        return $this->request('/Domain/Create', $params);
    }

    public function renewDomain(string $domain, int $years): array
    {
        return $this->request('/Domain/Renew', [
            'Domain' => $domain,
            'Period' => "{$years}Y",
        ]);
    }

    public function getDomainInfo(string $domain): array
    {
        return $this->request('/Domain/Info', ['Domain' => $domain]);
    }

    public function transferDomain(string $domain, array $params): array
    {
        $params['Domain'] = $domain;
        return $this->request('/Domain/Transfer/Initiate', $params);
    }

    public function transferRetry(string $domain, array $params = []): array
    {
        $params['Domain'] = $domain;
        return $this->request('/Domain/Transfer/Retry', $params);
    }

    public function transferCancel(string $domain): array
    {
        return $this->request('/Domain/Transfer/Cancel', ['Domain' => $domain]);
    }

    public function transferResendAuth(string $domain): array
    {
        return $this->request('/Domain/Transfer/ResendAuthEmail', ['Domain' => $domain]);
    }

    public function transferHistory(string $domain): array
    {
        return $this->request('/Domain/Transfer/History', ['Domain' => $domain]);
    }

    public function transferAwayApprove(string $domain): array
    {
        return $this->request('/Domain/TransferAway/Approve', ['Domain' => $domain]);
    }

    public function transferAwayReject(string $domain, string $reason): array
    {
        return $this->request('/Domain/TransferAway/Reject', [
            'Domain' => $domain,
            'Reason' => $reason,
        ]);
    }

    public function tradeDomain(string $domain, array $params): array
    {
        $params['Domain'] = $domain;
        return $this->request('/Domain/Trade', $params);
    }

    public function enableRegistrarLock(string $domain): array
    {
        return $this->request('/Domain/RegistrarLock/Enable', ['Domain' => $domain]);
    }

    public function disableRegistrarLock(string $domain): array
    {
        return $this->request('/Domain/RegistrarLock/Disable', ['Domain' => $domain]);
    }

    public function registrarLockStatus(string $domain): array
    {
        return $this->request('/Domain/RegistrarLock/Status', ['Domain' => $domain]);
    }

    public function enablePrivateWhois(string $domain, string $type = 'FULL'): array
    {
        return $this->request('/Domain/PrivateWhois/Enable', ['Domain' => $domain, 'Type' => $type]);
    }

    public function disablePrivateWhois(string $domain): array
    {
        return $this->request('/Domain/PrivateWhois/Disable', ['Domain' => $domain]);
    }

    public function privateWhoisStatus(string $domain): array
    {
        return $this->request('/Domain/PrivateWhois/Status', ['Domain' => $domain]);
    }

    public function pushDomain(string $domain, string $destination): array
    {
        return $this->request('/Domain/Push', ['Domain' => $domain, 'Destination' => $destination]);
    }

    public function changeTagDotUK(string $domain, string $newTag, ?string $accountId = null): array
    {
        $params = ['Domain' => $domain, 'NewTag' => $newTag];
        if ($accountId) {
            $params['AccountId'] = $accountId;
        }
        return $this->request('/Domain/ChangeTag/DotUK', $params);
    }

    public function listDomains(array $filter = []): array
    {
        return $this->request('/Domain/List', $filter);
    }

    public function restoreDomain(string $domain): array
    {
        return $this->request('/Domain/Restore', ['Domain' => $domain]);
    }

    public function countDomains(): array
    {
        return $this->request('/Domain/Count');
    }

    public function getRegistrantVerificationInfo(string $domain): array
    {
        return $this->request('/Domain/RegistrantVerification/Info', ['Domain' => $domain]);
    }

    public function sendRegistrantVerification(string $domain): array
    {
        return $this->request('/Domain/RegistrantVerification/Send', ['Domain' => $domain]);
    }

    // -------------------------------------------------------------------------
    // Host (child) name server operations
    // -------------------------------------------------------------------------

    public function hostCreate(string $host, array $ipList): array
    {
        return $this->request('/Domain/Host/Create', [
            'Host' => $host,
            'IP_List' => implode(',', $ipList),
        ]);
    }

    public function hostInfo(string $host): array
    {
        return $this->request('/Domain/Host/Info', ['Host' => $host]);
    }

    public function hostUpdate(string $host, array $ipList): array
    {
        return $this->request('/Domain/Host/Update', [
            'Host' => $host,
            'IP_List' => implode(',', $ipList),
        ]);
    }

    public function hostDelete(string $host): array
    {
        return $this->request('/Domain/Host/Delete', ['Host' => $host]);
    }

    public function listHosts(string $domain, bool $compact = true): array
    {
        $params = ['Domain' => $domain];
        if (!$compact) {
            $params['CompactList'] = 'no';
        }
        return $this->request('/Domain/Host/List', $params);
    }

    // -------------------------------------------------------------------------
    // URL Forwarding
    // -------------------------------------------------------------------------

    public function urlForwardAdd(string $source, string $destination, bool $framed = true, array $options = []): array
    {
        $params = [
            'Source' => $source,
            'Destination' => $destination,
            'IsFramed' => $framed ? 'YES' : 'NO',
        ];
        return $this->request('/Domain/UrlForward/Add', array_merge($params, $options));
    }

    public function urlForwardUpdate(string $source, array $options): array
    {
        return $this->request('/Domain/UrlForward/Update', array_merge(['Source' => $source], $options));
    }

    public function urlForwardRemove(string $source): array
    {
        return $this->request('/Domain/UrlForward/Remove', ['Source' => $source]);
    }

    public function listUrlForwards(string $domain): array
    {
        return $this->request('/Domain/UrlForward/List', ['Domain' => $domain]);
    }

    // -------------------------------------------------------------------------
    // Email Forwarding
    // -------------------------------------------------------------------------

    public function emailForwardAdd(string $source, string $destination): array
    {
        return $this->request('/Domain/EmailForward/Add', ['Source' => $source, 'Destination' => $destination]);
    }

    public function emailForwardUpdate(string $source, string $destination): array
    {
        return $this->request('/Domain/EmailForward/Update', ['Source' => $source, 'Destination' => $destination]);
    }

    public function emailForwardRemove(string $source): array
    {
        return $this->request('/Domain/EmailForward/Remove', ['Source' => $source]);
    }

    public function listEmailForwards(string $domain): array
    {
        return $this->request('/Domain/EmailForward/List', ['Domain' => $domain]);
    }

    // -------------------------------------------------------------------------
    // DNS Record Management
    // -------------------------------------------------------------------------

    public function dnsRecordAdd(array $record): array
    {
        return $this->request('/Domain/DnsRecord/Add', $record);
    }

    public function dnsRecordRemove(array $record): array
    {
        return $this->request('/Domain/DnsRecord/Remove', $record);
    }

    public function dnsRecordUpdate(array $record): array
    {
        return $this->request('/Domain/DnsRecord/Update', $record);
    }

    public function listDnsRecords(string $domain, string $filterType = 'ALL'): array
    {
        return $this->request('/Domain/DnsRecord/List', ['Domain' => $domain, 'FilterType' => $filterType]);
    }

    // -------------------------------------------------------------------------
    // Account Operations
    // -------------------------------------------------------------------------

    public function getBalance(?string $currency = null): array
    {
        $params = [];
        if ($currency) {
            $params['Currency'] = $currency;
        }
        return $this->request('/Account/Balance/Get', $params);
    }

    public function setDefaultCurrency(string $currency): array
    {
        return $this->request('/Account/DefaultCurrency/Set', ['Currency' => $currency]);
    }

    public function getDefaultCurrency(): array
    {
        return $this->request('/Account/DefaultCurrency/Get');
    }

    public function getPriceList(?string $currency = null, int $version = 1): array
    {
        $params = ['version' => $version];
        if ($currency) {
            $params['Currency'] = $currency;
        }
        return $this->request('/Account/PriceList/Get', $params);
    }

    public function getConfiguration(): array
    {
        return $this->request('/Account/Configuration/Get');
    }

    public function setConfiguration(array $config): array
    {
        return $this->request('/Account/Configuration/Set', $config);
    }
}
