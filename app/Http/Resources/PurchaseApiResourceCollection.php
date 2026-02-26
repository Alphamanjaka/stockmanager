<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PurchaseApiResourceCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = PurchaseApiResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // Cette structure personnalisée correspond exactement à ce que Tabulator attend
        // pour la pagination à distance, ce qui évite d'avoir à utiliser un rappel ajaxResponse côté client.
        return [
            'last_page' => $this->resource->lastPage(),
            'data'      => $this->collection,
        ];
    }
}