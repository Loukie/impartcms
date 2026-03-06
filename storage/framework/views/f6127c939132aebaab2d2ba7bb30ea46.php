

<?php $__env->startSection('content'); ?>
<style>
    /* Hide overlay by default; Vue will show it when analyzing/building */
    [v-cloak] { display: none !important; }
    .clone-overlay { display: none; }
    .clone-overlay.show { display: flex; }
</style>
<div class="container mx-auto px-4 py-8">
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
                    <p class="mt-2 text-blue-600 text-lg font-semibold">{{ analyzing ? 'Analyzing website…' : 'Building site…' }}</p>
                </div>
            </div>
            <!-- Step 1: Input Form -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Step 1: Analyze Website</h2>
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

                    <div>
                        <label for="maxPages" class="block text-sm font-medium text-gray-700 mb-2">Maximum Pages</label>
                        <select
                            id="maxPages"
                            v-model.number="input.maxPages"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="3">3 pages</option>
                            <option value="5">5 pages</option>
                            <option value="8" selected>8 pages</option>
                            <option value="10">10 pages</option>
                            <option value="15">15 pages</option>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Limit the number of pages to clone.</p>
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

            <!-- Step 2: Analysis Results -->
            <div v-if="analysis" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Step 2: Review Analysis</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Site Title</p>
                        <p class="text-gray-900">{{ analysis.title }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Pages Found</p>
                        <p class="text-gray-900">{{ analysis.pages.length }}</p>
                    </div>
                </div>

                <div v-if="analysis.navigation.length > 0" class="mb-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Navigation Structure</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="(nav, i) in analysis.navigation" :key="i" class="inline-block bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded">
                            {{ nav }}
                        </span>
                    </div>
                </div>

                <div v-if="analysis.colors.length > 0" class="mb-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Detected Colors</p>
                    <div class="flex flex-wrap gap-2">
                        <div v-for="(color, i) in analysis.colors" :key="i" class="inline-flex items-center">
                            <div :style="{backgroundColor: color}" class="w-8 h-8 rounded border border-gray-300 mr-2"></div>
                            <span class="text-sm text-gray-600">{{ color }}</span>
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
                            <span class="text-sm text-gray-900">{{ design.primary_color }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Layout</p>
                        <p class="text-sm text-gray-900capitalize mt-1">{{ design.layout_pattern }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Nav Style</p>
                        <p class="text-sm text-gray-900 mt-1">{{ design.nav_style }}</p>
                    </div>
                </div>
            </div>

            <!-- Step 4: Blueprint Review & Build -->
            <div v-if="blueprint" class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Step 4: Review Blueprint & Build</h2>
                <p class="text-gray-600 mb-4">{{ blueprint.pages.length }} pages will be created from this blueprint.</p>

                <div class="space-y-4 mb-6">
                    <div v-for="(page, i) in blueprint.pages" :key="i" class="border border-gray-200 rounded p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-medium text-gray-900">{{ page.title }}</h3>
                                <p class="text-sm text-gray-600">{{ page.slug }}</p>
                                <p v-if="page.is_homepage" class="text-sm text-blue-600 font-medium">🏠 Homepage</p>
                            </div>
                            <span class="inline-block text-xs font-medium bg-gray-100 text-gray-800 px-2 py-1 rounded">{{ page.template }}</span>
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
                <p class="text-green-700 mb-4">{{ success.message }}</p>
                <div v-if="success.created > 0" class="text-sm text-green-700 mb-4">
                    <p>✓ {{ success.created }} pages created</p>
                    <p v-if="success.homepage_id">✓ Homepage set (ID: {{ success.homepage_id }})</p>
                </div>
                <div v-if="success.warnings.length > 0" class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
                    <p class="text-sm font-medium text-yellow-800 mb-2">Warnings:</p>
                    <ul class="list-disc list-inside text-sm text-yellow-700">
                        <li v-for="(warning, i) in success.warnings" :key="i">{{ warning }}</li>
                    </ul>
                </div>
                <a href="<?php echo e(route('admin.pages.index')); ?>" class="inline-block bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                    View All Pages
                </a>
            </div>

            <!-- Error Display -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 class="text-lg font-bold text-red-800 mb-2">❌ Error</h3>
                <p class="text-red-700">{{ error }}</p>
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
                maxPages: 8,
            },
            analyzing: false,
            building: false,
            analysis: null,
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
                console.log('site-clone: analyzing changed to', val);
                this.updateOverlay();
            },
            building(val) {
                console.log('site-clone: building changed to', val);
                this.updateOverlay();
            }
        },
        methods: {
            updateOverlay() {
                const overlay = document.getElementById('analyzeOverlay');
                if (overlay) {
                    if (this.analyzing || this.building) {
                        console.log('site-clone: showing overlay');
                        overlay.style.display = 'flex';
                    } else {
                        console.log('site-clone: hiding overlay');
                        overlay.style.display = 'none';
                    }
                } else {
                    console.warn('site-clone: overlay element not found');
                }
            },
            async analyzeWebsite() {
                console.log('site clone: start health check');
                this.error = null;
                this.analysis = null;
                this.design = null;
                this.blueprint = null;
                this.success = null;
                this.analyzing = true;

                try {
                    // First, check if AI is configured
                    console.log('site clone: checking AI health');
                    const healthRes = await fetch('<?php echo e(route("admin.site-clone.health")); ?>');
                    const healthData = await healthRes.json();

                    if (!healthData.healthy) {
                        throw new Error(healthData.message || 'AI provider not available');
                    }

                    console.log('site clone: AI health check passed, proceeding with analysis');

                    // Now proceed with analysis
                    const response = await fetch('<?php echo e(route("admin.site-clone.analyze")); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            url: this.input.url,
                            modification: this.input.modification,
                            max_pages: this.input.maxPages,
                        }),
                    });

                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.error || 'Analysis failed');
                    }

                    this.analysis = data.analysis;
                    this.design = data.design;
                    this.blueprint = data.blueprint;
                } catch (e) {
                    console.error('site clone analysis error', e);
                    this.error = 'Failed to analyze website: ' + e.message;
                } finally {
                    console.log('site clone: analysis complete');
                    this.analyzing = false;
                }
            },
            async buildSite() {
                console.log('site clone: start build');
                this.error = null;
                this.building = true;

                try {
                    const response = await fetch('<?php echo e(route("admin.site-clone.build")); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            source_url: this.input.url,
                            blueprint_json: JSON.stringify(this.blueprint),
                            design_system: JSON.stringify(this.design),
                            publish: this.buildOptions.publish,
                            set_homepage: this.buildOptions.setHomepage,
                        }),
                    });

                    const data = await response.json();
                    console.log('site clone: build response', data);
                    if (!data.success) {
                        throw new Error(data.error || 'Build failed');
                    }

                    this.success = data;
                } catch (e) {
                    console.error('site clone build error', e);
                    this.error = 'Failed to build site: ' + e.message;
                } finally {
                    this.building = false;
                }
            },
        },
        mounted() {
            console.log('site-clone: Vue app mounted successfully');
            this.updateOverlay();
        }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\2kocms\resources\views/admin/pages/ai-clone-site.blade.php ENDPATH**/ ?>