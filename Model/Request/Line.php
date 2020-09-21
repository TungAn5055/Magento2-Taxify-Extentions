<?php

namespace Mgroup\Taxify\Model\Request;


/**
 * Magento Line data
 *
 */
class Line implements \JsonSerializable
{
    private $quantity;
    private $ItemTaxabilityCode;
    private $sku;
    private $lineItemId;
    private $discountAmount;
    private $ActualExtendedPrice;

    /**
     * @inheritDoc
     */
    function jsonSerialize()
    {
        $vars = get_object_vars($this);
        return $vars;
    }

    public function __construct()
    {
        $arguments = func_get_args();
        $this->Line($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]);
    }

    /**
     * @param $quantity
     * @param $actualExtendedPrice
     * @param $itemTaxabilityCode
     * @param $sku
     * @param $lineItemId
     * @param $discountAmount
     */
    public function Line($quantity, $actualExtendedPrice, $itemTaxabilityCode, $sku, $lineItemId, $discountAmount)
    {
        $this->quantity = $quantity;
        $this->ActualExtendedPrice = $actualExtendedPrice;
        $this->ItemTaxabilityCode = $itemTaxabilityCode;
        $this->sku = $sku;
        $this->lineItemId = $lineItemId;
        $this->discountAmount = $discountAmount;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return Line
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @return float
     */
    public function getActualExtendedPrice()
    {
        return $this->ActualExtendedPrice;
    }

    /**
     * @param float $actualExtendedPrice
     * @return Line
     */
    public function setActualExtendedPrice($actualExtendedPrice)
    {
        $this->ActualExtendedPrice = $actualExtendedPrice;
        return $this;
    }

    /**
     * @return int
     */
    public function getTransactionType()
    {
        return $this->transactionType;
    }

    /**
     * @param int $transactionType
     * @return Line
     */
    public function setTransactionType($transactionType)
    {
        $this->transactionType = $transactionType;
        return $this;
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @param string $sku
     * @return Line
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLineItemId()
    {
        return $this->lineItemId;
    }

    /**
     * @param mixed $lineItemId
     * @return Line
     */
    public function setLineItemId($lineItemId)
    {
        $this->lineItemId = $lineItemId;
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscountAmount()
    {
        return $this->discountAmount;
    }

    /**
     * @param float $discountAmount
     * @return Line
     */
    public function setDiscountAmount($discountAmount)
    {
        $this->discountAmount = $discountAmount;
        return $this;
    }
}