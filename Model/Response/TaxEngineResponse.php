<?php

namespace Mgroup\Taxify\Model\Response;

/**
 * Class TaxEngineResponse
 * @package Mgroup\Taxify\Model\Response
 */
class TaxEngineResponse
{
    protected $salesTaxAmount;
    protected $transactionId;
    protected $isTransactionSuccess;
    protected $lines;
    protected $errorMessage;

    public function __construct()
    {
        $arguments = func_get_args();
        $this->TaxEngineResponse($arguments[0], $arguments[1]);
    }

    /**
     * @param $saleTaxAmount
     * @param $lines
     */
    public function TaxEngineResponse($saleTaxAmount, $lines)
    {
        $this->salesTaxAmount = $saleTaxAmount;
        $this->lines = array();
        foreach ($lines as $line) {
            if (array_key_exists('LineNumber', $line)) {
                $obj = new Line($line['LineNumber'], $line['SalesTaxAmount'], $line['TaxRate']);
            }
            array_push($this->lines, $obj);
        }

    }

    /**
     * @return int
     */
    public function getSalesTaxAmount()
    {
        return $this->salesTaxAmount;
    }

    /**
     * @return array
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * @param int $salesTaxAmount
     */
    public function setSalesTaxAmount($salesTaxAmount)
    {
        $this->salesTaxAmount = $salesTaxAmount;
    }

    /**
     * @param array $lines
     */
    public function setLines($lines = array())
    {
        $this->lines = $lines;
    }

    /**
     *
     * @param string $message
     */
    public function setErrorMessage($message)
    {
        $this->errorMessage = $message;
    }

    /**
     * return string error message
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }
}