<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\OrderStatus;
use App\Message\OrderStatusChanged;
use App\Repository\OrderRepository;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class OrderStatusChangedHandler
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly MailerService   $mailerService,
        private readonly LoggerInterface $logger,
    )
    {
    }

    public function __invoke(OrderStatusChanged $message): void
    {
        $order = $this->orderRepository->find($message->getOrderId());

        if ($order === null) {
            $this->logger->warning('Commande introuvable pour l\'envoi d\'email.', [
                'orderId' => $message->getOrderId(),
            ]);

            return;
        }

        $status = $order->getStatus();

        if ($status === OrderStatus::Confirmed) {
            $this->mailerService->sendOrderConfirmationEmail($order);
        } else {
            $this->mailerService->sendOrderStatusChangeEmail($order);
        }
    }
}
