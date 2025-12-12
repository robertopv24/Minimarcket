<?php

namespace Minimarcket\Core\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mail;

    public function __construct()
    {
        // Adjust paths relative to src/Core/Services
        // vendor is at project root. config is at project root.
        $rootPath = __DIR__ . '/../../../';

        if (file_exists($rootPath . 'vendor/autoload.php')) {
            require_once $rootPath . 'vendor/autoload.php';
        }

        $configFile = $rootPath . 'config/smtp_config.php';
        if (file_exists($configFile)) {
            $smtpConfig = require $configFile;
        } else {
            // Fallback or empty
            $smtpConfig = [];
        }

        $this->mail = new PHPMailer(true);
        try {
            if (!empty($smtpConfig)) {
                $this->mail->isSMTP();
                $this->mail->Host = $smtpConfig['host'] ?? '';
                $this->mail->SMTPAuth = true;
                $this->mail->Username = $smtpConfig['username'] ?? '';
                $this->mail->Password = $smtpConfig['password'] ?? '';
                $this->mail->SMTPSecure = $smtpConfig['encryption'] ?? '';
                $this->mail->Port = $smtpConfig['port'] ?? 587;
                $this->mail->setFrom($smtpConfig['from_email'] ?? '', $smtpConfig['from_name'] ?? '');
                $this->mail->isHTML(true);
            }
        } catch (Exception $e) {
            error_log("Error en configuración SMTP Service: " . $e->getMessage());
        }
    }

    public function sendEmail($to, $subject, $body)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Error al enviar correo (Service): " . $e->getMessage());
            return false;
        }
    }

    public function sendOrderConfirmation($to, $orderId, $orderDetails)
    {
        $subject = "Confirmación de Orden #{$orderId}";
        $body = $this->buildOrderConfirmationBody($orderDetails);
        return $this->sendEmail($to, $subject, $body);
    }

    public function sendInvoice($to, $invoiceData)
    {
        $subject = "Factura de Compra - ID: {$invoiceData['invoice_id']}";
        $body = $this->buildInvoiceBody($invoiceData);
        return $this->sendEmail($to, $subject, $body);
    }

    public function sendGenericEmail($to, $subject, $body)
    {
        return $this->sendEmail($to, $subject, $body);
    }

    private function buildOrderConfirmationBody($orderDetails)
    {
        $body = "<h1>Confirmación de tu Orden</h1>";
        $body .= "<p>Gracias por tu compra. Aquí están los detalles:</p>";
        $body .= "<ul>";
        if (!empty($orderDetails['items'])) {
            foreach ($orderDetails['items'] as $item) {
                $body .= "<li><strong>{$item['product_name']}</strong> - Cantidad: {$item['quantity']} - Precio: {$item['price']}</li>";
            }
        }
        $body .= "</ul>";
        $body .= "<p><strong>Total: {$orderDetails['total']}</strong></p>";
        $body .= "<p>Esperamos que disfrutes tu compra. Gracias por elegirnos.</p>";
        return $body;
    }

    private function buildInvoiceBody($invoiceData)
    {
        $body = "<h1>Factura de Compra</h1>";
        $body .= "<p><strong>ID de Factura:</strong> {$invoiceData['invoice_id']}</p>";
        $body .= "<p><strong>Fecha:</strong> {$invoiceData['date']}</p>";
        $body .= "<p><strong>Total:</strong> {$invoiceData['total']}</p>";
        $body .= "<h3>Productos:</h3>";
        $body .= "<ul>";
        if (!empty($invoiceData['items'])) {
            foreach ($invoiceData['items'] as $item) {
                $body .= "<li>{$item['name']} - Cantidad: {$item['quantity']} - Precio: {$item['price']}</li>";
            }
        }
        $body .= "</ul>";
        $body .= "<p>Gracias por tu compra.</p>";
        return $body;
    }
}
