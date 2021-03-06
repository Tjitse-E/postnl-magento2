<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */
namespace TIG\PostNL\Controller\DeliveryOptions;

use TIG\PostNL\Controller\AbstractDeliveryOptions;
use TIG\PostNL\Model\OrderFactory;
use TIG\PostNL\Model\OrderRepository;
use TIG\PostNL\Helper\AddressEnhancer;
use TIG\PostNL\Webservices\Endpoints\DeliveryDate;
use TIG\PostNL\Webservices\Endpoints\TimeFrame;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Model\Session;

class Timeframes extends AbstractDeliveryOptions
{
    /**
     * @var AddressEnhancer
     */
    private $addressEnhancer;

    /**
     * @var  TimeFrame
     */
    private $timeFrameEndpoint;

    /**
     * @param Context         $context
     * @param OrderFactory    $orderFactory
     * @param OrderRepository $orderRepository
     * @param Data            $jsonHelper
     * @param Session         $checkoutSession
     * @param AddressEnhancer $addressEnhancer
     * @param DeliveryDate    $deliveryDate
     * @param TimeFrame       $timeFrame
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        OrderRepository $orderRepository,
        Data $jsonHelper,
        Session $checkoutSession,
        AddressEnhancer $addressEnhancer,
        DeliveryDate $deliveryDate,
        TimeFrame $timeFrame
    ) {
        $this->addressEnhancer   = $addressEnhancer;
        $this->timeFrameEndpoint = $timeFrame;

        parent::__construct(
            $context,
            $jsonHelper,
            $orderFactory,
            $orderRepository,
            $checkoutSession,
            $deliveryDate
        );
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        if (!isset($params['address']) || !is_array($params['address'])) {
            return $this->jsonResponse(__('No Address data found.'));
        }

        $this->addressEnhancer->set($params['address']);

        try {
            return $this->jsonResponse($this->getValidResponeType());
        } catch (LocalizedException $exception) {
            return $this->jsonResponse($exception->getMessage(), Http::STATUS_CODE_503);
        } catch (\Exception $exception) {
            return $this->jsonResponse($exception->getMessage(), Http::STATUS_CODE_503);
        }
    }

    /**
     * @param $address
     *
     * @return array
     */
    private function getPosibleDeliveryDays($address)
    {
        $startDate  = $this->getDeliveryDay($address);

        return $this->getTimeFrames($address, $startDate);
    }

    /**
     * @return array|\Magento\Framework\Phrase
     */
    private function getValidResponeType()
    {
        $address = $this->addressEnhancer->get();

        if (isset($address['error'])) {
            //@codingStandardsIgnoreLine
            return ['error' => __('%1 : %2', $address['error']['code'], $address['error']['message'])];
        }

        return $this->getPosibleDeliveryDays($address);
    }

    /**
     * CIF call to get the timeframes.
     * @param $address
     * @param $startDate
     *
     * @return \Magento\Framework\Phrase
     */
    private function getTimeFrames($address, $startDate)
    {
        $quote = $this->checkoutSession->getQuote();
        $storeId = $quote->getStoreId();
        $this->timeFrameEndpoint->setStoreId($storeId);
        $this->timeFrameEndpoint->setParameters($address, $startDate);

        return $this->timeFrameEndpoint->call();
    }
}
