<?php

namespace Divido\DividoFinancing\Helper;

require __DIR__ . '/../vendor/divido/divido-php/lib/Divido.php';

class Data extends \Magento\Framework\App\Helper\AbstractHelper{

    const CACHE_DIVIDO_TAG = 'divido_cache';
    const CACHE_PLANS_KEY  = 'divido_plans';
    const CACHE_PLANS_TTL  = 3600;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\CacheInterface $cache
    )
    {
        $this->config = $scopeConfig;
        $this->logger = $logger;
        $this->cache  = $cache;
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
}
