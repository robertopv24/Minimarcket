<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Cargar PHPMailer si usaste Composer
require_once __DIR__ . '/../config/smtp_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailController {
    private $mail;

    public function __construct() {
        // Cargar configuración SMTP
        $smtpConfig = require __DIR__ . '/../config/smtp_config.php';

        $this->mail = new PHPMailer(true);
        try {
            $this->mail->isSMTP();
            $this->mail->Host = $smtpConfig['host'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $smtpConfig['username'];
            $this->mail->Password = $smtpConfig['password'];
            $this->mail->SMTPSecure = $smtpConfig['encryption'];
            $this->mail->Port = $smtpConfig['port'];
            $this->mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
            $this->mail->isHTML(true);
        } catch (Exception $e) {
            error_log("Error en configuración SMTP: " . $e->getMessage());
        }
    }

    // Método general para enviar correos
    private function sendEmail($to, $subject, $body) {
        try {
            $this->mail->clearAddresses(); // Limpiar destinatarios previos
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Error al enviar correo: " . $e->getMessage());
            return false;
        }
    }

    // Enviar confirmación de orden
    public function sendOrderConfirmation($to, $orderId, $orderDetails) {
        $subject = "Confirmación de Orden #{$orderId}";
        $body = $this->buildOrderConfirmationBody($orderDetails);
        return $this->sendEmail($to, $subject, $body);
    }

    // Enviar factura de compra
    public function sendInvoice($to, $invoiceData) {
        $subject = "Factura de Compra - ID: {$invoiceData['invoice_id']}";
        $body = $this->buildInvoiceBody($invoiceData);
        return $this->sendEmail($to, $subject, $body);
    }

    // Enviar correo genérico
    public function sendGenericEmail($to, $subject, $body) {
        return $this->sendEmail($to, $subject, $body);
    }

    // Construir cuerpo de confirmación de orden
    private function buildOrderConfirmationBody($orderDetails) {
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

    // Construir cuerpo de factura de compra
    private function buildInvoiceBody($invoiceData) {
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
?>
