<?php

namespace Mgroup\Taxify\Model\Response;

use Mgroup\Taxify\Model\Response\JurisdictionLine;

/**
 * TWE response lines
 * 
 */
class Line implements \JsonSerializable {

    protected $lineNumber;
    protected $salesTaxAmount;
    protected $taxRate;
    protected $lineId;
    protected $jurisdictionLines;

    /**
     * @inheritDoc
     */
    function jsonSerialize(){
        $vars = get_object_vars($this);
        return $vars;
    }

    public function __construct(){
        $arguments = func_get_args();
        $this->Line($arguments[0], $arguments[1], $arguments[2]);
    }

    /**
     * @param $lineNumber
     * @param $salesTaxAmount
     * @param $taxRate
     */
    public function Line($lineNumber, $salesTaxAmount, $taxRate){
        $this->lineNumber = $lineNumber;
        $this->salesTaxAmount = $salesTaxAmount;
        $this->taxRate = $taxRate;
    }

    /**
     * @return int
     */
    public function getLineNumber()
    {
        return $this->lineNumber;
    }

    /**
     * @return int
     */
    public function getSalesTaxAmount()
    {
        return $this->salesTaxAmount;
    }

    /**
     * @return int
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * @param int $lineNumber
     */
    public function setLineNumber($lineNumber)
    {
        $this->lineNumber = $lineNumber;
    }

    /**
     * @param $salesTaxAmount
     */
    public function setSalesTaxAmount($salesTaxAmount)
    {
        $this->salesTaxAmount = $salesTaxAmount;
    }

    /**
     * @param int $taxRate
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;
    }
}