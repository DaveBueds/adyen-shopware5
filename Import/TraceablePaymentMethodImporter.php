<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use AdyenPayment\Models\PaymentMethod\ImportResult;
use Psr\Log\LoggerInterface;
use Shopware\Models\Shop\Shop;

final class TraceablePaymentMethodImporter implements PaymentMethodImporterInterface
{
    /**
     * @var PaymentMethodImporterInterface
     */
    private $paymentMethodImporter;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PaymentMethodImporterInterface $paymentMethodImporter,
        LoggerInterface $logger
    ) {
        $this->paymentMethodImporter = $paymentMethodImporter;
        $this->logger = $logger;
    }

    public function importAll(): \Generator
    {
        foreach ($this->paymentMethodImporter->importAll() as $importResult) {
            $this->log($importResult);
            yield $importResult;
        }
    }

    public function importForShop(Shop $shop): \Generator
    {
        foreach ($this->paymentMethodImporter->importForShop($shop) as $importResult) {
            $this->log($importResult);
            yield $importResult;
        }
    }

    private function log(ImportResult $importResult)
    {
        if ($importResult->isSuccess()) {
            $this->logger->info('Adyen payment method imported', [
                'shop id' => $importResult->getShop()->getId(),
                'shop name' => $importResult->getShop()->getName(),
                'payment method' => $importResult->getPaymentMethod()
                    ? $importResult->getPaymentMethod()->getType()
                    : 'all',
            ]);

            return;
        }

        $this->logger->error('Adyen payment method could not be imported', [
            'shop id' => $importResult->getShop()->getId(),
            'shop name' => $importResult->getShop()->getName(),
            'payment method' => $importResult->getPaymentMethod()
                ? $importResult->getPaymentMethod()->getType()
                : 'n/a',
            'message' => $importResult->getException()->getMessage(),
            'exception' => $importResult->getException()
        ]);
    }
}
