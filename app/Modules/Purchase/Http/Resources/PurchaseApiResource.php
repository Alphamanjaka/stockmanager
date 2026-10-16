<?php

namespace App\Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'date' => $this->created_at->format('d/m/Y H:i'),
            'state' => $this->state,
            'total_amount' => money($this->total_amount),
            'discount' => $this->discount,
            'supplier' => $this->supplier ? $this->supplier->name : null,
            'total_net' => money($this->total_net),
            'urls' => [
                'show' => route('admin.purchases.show', $this->id),
                'edit' => route('admin.purchases.edit', $this->id),
                'destroy' => route('admin.purchases.destroy', $this->id),
                'update_state' => route('admin.purchases.updateState', $this->id),
                'csrf' => csrf_token(),
            ],
        ];
    }
}
