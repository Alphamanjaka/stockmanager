<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StockDropAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $currentValue;
    public $previousValue;
    public $dropPercentage;

    public function __construct(float $currentValue, float $previousValue, float $dropPercentage)
    {
        $this->currentValue = $currentValue;
        $this->previousValue = $previousValue;
        $this->dropPercentage = $dropPercentage;
    }

    public function build()
    {
        return $this->subject('⚠️ Alerte Critique : Chute brutale de la valeur du stock')
            ->view('emails.stock_drop_alert');
    }
}
