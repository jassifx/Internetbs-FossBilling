<?php

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Component\HttpClient\HttpClient;

class Registrar_Adapter_Internetbs extends Registrar_AdapterAbstract
{
    protected $config = [
        'apikey' => null,
        'password' => null,
        'testMode' => false,
    ];

    public function __construct($options)
    {
        foreach (['apikey', 'password'] as $key) {
            if (empty($options[$key])) {
                throw new Registrar_Exception('The ":domain_registrar" domain registrar is not fully configured. Missing :missing', [':domain_registrar' => 'Internetbs', ':missing' => $key], 3001);
            }
            $this->config[$key] = $options[$key];
        }

        $this->config['testMode'] = $options['testMode'] ?? false;
    }

    public static function getConfig()
    {
        return [
            'label' => 'Manages domains on Internetbs via API',
            'form' => [
                'apikey' => ['text', [
                    'label' => 'Internetbs API key',
                    'description' => 'Internetbs API key',
                ]],
                'password' => ['password', [
                    'label' => 'Internetbs API password',
                    'description' => 'Internetbs API password',
                    'renderPassword' => true,
                ]],
                'testMode' => ['checkbox', [
                    'label' => 'Enable Test Mode',
                    'description' => 'Use the Internetbs Test API endpoint',
                ]],
            ],
        ];
    }

    private function httpClient()
    {
        return HttpClient::create([
            'base_uri' => $this->_getApiUrl(),
            'timeout' => 30,
            'verify_peer' => true,
            'verify_host' => true,
        ]);
    }

    private function _process($command, $params)
    {
        $params['ApiKey'] = $this->config['apikey'];
        $params['Password'] = $this->config['password'];
        $params['ResponseFormat'] = 'JSON';

        try {
            $response = $this->httpClient()->request('POST', $command, [
                'body' => $params,
            ]);
            $data = json_decode($response->getContent(), true);
        } catch (HttpExceptionInterface $e) {
            throw new Registrar_Exception('API request error: '.$e->getMessage());
        }

        if (($data['status'] ?? '') === 'FAILURE') {
            throw new Registrar_Exception($data['message'] ?? 'Unknown error from API');
        }

        return $data;
    }

    private function _getApiUrl()
    {
        return $this->config['testMode'] ? 'https://testapi.internet.bs' : 'https://api.internet.bs';
    }

    public function isDomainAvailable(Registrar_Domain $domain)
    {
        $result = $this->_process('/Domain/Check', ['Domain' => $domain->getName()]);
        return ($result['status'] ?? '') == 'AVAILABLE';
    }

    public function registerDomain(Registrar_Domain $domain)
    {
        $c = $domain->getContactRegistrar();
        $params = [
            'Domain' => $domain->getName(),
            'Period' => $domain->getRegistrationPeriod().'Y',
            'Ns_list' => implode(',', array_filter([$domain->getNs1(), $domain->getNs2(), $domain->getNs3(), $domain->getNs4()])),
        ];

        foreach (['Registrant', 'Admin', 'Technical', 'Billing'] as $type) {
            $params += [
                "{$type}_Organization" => $c->getCompany(),
                "{$type}_FirstName" => $c->getFirstName(),
                "{$type}_LastName" => $c->getLastName(),
                "{$type}_Email" => $c->getEmail(),
                "{$type}_PhoneNumber" => '+' . $c->getTelCc() . '.' . $c->getTel(),
                "{$type}_Street" => $c->getAddress1(),
                "{$type}_City" => $c->getCity(),
                "{$type}_CountryCode" => $c->getCountry(),
                "{$type}_PostalCode" => $c->getZip(),
                "{$type}_Language" => 'en',
            ];
        }

        $result = $this->_process('/Domain/Create', $params);

        return in_array(($result['product_0_status'] ?? ''), ['PENDING', 'SUCCESS']);
    }

    public function renewDomain(Registrar_Domain $domain)
    {
        $params = ['Domain' => $domain->getName(), 'Period' => $domain->getRegistrationPeriod().'Y'];
        $result = $this->_process('/Domain/Renew', $params);

        return ($result['product_0_status'] ?? '') == 'SUCCESS';
    }

    public function modifyNs(Registrar_Domain $domain)
    {
        $params = ['Domain' => $domain->getName(), 'Ns_list' => implode(',', array_filter([$domain->getNs1(), $domain->getNs2(), $domain->getNs3(), $domain->getNs4()]))];
        $result = $this->_process('/Domain/Update', $params);

        return ($result['status'] ?? '') == 'SUCCESS';
    }

    public function enablePrivacyProtection(Registrar_Domain $domain)
    {
        return ($this->_process('/Domain/PrivateWhois/Enable', ['Domain' => $domain->getName()])['status'] ?? '') == 'SUCCESS';
    }

    public function disablePrivacyProtection(Registrar_Domain $domain)
    {
        return ($this->_process('/Domain/PrivateWhois/Disable', ['Domain' => $domain->getName()])['status'] ?? '') == 'SUCCESS';
    }

    public function lock(Registrar_Domain $domain)
    {
        return ($this->_process('/Domain/RegistrarLock/Enable', ['Domain' => $domain->getName()])['status'] ?? '') == 'SUCCESS';
    }

    public function unlock(Registrar_Domain $domain)
    {
        return ($this->_process('/Domain/RegistrarLock/Disable', ['Domain' => $domain->getName()])['status'] ?? '') == 'SUCCESS';
    }
}
