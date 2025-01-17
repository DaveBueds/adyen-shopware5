<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Checkout;

use Adyen\Util\Currency;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;

final class AddGooglePayConfigToViewSubscriber implements SubscriberInterface
{
    private static $PAY_WITH_GOOGLE = 'paywithgoogle';

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => '__invoke',
        ];
    }

    public function __invoke(Enlight_Event_EventArgs $args)
    {
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['confirm'])) {
            return;
        }

        $userData = $subject->View()->getAssign('sUserData');
        $source = (int) ($userData['additional']['payment']['source'] ?? null);
        if (!SourceType::load($source)->equals(SourceType::adyen())) {
            return;
        }

        $basket = $subject->View()->getAssign('sBasket');
        if (!$basket) {
            return;
        }

        $adyenType = (string) ($userData['additional']['payment']['attributes']['core']['adyen_type'] ?? '');
        if (self::$PAY_WITH_GOOGLE !== $adyenType) {
            return;
        }

        $currencyUtil = new Currency();
        $adyenGoogleConfig = [
            'environment' => $this->configuration->getEnvironment() === Configuration::ENV_LIVE ? 'PRODUCTION' : 'TEST',
            'showPayButton' => true,
            'currencyCode' => $basket['sCurrencyName'],
            'amount' => $currencyUtil->sanitize($basket['AmountNumeric'], $basket['sCurrencyName'])
        ];

        $subject->View()->assign('sAdyenGoogleConfig', htmlentities(json_encode($adyenGoogleConfig)));
    }
}
