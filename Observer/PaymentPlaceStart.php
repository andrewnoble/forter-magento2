<?php
namespace Forter\Forter\Observer;

use Forter\Forter\Model\AuthRequestBuilder;
use Forter\Forter\Model\Config;
use Magento\Framework\Event\ObserverInterface;

class PaymentPlaceStart implements ObserverInterface
{
    public function __construct(
        Config $config,
        AuthRequestBuilder $authRequestBuilder
    ) {
        $this->config = $config;
        $this->authRequestBuilder = $authRequestBuilder;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return false;
        }

        $this->config->log('1-PaymentPlaceStart');
        $order = $observer->getEvent()->getPayment()->getOrder();
        $data = $this->authRequestBuilder->buildTransaction($order);
        $this->config->log('PaymentPlaceStart');
        $this->config->log($data);
    }
}