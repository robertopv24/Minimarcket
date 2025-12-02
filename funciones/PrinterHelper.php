<?php
class PrinterHelper {
    private $printerName = "Gezhi";

    // Comandos ESC/POS
    const ESC = "\x1B";
    const GS = "\x1D";

    // QUITAMOS 'INIT' PARA NO REINICIAR LA CONFIGURACIÓN DEL ROLLO
    // const INIT = "\x1B\x40";

    // COMANDOS DE CORTE
    // 0x1D 0x56 0x00 = Corte Full
    // 0x1D 0x56 0x01 = Corte Parcial
    const CUT = "\x1D\x56\x00";

    // COMANDO DE AVANCE DE LÍNEAS (Feed n lines)
    // ESC d n
    const FEED_3_LINES = "\x1B\x64\x03";

    const ALIGN_LEFT = "\x1B\x61\x00";
    const ALIGN_CENTER = "\x1B\x61\x01";
    const ALIGN_RIGHT = "\x1B\x61\x02";

    const BOLD_ON = "\x1B\x45\x01";
    const BOLD_OFF = "\x1B\x45\x00";

    const SIZE_NORMAL = "\x1D\x21\x00";
    const SIZE_DOUBLE = "\x1D\x21\x11";

    public function printTicket($header, $items, $totals, $kitchen) {
        try {
            // Empezamos con buffer vacío (sin INIT)
            $buffer = "";

            // --- 1. CABECERA ---
            $buffer .= self::ALIGN_CENTER . self::BOLD_ON . self::SIZE_DOUBLE;
            $buffer .= $header['company'] . "\n";
            $buffer .= self::SIZE_NORMAL . self::BOLD_OFF;
            $buffer .= "Orden #" . $header['order_id'] . "\n";
            $buffer .= $header['date'] . "\n\n";

            $buffer .= self::ALIGN_LEFT;
            $buffer .= "Cajero: " . substr($header['cajero'], 0, 20) . "\n";
            $buffer .= "Cliente: " . substr($header['cliente'], 0, 20) . "\n";
            $buffer .= str_repeat("-", 32) . "\n";

            // --- 2. ÍTEMS (CLIENTE) ---
            foreach ($items as $item) {
                $buffer .= self::BOLD_ON . $item['qty'] . " " . $item['name'] . self::BOLD_OFF . "\n";

                $priceTxt = "$" . $item['total'];
                $padding = 32 - strlen($priceTxt);
                if($padding < 0) $padding = 0;

                $buffer .= self::ALIGN_RIGHT . $priceTxt . "\n" . self::ALIGN_LEFT;

                if (!empty($item['extras_finance'])) {
                    foreach($item['extras_finance'] as $ex) {
                        $buffer .= " + " . $ex['name'] . " ($" . $ex['price'] . ")\n";
                    }
                }
            }
            $buffer .= str_repeat("-", 32) . "\n";

            // --- 3. TOTALES ---
            $buffer .= self::ALIGN_RIGHT . self::BOLD_ON . self::SIZE_DOUBLE;
            $buffer .= "TOTAL: $" . $totals['total'] . "\n";
            $buffer .= self::SIZE_NORMAL . self::BOLD_OFF . "\n";

            $buffer .= self::ALIGN_CENTER;
            $buffer .= "Gracias por su compra\n\n";

            // --- 4. COMANDA COCINA ---
            $buffer .= str_repeat("- ", 16) . "\n";
            $buffer .= self::BOLD_ON . "COCINA #" . $header['order_id'] . self::BOLD_OFF . "\n";
            $buffer .= str_repeat("- ", 16) . "\n\n";

            $buffer .= self::ALIGN_LEFT;

            foreach ($kitchen as $kItem) {
                $buffer .= self::BOLD_ON . self::SIZE_DOUBLE;
                $buffer .= $kItem['qty'] . " X " . substr($kItem['name'], 0, 10) . "\n";
                $buffer .= self::SIZE_NORMAL . self::BOLD_OFF;

                foreach($kItem['subs'] as $sub) {
                    $tag = $sub['is_takeaway'] ? '[LLEVAR]' : '[MESA]';
                    $buffer .= " $tag #" . $sub['num'] . " " . $sub['name'] . "\n";

                    foreach($sub['mods'] as $mod) {
                        $buffer .= "   " . $mod . "\n";
                    }
                    if($sub['note']) $buffer .= "   NOT: " . $sub['note'] . "\n";
                    $buffer .= "\n";
                }
                $buffer .= str_repeat("-", 32) . "\n";
            }

            // --- 5. CIERRE COMPACTO ---

            // Avanzamos 3 líneas exactas (lo justo para que salga el texto)
            $buffer .= self::FEED_3_LINES;

            // Cortamos
            $buffer .= self::CUT;

            // --- ENVÍO A CUPS ---
            $tempFile = tempnam(sys_get_temp_dir(), 'ticket_POS_');
            file_put_contents($tempFile, $buffer);

            // Usamos '-o raw' para evitar que CUPS agregue sus propios márgenes
            $command = "lp -d " . escapeshellarg($this->printerName) . " -o raw " . escapeshellarg($tempFile);

            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);
            unlink($tempFile);

            if ($returnVar !== 0) {
                throw new Exception("Error CUPS: " . implode("\n", $output));
            }

            return true;

        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
?>
