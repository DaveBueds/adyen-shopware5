<?php

namespace AdyenPayment\Commands;

use AdyenPayment\Components\FifoNotificationLoader;
use AdyenPayment\Components\NotificationProcessor;
use AdyenPayment\Models\Feedback\NotificationProcessorFeedback;
use AdyenPayment\Subscriber\Cronjob\ProcessNotifications as ProcessNotificationsCronjob;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessNotifications
 * @package AdyenPayment\Commands
 */
class ProcessNotifications extends ShopwareCommand
{
    /**
     * @var FifoNotificationLoader
     */
    private $loader;
    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    /**
     * ProcessNotifications constructor.
     * @param FifoNotificationLoader $fifoNotificationLoader
     * @param NotificationProcessor $notificationProcessor
     */
    public function __construct(
        FifoNotificationLoader $fifoNotificationLoader,
        NotificationProcessor $notificationProcessor
    ) {
        $this->loader = $fifoNotificationLoader;
        $this->notificationProcessor = $notificationProcessor;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Process notifications in queue')
            ->addOption(
                'number',
                'no',
                \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
                'Number of notifications to process. Defaults to ' .
                ProcessNotificationsCronjob::NUMBER_OF_NOTIFICATIONS_TO_HANDLE . '.',
                ProcessNotificationsCronjob::NUMBER_OF_NOTIFICATIONS_TO_HANDLE
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Enlight_Event_Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $number = $input->getOption('number');

        /** @var \Generator<NotificationProcessorFeedback> $feedback */
        $feedback = $this->notificationProcessor->processMany(
            $this->loader->load($number)
        );

        $totalCount = 0;
        $successCount = 0;

        /** @var NotificationProcessorFeedback $item */
        foreach ($feedback as $item) {
            $totalCount++;
            $successCount += (int)$item->isSuccess();
            $output->writeln($item->getNotification()->getId() . ": " . $item->getMessage());
        }

        $output->writeln(sprintf(
            'Imported %d items. %s OK, %s FAILED',
            $totalCount,
            $successCount,
            $totalCount - $successCount
        ));
    }
}
