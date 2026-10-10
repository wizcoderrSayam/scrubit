<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_nested_category(): void
    {
        $parent = ServiceCategory::factory()->create(['name' => 'Residential']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.categories.store'), [
                'name' => 'Kitchens',
                'slug' => 'kitchens',
                'parent_id' => $parent->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $child = ServiceCategory::query()->where('slug', 'kitchens')->sole();
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertTrue($child->is_active);
    }

    public function test_update_rejects_a_category_as_its_own_parent(): void
    {
        $category = ServiceCategory::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.categories.update', $category), [
                'name' => $category->name,
                'slug' => $category->slug,
                'parent_id' => $category->id,
            ])
            ->assertSessionHasErrors(['parent_id' => 'A category cannot be its own parent.']);

        $this->assertNull($category->fresh()->parent_id);
    }

    public function test_update_rejects_a_descendant_as_the_parent(): void
    {
        $root = ServiceCategory::factory()->create(['name' => 'Residential']);
        $child = ServiceCategory::factory()->forParent($root)->create(['name' => 'Kitchens']);
        $grandchild = ServiceCategory::factory()->forParent($child)->create(['name' => 'Ovens']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.categories.update', $root), [
                'name' => $root->name,
                'slug' => $root->slug,
                'parent_id' => $grandchild->id,
            ])
            ->assertSessionHasErrors(['parent_id' => 'A category cannot be moved under one of its sub-categories.']);

        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_delete_is_blocked_while_sub_categories_exist(): void
    {
        $parent = ServiceCategory::factory()->create();
        ServiceCategory::factory()->forParent($parent)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.categories.destroy', $parent))
            ->assertSessionHasErrors(['category' => 'Move or delete sub-categories first.']);

        $this->assertModelExists($parent);
    }

    public function test_delete_is_blocked_while_services_exist(): void
    {
        $category = ServiceCategory::factory()->create();
        Service::factory()->for($category, 'category')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHasErrors(['category' => 'Move or delete this category\'s services before deleting it.']);

        $this->assertModelExists($category);
    }

    public function test_index_lists_sub_categories_under_their_parent(): void
    {
        $parent = ServiceCategory::factory()->create(['name' => 'Residential', 'sort_order' => 0]);
        ServiceCategory::factory()->forParent($parent)->create(['name' => 'Kitchens', 'sort_order' => 0]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSeeInOrder(['Residential', '— Kitchens'])
            ->assertSee('1');
    }

    public function test_service_form_shows_indented_category_paths(): void
    {
        $parent = ServiceCategory::factory()->create(['name' => 'Residential', 'slug' => 'residential']);
        ServiceCategory::factory()->forParent($parent)->create(['name' => 'Kitchens', 'slug' => 'kitchens']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.services.create'))
            ->assertOk()
            ->assertSee('— Residential / Kitchens', false);
    }
}
