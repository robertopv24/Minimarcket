<?php

use Minimarcket\Core\Container;
use Minimarcket\Core\Services\EmailService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Core\Services\EmailService instead.
 */
class EmailController
{
    private $service;

    public function __construct()
    {
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(EmailService::class);
        } else {
            $this->service = new EmailService();
        }
    }

    public function sendOrderConfirmation($to, $orderId, $orderDetails)
    {
        return $this->service->sendOrderConfirmation($to, $orderId, $orderDetails);
    }

    public function sendInvoice($to, $invoiceData)
    {
        return $this->service->sendInvoice($to, $invoiceData);
    }

    public function sendGenericEmail($to, $subject, $body)
    {
        return $this->service->sendGenericEmail($to, $subject, $body);
    }
}
?>