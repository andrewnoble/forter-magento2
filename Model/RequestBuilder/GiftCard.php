<?php
/**
 * Forter Payments For Magento 2
 * https://www.Forter.com/
 *
 * @category Forter
 * @package  Forter_Forter
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Forter\Forter\Model\RequestBuilder;

/**
 * Class GiftCard
 * @package Forter\Forter\Model\RequestBuilder
 */
class GiftCard
{
    /**
     * @param \Magento\Sales\Model\Order\Item $item
     *
     * @return bool
     */
    public function isGiftCard($item)
    {
        $productOptions = $item->getProductOptions();

        return $productOptions !== null && !empty($productOptions["giftcard_recipient_name"]) && !empty($productOptions["giftcard_recipient_email"]);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     *
     * @return array
     */
    public function getGiftCardBeneficiaries($item)
    {
        $productOptions = $item->getProductOptions();
        $name           = explode(" ", $productOptions['giftcard_recipient_name'], 2);

        return [
            [
                "personalDetails" => [
                    "firstName" => !empty($name[0]) ? $name[0] : "",
                    "lastName"  => !empty($name[1]) ? $name[1] : "",
                    "email"     => $productOptions["giftcard_recipient_email"]
                ],
                "comments"        => [
                    "messageToBeneficiary" => !empty($productOptions["giftcard_message"]) ? $productOptions["giftcard_message"] : ""
                ]
            ]
        ];
    }
}
