<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreColorRequest;
use App\Http\Resources\ColorResource;
use App\Models\Color;
use App\Services\ColorService;

class ColorControllerAPI extends Controller
{
    public function __construct(private ColorService $color_service )
    {

    }
    // display a listing of the resource
    public function index()
    {
        // return a collection of colors
        return ColorResource::collection(Color::paginate(15));
    }
    // store a newly created resource in storage
    public function store(StoreColorRequest $request)
    {
        // validate the request data
        $validated = $request->validated();
        // create a new color
        $color = $this->color_service->create($validated);
        // return the created color as a resource
        return new ColorResource($color);
    }
}