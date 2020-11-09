<?php

namespace Osio\GuestToCustomerOrders\Block\Account\Dashboard;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Osio\GuestToCustomerOrders\Helper\Data as Helper;

class TransformPopup extends Template
{
    private CurrentCustomer $currentCustomer;

    private Helper $helper;

    /** @var Session */
    private $session;

    private CustomerRepositoryInterface $customerRepository;

    public function __construct(
        SessionManagerInterface $session,
        Helper $helper,
        Context $context,
        CurrentCustomer $currentCustomer,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->currentCustomer = $currentCustomer;
        $this->helper = $helper;
        $this->session = $session;
        $this->customerRepository = $customerRepository;
    }

    public function getLoadedCustomer(): CustomerInterface
    {
        return $this->currentCustomer->getCustomer();
    }

    public function getGreeting(): string
    {
        return $this->getLove() . ' ' . $this->getFullName();
    }

    public function showPop(): bool
    {
        $customer = $this->setInteractionWithPopup();

        return (
            $this->helper->isEnabled()
            &&
            $customer->getCustomAttribute('was_guest')->getValue()
            &&
            empty($this->getRequest()->getParam('transform'))
            &&
            !$customer->getCustomAttribute('asked_transform_guest_orders')->getValue()
        );
    }

    /**
     * @return false|CustomerInterface
     */
    private function setInteractionWithPopup()
    {
        if (empty($this->getRequest()->getParam('transform'))) {
            return $this->getLoadedCustomer();
        }

        try {
            $customer = $this->getLoadedCustomer()->setCustomAttribute('asked_transform_guest_orders', 1);
            return $this->customerRepository->save($customer);
        } catch (Exception $e) {
            $this->helper->log($e);
        }

        return false;
    }

    private function getFullName(): string
    {
        return $this->getLoadedCustomer()->getFirstname() . ' ' . $this->getLoadedCustomer()->getLastname();
    }

    private function getLove(): string
    {
        return ($this->getLoadedCustomer()->getPrefix() == 'Mr.') ? __('Dear') : __('Dearest');
    }
}
