<?php

namespace Osio\GuestToCustomerOrders\Observer;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Osio\GuestToCustomerOrders\Helper\Data as Helper;

class CustomerRegister implements ObserverInterface
{
    private Helper $helper;

    private Collection $orders;

    private CustomerRepositoryInterface $customerRepository;

    public function __construct(
        Helper $helper,
        Collection $orders,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->helper = $helper;
        $this->orders = $orders;
        $this->customerRepository = $customerRepository;
    }

    public function execute(EventObserver $observer)
    {
        if ($this->helper->isEnabled()) {
            $this->handleFormerGuestAccount($this->getCustomer($observer));
        }
    }

    private function handleFormerGuestAccount(CustomerInterface $customer)
    {
        if ($this->hasGuestOrders($customer)) {
            try {
                $customer->setCustomAttribute('was_guest', 1);
                $this->customerRepository->save($customer);
            } catch (Exception $e) {
                $this->helper->log($e);
            }
        }
    }

    public function hasGuestOrders(CustomerInterface $customer): bool
    {
        return (bool)$this->orders
            ->addFieldToFilter('customer_email', $customer->getEmail())
            ->count();
    }

    private function getCustomer(EventObserver $observer): CustomerInterface
    {
        return $observer->getEvent()->getData('customer');
    }
}
