<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\Page;
use Illuminate\Database\Seeder;

class CmsStarterSeeder extends Seeder
{
    public function run(): void
    {
        Page::query()->firstOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Home',
                'body' => "Welcome 👋\n\nHere is a contact form:\n\n[form slug=\"contact\"]",
                'status' => 'published',
                'template' => 'default',
                'is_homepage' => true,
                'published_at' => now(),
            ]
        );

        Form::query()->firstOrCreate(
            ['slug' => 'contact'],
            [
                'name' => 'Contact Us',
                'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Your name', 'required' => true],
                    ['name' => 'email', 'type' => 'email', 'label' => 'Your email', 'required' => true],
                    ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true],
                ],
                'settings' => [
                    'default_recipients' => ['you@example.com'],
                ],
                'is_active' => true,
            ]
        );

        // ensure there is at least one admin user (use Lourens credentials)
        if (!\App\Models\User::query()->where('is_admin', true)->exists()) {
            \App\Models\User::factory()->create([
                'name' => 'Administrator',
                'email' => 'lourens@2ko.co.za',
                'password' => bcrypt('L0ur3nsn3l2630'),
                'is_admin' => true,
            ]);
        }
    }
}
