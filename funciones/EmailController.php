<?php
/**
 * EmailController.php
 * Manejador de correos electrónicos con soporte para SMTP (PHPMailer) 
 * y fallback automático a la función mail() de PHP.
 */

class EmailController {
    private $mail = null;
    private $usePHPMailer = false;
    private $config;

    public function __construct() {
        global $config;
        $this->config = $config;

        // Intentar cargar PHPMailer si está disponible en vendor
        $vendorPath = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($vendorPath)) {
            require_once $vendorPath;
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $this->usePHPMailer = true;
                $this->initPHPMailer();
            }
        }
    }

    private function initPHPMailer() {
        // Usamos una variable para instanciar y evitar errores estáticos de linting
        $phpMailerClass = 'PHPMailer\PHPMailer\PHPMailer';
        $this->mail = new $phpMailerClass(true);
        
        try {
            $this->mail->isSMTP();
            $this->mail->Host       = $this->config->get('smtp_host', 'localhost');
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $this->config->get('smtp_user');
            $this->mail->Password   = $this->config->get('smtp_password');
            $this->mail->SMTPSecure = $this->config->get('smtp_secure', 'tls');
            $this->mail->Port       = $this->config->get('smtp_port', 587);
            
            $fromEmail = $this->config->get('smtp_from_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $fromName  = $this->config->get('smtp_from_name', $this->config->get('site_name', 'Minimarcket'));
            
            $this->mail->setFrom($fromEmail, $fromName);
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log("Error en configuración PHPMailer: " . $e->getMessage());
            $this->usePHPMailer = false;
        }
    }

    /**
     * Envía un correo electrónico usando el mejor método disponible.
     */
    public function sendEmail($to, $subject, $body) {
        if ($this->usePHPMailer && $this->mail) {
            try {
                $this->mail->clearAddresses();
                $this->mail->addAddress($to);
                $this->mail->Subject = $subject;
                $this->mail->Body    = $body;
                $this->mail->AltBody = strip_tags($body);
                return $this->mail->send();
            } catch (Exception $e) {
                error_log("PHPMailer failed: " . $e->getMessage() . ". Falling back to mail()");
                return $this->sendNativeMail($to, $subject, $body);
            }
        } else {
            return $this->sendNativeMail($to, $subject, $body);
        }
    }

    /**
     * Fallback usando la función mail() nativa de PHP
     */
    private function sendNativeMail($to, $subject, $body) {
        $fromEmail = $this->config->get('smtp_from_email', 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fromName  = $this->config->get('smtp_from_name', $this->config->get('site_name', 'Minimarcket'));

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: $fromName <$fromEmail>" . "\r\n";

        return mail($to, $subject, $body, $headers);
    }

    // --- Métodos de Conveniencia ---

    public function sendOrderConfirmation($to, $orderId, $orderDetails) {
        $subject = "Confirmación de Orden #{$orderId}";
        $body = $this->buildOrderConfirmationBody($orderDetails);
        return $this->sendEmail($to, $subject, $body);
    }

    public function sendInvoice($to, $invoiceData) {
        $subject = "Factura de Compra - ID: {$invoiceData['invoice_id']}";
        $body = $this->buildInvoiceBody($invoiceData);
        return $this->sendEmail($to, $subject, $body);
    }

    public function sendGenericEmail($to, $subject, $body) {
        return $this->sendEmail($to, $subject, $body);
    }

    private function buildOrderConfirmationBody($orderDetails) {
        $body = "<h1>Confirmación de tu Orden</h1>";
        $body .= "<p>Gracias por tu compra. Detalles:</p><ul>";
        if (!empty($orderDetails['items'])) {
            foreach ($orderDetails['items'] as $item) {
                $body .= "<li><strong>{$item['product_name']}</strong> - Qty: {$item['quantity']} - Price: {$item['price']}</li>";
            }
        }
        $body .= "</ul><p><strong>Total: {$orderDetails['total']}</strong></p>";
        return $body;
    }

    private function buildInvoiceBody($invoiceData) {
        $body = "<h1>Factura de Compra</h1>";
        $body .= "<p>ID: {$invoiceData['invoice_id']}</p><p>Total: {$invoiceData['total']}</p>";
        return $body;
    }
}
