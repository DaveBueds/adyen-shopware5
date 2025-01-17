<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Manager;

use AdyenPayment\Models\PaymentInfo;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Shopware\Models\Order\Order;

/**
 * Class AdyenManager
 * @package AdyenPayment\Components\Manager
 */
class AdyenManager
{
    /**
     * @var EntityManagerInterface
     */
    private $modelManager;

    public function __construct(EntityManagerInterface $modelManager)
    {
        $this->modelManager = $modelManager;
    }

    public function storePaymentData(PaymentInfo $transaction, string $paymentData)
    {
        $transaction->setPaymentData($paymentData);
        $this->modelManager->persist($transaction);
        $this->modelManager->flush();
    }

    /**
     * @param Order|null $order
     * @return string
     */
    public function fetchOrderPaymentData($order): string
    {
        if (!$order) {
            return '';
        }

        /* @var PaymentInfo $transaction */
        $transaction = $this->getPaymentInfoRepository()->findOneBy(['orderId' => $order->getId()]);

        return $transaction ? $transaction->getPaymentData() : '';
    }

    private function getPaymentInfoRepository(): ObjectRepository
    {
        return $this->modelManager->getRepository(PaymentInfo::class);
    }
}
