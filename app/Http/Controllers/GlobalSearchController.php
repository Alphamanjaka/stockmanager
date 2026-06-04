<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __construct(protected GlobalSearchService $globalSearchService) {}
    
    public function search(Request $request)
    {
        $query = $request->input('q');
        if (empty($query)) {
            return response()->json([]);
        }
        $results = $this->globalSearchService->search($query);
        return response()->json($results);
    }
}
