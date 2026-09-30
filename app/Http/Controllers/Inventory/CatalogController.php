<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Trang chỉ mục kiểu menu GLPI: mỗi mục liên kết tới trang danh mục sẵn có của Snipe-IT.
     */
    public function index(): View
    {
        $this->authorize('view', Category::class);

        $entries = collect(config('inventory_catalog.entries'))
            ->map(function (array $entry, string $key): array {
                $category = Category::where('name', $entry['category'])
                    ->where('category_type', $entry['type'])
                    ->first();

                return [
                    'key' => $key,
                    'name' => $entry['category'],
                    'type' => $entry['type'],
                    'category' => $category,
                    'count' => $category ? $category->itemCount() : 0,
                ];
            });

        return view('inventory.catalog', ['entries' => $entries->values()]);
    }
}
