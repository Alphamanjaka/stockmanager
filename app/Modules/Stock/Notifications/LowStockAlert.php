<?php

namespace App\Modules\Stock\Notifications;

use App\Modules\Catalog\Models\ProductColor;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Cloche du back office : une variante vient de passer sous son seuil d'alerte (StockService).
 * Marquée comme lue quand on l'ouvre, ou d'elle-même quand le stock repasse au-dessus du seuil.
 */
class LowStockAlert extends Notification
{
    use Queueable;

    public function __construct(public ProductColor $variant) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'product_color_id' => $this->variant->id,
            'product_id' => $this->variant->product_id,
            'label' => $this->variant->label,
            'sku' => $this->variant->sku,
            'stock' => (int) $this->variant->stock,
            'alert_stock' => (int) $this->variant->alert_stock,
            // Lien relatif : la fiche produit, quel que soit le nom d'hôte utilisé
            'url' => route('admin.products.show', $this->variant->product_id, false),
        ];
    }
}
