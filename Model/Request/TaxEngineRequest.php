<?php

namespace Mgroup\Taxify\Model\Request;

use Magento\Framework\HTTP\Zend_Http_Client;
use Mgroup\Taxify\Model\Response\TaxEngineResponse;

/**
 * Class TaxEngineRequest
 * @package Mgroup\Taxify\Model\Request
 */
class TaxEngineRequest implements \JsonSerializable
{
    const DATA_TAXIFY = 'mgroup_taxify_section/general/apiKey';

    private $entityName;
    private $requestBody;
    private $logger;

    protected $url;
    protected $certificatePath;
    protected $response;
    protected $configurationInterface;
    protected $countryInformationInterface;
    protected $zendClient;
    protected $scopeConfig;

    /**
     * @inheritDoc
     */
    function jsonSerialize()
    {
        return get_object_vars($this);
    }

    // TODO: Update dependencies
    public function __construct(
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Zend\Http\Client $client
    ) {
        $this->countryInformationInterface = $countryInformationAcquirerInterface;
        $this->configurationInterface = $scopeConfig;
        $this->logger = $logger;
        $this->zendClient = $client;
//        $this->TaxEngineRequest($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]);
    }

    /**
     * Sets the parameters that need to be passes to TWE in the request
     *
     * @param string $entityName
     * @param RequestBody $requestBody
     * @param string $url
     * @param string $certificatePath
     *
     */
    public function TaxEngineRequest( $entityName, $requestBody, $url, $certificatePath)
    {
        $this->entityName = $entityName;
        $this->requestBody = $requestBody;
        $this->url = $url;
        $this->certificatePath = $certificatePath;
    }

    /**
     * Sets the URL
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Sets the certificate's path
     *
     * @param string $certificatePath
     */
    public function setCertificatePath($certificatePath)
    {
        $this->certificatePath = $certificatePath;
    }

    /**
     *
     *
     * @return array
     */
    public function getRequestObject()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $apiKey = $this->configurationInterface->getValue(self::DATA_TAXIFY, $storeScope);
        $originAddress = $this->getAddressFromConfiguration('shipping/origin/');
        $this->requestBody->setOriginAddress($originAddress);
        $this->requestBody->setSecurity($apiKey);
        $this->requestBody->setIsCommitted(false);
        return array(
            'CalculateTax' => $this->requestBody
        );
    }

    public function sendRequest()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/an33.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('data');
        $logger->info(json_encode($this->getRequestObject()));

        $url = 'https://ws.taxify.co/taxify/1.1/core/JSONService.asmx/CalculateTax';

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $this->zendClient->reset();
            $this->zendClient->setUri($url);
            $this->zendClient->setMethod(\Zend\Http\Request::METHOD_POST);
            $this->zendClient->setHeaders($headers);
            $this->zendClient->setRawBody(json_encode($this->getRequestObject()));
            $this->zendClient->send();
            if ($this->zendClient->getResponse()->getStatusCode() == 200) {
                $this->setResponse(json_decode($this->zendClient->getResponse()->getBody(), true));
            } else {
                $this->logger->error($this->zendClient->getResponse()->getStatusCode());
                $this->setResponse(false);
            }
        } catch (RuntimeException $runtimeException) {
            $this->logger->error($runtimeException->getMessage());
            $this->setResponse(false);
        }
    }


    public function getAddressFromConfiguration($sectionGroupId)
    {
        $adapterAddress = new AdapterAddress();
        if ($sectionGroupId != null) {
            $adapterAddress->setPostalCode($this->configurationInterface->getValue($sectionGroupId . "postcode"));
            $adapterAddress->setCity($this->configurationInterface->getValue($sectionGroupId . "city"));
            $adapterAddress->setStreetNameWithNumber($this->configurationInterface->getValue($sectionGroupId . "street_line1"));

            $countryId = $this->configurationInterface->getValue($sectionGroupId . "country_id");
            $adapterAddress->setCountry($countryId);

            $regionId = $this->configurationInterface->getValue($sectionGroupId . "region_id");
            $name = $this->determineRegionLabel($regionId, $countryId);
            $adapterAddress->setRegion($name);
        }

        return $adapterAddress;
    }

    private function determineRegionLabel($regionId, $countryId)
    {
        $name = $regionId;
        if (is_numeric($regionId) && $countryId != null) {
            $countryInformation = $this->countryInformationInterface->getCountryInfo($countryId);
            $allRegions = $countryInformation->getAvailableRegions();
            for ($i = 0; $i < count($allRegions); $i++) {
                $region = $allRegions[$i];
                if ($region->getId() == $regionId) {
                    $name = $region->getName();
                }
            }
        }
        return $name;
    }


    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param array $response
     */
    public function setResponse($response)
    {
        if ($response && isset($response['d']['SalesTaxAmount'])) {
            $this->response = new TaxEngineResponse($response['d']['SalesTaxAmount'], $response['d']['TaxLineDetails']);
        } else {
            $this->response = new TaxEngineResponse();
            $this->response->setErrorMessage("Api taxify call error!");
        }
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param string $entityName
     * @return TaxEngineRequest
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRequestBody()
    {
        return $this->requestBody;
    }

    /**
     * @param $requestBody
     * @return $this
     */
    public function setRequestBody($requestBody)
    {
        $this->requestBody = $requestBody;
        return $this;
    }

}