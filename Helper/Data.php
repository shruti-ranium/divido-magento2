<?php

namespace Divido\DividoFinancing\Helper;

use \Divido\DividoFinancing\Model\LookupFactory;
use Magento\Framework\UrlInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    const CACHE_DIVIDO_TAG = 'divido_cache';
    const CACHE_PLANS_KEY  = 'divido_plans';
    const CACHE_PLANS_TTL  = 3600;
    const CALLBACK_PATH    = 'rest/V1/divido/update/';
    const REDIRECT_PATH    = 'divido/financing/success/';
    const CHECKOUT_PATH    = 'checkout/';

    private
        $config,
        $logger,
        $cache,
        $cart,
        $storeManager,
        $lookupFactory,
        $resource,
        $connection,
        $urlBuilder;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resource,
        LookupFactory $lookupFactory,
        UrlInterface $urlBuilder
    )
    {
        $this->config        = $scopeConfig;
        $this->logger        = $logger;
        $this->cache         = $cache;
        $this->cart          = $cart;
        $this->storeManager  = $storeManager;
        $this->resource      = $resource;
        $this->lookupFactory = $lookupFactory;
        $this->urlBuilder    = $urlBuilder;
    }

    public function getConnection ()
    {
        if (! $this->connection) {
            $this->connection = $this->resource->getConnection('core_write');
        }

        return $this->connection;
    }

    public function cleanCache ()
    {
        $this->cache->clean('matchingTag', [self::CACHE_DIVIDO_TAG]);
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

    public function getGlobalSelectedPlans ()
    {
        $plansDisplayed = $this->config->getValue('payment/divido_financing/plans_displayed',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $plansDisplayed = $plansDisplayed ?: 'plans_all';

        $plansSelection = $this->config->getValue('payment/divido_financing/plan_selection',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $plansSelection = $plansSelection ? explode(',', $plansSelection) : [];

        $plans = $this->getAllPlans();

        if ($plansDisplayed != 'plans_all') {
            foreach ($plans as $key => $plan) {
                if (! in_array($plan->id, $plansSelection)) {
                    unset($plans[$key]);
                }
            }
        }

        return $plans;
    }

    public function getQuotePlans ($quote)
    {
        $totals = $quote->getTotals();
        $items  = $quote->getAllVisibleItems();

        $grandTotal = $totals['grand_total']->getValue();

        $plans = [];
        foreach ($items as $item) {
            $product    = $item->getProduct();
            $localPlans = $this->getLocalPlans($product->getId());
            $plans      = array_merge($plans, $localPlans);
        }

        foreach ($plans as $key => $plan) {
            $planMinTotal = $grandTotal - ($grandTotal * ($plan->min_deposit / 100));
            if ($planMinTotal < $plan->min_amount) {
                unset($plans[$key]);
            }
        }

        return $plans;
    }

    public function getLocalPlans ($productId)
    {
		$isActive = $this->config->getValue('payment/divido_financing/active',
			\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if (! $isActive) {
			return[];
		}

        $dbConn  = $this->getConnection();
        $tblCpev = $this->resource->getTableName('catalog_product_entity_varchar'); 
        $tblEava = $this->resource->getTableName('eav_attribute'); 
        
        $sqlTpl = "
            select cpev.value 
            from %s cpev 
                join %s eava 
                on eava.attribute_id = cpev.attribute_id 
            where eava.attribute_code = '%s' 
            and cpev.entity_id = %s";

        $sqlDisplay = sprintf($sqlTpl, $tblCpev, $tblEava, 'divido_plans_display', $productId);
        $sqlPlans   = sprintf($sqlTpl, $tblCpev, $tblEava, 'divido_plans_list', $productId);

        $globalProdSelection = $this->config->getValue('payment/divido_financing/product_selection',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $display = $dbConn->fetchRow($sqlDisplay);
        if ($display) {
            $display = $display['value'];
        }

        $productPlans = $dbConn->fetchRow($sqlPlans);
        if ($productPlans) {
            $productPlans = $productPlans['value'];
            $productPlans = empty($productPlans) ? [] : explode(',', $productPlans);
        }

        if (!$display || $display == 'product_plans_default' || (empty($productPlans) && $globalProdSelection != 'products_selected')) {
            return $this->getGlobalSelectedPlans();
        }

        $plans = $this->getAllPlans();
        foreach ($plans as $key => $plan) {
            if (! in_array($plan->id, $productPlans)) {
                unset($plans[$key]);
            }
        }

        return $plans;
    }

    public function creditRequest ($planId, $depositPercentage, $email)
    {
        ini_set('html_errors', 0);
        $apiKey = $this->getApiKey();

        \Divido::setMerchant($apiKey);

        $quote   = $this->cart->getQuote();
        $shipAddr = $quote->getShippingAddress();
        $country = $shipAddr->getCountryId();

        $quote->setCustomerEmail($email);
        $quote->save();

        $language = 'EN';

        $store = $this->storeManager->getStore();
        $currency = $store->getCurrentCurrencyCode();

        $customer = [
            'title'         => '',
            'first_name'    => $shipAddr->getFirstName(),
            'middle_name'   => $shipAddr->getMiddleName(),
            'last_name'     => $shipAddr->getLastName(),
            'country'       => $country,
            'postcode'      => $shipAddr->getPostcode(),
            'email'         => $email,
            'mobile_number' => '',
            'phone_number'  => $shipAddr->getTelephone(),
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

        $shipping = $shipAddr->getShippingAmount();
        if (! empty($shipping)) {
            $products[] = [
                'type'     => 'product',
                'text'     => 'Shipping & Handling',
                'quantity' => '1',
                'value'    => $shipping,
            ];
        }

        $discount = $shipAddr->getDiscountAmount();
        if (! empty($discount)) {
            $products[] = [
                'type'     => 'product',
                'text'     => 'Discount',
                'quantity' => '1',
                'value'    => $discount,
            ];
        }

        $quoteId   = $quote->getId();
        $salt      = uniqid('', true);
        $quoteHash = $this->hashQuote($salt, $quoteId);

        $response_url = $this->urlBuilder->getUrl(self::CALLBACK_PATH);
        $checkout_url = $this->urlBuilder->getUrl(self::CHECKOUT_PATH);
        $redirect_url = $this->urlBuilder->getUrl(self::REDIRECT_PATH, 
            ['quote_id' => $quoteId]);

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
            $lookupModel->setData('deposit_value', $deposit);
            $lookupModel->setData('proposal_id', $response->id);
            $lookupModel->save();

            return $response->url;
        } else {
            if ($response->status === 'error') {
                throw new \Exception($response->error);
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

        return "//cdn.divido.com/calculator/{$jsKey}.js";
    }

    public function plans2list ($plans)
    {
        $plansBare = array_map(function ($plan) {
            return $plan->id;
        }, $plans);

        $plansBare = array_unique($plansBare);

        return implode(',', $plansBare);
    }

    public function getLookupForOrder ($order)
    {
        $quoteId = $order->getQuoteId();

        $lookupModel = $this->lookupFactory->create();
        $lookupModel->load($quoteId, 'quote_id');
        if (! $lookupModel->getId()) {
            return null;
        }

        return [
            'proposal_id'    => $lookupModel->getData('proposal_id'),
            'application_id' => $lookupModel->getData('application_id'),
            'deposit_amount' => $lookupModel->getData('deposit_value'),
        ];
    }
}
