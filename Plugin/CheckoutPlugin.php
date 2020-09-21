<?php

namespace Mgroup\Taxify\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\PaymentDetailsFactory;
use Magento\Quote\Api\Data\TotalSegmentInterfaceFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Setup\Exception;
use Mgroup\Taxify\Model\Request\Line;
use Mgroup\Taxify\Model\Request\RequestBody;
use Mgroup\Taxify\Model\Request\TaxEngineRequest;
use Mgroup\Taxify\Model\Response\TaxEngineResponse;
use Mgroup\Taxify\Plugin\Util\TaxCalculationUtils;
use Mgroup\Taxify\Plugin\Util\ValidationUtils;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;


/**
 * Class CheckoutPlugin
 * @package Mgroup\Taxify\Plugin
 */
class CheckoutPlugin
{

    const SEGMENT_CODE_TAX = 'tax';
    const SEGMENT_CODE_SHIPPING = 'shipping';
    const SEGMENT_CODE_SUBTOTAL = 'subtotal';
    const SEGMENT_CODE_TOTAL = 'grand_total';

    protected $messageManager;
    protected $checkoutSession;
    protected $total;
    protected $itemConverter;
    protected $totalsConverter;
    protected $dataObjectHelper;
    protected $totalsFactory;
    protected $paymentDetailsFactory;
    protected $paymentMethodManagement;
    protected $cartTotalsRepository;
    protected $logger;
    protected $countryInformationAcquirerInterface;
    protected $scopeConfig;
    protected $client;

    /**
     * CheckoutPlugin constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Address\Total $total
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
     * @param \Magento\Quote\Model\Cart\Totals\ItemConverter $itemConverter
     * @param \Magento\Quote\Model\Cart\TotalsConverter $totalsConverter
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param TotalSegmentInterfaceFactory $totalsFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Model\Quote\Address\Total $total,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Quote\Model\Cart\Totals\ItemConverter $itemConverter,
        \Magento\Quote\Model\Cart\TotalsConverter $totalsConverter,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Quote\Api\Data\TotalSegmentInterfaceFactory $totalsFactory,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Zend\Http\Client $client,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->total = $total;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->itemConverter = $itemConverter;
        $this->totalsConverter = $totalsConverter;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->totalsFactory = $totalsFactory;
        $this->logger = $logger;
        $this->client = $client;
        $this->scopeConfig = $scopeConfig;
        $this->countryInformationAcquirerInterface = $countryInformationAcquirerInterface;
    }


    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return \Magento\Checkout\Model\PaymentDetails
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        \Closure $proceed,
        $cartId,
        ShippingInformationInterface $addressInformation)
    {
        try {
            /** Run original method to load addresses */
            $proceed($cartId, $addressInformation);
            /** Read current Cart data */
            $quote = $this->checkoutSession->getQuote();

            $request = new TaxEngineRequest($this->countryInformationAcquirerInterface, $this->scopeConfig, $this->logger, $this->client);
            $this->mapRequestInformation($request, $quote);
            $request->sendRequest();
            $response = $request->getResponse();

            if (!$response) {
                $cartTotals = $this->cartTotalsRepository->get($cartId);

                /** Update Payment Details with Cart Totals Information*/
                $paymentDetails = $this->paymentDetailsFactory->create();
                $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
                $paymentDetails->setTotals($this->cartTotalsRepository->get($cartId));
                return $paymentDetails;
            }

            /** Get Cart Totals Interface */
            $cartTotals = $this->cartTotalsRepository->get($cartId);

            /** Update Cart Totals with TWE Response*/
            $this->updateTotals($cartTotals, $response);

            /** Update Payment Details with Cart Totals Information*/
            $paymentDetails = $this->paymentDetailsFactory->create();
            $paymentDetails->setPaymentMethods($this->paymentMethodManagement->getList($cartId));
            $paymentDetails->setTotals($cartTotals);

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $paymentDetails;
    }

    /**
     * @param \Magento\Quote\Api\Data\TotalsInterface $cartTotals
     * @param TaxEngineResponse $response
     */
    public function updateTotals(\Magento\Quote\Api\Data\TotalsInterface &$cartTotals, \Mgroup\Taxify\Model\Response\TaxEngineResponse $response)
    {

        /** Update Items Totals Information */
        $this->updateItemsTotals($cartTotals, $response);

        /** Update Summary Totals Information */
        $this->updateSummaryTotals($cartTotals, $response);

        /** Update Total Segments Information */
        $this->updateTotalSegments($cartTotals, $response);

    }

    /**
     * @param TaxEngineRequest $request
     * @param Quote $quote
     */
    public function mapRequestInformation(TaxEngineRequest &$request, Quote $quote)
    {
        if ($quote->isVirtual()) {
            $shippingAddress = $quote->getBillingAddress();
        } else {
            $shippingAddress = $quote->getShippingAddress();
        }

        $shipTo = TaxCalculationUtils::mapAddressInformation($shippingAddress);

        // Checkout Audit needs to be false but PlaceOrder depends on plugin settings -> $isAudit = ISAUDIT;
        $isAudit = false;
        $currency = $quote->getBaseCurrencyCode();
        $lines = array();

        foreach ($quote->getAllVisibleItems() as $item) {
            $grossAmount = $item->getBasePrice() * $item->getQty();
            $quantity = $item->getQty();
            $sku = ValidationUtils::validateSkuWithLength40($item->getSku());
            $discountAmount = $item->getDiscountAmount();
            $line = new Line($quantity, $grossAmount, 'TAXABLE', $sku, null, $discountAmount);
            array_push($lines, $line);
        }

//        $shippingAmount = $quote->getShippingAddress()->getShippingAmount();
//        $shippingDiscountAmount = $quote->getShippingAddress()->getShippingDiscountAmount();
//        /** ShippingAmount will be considered as a item line **/
//        $shippingLine = new Line(1, $shippingAmount, $transactionType, $skuShipping , null, null, null, $shippingDiscountAmount, '');
//        array_push($lines, $shippingLine);
        $trnDocNum = $quote->getId();

        $requestBody = new RequestBody();
        $requestBody->setIsAudit($isAudit);
        $requestBody->setOriginAddress($request->getAddressFromConfiguration('shipping/origin/'));
        $requestBody->setDestinationAddress($shipTo);
        $requestBody->setCurrency($currency);
        $requestBody->setTransactionDocNumber($trnDocNum);
//        $requestBody->setTaxCalculationType($taxCalculationType);
        $requestBody->setDeliveryAmount(0);
        $requestBody->setLines($lines);

        $request->setRequestBody($requestBody);
    }

    /**
     * Return ShippingTaxAmount using TWE TaxRate
     *
     * @param float $shippingAmount
     * @param float $taxRate
     * @return float $shippingTaxAmount
     */
    public function calculateShippingTaxAmount($shippingAmount, $taxRate)
    {
        return ($shippingAmount * $taxRate);
    }

    /**
     * Update Shipping Totals Information
     *
     * @param \Magento\Quote\Api\Data\TotalsInterface $cartTotals
     * @param \Mgroup\Taxify\Model\Response\TaxEngineResponse $response
     */
    public function updateShippingTotals(\Magento\Quote\Api\Data\TotalsInterface $cartTotals, TaxEngineResponse $response)
    {

        $shippingAmount = $cartTotals->getShippingAmount() - $cartTotals->getShippingDiscountAmount();

        $lines = $response->getLines();

        /** Since the shipping item line is the last to be inserted */
        $shippingAsItemIndex = count($lines) - 1;

        if ($lines[$shippingAsItemIndex]->hasFees()) {
            $taxRate = $lines[$shippingAsItemIndex]->getTaxRateExclFees();
        } else {
            $taxRate = $lines[$shippingAsItemIndex]->getTaxRate();
        }

        $shippingTaxAmount = $lines[$shippingAsItemIndex]->getTaxAmount();

        $shippingAmountInclTax = $shippingAmount + $shippingTaxAmount;

        //Shipping Incl. Tax
        $cartTotals->setShippingInclTax($shippingAmountInclTax);
        $cartTotals->setBaseShippingInclTax($shippingAmountInclTax);

        //Shipping Tax Amount
        $cartTotals->setShippingTaxAmount($shippingTaxAmount);
        $cartTotals->setBaseShippingTaxAmount($shippingTaxAmount);
    }

    /**
     *  Update Order Summary Totals Information
     *
     * @param \Magento\Quote\Api\Data\TotalsInterface $cartTotals
     * @param \Mgroup\Taxify\Model\Response\TaxEngineResponse $response
     */
    public function updateSummaryTotals(\Magento\Quote\Api\Data\TotalsInterface $cartTotals, TaxEngineResponse $response)
    {

        try {
            //Tax Amount
            $cartTotals->setTaxAmount($response->getSalesTaxAmount());
            $cartTotals->setBaseTaxAmount($response->getSalesTaxAmount());


            //Totals
            $subtotalInclTax = $cartTotals->getSubtotal() + $cartTotals->getTaxAmount();
            $grandTotal = $cartTotals->getSubtotal() + $cartTotals->getShippingAmount() + $cartTotals->getDiscountAmount();

//        if ($response->getFeesAmount() <= 0 && $response->getExemptAmount() <= 0) {
//            $subtotalInclTax -= $this->calculateShippingTaxAmount($cartTotals->getShippingAmount(), $response->getTaxRateExclFees());
//        }

            //Cart Subtotal Incl. Tax
            $cartTotals->setSubtotalInclTax($subtotalInclTax);
            $cartTotals->setBaseSubtotalInclTax($subtotalInclTax);

            //Order Total Excl. Tax - SEGMENT_CODE_TOTAL
            $cartTotals->setGrandTotal($grandTotal);

            //Order Total Incl. Tax - SEGMENT_CODE_TOTAL
            $cartTotals->setBaseGrandTotal($grandTotal + $response->getSalesTaxAmount());
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }


    /**
     *  Update Quote Items Total Information
     *
     * @param \Magento\Quote\Api\Data\TotalsInterface $cartTotals
     * @param \Mgroup\Taxify\Model\Response\TaxEngineResponse $response
     */
    public function updateItemsTotals(\Magento\Quote\Api\Data\TotalsInterface $cartTotals, \Mgroup\Taxify\Model\Response\TaxEngineResponse $response)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/an3.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info('data123');

        try {

            $quote = $this->checkoutSession->getQuote();

            $items = $quote->getAllVisibleItems();
            $lines = $response->getLines();
            foreach ($lines as $key => $line) {

                $taxRate = $line->getTaxRate();
                $taxAmount = $line->getSalesTaxAmount();
                $priceInclTax = $items[$key]->getBasePrice() + ($taxAmount / $items[$key]->getQty());
                $rowTotalInclTax = ($items[$key]->getBasePrice() * $items[$key]->getQty()) + $taxAmount;

                $items[$key]->setTaxPercent($taxRate);
                $items[$key]->setTaxAmount($taxAmount);
                $items[$key]->setBaseTaxAmount($taxAmount);

                $items[$key]->setPriceInclTax($priceInclTax);
                $items[$key]->setBasePriceInclTax($priceInclTax);

                $items[$key]->setRowTotalInclTax($rowTotalInclTax);
                $items[$key]->setBaseRowTotalInclTax($rowTotalInclTax);

                $items[$key] = $this->itemConverter->modelToDataObject($items[$key]);
            }


            $cartTotals->setItems($items);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }


    /**
     * Update Total Segments Information
     *
     * @param \Magento\Quote\Api\Data\TotalsInterface $cartTotals
     * @param \Mgroup\Taxify\Model\Response\TaxEngineResponse $response
     */
    public function updateTotalSegments(\Magento\Quote\Api\Data\TotalsInterface &$cartTotals, \Mgroup\Taxify\Model\Response\TaxEngineResponse $response)
    {

        $quote = $this->checkoutSession->getQuote();

        $addressTotalsData = $quote->getShippingAddress()->getTotals();
        $addressTotals = $quote->getShippingAddress()->getTotals();

        foreach ($addressTotals as &$addressTotal) {

            switch ($addressTotal->getCode()) {
                case self::SEGMENT_CODE_TAX:
                    $addressTotal->setValue($cartTotals->getBaseTaxAmount());
                    break;
                case self::SEGMENT_CODE_SUBTOTAL:
                    $addressTotal->setValue($cartTotals->getBaseSubtotalInclTax());
                    break;
                case self::SEGMENT_CODE_TOTAL:
                    $addressTotal->setValue($cartTotals->getBaseGrandTotal());
                    break;
            }

        }
        $quoteTotals = $this->totalsFactory->create();

        $this->dataObjectHelper->populateWithArray(
            $quoteTotals,
            $addressTotalsData,
            '\Magento\Quote\Api\Data\TotalsInterface'
        );

        $calculatedTotals = $this->totalsConverter->process($addressTotals);

        $cartTotals->setTotalSegments($calculatedTotals);
    }
}