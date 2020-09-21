<?php

namespace Mgroup\Taxify\Model\Request;

/**
 * Magento Address data
 *
 */
class AdapterAddress implements \JsonSerializable {
    private $streetNameWithNumber;
    private $city;
    private $region;
    private $country;
    private $postalCode;

    /**
     * @inheritDoc
     */
    function jsonSerialize()
    {
        $vars = get_object_vars($this);
        return $vars;
    }

    public function __construct(){
        $arguments = func_get_args();
        $num_arguments = sizeof($arguments);

        if($num_arguments <= 0) {
            $this->streetNameWithNumber = '';
            $this->city = '';
            $this->region = '';
            $this->country = 'US';
            $this->postalCode = '';
        }else{
            $this->AdapterAddress($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
        }
    }

    /**
     * Sets the shipping information needed for tax calculation
     *
     * @param string $streetNameNumber
     * @param string $city
     * @param string $region
     * @param string $country
     * @param string $postalCode
     */
    public function AdapterAddress($streetNameNumber, $city, $region, $country, $postalCode){
        $this->streetNameWithNumber = $streetNameNumber;
        $this->city = $city;
        $this->region = $region;
        $this->country = $country;
        $this->postalCode = $postalCode;
    }

    /**
     * @return string
     */
    public function getStreetNameWithNumber()
    {
        return $this->streetNameWithNumber;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param string $streetNameWithNumber
     */
    public function setStreetNameWithNumber($streetNameWithNumber)
    {
        $this->streetNameWithNumber = $streetNameWithNumber;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @param string $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @param string $postalCode
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
    }


}