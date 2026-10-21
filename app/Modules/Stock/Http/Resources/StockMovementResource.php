<?php

namespace App\Modules\Stock\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'variant_label' => $this->productColor?->label,
            'user_name' => $this->user?->name,
        ];
    }
}
