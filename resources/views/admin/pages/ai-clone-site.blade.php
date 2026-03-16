@extends('layouts.admin')

@section('content')
<style>
    [v-cloak] { display: none !important; }
    .clone-overlay { display: none; }
    .clone-overlay.show { display: flex; }
    .page-checkbox { margin-bottom: 8px; }
    .color-swatch { 
        display: inline-flex; 
        align-items: center; 
        gap: 8px; 
        padding: 8px 12px; 
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .color-swatch:hover {
        background: #f3f4f6;
    }
    .color-swatch.selected {
        background: #dbeafe;
        border-color: #2563eb;
    }
</style>
<div class="w-full px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-2">Clone a Website</h1>
        <p class="text-gray-600 mb-8">Fetch an existing website, analyze it, and create an improved version in your CMS with consistent styling and navigation.</p>

        <div id="cloneApp" class="space-y-8">
            <!-- global spinner/overlay while analyzing or building -->
            <div id="analyzeOverlay" class="clone-overlay" style="position: fixed; inset: 0; background-color: rgba(255, 255, 255, 0.75); display: flex; align-items: center; justify-content: center; z-index: 9999;">
                <div class="text-center">
                    <svg class="animate-spin h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <p class="mt-2 text-blue-600 text-lg font-semibold">@{{ analyzing ? 'Analyzing website…' : 'Building site…' }}</p>
                </div>
            </div>

            <!-- Step 1: Input Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Step 1: Enter Website URL</h2>
                <div class="space-y-4">
                    <div>
                        <label for="siteUrl" class="block text-sm font-medium text-gray-700 mb-2">Website URL to Clone</label>
                        <input
                            type="url"
                            id="siteUrl"
                            v-model="input.url"
                            placeholder="https://example.com"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <p class="text-sm text-gray-500 mt-1">Enter the full URL of the site you want to clone.</p>
                    </div>

                    <div>
                        <label for="modification" class="block text-sm font-medium text-gray-700 mb-2">Improvement Request (Optional)</label>
                        <textarea
                            id="modification"
                            v-model="input.modification"
                            placeholder="e.g., Make it more modern and professional, Add more services section"
                            rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        ></textarea>
                        <p class="text-sm text-gray-500 mt-1">Describe any improvements you'd like Claude to make.</p>
                    </div>

                    <button
                        @click="analyzeWebsite"
                        :disabled="!input.url || analyzing"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg transition"
                    >
                        <span v-if="!analyzing">Analyze Website</span>
                        <span v-else>Analyzing... (this may take a minute)</span>
                    </button>
                </div>
            </div>

            <!-- Step 2: Page Selection & Color Picker -->
            <div v-if="analysis" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-6">Step 2: Select Pages & Customize Colors</h2>

                <!-- Pages Section -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Select Pages to Clone</h3>
                        <button 
                            @click="selectAllPages"
                            class="text-sm text-blue-600 hover:text-blue-700 underline"
                        >
                            @{{ selectedPages.length === analysis.pages.length ? 'Deselect All' : 'Select All' }}
                        </button>
                    </div>
                    <div class="space-y-3 max-h-96 overflow-y-auto border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <div v-if="analysis.pages.length === 0" class="text-gray-600">
                            No pages detected.
                        </div>
                        <template v-else>
                            <label v-for="(page, idx) in analysis.pages" :key="idx" class="page-checkbox flex items-start cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    :value="page.url"
                                    v-model="selectedPages"
                                    class="w-4 h-4 text-blue-600 mt-1"
                                />
                                <div class="ml-3 flex-1">
                                    <div class="font-medium text-gray-900">@{{ page.title || 'Untitled' }}</div>
                                    <div class="text-xs text-gray-600">@{{ page.url }}</div>
                                </div>
                                <span v-if="page.is_home" class="text-xs font-medium text-blue-600 ml-2">🏠 HOME</span>
                            </label>
                        </template>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">@{{ selectedPages.length }} of @{{ analysis.pages.length }} pages selected</p>
                </div>

                <!-- Colors Section -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Customize Colors</h3>
                    
                    <!-- Primary Color Picker -->
                    <div class="mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Primary Color</label>
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <input 
                                    type="color" 
                                    v-model="customColors.primary"
                                    class="w-16 h-16 rounded-lg border-2 border-gray-300 cursor-pointer"
                                />
                            </div>
                            <div class="flex-1">
                                <input 
                                    type="text" 
                                    v-model="customColors.primary"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm"
                                    placeholder="#2563eb"
                                />
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Click the color square or enter a hex code</p>
                    </div>

                    <!-- Detected Colors -->
                    <div v-if="analysis.colors.length > 0">
                        <p class="text-sm font-medium text-gray-700 mb-3">Detected Colors (click to use as primary)</p>
                        <div class="flex flex-wrap gap-2">
                            <button 
                                v-for="(color, idx) in analysis.colors.slice(0, 12)"
                                :key="idx"
                                @click="customColors.primary = color"
                                :class="['color-swatch', { 'selected': customColors.primary.toUpperCase() === color.toUpperCase() }]"
                            >
                                <div :style="{backgroundColor: color}" class="w-6 h-6 rounded border border-gray-300"></div>
                                <span class="text-sm font-mono text-gray-700">@{{ color }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Continue Button -->
                <button
                    @click="generateBlueprint"
                    :disabled="selectedPages.length === 0 || analyzing"
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white font-medium py-2 px-4 rounded-lg transition"
                >
                    <span v-if="!analyzing">Continue to Blueprint (Generating design for @{{ selectedPages.length }} page@{{ selectedPages.length !== 1 ? 's' : '' }})</span>
                    <span v-else>Generating blueprint...</span>
                </button>
            </div>

            <!-- Step 3: Design System Review -->
            <div v-if="design && !blueprint" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Step 3: Design System</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Primary Color</p>
                        <div class="flex items-center mt-2">
                            <div :style="{backgroundColor: design.primary_color}" class="w-8 h-8 rounded border border-gray-300 mr-2"></div>
                            <span class="text-sm text-gray-900 font-mono">@{{ design.primary_color }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Accent Color</p>
                        <div class="flex items-center mt-2">
                            <div :style="{backgroundColor: design.accent_color || '#6b7280'}" class="w-8 h-8 rounded border border-gray-300 mr-2"></div>
                            <span class="text-sm text-gray-900 font-mono">@{{ design.accent_color || 'N/A' }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Layout</p>
                        <p class="text-sm text-gray-900 mt-2 capitalize">@{{ design.layout_pattern }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Nav Style</p>
                        <p class="text-sm text-gray-900 mt-2">@{{ design.nav_style }}</p>
                    </div>
                </div>
            </div>

            <!-- Step 4: Blueprint Review & Build -->
            <div v-if="blueprint" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Step 4: Review Blueprint & Build</h2>
                <p class="text-gray-600 mb-4">@{{ blueprint.pages.length }} pages will be created from this blueprint.</p>

                <div class="space-y-3 mb-6 max-h-64 overflow-y-auto">
                    <div v-for="(page, i) in blueprint.pages" :key="i" class="border border-gray-200 rounded p-3 bg-gray-50">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-gray-900 text-sm">@{{ page.title }}</h3>
                                <p class="text-xs text-gray-600">/@{{ page.slug }}</p>
                            </div>
                            <div class="flex gap-2">
                                <span v-if="page.is_homepage" class="inline-block text-xs font-medium bg-blue-100 text-blue-800 px-2 py-1 rounded">🏠 Home</span>
                                <span class="inline-block text-xs font-medium bg-gray-100 text-gray-800 px-2 py-1 rounded">@{{ page.template }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" v-model="buildOptions.publish" class="w-4 h-4 text-blue-600" />
                        <span class="ml-2 text-sm text-gray-700">Publish pages immediately</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" v-model="buildOptions.setHomepage" class="w-4 h-4 text-blue-600" />
                        <span class="ml-2 text-sm text-gray-700">Set homepage as active</span>
                    </label>
                </div>

                <button
                    @click="buildSite"
                    :disabled="building"
                    class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium py-3 px-4 rounded-lg transition"
                >
                    <span v-if="!building">🚀 Build This Site</span>
                    <span v-else>Building... (this may take several minutes)</span>
                </button>
            </div>

            <!-- Success -->
            <div v-if="success" class="bg-green-50 border border-green-200 rounded-lg p-6">
                <h2 class="text-xl font-bold text-green-800 mb-2">✅ Site Successfully Cloned!</h2>
                <p class="text-green-700 mb-4">@{{ success.message }}</p>
                <div v-if="success.created > 0" class="text-sm text-green-700 mb-4">
                    <p>✓ @{{ success.created }} pages created</p>
                    <p v-if="success.homepage_id">✓ Homepage set (ID: @{{ success.homepage_id }})</p>
                </div>
                <div v-if="success.warnings && success.warnings.length > 0" class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                    <p class="text-sm font-medium text-yellow-800 mb-2">Warnings:</p>
                    <ul class="list-disc list-inside text-sm text-yellow-700">
                        <li v-for="(warning, i) in success.warnings" :key="i">@{{ warning }}</li>
                    </ul>
                </div>
                <a href="{{ route('admin.pages.index') }}" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                    View All Pages
                </a>
            </div>

            <!-- Errors -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 class="text-lg font-bold text-red-800 mb-2">❌ Error</h3>
                <p class="text-red-700">@{{ error }}</p>
                <button @click="error = null" class="mt-3 text-sm text-red-600 hover:text-red-800 underline">Dismiss</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
<script>
                    </div>
                </div>

                <div v-if="analysis.colors.length > 0" class="mb-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Detected Colors</p>
                    <div class="flex flex-wrap gap-2">
                        <div v-for="(color, i) in analysis.colors" :key="i" class="inline-flex items-center">
                            <div :style="{backgroundColor: color}" class="w-8 h-8 rounded border border-gray-300 mr-2"></div>
                            <span class="text-sm text-gray-600">@{{ color }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Design System -->
            <div v-if="design" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Step 3: Design System</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Primary Color</p>
                        <div class="flex items-center mt-1">
                            <div :style="{backgroundColor: design.primary_color}" class="w-8 h-8 rounded border border-gray-300 mr-2"></div>
                            <span class="text-sm text-gray-900">@{{ design.primary_color }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Layout</p>
                        <p class="text-sm text-gray-900capitalize mt-1">@{{ design.layout_pattern }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Nav Style</p>
                        <p class="text-sm text-gray-900 mt-1">@{{ design.nav_style }}</p>
                    </div>
                </div>
            </div>

            <!-- Step 4: Blueprint Review & Build -->
            <div v-if="blueprint" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Step 4: Review Blueprint & Build</h2>
                <p class="text-gray-600 mb-4">@{{ blueprint.pages.length }} pages will be created from this blueprint.</p>

                <div class="space-y-4 mb-6">
                    <div v-for="(page, i) in blueprint.pages" :key="i" class="border border-gray-200 rounded p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-gray-900">@{{ page.title }}</h3>
                                <p class="text-sm text-gray-600">@{{ page.slug }}</p>
                                <p v-if="page.is_homepage" class="text-sm text-blue-600 font-medium">🏠 Homepage</p>
                            </div>
                            <span class="inline-block text-xs font-medium bg-gray-100 text-gray-800 px-2 py-1 rounded">@{{ page.template }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 mb-6">
                    <label class="flex items-center">
                        <input type="checkbox" v-model="buildOptions.publish" class="w-4 h-4 text-blue-600" />
                        <span class="ml-2 text-sm text-gray-700">Publish pages immediately</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" v-model="buildOptions.setHomepage" class="w-4 h-4 text-blue-600" />
                        <span class="ml-2 text-sm text-gray-700">Set homepage as active</span>
                    </label>
                </div>

                <button
                    @click="buildSite"
                    :disabled="building"
                    class="w-full bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-medium py-3 px-4 rounded-lg transition"
                >
                    <span v-if="!building">🚀 Build This Site</span>
                    <span v-else>Building... (this may take several minutes)</span>
                </button>
            </div>

            <!-- Step 5: Success -->
            <div v-if="success" class="bg-green-50 border border-green-200 rounded-lg p-6">
                <h2 class="text-xl font-bold text-green-800 mb-2">✅ Site Successfully Cloned!</h2>
                <p class="text-green-700 mb-4">@{{ success.message }}</p>
                <div v-if="success.created > 0" class="text-sm text-green-700 mb-4">
                    <p>✓ @{{ success.created }} pages created</p>
                    <p v-if="success.homepage_id">✓ Homepage set (ID: @{{ success.homepage_id }})</p>
                </div>
                <div v-if="success.warnings.length > 0" class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                    <p class="text-sm font-medium text-yellow-800 mb-2">Warnings:</p>
                    <ul class="list-disc list-inside text-sm text-yellow-700">
                        <li v-for="(warning, i) in success.warnings" :key="i">@{{ warning }}</li>
                    </ul>
                </div>
                <a href="{{ route('admin.pages.index') }}" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                    View All Pages
                </a>
            </div>

            <!-- Error Display -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 class="text-lg font-bold text-red-800 mb-2">❌ Error</h3>
                <p class="text-red-700">@{{ error }}</p>
                <button @click="error = null" class="mt-3 text-sm text-red-600 hover:text-red-800 underline">Dismiss</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>

<script>
console.log('site-clone: Vue script loaded, Vue available:', typeof window.Vue !== 'undefined');

document.addEventListener('DOMContentLoaded', function() {
    if (typeof Vue === 'undefined') {
        console.error('site-clone: Vue failed to load from CDN');
        document.getElementById('cloneApp').innerHTML = '<div class="bg-red-50 border border-red-200 rounded-lg p-6"><p class="text-red-700">Error: Vue framework failed to load. Please refresh the page.</p></div>';
        return;
    }

    console.log('site-clone: Vue is available, mounting app');
    const app = new Vue({
        el: '#cloneApp',
        data: {
            input: {
                url: '',
                modification: '',
            },
            analyzing: false,
            building: false,
            analysis: null,
            selectedPages: [],
            customColors: {
                primary: '#2563eb',
            },
            design: null,
            blueprint: null,
            success: null,
            error: null,
            buildOptions: {
                publish: false,
                setHomepage: false,
            },
        },
        watch: {
            analyzing(val) {
                this.updateOverlay();
            },
            building(val) {
                this.updateOverlay();
            }
        },
        methods: {
            async parseApiResponse(response, fallbackMessage) {
                const contentType = String(response.headers.get('content-type') || '').toLowerCase();
                const raw = await response.text();

                if (!contentType.includes('application/json')) {
                    const preview = raw.replace(/\s+/g, ' ').slice(0, 140);
                    throw new Error(`${fallbackMessage} (HTTP ${response.status}). Server returned non-JSON response: ${preview}`);
                }

                let data;
                try {
                    data = JSON.parse(raw);
                } catch (err) {
                    throw new Error(`${fallbackMessage} (HTTP ${response.status}). Server returned invalid JSON.`);
                }

                if (!response.ok) {
                    const details = data?.error || data?.message || fallbackMessage;
                    throw new Error(`${details} (HTTP ${response.status})`);
                }

                return data;
            },
            updateOverlay() {
                const overlay = document.getElementById('analyzeOverlay');
                if (overlay) {
                    if (this.analyzing || this.building) {
                        overlay.style.display = 'flex';
                    } else {
                        overlay.style.display = 'none';
                    }
                }
            },
            selectAllPages() {
                if (this.selectedPages.length === this.analysis.pages.length) {
                    this.selectedPages = [];
                } else {
                    this.selectedPages = this.analysis.pages.map(p => p.url);
                }
            },
            async analyzeWebsite() {
                this.error = null;
                this.analysis = null;
                this.selectedPages = [];
                this.design = null;
                this.blueprint = null;
                this.success = null;
                this.analyzing = true;

                try {
                    // Health check
                    const healthRes = await fetch('{{ route("admin.site-clone.health") }}');
                    const healthData = await healthRes.json();

                    if (!healthData.healthy) {
                        throw new Error(healthData.message || 'AI provider not available');
                    }

                    // Analyze the website (NOT generating blueprint yet)
                    const response = await fetch('{{ route("admin.site-clone.analyze") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            url: this.input.url,
                            modification: this.input.modification,
                            max_pages: 20,  // Detect ALL pages (max allowed)
                        }),
                    });

                    const data = await this.parseApiResponse(response, 'Analysis failed');
                    if (!data.success) {
                        throw new Error(data.error || 'Analysis failed');
                    }

                    this.analysis = data.analysis;
                    // Pre-select home page if found, otherwise all pages
                    if (this.analysis.pages.length > 0) {
                        this.selectedPages = this.analysis.pages.map(p => p.url);
                    }
                    // Set default primary color from analysis
                    if (this.analysis.colors && this.analysis.colors.length > 0) {
                        this.customColors.primary = this.analysis.colors[0];
                    }
                } catch (e) {
                    console.error('analysis error', e);
                    this.error = 'Failed to analyze website: ' + e.message;
                } finally {
                    this.analyzing = false;
                }
            },
            async generateBlueprint() {
                this.error = null;
                this.design = null;
                this.blueprint = null;
                this.analyzing = true;

                try {
                    // Create a filtered analysis with only selected pages
                    const filteredAnalysis = {
                        ...this.analysis,
                        pages: this.analysis.pages.filter(p => this.selectedPages.includes(p.url))
                    };

                    // Generate blueprint with selected pages and custom color
                    const response = await fetch('{{ route("admin.site-clone.analyze") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            url: this.input.url,
                            modification: this.input.modification,
                            max_pages: this.selectedPages.length,
                            selected_pages: this.selectedPages,
                        }),
                    });

                    const data = await this.parseApiResponse(response, 'Blueprint generation failed');
                    if (!data.success) {
                        throw new Error(data.error || 'Blueprint generation failed');
                    }

                    this.design = data.design;
                    this.blueprint = data.blueprint;
                    
                    // Override primary color with user's choice
                    if (this.design) {
                        this.design.primary_color = this.customColors.primary;
                    }
                } catch (e) {
                    console.error('blueprint generation error', e);
                    this.error = 'Failed to generate blueprint: ' + e.message;
                } finally {
                    this.analyzing = false;
                }
            },
            async buildSite() {
                this.error = null;
                this.building = true;

                try {
                    const response = await fetch('{{ route("admin.site-clone.build") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            source_url: this.input.url,
                            blueprint_json: JSON.stringify(this.blueprint),
                            design_system: JSON.stringify(this.design),
                            analysis: JSON.stringify(this.analysis),
                            publish: this.buildOptions.publish,
                            set_homepage: this.buildOptions.setHomepage,
                        }),
                    });

                    const data = await this.parseApiResponse(response, 'Build failed');
                    if (!data.success) {
                        throw new Error(data.error || 'Build failed');
                    }

                    this.success = data;
                } catch (e) {
                    console.error('build error', e);
                    this.error = 'Failed to build site: ' + e.message;
                } finally {
                    this.building = false;
                }
            },
        },
        mounted() {
            console.log('Clone app mounted');
            this.updateOverlay();
        }
    });
});
</script>
@endsection
