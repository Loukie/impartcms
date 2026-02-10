<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'siteName' => Setting::get('site_name', config('app.name')),
            'logoPath' => Setting::get('site_logo_path', null),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_logo' => ['nullable', 'image', 'max:2048'], // 2MB
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        Setting::set('site_name', $validated['site_name']);

        $removeLogo = (bool)($validated['remove_logo'] ?? false);

        if ($removeLogo) {
            $existing = Setting::get('site_logo_path', null);
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }
            Setting::set('site_logo_path', null);
        }

        if ($request->hasFile('site_logo')) {
            $existing = Setting::get('site_logo_path', null);
            if ($existing) {
                Storage::disk('public')->delete($existing);
            }

            $path = $request->file('site_logo')->store('settings', 'public');
            Setting::set('site_logo_path', $path);
        }

        return back()->with('status', 'Settings updated.');
    }
}
