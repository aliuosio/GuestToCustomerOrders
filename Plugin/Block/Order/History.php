<?php

namespace Osio\GuestToCustomerOrders\Plugin\Block\Order;

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Block\Order\History as HistoryOrigin;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Osio\GuestToCustomerOrders\Helper\Data as Helper;

class History
{
    private HistoryOrigin $subject;

    private Helper $helper;

    private Collection $orderCollection;

    private CurrentCustomer $customerSession;

    private CustomerInterface $customer;

    private CustomerRepositoryInterface $customerRepository;

    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        CurrentCustomer $customerSession,
        Helper $helper,
        Collection $orderCollection,
        CustomerRepositoryInterface $customerRepository,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->helper = $helper;
        $this->orderCollection = $orderCollection;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
    }

    public function beforeGetOrders(HistoryOrigin $subject)
    {
        if (!$this->helper->isEnabled()) {
            return $this;
        }

        $this->subject = $subject;
        $this->customer = $this->customerSession->getCustomer();

        $this->setTransformAsked();

        if ($this->canTransform()) {
            $this->transform() ?: $this->customer->setCustomAttribute('guest_orders_transformed', 1);
        }

        $this->saveCustomer();

        return $this;
    }

    private function canTransform(): bool
    {
        return (
            (bool)$this->subject->getRequest()->getParam('transform')
            &&
            $this->customer->getCustomAttribute('was_guest')->getValue()
            &&
            $this->customer->getCustomAttribute('asked_transform_guest_orders')->getValue()
            &&
            !$this->customer->getCustomAttribute('guest_orders_transformed')->getValue()
        );
    }

    private function saveCustomer()
    {
        try {
            $this->customerRepository->save($this->customer);
        } catch (Exception $e) {
            $this->helper->log()->error($e->getTraceAsString());
        }
    }

    private function setTransformAsked()
    {
        if ((bool)$this->subject->getRequest()->getParam('transform')) {
            $this->customer->setCustomAttribute('asked_transform_guest_orders', 1);
        }
    }

    private function transform()
    {
        if ($this->getGuestOrders()->count() == 0) {
            return false;
        }

        /** @var OrderInterface $guestOrder */
        foreach ($this->getGuestOrders() as $guestOrder) {
            $guestOrder->setCustomerId($this->customer->getId());
            $this->orderRepository->save($guestOrder);
        }

        return true;
    }

    private function getGuestOrders(): Collection
    {
        return $this->orderCollection
            ->addFieldToFilter('customer_email', ['in' => [$this->customer->getEmail()]])
            ->load();
    }
}
