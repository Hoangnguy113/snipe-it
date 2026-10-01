<?php

namespace Tests\Feature\Inventory;

use App\Models\Category;
use App\Models\User;
use Tests\TestCase;

class CatalogPageTest extends TestCase
{
    private function useMiniCatalog(): void
    {
        config(['inventory_catalog.entries' => [
            'printers' => ['category' => 'Máy in thử', 'type' => 'asset'],
            'phones' => ['category' => 'Điện thoại thử', 'type' => 'asset'],
        ]]);
    }

    public function test_requires_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('inventory.catalog'))
            ->assertForbidden();
    }

    public function test_page_renders(): void
    {
        $this->useMiniCatalog();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('inventory.catalog'))
            ->assertOk()
            ->assertSee('Máy in thử')
            ->assertSee('Điện thoại thử');
    }

    public function test_installed_category_links_to_the_category_page(): void
    {
        $this->useMiniCatalog();
        $category = Category::factory()->create(['name' => 'Máy in thử', 'category_type' => 'asset']);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('inventory.catalog'))
            ->assertOk()
            ->assertSee(route('categories.show', $category->id), false);
    }

    public function test_entry_that_is_not_installed_yet_says_so_instead_of_linking(): void
    {
        $this->useMiniCatalog();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('inventory.catalog'))
            ->assertOk()
            ->assertSee(trans('admin/inventory/catalog.not_installed'));
    }
}
