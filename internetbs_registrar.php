<?php
/**
 * Internet.bs Registrar Module for FossBilling
 *
 * This module integrates the Internet.bs API with FossBilling.
 * It implements a full set of features: domain availability checking,
 * registration, updates, information retrieval, registry status,
 * domain restoration, domain listing, transfers (initiate, retry, cancel,
 * resend auth email, history), nameserver management, forwarding (URL and email),
 * DNS management, and account operations.
 *
 * This module also provides an admin configuration screen for setting the
 * API key, API password, and Test Mode (which switches between testapi.internet.bs
 * and the production endpoint).
 *
 * Compatible with PHP 8.3.
 */

if (!defined("FOSS_BILLING")) {
    die("This file cannot be accessed directly");
}

/**
 * Module configuration function.
 * This defines which settings are available from FossBilling's admin area.
 */
function internetbs_config()
{
    $configarray = [
        "FriendlyName" => [
            "Type"        => "System",
            "Value"       => "Internet.bs Registrar Module",
            "Description" => "Integrates Internet.bs API with FossBilling"
        ],
        "APITitle" => [
            "Type"        => "text",
            "Size"        => "25",
            "Description" => "Your Internet.bs API Key"
        ],
        "APIPassword" => [
            "Type"        => "password",
            "Size"        => "25",
            "Description" => "Your Internet.bs API Password"
        ],
        "TestMode" => [
            "Type"        => "yesno",
            "Description" => "Enable test mode? (Uses testapi.internet.bs)"
        ]
    ];
    return $configarray;
}

/**
 * Class InternetBsApi
 *
 * Contains all API operations based on the Internet.bs API specification.
 */
class InternetBsApi
{
    // Base endpoints:
    private const TEST_BASE_URL = 'https://testapi.internet.bs/';
    private const PROD_BASE_URL = 'https://api.internet.bs/';

    private string $apiKey;
    private string $password;
    private bool $isTestMode;

    /**
     * Constructor.
     *
     * @param string $apiKey
     * @param string $password
     * @param bool $isTestMode
     */
    public function __construct(string $apiKey, string $password, bool $isTestMode = true)
    {
        $this->apiKey     = $apiKey;
        $this->password   = $password;
        $this->isTestMode = $isTestMode;
    }

    /**
     * Get the current API base URL.
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        return $this->isTestMode ? self::TEST_BASE_URL : self::PROD_BASE_URL;
    }

    /**
     * Generic API call.
     *
     * @param string $resource API resource path (e.g. "Domain/Check")
     * @param array $params Request parameters.
     * @param string $method HTTP method: GET or POST.
     * @return array Parsed API response.
     * @throws Exception on failure.
     */
    private function apiCall(string $resource, array $params = [], string $method = 'GET'): array
    {
        $baseUrl = $this->getBaseUrl();
        // Merge authentication parameters.
        $params = array_merge([
            'ApiKey'   => $this->apiKey,
            'Password' => $this->password,
        ], $params);
        $query = http_build_query($params);
        $url   = rtrim($baseUrl, '/') . '/' . ltrim($resource, '/') . '?' . $query;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        }

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $errorMsg = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL error: $errorMsg");
        }
        curl_close($ch);

        // First try JSON
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            if (isset($decoded['status']) && strtoupper($decoded['status']) === 'FAILURE') {
                throw new Exception("API error: " . ($decoded['error'] ?? 'Unknown error'));
            }
            return $decoded;
        }
        // Fallback to key=value pair parsing
        $result = [];
        $lines  = preg_split("/\r\n|\n|\r/", $response);
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $pair = explode('=', $line, 2);
            if (count($pair) === 2) {
                $result[trim($pair[0])] = trim($pair[1]);
            }
        }
        return $result;
    }

    // -----------------------
    // DOMAIN RELATED OPERATIONS
    // -----------------------

    public function checkDomainAvailability(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/Check', $params);
    }

    public function registerDomain(string $domain, array $contactData, array $optionalParams = []): array
    {
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $contactData, $optionalParams);
        return $this->apiCall('Domain/Create', $params, 'POST');
    }

    public function updateDomain(string $domain, array $updateData): array
    {
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $updateData);
        return $this->apiCall('Domain/Update', $params, 'POST');
    }

    public function getDomainInfo(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/Info', $params);
    }

    public function getRegistryStatus(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/RegistryStatus', $params);
    }

    public function getDomainCount(): array
    {
        $params = [
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/Count', $params);
    }

    public function restoreDomain(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/Restore', $params, 'POST');
    }

    public function listDomains(): array
    {
        $params = [
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/List', $params);
    }

    // -----------------------
    // DOMAIN TRANSFER OPERATIONS
    // -----------------------

    public function transferInitiate(string $domain, array $transferData): array
    {
        // $transferData should include transferAuthInfo and any required contact info.
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $transferData);
        return $this->apiCall('Domain/Transfer/Initiate', $params, 'POST');
    }

    public function transferRetry(string $domain, array $optionalData = []): array
    {
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $optionalData);
        return $this->apiCall('Domain/Transfer/Retry', $params, 'POST');
    }

    public function transferCancel(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/Transfer/Cancel', $params, 'POST');
    }

    public function transferResendAuthEmail(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/Transfer/ResendAuthEmail', $params, 'POST');
    }

    public function transferHistory(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/Transfer/History', $params);
    }

    // -----------------------
    // NAMESERVER (HOST) MANAGEMENT
    // -----------------------

    public function hostCreate(string $domain, array $hostData): array
    {
        // $hostData must include the nameserver hostname and any glue records if required.
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $hostData);
        return $this->apiCall('Domain/Host/Create', $params, 'POST');
    }

    public function hostInfo(string $hostname): array
    {
        $params = [
            'HostName'       => $hostname,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/Host/Info', $params);
    }

    public function hostUpdate(string $hostname, array $updateData): array
    {
        $params = array_merge([
            'HostName'       => $hostname,
            'ResponseFormat' => 'JSON'
        ], $updateData);
        return $this->apiCall('Domain/Host/Update', $params, 'POST');
    }

    public function hostDelete(string $hostname): array
    {
        $params = [
            'HostName'       => $hostname,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/Host/Delete', $params, 'POST');
    }

    public function hostList(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/Host/List', $params);
    }

    // -----------------------
    // DOMAIN FORWARDING
    // -----------------------

    // URL Forwarding
    public function urlForwardAdd(string $domain, array $forwardData): array
    {
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $forwardData);
        return $this->apiCall('Domain/UrlForward/Add', $params, 'POST');
    }

    public function urlForwardUpdate(string $domain, array $forwardData): array
    {
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $forwardData);
        return $this->apiCall('Domain/UrlForward/Update', $params, 'POST');
    }

    public function urlForwardRemove(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/UrlForward/Remove', $params, 'POST');
    }

    public function urlForwardList(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/UrlForward/List', $params);
    }

    // Email Forwarding
    public function emailForwardAdd(string $domain, array $forwardData): array
    {
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $forwardData);
        return $this->apiCall('Domain/EmailForward/Add', $params, 'POST');
    }

    public function emailForwardUpdate(string $domain, array $forwardData): array
    {
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $forwardData);
        return $this->apiCall('Domain/EmailForward/Update', $params, 'POST');
    }

    public function emailForwardRemove(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/EmailForward/Remove', $params, 'POST');
    }

    public function emailForwardList(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/EmailForward/List', $params);
    }

    // -----------------------
    // DNS MANAGEMENT
    // -----------------------

    public function dnsRecordAdd(string $domain, array $recordData): array
    {
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $recordData);
        return $this->apiCall('Domain/DnsRecord/Add', $params, 'POST');
    }

    public function dnsRecordRemove(string $domain, array $recordData): array
    {
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $recordData);
        return $this->apiCall('Domain/DnsRecord/Remove', $params, 'POST');
    }

    public function dnsRecordUpdate(string $domain, array $recordData): array
    {
        $params = array_merge([
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ], $recordData);
        return $this->apiCall('Domain/DnsRecord/Update', $params, 'POST');
    }

    public function dnsRecordList(string $domain): array
    {
        $params = [
            'Domain'         => $domain,
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Domain/DnsRecord/List', $params);
    }

    // -----------------------
    // ACCOUNT RELATED OPERATIONS
    // -----------------------

    public function accountBalanceGet(): array
    {
        $params = [
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Account/Balance/Get', $params);
    }

    public function accountDefaultCurrencySet(string $currency): array
    {
        $params = [
            'DefaultCurrency' => $currency,
            'ResponseFormat'  => 'JSON'
        ];
        return $this->apiCall('Account/DefaultCurrency/Set', $params, 'POST');
    }

    public function accountDefaultCurrencyGet(): array
    {
        $params = [
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Account/DefaultCurrency/Get', $params);
    }

    public function accountPriceListGet(): array
    {
        $params = [
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Account/PriceList/Get', $params);
    }

    public function accountConfigurationGet(): array
    {
        $params = [
            'ResponseFormat' => 'JSON'
        ];
        return $this->apiCall('Account/Configuration/Get', $params);
    }

    public function accountConfigurationSet(array $configData): array
    {
        $params = array_merge([
            'ResponseFormat' => 'JSON'
        ], $configData);
        return $this->apiCall('Account/Configuration/Set', $params, 'POST');
    }
}

/**
 * Helper function to instantiate InternetBsApi with config parameters.
 */
function internetbs_getApiInstance(array $params): InternetBsApi
{
    $apiKey   = trim($params['APITitle']);
    $password = trim($params['APIPassword']);
    $isTest   = (isset($params['TestMode']) && ($params['TestMode'] === 'on' || $params['TestMode'] === true));
    return new InternetBsApi($apiKey, $password, $isTest);
}


// ========================================================
// FossBilling module functions (wrappers) for each operation
// ========================================================

/**
 * Check Domain Availability.
 */
function internetbs_CheckDomainAvailability($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $domain = $params['sld'] . '.' . $params['tld'];
        $response = $api->checkDomainAvailability($domain);
        return (strtoupper($response['status'] ?? '') === 'AVAILABLE') ? "available" : "unavailable";
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

/**
 * Register a Domain.
 */
function internetbs_RegisterDomain($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $domain = $params['sld'] . '.' . $params['tld'];

        // Map FossBilling order parameters to Internet.bs contact fields.
        $contactData = [
            'registrant_firstname'   => $params['firstname'],
            'registrant_lastname'    => $params['lastname'],
            'registrant_email'       => $params['email'],
            'registrant_phonenumber' => $params['phone'],
            'registrant_street'      => $params['address1'],
            'registrant_city'        => $params['city'],
            'registrant_countrycode' => $params['country'],
            'registrant_postalcode'  => $params['postcode']
        ];
        // Optional parameters (such as nameservers and period).
        $optionalParams = [
            'ns_list' => isset($params['ns']) ? implode(',', $params['ns']) : '',
            'period'  => $params['regperiod'] ?? '1Y'
        ];
        $response = $api->registerDomain($domain, $contactData, $optionalParams);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Registration failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

/**
 * Update Domain information.
 */
function internetbs_UpdateDomain($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $domain = $params['sld'] . '.' . $params['tld'];
        // Example: updating the registrant email.
        $updateData = [
            'Registrant_Email' => $params['newemail'] ?? ''
        ];
        $response = $api->updateDomain($domain, $updateData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Update failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

/**
 * Get detailed domain info.
 */
function internetbs_GetDomainDetails($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $domain = $params['sld'] . '.' . $params['tld'];
        return $api->getDomainInfo($domain);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Get registry status of a domain.
 */
function internetbs_GetRegistryStatus($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $domain = $params['sld'] . '.' . $params['tld'];
        return $api->getRegistryStatus($domain);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Initiate domain transfer.
 */
function internetbs_TransferInitiate($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $domain = $params['sld'] . '.' . $params['tld'];
        // $transferData should include transferAuthInfo and any required contact info.
        $transferData = [
            'transferauthinfo'      => $params['transferauthinfo'],
            // Additional required fields can be mapped here.
            'registrant_firstname'  => $params['firstname'],
            'registrant_lastname'   => $params['lastname'],
            'registrant_email'      => $params['email'],
            'registrant_phonenumber'=> $params['phone'],
            'registrant_street'     => $params['address1'],
            'registrant_city'       => $params['city'],
            'registrant_countrycode'=> $params['country'],
            'registrant_postalcode' => $params['postcode']
        ];
        $response = $api->transferInitiate($domain, $transferData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Transfer initiation failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

/**
 * Retry a domain transfer.
 */
function internetbs_TransferRetry($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $domain = $params['sld'] . '.' . $params['tld'];
        // Optionally update transferAuthInfo if necessary.
        $optionalData = [];
        if (!empty($params['transferauthinfo'])) {
            $optionalData['transferauthinfo'] = $params['transferauthinfo'];
        }
        $response = $api->transferRetry($domain, $optionalData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Transfer retry failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

/**
 * Cancel a domain transfer.
 */
function internetbs_TransferCancel($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $domain = $params['sld'] . '.' . $params['tld'];
        $response = $api->transferCancel($domain);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Transfer cancellation failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

/**
 * Resend initial authorization email for a transfer.
 */
function internetbs_TransferResendAuthEmail($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $domain = $params['sld'] . '.' . $params['tld'];
        $response = $api->transferResendAuthEmail($domain);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Resend auth email failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

/**
 * Get transfer history.
 */
function internetbs_TransferHistory($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $domain = $params['sld'] . '.' . $params['tld'];
        return $api->transferHistory($domain);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Create a nameserver (host).
 */
function internetbs_HostCreate($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        // $params['hostname'] should contain the host name and optional glue records.
        $response = $api->hostCreate($params['domain'], ['HostName' => $params['hostname'], 'Glue' => $params['glue'] ?? '']);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Host creation failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

/**
 * Retrieve nameserver (host) info.
 */
function internetbs_HostInfo($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        return $api->hostInfo($params['hostname']);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Update a nameserver (host).
 */
function internetbs_HostUpdate($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $updateData = $params['updateData'] ?? [];
        $response = $api->hostUpdate($params['hostname'], $updateData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Host update failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

/**
 * Delete a nameserver (host).
 */
function internetbs_HostDelete($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $response = $api->hostDelete($params['hostname']);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Host deletion failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

/**
 * List nameservers (hosts) for a domain.
 */
function internetbs_HostList($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        return $api->hostList($params['domain']);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * URL Forwarding operations.
 */
function internetbs_UrlForwardAdd($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $forwardData = $params['forwardData'] ?? [];
        $response = $api->urlForwardAdd($params['domain'], $forwardData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'URL forward add failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

function internetbs_UrlForwardUpdate($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $forwardData = $params['forwardData'] ?? [];
        $response = $api->urlForwardUpdate($params['domain'], $forwardData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'URL forward update failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

function internetbs_UrlForwardRemove($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $response = $api->urlForwardRemove($params['domain']);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'URL forward remove failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

function internetbs_UrlForwardList($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        return $api->urlForwardList($params['domain']);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Email Forwarding operations.
 */
function internetbs_EmailForwardAdd($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $forwardData = $params['forwardData'] ?? [];
        $response = $api->emailForwardAdd($params['domain'], $forwardData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Email forward add failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

function internetbs_EmailForwardUpdate($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $forwardData = $params['forwardData'] ?? [];
        $response = $api->emailForwardUpdate($params['domain'], $forwardData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Email forward update failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

function internetbs_EmailForwardRemove($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $response = $api->emailForwardRemove($params['domain']);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Email forward remove failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

function internetbs_EmailForwardList($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        return $api->emailForwardList($params['domain']);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * DNS Record Management operations.
 */
function internetbs_DnsRecordAdd($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $recordData = $params['recordData'] ?? [];
        $response = $api->dnsRecordAdd($params['domain'], $recordData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'DNS record add failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

function internetbs_DnsRecordRemove($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $recordData = $params['recordData'] ?? [];
        $response = $api->dnsRecordRemove($params['domain'], $recordData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'DNS record remove failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

function internetbs_DnsRecordUpdate($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $recordData = $params['recordData'] ?? [];
        $response = $api->dnsRecordUpdate($params['domain'], $recordData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'DNS record update failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

function internetbs_DnsRecordList($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        return $api->dnsRecordList($params['domain']);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Account Operations.
 */
function internetbs_AccountBalanceGet($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        return $api->accountBalanceGet();
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function internetbs_AccountDefaultCurrencySet($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        $currency = $params['currency'] ?? 'USD';
        $response = $api->accountDefaultCurrencySet($currency);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Default currency set failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}

function internetbs_AccountDefaultCurrencyGet($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        return $api->accountDefaultCurrencyGet();
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function internetbs_AccountPriceListGet($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        return $api->accountPriceListGet();
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function internetbs_AccountConfigurationGet($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        return $api->accountConfigurationGet();
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function internetbs_AccountConfigurationSet($params)
{
    try {
        $api = internetbs_getApiInstance($params);
        // $configData should include necessary configuration keys.
        $configData = $params['configData'] ?? [];
        $response = $api->accountConfigurationSet($configData);
        return (isset($response['status']) && strtoupper($response['status']) === 'SUCCESS') ? "success" : "error: " . ($response['error'] ?? 'Account configuration set failed');
    } catch (Exception $e) {
        return "error: " . $e->getMessage();
    }
}
