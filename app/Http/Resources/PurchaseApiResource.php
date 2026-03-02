<?php

namespace App\Http\Resources;

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
            'total_amount' => number_format($this->total_amount, 2) . ' Mga',
            'discount' => $this->discount,
            'supplier' => $this->supplier ? $this->supplier->name : null,
            'total_net' => number_format($this->total_net, 2) . ' Mga',
            'urls' => [
                'show' => route('admin.purchases.show', $this->id),
                'edit' => route('admin.purchases.edit', $this->id),
                'destroy' => route('admin.purchases.destroy', $this->id),
                'csrf' => csrf_token(),
            ],
        ];
    }
}
