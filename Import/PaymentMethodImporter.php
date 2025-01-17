<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use AdyenPayment\Components\Adyen\Mapper\PaymentMethodMapperInterface;
use AdyenPayment\Components\Adyen\PaymentMethod\PaymentMethodsProviderInterface;
use AdyenPayment\Dbal\Writer\Payment\PaymentMeansSubshopsWriterInterface;
use AdyenPayment\Doctrine\Writer\PaymentMethodWriterInterface;
use AdyenPayment\Models\Enum\PaymentMethod\ImportStatus;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use AdyenPayment\Rule\AdyenApi\UsedFallbackConfigRuleInterface;
use Doctrine\Common\Persistence\ObjectRepository;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Shop;

final class PaymentMethodImporter implements PaymentMethodImporterInterface
{
    /**
     * @var PaymentMethodsProviderInterface
     */
    private $paymentMethodsProvider;
    /**
     * @var ObjectRepository
     */
    private $shopRepository;
    /**
     * @var UsedFallbackConfigRuleInterface
     */
    private $usedFallbackConfigRule;
    /**
     * @var PaymentMethodMapperInterface
     */
    private $paymentMethodMapper;
    /**
     * @var PaymentMethodWriterInterface
     */
    private $paymentMethodWriter;
    /** @var ModelManager */
    private $entityManager;
    /**
     * @var PaymentMeansSubshopsWriterInterface
     */
    private $paymentMeansSubshopsWriter;


    public function __construct(
        PaymentMethodsProviderInterface $paymentMethodsProvider,
        ObjectRepository $shopRepository,
        UsedFallbackConfigRuleInterface $usedFallbackConfigRule,
        PaymentMethodMapperInterface $paymentMethodMapper,
        PaymentMethodWriterInterface $paymentMethodWriter,
        ModelManager $entityManager,
        PaymentMeansSubshopsWriterInterface $paymentMeansSubshopsWriter
    ) {
        $this->paymentMethodsProvider = $paymentMethodsProvider;
        $this->shopRepository = $shopRepository;
        $this->usedFallbackConfigRule = $usedFallbackConfigRule;
        $this->paymentMethodMapper = $paymentMethodMapper;
        $this->paymentMethodWriter = $paymentMethodWriter;
        $this->entityManager = $entityManager;
        $this->paymentMeansSubshopsWriter = $paymentMeansSubshopsWriter;
    }

    public function importAll(): \Generator
    {
        /** @var Shop $shop */
        foreach ($this->shopRepository->findAll() as $shop) {
            if (true === ($this->usedFallbackConfigRule)($shop->getId())) {
                $this->paymentMeansSubshopsWriter->registerAdyenPaymentMethodForSubshop($shop->getId());
                yield ImportResult::successSubshopFallback($shop, ImportStatus::updated());

                continue;
            }

            yield from $this->import($shop);
        }
    }

    public function importForShop(Shop $shop): \Generator
    {
        yield from $this->import($shop);

        $this->entityManager->flush();
    }

    private function import(Shop $shop): \Generator
    {
        try {
            $generator = $this->paymentMethodMapper->mapFromAdyen(
                ($this->paymentMethodsProvider)($shop)
            );
        } catch (\Exception $exception) {
            yield ImportResult::fromException(
                $shop,
                null,
                $exception
            );
        }

        foreach ($generator as $adyenPaymentMethod) {
            try {
                yield $this->paymentMethodWriter->__invoke(
                    $adyenPaymentMethod,
                    $shop
                );
            } catch (\Exception $exception) {
                yield ImportResult::fromException(
                    $shop,
                    $adyenPaymentMethod ?? null,
                    $exception
                );
            }
        }
    }
}
