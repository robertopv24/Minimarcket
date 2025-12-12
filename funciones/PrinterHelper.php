<?php
use Minimarcket\Core\Container;
use Minimarcket\Core\Helpers\PrinterHelper as CorePrinterHelper;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Core\Helpers\PrinterHelper instead.
 */
class PrinterHelper
{
    // Replicate Constants for legacy compat if accessed statically or via class constant
    const ESC = "\x1B";
    const GS = "\x1D";
    const CUT = "\x1D\x56\x00";
    const FEED_3_LINES = "\x1B\x64\x03";
    const ALIGN_LEFT = "\x1B\x61\x00";
    const ALIGN_CENTER = "\x1B\x61\x01";
    const ALIGN_RIGHT = "\x1B\x61\x02";
    const BOLD_ON = "\x1B\x45\x01";
    const BOLD_OFF = "\x1B\x45\x00";
    const SIZE_NORMAL = "\x1D\x21\x00";
    const SIZE_DOUBLE = "\x1D\x21\x11";

    private $service;

    public function __construct()
    {
        $container = Container::getInstance();
        try {
            $this->service = $container->get(CorePrinterHelper::class);
        } catch (Exception $e) {
            $this->service = new CorePrinterHelper();
        }
    }

    public function printTicket($header, $items, $totals, $kitchen)
    {
        return $this->service->printTicket($header, $items, $totals, $kitchen);
    }
}
