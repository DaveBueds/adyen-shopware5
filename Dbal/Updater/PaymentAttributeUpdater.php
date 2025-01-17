<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Updater;

use AdyenPayment\AdyenPayment;
use Doctrine\Common\Cache\Cache;
use Shopware\Bundle\AttributeBundle\Service\CrudServiceInterface;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Components\Model\ModelManager;

class PaymentAttributeUpdater implements PaymentAttributeUpdaterInterface
{
    /** @var CrudServiceInterface */
    private $crudService;
    /** @var ModelManager */
    private $entityManager;

    public function __construct(
        CrudServiceInterface $crudService,
        ModelManager $entityManager
    ) {
        $this->crudService = $crudService;
        $this->entityManager = $entityManager;
    }

    public function updateReadonlyOnAdyenPaymentAttributes(array $columns, bool $readOnly)
    {
        foreach ($columns as $column) {
            $this->crudService->update(
                's_core_paymentmeans_attributes',
                $column,
                TypeMapping::TYPE_STRING,
                [
                    'displayInBackend' => true,
                    'readonly' => $readOnly,
                    'label' => 'Adyen payment type'
                ]
            );
        }

        $this->rebuildPaymentAttributeModel();
    }

    private function rebuildPaymentAttributeModel()
    {
        /** @var Cache $metaDataCache */
        $metaDataCache = $this->entityManager->getConfiguration()->getMetadataCacheImpl();
        if ($metaDataCache) {
            $metaDataCache->deleteAll();
        }

        $this->entityManager->generateAttributeModels(['s_core_paymentmeans_attributes']);
    }
}
