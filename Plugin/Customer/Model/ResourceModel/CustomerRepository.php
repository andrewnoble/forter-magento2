<?php

namespace Forter\Forter\Plugin\Customer\Model\ResourceModel;

use Forter\Forter\Model\AbstractApi;
use Forter\Forter\Model\RequestBuilder\RequestPrepare;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository as CustomerRepositoryOriginal;
use Magento\Framework\App\State;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class CustomerAfterSave
 * @package [Vendor_Name]\[Module_Name]\Plugin
 */
class CustomerRepository
{
    /**
     *
     */
    const API_ENDPOINT = "https://api.forter-secure.com/v2/accounts/update/";
    /**
     * @var RequestPrepare
     */
    private $requestPrepare;
    /**
     * @var State
     */
    private $state;
    /**
     * @var AbstractApi
     */
    private $abstractApi;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;
    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * CustomerRepository constructor.
     * @param State $state
     * @param RequestPrepare $requestPrepare
     * @param AbstractApi $abstractApi
     * @param GroupRepositoryInterface $groupRepository
     * @param RemoteAddress $remoteAddress
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        State $state,
        RequestPrepare $requestPrepare,
        AbstractApi $abstractApi,
        GroupRepositoryInterface $groupRepository,
        RemoteAddress $remoteAddress,
        StoreManagerInterface $storeManager
    )
    {
        $this->requestPrepare = $requestPrepare;
        $this->state = $state;
        $this->abstractApi = $abstractApi;
        $this->storeManager = $storeManager;
        $this->groupRepository = $groupRepository;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @param CustomerRepositoryOriginal $subject
     * @param $savedCustomer
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterSave(
        CustomerRepositoryOriginal $subject,
        $savedCustomer
    )
    {
        $customerGroup = $this->groupRepository->getById($savedCustomer->getGroupId());
        $customerAccountData = $this->requestPrepare->getCustomerAccountData(null, $savedCustomer);
        $areaCode = ($this->state->getAreaCode() == 'frontend' ? 'END_USER' : 'MERCHANT_ADMIN');
        $type = ($this->state->getAreaCode() == 'frontend' ? 'PRIVATE' : 'MERCHANT_EMPLOYEE');

        $json = [
            "accountId" => $savedCustomer->getId(),
            "eventTime" => time(),
            "connectionInformation" => $this->requestPrepare->getConnectionInformation($this->remoteAddress->getRemoteAddress()),
            "accountData" => [
                "type" => $type,
                "statusChangeBy" => $areaCode,
                "addressesInAccount" => $this->getAddressInAccount($savedCustomer->getAddresses()),
                "customerEngagement" => $customerAccountData['customerEngagement'],
                "status" => $customerAccountData['status']
            ],
            "merchantIdentifiers" => [
                'merchantId' => $this->storeManager->getStore()->getId(),
                'merchantDomain' => $this->storeManager->getStore()->getUrl(),
                'merchantName' => $this->storeManager->getStore()->getName()
            ]
        ];

        $url = self::API_ENDPOINT . $savedCustomer->getId();
        $response = $this->abstractApi->sendApiRequest($url, json_encode($json));

        return $savedCustomer;
    }

    /**
     * @param $addresses
     * @return array|bool
     */
    private function getAddressInAccount($addresses)
    {
        if (!isset($addresses) || !$addresses) {
            return false;
        }
        foreach ($addresses as $address) {
            $street = $address->getStreet();
            $customerAddress['address1'] = $street[0];
            $customerAddress['city'] = $address->getCity();
            $customerAddress['country'] = $address->getCountryId();
            $customerAddress['address2'] = (isset($street[1]) ? $street[1] : "");
            $customerAddress['zip'] = $address->getPostcode();
            $customerAddress['region'] = $address->getRegionId();
            $customerAddress['company'] = $address->getCompany();

            $addressInAccount[] = $customerAddress;
        }

        return $addressInAccount;
    }
}