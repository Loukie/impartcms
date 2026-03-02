<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Ai\AiSiteBlueprintGenerator;
use App\Support\Ai\AiSiteBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiSiteBuilderAdminController extends Controller
{
    public function create(Request $request): View
    {
        $step = 'input';
        $input = [
            'site_name' => (string) old('site_name', ''),
            'industry' => (string) old('industry', ''),
            'location' => (string) old('location', ''),
            'audience' => (string) old('audience', ''),
            'tone' => (string) old('tone', 'clear, modern, confident'),
            'primary_cta' => (string) old('primary_cta', 'Get in touch'),
            'page_preset' => (string) old('page_preset', 'business'),
            'notes' => (string) old('notes', ''),
        ];

        $blueprintJson = null;
        $blueprint = null;
        $report = null;

        // PRG: if a blueprint result was flashed to session, show it now
        if ($request->session()->has('ai_blueprint')) {
            $step = 'blueprint';
            $res = $request->session()->get('ai_blueprint');
            $input = $request->session()->get('ai_blueprint_input', $input);
            $blueprintJson = (string) ($res['raw_json'] ?? '');
            $blueprint = $res['blueprint'] ?? null;
        }

        return view('admin.pages.ai-site-builder', [
            'step' => $step,
            'input' => $input,
            'blueprintJson' => $blueprintJson,
            'blueprint' => $blueprint,
            'report' => $report,
        ]);
    }

    public function blueprint(Request $request, AiSiteBlueprintGenerator $blueprints): View|RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:180'],
            'industry' => ['nullable', 'string', 'max:180'],
            'location' => ['nullable', 'string', 'max:180'],
            'audience' => ['nullable', 'string', 'max:280'],
            'tone' => ['nullable', 'string', 'max:200'],
            'primary_cta' => ['nullable', 'string', 'max:200'],
            'page_preset' => ['required', 'string', 'in:basic,business,full'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        try {
            $res = $blueprints->generate($data);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'site_name' => 'AI blueprint failed: ' . $e->getMessage(),
            ]);
        }

        // flash results and redirect to GET page to avoid 404 on refresh
        return redirect()->route('admin.site-builder.create')
            ->with('ai_blueprint', $res)
            ->with('ai_blueprint_input', $data);
    }

    public function build(Request $request, AiSiteBuilder $builder): View|RedirectResponse
    {
        $data = $request->validate([
            'blueprint_json' => ['required', 'string', 'max:200000'],
            'style_mode' => ['nullable', 'string', 'in:inline,classes'],
            'template' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'in:draft,publish'],
            'publish_homepage' => ['nullable', 'boolean'],
            'set_homepage' => ['nullable', 'boolean'],
        ]);

        try {
            $report = $builder->buildFromBlueprintJson(
                blueprintJson: (string) $data['blueprint_json'],
                options: [
                    'style_mode' => (string) ($data['style_mode'] ?? 'inline'),
                    'template' => (string) ($data['template'] ?? 'blank'),
                    'action' => (string) ($data['action'] ?? 'draft'),
                    'publish_homepage' => (bool) ($data['publish_homepage'] ?? false),
                    'set_homepage' => (bool) ($data['set_homepage'] ?? false),
                ],
            );
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors([
                'blueprint_json' => 'Build failed: ' . $e->getMessage(),
            ]);
        }

        return view('admin.pages.ai-site-builder', [
            'step' => 'report',
            'input' => null,
            'blueprintJson' => (string) $data['blueprint_json'],
            'blueprint' => null,
            'report' => $report,
        ]);
    }
}
