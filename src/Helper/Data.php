<?php

namespace Divido\DividoFinancing\Helper;

require __DIR__ . '/../vendor/divido/divido-php/lib/Divido.php';

use \Divido\DividoFinancing\Model\LookupFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const CACHE_DIVIDO_TAG = 'divido_cache';
    const CACHE_PLANS_KEY  = 'divido_plans';
    const CACHE_PLANS_TTL  = 3600;
    const CALLBACK_PATH    = 'rest/V1/divido/update';
    const REDIRECT_PATH    = 'checkout/success';

    private
        $config,
        $logger,
        $cache,
        $cart,
        $storeManager,
        $lookupFactory;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        LookupFactory $lookupFactory
    )
    {
        $this->config        = $scopeConfig;
        $this->logger        = $logger;
        $this->cache         = $cache;
        $this->cart          = $cart;
        $this->storeManager  = $storeManager;
        $this->lookupFactory = $lookupFactory;
    }

    public function getAllPlans ()
    {
        $apiKey = $this->config->getValue('payment/divido_financing/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (empty($apiKey)) {
            $this->cleanCache();
            return [];
        }

        if ($plans = $this->cache->load(self::CACHE_PLANS_KEY)) {
            $plans = unserialize($plans);
            return $plans;
        }

        \Divido::setMerchant($apiKey);

        $response = \Divido_Finances::all();

        if ($response->status !== 'ok') {
            $this->logger->addError('Divido: Could not get financing plans.');
            $this->cleanCache();
            return [];
        }

        $plans = $response->finances;

        $this->cache->save(serialize($plans), 
            self::CACHE_PLANS_KEY, 
            [self::CACHE_DIVIDO_TAG], 
            self::CACHE_PLANS_TTL);
        
        return $plans;
    }

    public function cleanCache ()
    {
        $this->cache->clean('matchingTag', [self::CACHE_DIVIDO_TAG]);
    }

    public function creditRequest ($planId, $depositPercentage, $email)
    {
        ini_set('html_errors', 0);
        $apiKey = $this->getApiKey();

        \Divido::setMerchant($apiKey);

        $quote   = $this->cart->getQuote();
        $billing = $quote->getBillingAddress();
        $country = $billing->getCountryId();

        $language = 'EN';

        $store = $this->storeManager->getStore();
        $currency = $store->getCurrentCurrencyCode();

        $customer = [
            'title'         => '',
            'first_name'    => $billing->getFirstName(),
            'middle_name'   => $billing->getMiddleName(),
            'last_name'     => $billing->getLastName(),
            'country'       => $country,
            'postcode'      => $billing->getPostcode(),
            'email'         => $email,
            'mobile_number' => '',
            'phone_number'  => $billing->getTelephone(),
        ];

        $products = [];
        foreach ($quote->getAllItems() as $item) {
            $products[] = [
                'type'     => 'product',
                'text'     => $item->getName(),
                'quantity' => $item->getQty(),
                'value'    => $item->getPrice(),
            ];
        }

        $totals = $quote->getTotals();
        $grandTotal = $totals['grand_total']->getValue();
        $deposit = round(($depositPercentage/100) * $grandTotal, 2);

        $shipping = $billing->getShippingAmount();
        if (! empty($shipping)) {
            $products[] = [
                'type'     => 'product',
                'text'     => 'Shipping & Handling',
                'quantity' => '1',
                'value'    => $shipping,
            ];
        }

        $discount = $billing->getDiscountAmount();
        if (! empty($discount)) {
            $products[] = [
                'type'     => 'product',
                'text'     => 'Discount',
                'quantity' => '1',
                'value'    => $discount,
            ];
        }

        $response_url = $store->getBaseUrl() . self::CALLBACK_PATH;
        $redirect_url = $store->getBaseUrl() . self::REDIRECT_PATH;

        $quoteId = $quote->getId();
        $salt = uniqid('', true);
        $quoteHash = $this->hashQuote($salt, $quoteId);

        $requestData = [
            'merchant' => $apiKey,
            'deposit'  => $deposit,
            'finance'  => $planId,
            'country'  => $country,
            'language' => $language,
            'currency' => $currency,
            'metadata' => [
                'quote_id'   => $quoteId,
                'quote_hash' => $quoteHash,
            ],
            'customer'     => $customer,
            'products'     => $products,
            'response_url' => $response_url,
            'redirect_url' => $redirect_url,
        ];

        $response = \Divido_CreditRequest::create($requestData);

        if ($response->status == 'ok') {
            $lookupModel = $this->lookupFactory->create();
            $lookupModel->load($quoteId, 'quote_id');

            $lookupModel->setData('quote_id', $quoteId);
            $lookupModel->setData('salt', $salt);
            $lookupModel->save();

            return $response->url;
        } else {
            if ($response->status === 'error') {
                throw new Exception($response->error);
            }
        }
    }

    public function hashQuote ($salt, $quoteId)
    {
        return hash('sha256', $salt.$quoteId);
    }

    public function getApiKey ()
    {
        $apiKey = $this->config->getValue('payment/divido_financing/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        return $apiKey;
    }

    public function getScriptUrl ()
    {
        $apiKey = $this->getApiKey();
    
        if (empty($apiKey)) {
            return '';
        }

        $keyParts = explode('.', $apiKey);
        $relevantPart = array_shift($keyParts);

        $jsKey = strtolower($relevantPart);

        return "//js.divido.dev/calculator.php";

        return "//cdn.divido.com/calculator/{$jsKey}.js";
    }
}
