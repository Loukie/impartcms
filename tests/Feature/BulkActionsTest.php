<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private function signInAdmin()
    {
        // ensure a predictable admin user for tests
        $user = User::factory()->create([
            "is_admin" => true,
            "email" => "lourens@2ko.co.za",
            // password must be hashed; tests can use this plain text
            "password" => bcrypt("L0ur3nsn3l2630"),
        ]);

        $this->actingAs($user);
    }

    public function test_bulk_trash_pages_skips_homepage()
    {
        $this->signInAdmin();

        $page1 = Page::create([
            'title' => 'First',
            'slug' => 'first',
            'status' => 'published',
        ]);
        $page2 = Page::create([
            'title' => 'Second',
            'slug' => 'second',
            'status' => 'published',
        ]);

        // mark first as homepage via setting since controller uses isCurrentHomepage
        $page1->is_homepage = true;
        $page1->save();

        $response = $this->post(route('admin.pages.bulk'), ['ids' => [$page1->id, $page2->id]]);
        $response->assertRedirect(route('admin.pages.index'));

        $this->assertNull(Page::find($page1->id)->deleted_at, 'Homepage should not be trashed');
        $this->assertNotNull(Page::withTrashed()->find($page2->id)->deleted_at, 'Second page should be trashed');
    }

    public function test_bulk_trash_forms_deletes_selected()
    {
        $this->signInAdmin();

        $form1 = Form::create(['name' => 'One', 'slug' => 'one']);
        $form2 = Form::create(['name' => 'Two', 'slug' => 'two']);

        $response = $this->post(route('admin.forms.bulk'), ['ids' => [$form1->id, $form2->id]]);
        $response->assertRedirect(route('admin.forms.index'));

        $this->assertNotNull(Form::withTrashed()->find($form1->id)->deleted_at);
        $this->assertNotNull(Form::withTrashed()->find($form2->id)->deleted_at);
    }

    public function test_bulk_trash_users_skip_self_and_last_admin()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $other = User::factory()->create();
        $last = User::factory()->create(['is_admin' => true]);

        // attempt to bulk trash all three
        $response = $this->post(route('admin.users.bulk'), ['ids' => [$admin->id, $other->id, $last->id]]);
        $response->assertRedirect(route('admin.users.index'));

        // self should remain
        $this->assertNotNull(User::find($admin->id));
        // other should be trashed
        $this->assertNotNull(User::withTrashed()->find($other->id)->deleted_at);
        // last admin should be skipped (can't trash last admin)
        $this->assertNull(User::withTrashed()->find($last->id)->deleted_at);
    }

    public function test_bulk_permanent_delete_trashed_pages_skips_homepage()
    {
        $this->signInAdmin();

        $page1 = Page::create([
            'title' => 'First',
            'slug' => 'first',
            'status' => 'published',
        ]);
        $page2 = Page::create([
            'title' => 'Second',
            'slug' => 'second',
            'status' => 'published',
        ]);

        // mark page1 as homepage and trash both
        $page1->is_homepage = true;
        $page1->save();
        $page1->delete();
        $page2->delete();

        $response = $this->post(route('admin.pages.trash.bulk'), ['ids' => [$page1->id, $page2->id]]);
        $response->assertRedirect(route('admin.pages.trash'));

        // homepage should still exist in trashed (not force-deleted)
        $this->assertNotNull(Page::withTrashed()->find($page1->id));
        // other page should be completely removed
        $this->assertNull(Page::withTrashed()->find($page2->id));
    }
}
