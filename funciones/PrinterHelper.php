<?php
class PrinterHelper
{
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

    public function printTicket($header, $items, $totals, $kitchen)
    {
        $res1 = $this->printCustomerTicket($header, $items, $totals);
        $res2 = $this->printKitchenTicket($header, $kitchen);
        return ($res1 === true && $res2 === true) ? true : ($res1 !== true ? $res1 : $res2);
    }

    public function printCustomerTicket($header, $items, $totals)
    {
        try {
            $buffer = "";
            $buffer .= self::ALIGN_CENTER . self::BOLD_ON . self::SIZE_DOUBLE;
            $buffer .= $header['company'] . "\n";
            $buffer .= self::SIZE_NORMAL . self::BOLD_OFF;
            $buffer .= "Orden #" . $header['order_id'] . "\n";
            $buffer .= $header['date'] . "\n\n";

            $buffer .= self::ALIGN_LEFT;
            $buffer .= "Cajero: " . substr($header['cajero'], 0, 20) . "\n";
            $buffer .= "Cliente: " . substr($header['cliente'], 0, 20) . "\n";
            $buffer .= str_repeat("-", 32) . "\n";

            foreach ($items as $item) {
                $buffer .= self::BOLD_ON . $item['qty'] . " " . $item['name'] . self::BOLD_OFF . "\n";
                $priceTxt = "$" . $item['total'];
                $padding = 32 - strlen($priceTxt);
                if ($padding < 0)
                    $padding = 0;
                $buffer .= self::ALIGN_RIGHT . $priceTxt . "\n" . self::ALIGN_LEFT;

                if (!empty($item['extras_finance'])) {
                    foreach ($item['extras_finance'] as $ex) {
                        $buffer .= " + " . $ex['name'] . " ($" . $ex['price'] . ")\n";
                    }
                }
            }
            $buffer .= str_repeat("-", 32) . "\n";

            $buffer .= self::ALIGN_RIGHT . self::BOLD_ON . self::SIZE_DOUBLE;
            $buffer .= "TOTAL: $" . $totals['total'] . "\n";
            $buffer .= self::SIZE_NORMAL . self::BOLD_OFF . "\n";

            $buffer .= self::ALIGN_CENTER;
            $buffer .= "Gracias por su compra\n\n";
            $buffer .= self::FEED_3_LINES;
            $buffer .= self::CUT;

            return $this->sendToCcups($buffer);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function printKitchenTicket($header, $kitchen)
    {
        try {
            $buffer = "";
            $buffer .= self::ALIGN_CENTER;
            $buffer .= str_repeat("- ", 16) . "\n";
            $buffer .= self::BOLD_ON . "COCINA #" . $header['order_id'] . self::BOLD_OFF . "\n";
            $buffer .= str_repeat("- ", 16) . "\n\n";

            $buffer .= self::ALIGN_LEFT;
            foreach ($kitchen as $kItem) {
                $buffer .= self::BOLD_ON . self::SIZE_DOUBLE;
                $buffer .= $kItem['qty'] . " X " . substr($kItem['name'], 0, 10) . "\n";
                $buffer .= self::SIZE_NORMAL . self::BOLD_OFF;

                foreach ($kItem['subs'] as $sub) {
                    $tag = $sub['is_takeaway'] ? '[LLEVAR]' : '[MESA]';
                    $buffer .= " $tag #" . $sub['num'] . " " . $sub['name'] . "\n";

                    foreach ($sub['mods'] as $mod) {
                        $buffer .= "   " . $mod . "\n";
                    }
                    if ($sub['note'])
                        $buffer .= "   NOT: " . $sub['note'] . "\n";
                    $buffer .= "\n";
                }
                $buffer .= str_repeat("-", 32) . "\n";
            }

            $buffer .= self::FEED_3_LINES;
            $buffer .= self::CUT;

            return $this->sendToCcups($buffer);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function sendToCcups($buffer)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'ticket_POS_');
        file_put_contents($tempFile, $buffer);
        $command = "lp -d " . escapeshellarg($this->printerName) . " -o raw " . escapeshellarg($tempFile);
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        unlink($tempFile);
        if ($returnVar !== 0) {
            return "Error CUPS: " . implode("\n", $output);
        }
        return true;
    }

}
?>