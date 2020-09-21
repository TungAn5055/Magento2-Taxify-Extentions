<?php

namespace Mgroup\Taxify\Model\Response;

/**
 * Class JurisdictionLine
 * @package Mgroup\Taxify\Model\Response
 */
class JurisdictionLine implements \JsonSerializable {
    protected $taxableAmount;
    protected $taxAmount;
    protected $exemptAmount;
    protected $taxRate;
    protected $taxName;
    protected $taxNameId;
    protected $taxJurisdictionType;

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
        $num_arguments = count($arguments);

        if($num_arguments <= 0){
            $this->taxableAmount = 0;
            $this->taxAmount = 0;
            $this->exemptAmount = 0;
            $this->taxRate = 0;
            $this->taxName = '';
            $this->taxNameId = 0;
            $this->taxJurisdiction = '';
        }else{
            $this->JurisdictionLine($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5], $arguments[6]);
        }
    }

    /**
     * Sets the data coming from TWE into the line
     * 
     * @param float $taxableAmount
     * @param float $taxAmount
     * @param float $exemptAmount
     * @param float $taxRate
     * @param string $taxName
     * @param int $taxNameId
     * @param string $taxJurisdictionType
     */
    public function JurisdictionLine($taxableAmount, $taxAmount, $exemptAmount, $taxRate, $taxName, $taxNameId, $taxJurisdictionType){
        $this->taxableAmount = $taxableAmount;
        $this->taxAmount = $taxAmount;
        $this->exemptAmount = $exemptAmount;
        $this->taxRate = $taxRate;
        $this->taxName = $taxName;
        $this->taxNameId = $taxNameId;
        $this->taxJurisdictionType = $taxJurisdictionType;
    }

    /**
     * Check whether JurisdictionLine corresponds to a Fee
     *
     * @return bool
     */
    public function isFee(){
        return (bool) preg_match('/Fee/', $this->getTaxName());
    }




    /**
     * @return int
     */
    public function getTaxableAmount()
    {
        return $this->taxableAmount;
    }

    /**
     * @return int
     */
    public function getTaxAmount()
    {
        return $this->taxAmount;
    }

    /**
     * @return int
     */
    public function getExemptAmount()
    {
        return $this->exemptAmount;
    }

    /**
     * @return int
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * @return string
     */
    public function getTaxName()
    {
        return $this->taxName;
    }

    /**
     * @return int
     */
    public function getTaxNameId()
    {
        return $this->taxNameId;
    }

    /**
     * @return string
     */
    public function getTaxJurisdictionType()
    {
        return $this->taxJurisdictionType;
    }

    /**
     * @param int $taxableAmount
     */
    public function setTaxableAmount($taxableAmount)
    {
        $this->taxableAmount = $taxableAmount;
    }

    /**
     * @param int $taxAmount
     */
    public function setTaxAmount($taxAmount)
    {
        $this->taxAmount = $taxAmount;
    }

    /**
     * @param int $exemptAmount
     */
    public function setExemptAmount($exemptAmount)
    {
        $this->exemptAmount = $exemptAmount;
    }

    /**
     * @param int $taxRate
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;
    }

    /**
     * @param string $taxName
     */
    public function setTaxName($taxName)
    {
        $this->taxName = $taxName;
    }

    /**
     * @param int $taxNameId
     */
    public function setTaxNameId($taxNameId)
    {
        $this->taxNameId = $taxNameId;
    }

    /**
     * @param string $taxJurisdictionType
     */
    public function setTaxJurisdictionType($taxJurisdictionType)
    {
        $this->taxJurisdictionType = $taxJurisdictionType;
    }

}