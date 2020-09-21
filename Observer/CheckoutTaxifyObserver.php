<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace Mgroup\Taxify\Observer;

use Magento\Quote\Model\Quote;
use Mgroup\Taxify\Model\Request\Line;
use Mgroup\Taxify\Model\Request\RequestBody;
use Mgroup\Taxify\Model\Request\TaxEngineRequest;
use Mgroup\Taxify\Model\Response\TaxEngineResponse;
use Mgroup\Taxify\Plugin\Util\TaxCalculationUtils;
use Mgroup\Taxify\Plugin\Util\ValidationUtils;

/**
 * Class CheckoutTaxifyObserver
 * @package Mgroup\Taxify\Observer
 */
class CheckoutTaxifyObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * CheckoutCheckFailuresObserver constructor.
     *
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession *Proxy
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Api\CountryInformationAcquirerInterface $countryInformationAcquirerInterface,
        \Zend\Http\Client $client,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->client = $client;
        $this->scopeConfig = $scopeConfig;
        $this->countryInformationAcquirerInterface = $countryInformationAcquirerInterface;
    }

    /**
     * If customer has failed checkout more than X times within the last Y seconds, block them from further checkout
     * attempts. This is to prevent credit card testing on checkout, runaway txn charges, and an unhappy gateway.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData('order');

        $request = new TaxEngineRequest($this->countryInformationAcquirerInterface, $this->scopeConfig, $this->logger, $this->client);
        $this->mapRequestInformation($request, $order);
        $request->sendRequest();
        $response = $request->getResponse();
        if ($response) {
            $this->updateTotals($order, $response);
        }
    }

    /**
     * @param \Magento\Quote\Api\Data\TotalsInterface $cartTotals
     * @param TaxEngineResponse $response
     */
    public function updateTotals($order, \Mgroup\Taxify\Model\Response\TaxEngineResponse $response)
    {

        //Tax Amount
        $order->setTaxAmount($response->getSalesTaxAmount());
        $order->setBaseTaxAmount($response->getSalesTaxAmount());

        //Totals
        $subtotalInclTax = $order->getSubtotal() + $order->getTaxAmount();
        $grandTotal = $order->getSubtotal() + $order->getShippingAmount() + $order->getDiscountAmount();

        //Cart Subtotal Incl. Tax
        $order->setSubtotalInclTax($subtotalInclTax);
        $order->setBaseSubtotalInclTax($subtotalInclTax);

        //Order Total Excl. Tax - SEGMENT_CODE_TOTAL
        $order->setGrandTotal($grandTotal + $response->getSalesTaxAmount());

        //Order Total Incl. Tax - SEGMENT_CODE_TOTAL
        $order->setBaseGrandTotal($grandTotal + $response->getSalesTaxAmount());
    }

    /**
     * @param TaxEngineRequest $request
     * @param Quote $quote
     */
    public function mapRequestInformation(TaxEngineRequest &$request, $quote)
    {
        $shippingAddress = $quote->getShippingAddress();

        $shipTo = TaxCalculationUtils::mapAddressInformation($shippingAddress);

        // Checkout Audit needs to be false but PlaceOrder depends on plugin settings -> $isAudit = ISAUDIT;
        $isAudit = false;
        $currency = $quote->getBaseCurrencyCode();
        $lines = array();

        foreach ($quote->getAllVisibleItems() as $item) {
            $grossAmount = $item->getBasePrice() * $item->getQtyOrdered();
            $quantity = $item->getQtyOrdered();
            $sku = ValidationUtils::validateSkuWithLength40($item->getSku());
            $discountAmount = $item->getDiscountAmount();
            $line = new Line($quantity, $grossAmount, 'TAXABLE', $sku, null, $discountAmount);
            array_push($lines, $line);
        }

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

}
