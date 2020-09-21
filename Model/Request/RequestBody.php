<?php

namespace Mgroup\Taxify\Model\Request;

use Mgroup\Taxify\Model\Request\Line;

/**
 * Builds the request body of the object to be sent to TWE
 *
 */
class RequestBody implements \JsonSerializable {
    private $isAudit;
    private $currency;
    private $transactionDocNumber;
    private $transactionId;
    private $taxCalculationType;
    private $deliveryAmount;
    private $lines;
    private $originAddress;
    private $destinationAddress;
    private $storeInformationAddress;
    private $security;
    private $isCommitted;

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

        if($num_arguments <= 0){
            $this->isAudit = false;
            $this->currency = 'USD';
            $this->transactionDocNumber = '';
            $this->taxCalculationType = 1;
            $this->deliveryAmount = 0.0;
            $this->lines = array();
        }else{
        	$this->RequestBody($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]);
        }
    }

    /**
     * Sets the parameters in the RequestBody
     *
     * @param boolean $isAudit
     * @param string $currency
     * @param string $transactionDocNumber
     * @param int $taxCalculationType
     * @param float $deliveryAmount
     * @param array $lines
     */
    public function RequestBody($isAudit, $currency, $transactionDocNumber, $taxCalculationType, $deliveryAmount, $lines){
        $this->isAudit = $isAudit;
        $this->currency = $currency;
        $this->transactionDocNumber = $transactionDocNumber;
        $this->taxCalculationType = $taxCalculationType;
        $this->deliveryAmount = $deliveryAmount;
        $this->lines = $lines;
    }

    /**
     * Sets the line inside the request body
     *
     * @param Line $line
     */
    public function addLine($line){
        array_push($this->lines, $line);
    }

    /**
     * @return boolean
     */
    public function isIsAudit()
    {
        return $this->isAudit;
    }

    /**
     * @param boolean $isAudit
     * @return RequestBody
     */
    public function setIsAudit($isAudit)
    {
        $this->isAudit = $isAudit;
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     * @return RequestBody
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return int
     */
    public function getTaxCalculationType()
    {
        return $this->taxCalculationType;
    }

    /**
     * @param int $taxCalculationType
     * @return RequestBody
     */
    public function setTaxCalculationType($taxCalculationType)
    {
        $this->taxCalculationType = $taxCalculationType;
        return $this;
    }

    /**
     * @return array
     */
    public function getLines()
    {
        return $this->lines;
    }

    /**
     * @param array $lines
     * @return RequestBody
     */
    public function setLines($lines)
    {
        $this->lines = $lines;
        return $this;
    }

    /**
     * @return float
     */
    public function getDeliveryAmount()
    {
        return $this->deliveryAmount;
    }

    /**
     * @param float $deliveryAmount
     * @return RequestBody
     */
    public function setDeliveryAmount($deliveryAmount)
    {
        $this->deliveryAmount = $deliveryAmount;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionDocNumber()
    {
        return $this->transactionDocNumber;
    }

    /**
     * @param string $transactionDocNumber
     * @return RequestBody
     */
    public function setTransactionDocNumber($transactionDocNumber)
    {
        $this->transactionDocNumber = $transactionDocNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionDocNumber
     * @return RequestBody
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getIsCommitted()
    {
    	return $this->isCommitted;
    }

    /**
     * @param boolean $isCommitted
     * @return RequestBody
     */
    public function setIsCommitted($isCommitted)
    {
    	$this->isCommitted = $isCommitted;
    	return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginAddress(){
        return $this->originAddress;
    }

    /**
     *
     * @param AdapterAddress $originAddress
     * @return RequestBody
     */
    public function setOriginAddress($originAddress){
        $this->originAddress = $originAddress;
        return $this;
    }

    /**
     * @param $destinationAddress
     * @return $this
     */
    public function setDestinationAddress($destinationAddress){
        $this->destinationAddress = $destinationAddress;
        return $this;
    }

    /**
     * @param $apikey
     * @return $this
     */
    public function setSecurity($apikey){

        $this->security = (object) array('Password' => $apikey);
        return $this;
    }

    /**
     * @param $storeAddress
     * @return $this
     */
    public function setStoreInformationAddress($storeAddress){
    	$this->storeInformationAddress = $storeAddress;
    	return $this;
    }

}