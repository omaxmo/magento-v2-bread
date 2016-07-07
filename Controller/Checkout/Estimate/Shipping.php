<?php
/**
 * Get Shipping Method Prices
 *
 * @author  Bread   copyright 2016
 * @author  Joel    @Mediotype
 * @author  Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Controller\Checkout\Estimate;

class Shipping extends \Bread\BreadCheckout\Controller\Checkout
{
    /** @var \Magento\Framework\Controller\Result\JsonFactory */
    protected $resultJsonFactory;

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var \Psr\Log\LoggerInterface */
    protected $logger;

    /** @var \Bread\BreadCheckout\Helper\Data */
    protected $helper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger,
        \Bread\BreadCheckout\Helper\Data $helper
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $address    = $this->getShippingAddressForQuote($this->getRequest()->getParams());

            $data       = $address->getGroupedAllShippingRates();
            $methods    = [];
            $code       = [];
            foreach ($data as $method) {
                foreach ($method as $rate) {
                    if (array_key_exists($rate->getCode(), $code)) {
                        continue;
                    }
                    $code[$rate->getCode()] = true;
                    $methods[] = [
                        'type'   => $rate->getCarrierTitle(),
                        'typeId' => $rate->getCode(),
                        'cost'   => $rate->getPrice() * 100,
                    ];
                }
            }
            $response = $methods;
        } catch (\Exception $e) {
            $this->helper->log(["ERROR" => "Exception in shipping estimate action",
                                "PARAMS"=> $this->getRequest()->getParams()], 'bread-exception.log');
            $this->logger->critical($e);
            $this->messageManager->addError( __("Internal Error, Please Contact Store Owner. You may checkout by adding to cart and providing a payment in the checkout process.") );
            $response = ['error' => 1,
                         'text'  => 'Internal error'];
        }

        return $this->resultJsonFactory->create()->setData(['result' => $response]);
    }
}