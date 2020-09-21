<?php

namespace Mgroup\Taxify\Plugin\Util;

use Magento\Quote\Model\Quote\Address;
use Sovos\TaxCalculation\Model\Request\AdapterAddress;
use Sovos\TaxCalculation\Model\Request\Line;
use Sovos\TaxCalculation\Model\Request\RequestBody;

class TaxCalculationUtils {

    /**
     *
     */
	const DEFAULT_CLIENT_ERROR_MESSAGE = 'An error has occurred. Please try your transaction again.';

	/**
     * Map the address information to an adapter address
     *
     * @param Address $addressInformation
     * @return AdapterAddress
     */
    public static function mapAddressInformation($addressInformation){

        $adapterAddress = new AdapterAddress();
        $adapterAddress->setStreetNameWithNumber($addressInformation->getStreetFull());
        $adapterAddress->setCity($addressInformation->getCity());
        $adapterAddress->setRegion($addressInformation->getRegionCode());
        $adapterAddress->setCountry($addressInformation->getCountryId());
        $adapterAddress->setPostalCode($addressInformation->getPostcode());
        return $adapterAddress;
    }

    /**
     * Get the tax amount based on each destination address.
     *
     * @param TaxEngineResponse $response
     * @param string $shippingId
     * @return float
     */
    public static function getAddressTaxAmount($response, $shippingId){

        $addressTaxAmount = 0.0;
        foreach ($response->getLines() as $responseLine){
            // @var $responseLine \Sovos\TaxCalculation\Model\Response\Line
            $lineId = $responseLine->getLineId();
            if(strcmp($shippingId, $lineId) == 0){
                $addressTaxAmount += $responseLine->getTaxAmount();
            }
        }
        return $addressTaxAmount;
    }

    /**
     * Create the request body to be used in the TWE Tax Calculation Request
     *
     * @param string $currency
     * @param array $lines
     * @param int $taxCalculationType
     * @param string $quoteId
     * @return RequestBody
     */
    public static function createRequestBody($currency, $isAudit, $lines, $taxCalculationType, $transactionDocumentNumber, $transactionId){
        $requestBody = new RequestBody();
        $requestBody->setCurrency($currency);
        $requestBody->setIsAudit($isAudit);
        $requestBody->setLines($lines);
        $requestBody->setTaxCalculationType($taxCalculationType);
        $requestBody->setTransactionDocNumber($transactionDocumentNumber);
        
        if($isAudit){
        	$requestBody->setTransactionId($transactionId);
        }
        
        return $requestBody;
    }

    /**
     * Create the magento adapter lines, including the shipping information (mapped to a GSC in TWE)
     *
     * @param array $shippingAddressList
     * @param Address $billingAddress
     * @param int $transactionType
     * @param string $shipPrefix
     * @return array
     */
    public static function createLines($shippingAddressList, $billingAddress, $transactionType, $shipPrefix){

        $lines = array();
        foreach ($shippingAddressList as $address) {
            // @var $address Address
            $shipTo = TaxCalculationUtils::mapAddressInformation($address);
            $billTo = TaxCalculationUtils::mapAddressInformation($billingAddress);
            $shippingMethod = $address->getShippingMethod();
            $sku = $shipPrefix.substr($shippingMethod, 0, strpos($shippingMethod, "_"));
            $shippingId = $address->getId().$address->getRegionCode().$address->getCity();
            $line = TaxCalculationUtils::mapLineInformation($sku, $address->getShippingAmount() - $address->getShippingDiscountAmount(), 1,
                $transactionType, $shipTo, $billTo, $shippingId, 0.0 , '_');
            array_push($lines, $line);
            foreach ($address->getAllVisibleItems() as $item){
                /* @var $item Item*/
                $quantity = intval($item->getQty());
                $grossAmount = $item->getBasePrice()*$quantity;
                $discountAmount = $item->getDiscountAmount();
                $sku = $item->getSku();
                $line = TaxCalculationUtils::mapLineInformation($sku, $grossAmount, $quantity,
                    $transactionType, $shipTo, $billTo, $shippingId, $discountAmount);

                array_push($lines, $line);
            }
        }
        return $lines;
    }

    /**
     * Map the Magento line information to an adapter line object
     *
     * @param string $sku
     * @param float $grossAmount
     * @param int $quantity
     * @param int $transactionType
     * @param AdapterAddress $shipTo
     * @param AdapterAddress $billTo
     * @param string $shippingId
     * @param string $discountAmount
     * @param string $itemCategory
     *
     * @return Line
     */
    public static function mapLineInformation($sku, $grossAmount, $quantity, $transactionType, $shipTo, $billTo, $shippingId, $discountAmount, $itemCategory){

        $line = new Line();
        $line->setQuantity($quantity);
        $line->setSku($sku);
        $line->setGrossAmount($grossAmount);
        $line->setTransactionType($transactionType);
        $line->setShipTo($shipTo);
        $line->setBillTo($billTo);
        $line->setLineItemId($shippingId);
        $line->setDiscountAmount($discountAmount);
        $line->setItemCategory($itemCategory);
        return $line;
    }

    /**
     * Update the order tax amounts with the response from TWE.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param float $addressTaxAmount
     * @param float $addressTotalAmount
     * @param float $subtotal
     * @return \Magento\Sales\Model\Order
     */
    public static function updateOrderAmounts($order, $addressTaxAmount, $addressTotalAmount, $subtotal){
        $order->setTaxAmount($addressTaxAmount);
        $order->setBaseTaxAmount($addressTaxAmount);
        $order->setBaseSubtotalInclTax($addressTaxAmount+$subtotal);
        $order->setSubtotalInclTax($addressTaxAmount+$subtotal);
        $order->setBaseGrandTotal($addressTotalAmount);
        $order->setGrandTotal($addressTotalAmount);
        return $order;
    }


    /**
     * Update the address amounts with the corresponding taxes.
     *
     * @param Address $address
     * @param float $addressTaxAmount
     * @return Address
     */
    public static function updateAddressTaxes($address, $addressTaxAmount){
        $addressTotalAmount = $address->getSubtotal()+$address->getShippingAmount()+$addressTaxAmount+$address->getDiscountAmount();
        $subtotalInclTax = $addressTaxAmount+$address->getSubtotal();

        $address->setTaxAmount($addressTaxAmount);
        $address->setBaseTaxAmount($addressTaxAmount);
        $address->setGrandTotal($addressTotalAmount);
        $address->setBaseGrandTotal($addressTotalAmount);
        $address->setSubtotalInclTax($subtotalInclTax);
        return $address;
    }

    /**
     * Generate transaction ID number to send to TWE, based on the store name and quote id
     *
     * @param string $frontendName
     * @param string $id
     * @return string
     */
    public static function generateTransactionId($frontendName, $id){
        if($frontendName == null || $id == null){
            return null;
        }
        $storeName = str_replace(' ','',$frontendName);
        $transactionId = $storeName.'-'.$id;
        return $transactionId;
    }
}