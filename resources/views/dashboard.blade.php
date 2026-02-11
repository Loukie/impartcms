<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            Welcome to the ImpartCMS admin.
        </p>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <div class="text-sm text-gray-600">Quick links</div>
            <div class="mt-3 space-y-2">
                <a class="block underline text-gray-700 hover:text-gray-900" href="{{ route('admin.pages.index') }}">
                    Manage Pages
                </a>
                <a class="block underline text-gray-700 hover:text-gray-900" href="{{ route('admin.pages.trash') }}">
                    View Trash
                </a>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <div class="text-sm text-gray-600">Status</div>
            <div class="mt-3 text-gray-800">
                Admin sidebar is now global ✅
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-5">
            <div class="text-sm text-gray-600">Next up</div>
            <div class="mt-3 text-gray-800">
                Forms + Modules + SEO output
            </div>
        </div>
    </div>
</x-admin-layout>
