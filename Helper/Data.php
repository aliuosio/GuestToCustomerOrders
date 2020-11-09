<?php

namespace Osio\GuestToCustomerOrders\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{

    private LoggerInterface $log;

    /** @var string */
    const IS_MODULE_ENABLED = 'gtco/general/enable';

    public function __construct(Context $context, LoggerInterface $log)
    {
        parent::__construct($context);
        $this->log = $log;
    }

    public function log(Exception $e)
    {
        $this->log->error(get_class($e) . ': ' . $e->getMessage());
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isModuleEnabled(self::IS_MODULE_ENABLED);
    }

    public function getConfig($config_path, $scopeCode = null): array
    {
        return $this->scopeConfig->getValue(
            $config_path,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );
    }

    public function isModuleEnabled(string $path, $scopeCode = null): bool
    {
        return (bool)$this->getConfig($path, $scopeCode);
    }

}
