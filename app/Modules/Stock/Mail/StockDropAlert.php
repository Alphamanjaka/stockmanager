<?php

namespace App\Modules\Stock\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Alerte envoyée aux administrateurs quand les pertes non expliquées dépassent le seuil (stock:monitor-drop).
 */
class StockDropAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<array{date: \DateTimeInterface, label: string, sku: ?string, quantity: int, reason: string, value: float}>  $lines
     */
    public function __construct(
        public array $lines,
        public float $lossValue,
        public float $lossPercentage,
        public int $hours,
    ) {}

    public function build()
    {
        return $this->subject('⚠️ Alerte : pertes de stock non expliquées')
            ->view('stock::emails.stock_drop_alert');
    }
}
