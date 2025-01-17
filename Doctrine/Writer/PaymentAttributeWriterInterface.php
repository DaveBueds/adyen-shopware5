<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Models\Payment\PaymentMethod;

interface PaymentAttributeWriterInterface
{
    public function storeAdyenPaymentMethodType(int $paymentMeanId, PaymentMethod $adyenPaymentMethod);
}
