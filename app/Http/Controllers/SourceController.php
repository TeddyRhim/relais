<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Rules\SafeTargetUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SourceController extends Controller
{
    public function index(): View
    {
        return view('sources.index', [
            'sources' => Source::withCount('events')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $source = Source::create($this->validated($request));

        return redirect()->route('sources.show', $source)->with('status', 'Source créée.');
    }

    public function show(Source $source): View
    {
        return view('sources.show', [
            'source' => $source,
            'events' => $source->events()->latest()->limit(10)->get(),
        ]);
    }

    public function update(Request $request, Source $source): RedirectResponse
    {
        $source->update($this->validated($request));

        return back()->with('status', 'Source mise à jour.');
    }

    public function destroy(Source $source): RedirectResponse
    {
        $source->delete();

        return redirect()->route('sources.index')->with('status', 'Source supprimée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'signature_header' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9-]+$/'],
            'require_signature' => ['sometimes', 'boolean'],
            'target_url' => ['nullable', 'string', 'max:2000', new SafeTargetUrl],
        ]);

        $data['require_signature'] = $request->boolean('require_signature');
        $data['signature_header'] = $data['signature_header'] ?? config('relais.default_signature_header');

        return $data;
    }
}
