<?php

namespace App\Http\Controllers;

use App\Enums\IpListType;
use App\Filters\IpFilters;
use App\Http\Requests\StoreIpRequest;
use App\Http\Requests\UpdateIpRequest;
use App\Models\Ip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IpController extends Controller
{
    public function index(Request $request, IpFilters $filters): Response
    {
        $this->authorize('viewAny', Ip::class);

        $ips = $filters->apply(Ip::query())
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Ips/Index', [
            'ips' => cursorResponse($ips, fn (Ip $ip) => $this->row($ip)),
            'filters' => $filters->echoBack(),
            'listTypes' => IpListType::options(),
        ]);
    }

    public function store(StoreIpRequest $request): RedirectResponse
    {
        $this->authorize('create', Ip::class);

        Ip::create($request->validated());

        return redirect()->route('ips.index')->with('success', 'IP entry created.');
    }

    public function show(Ip $ip): Response
    {
        $this->authorize('view', $ip);

        return Inertia::render('Ips/Show', [
            'ip' => $this->row($ip),
            'listTypes' => IpListType::options(),
        ]);
    }

    public function update(UpdateIpRequest $request, Ip $ip): RedirectResponse
    {
        $this->authorize('update', $ip);

        $ip->update($request->validated());

        return back()->with('success', 'IP entry updated.');
    }

    public function destroy(Ip $ip): RedirectResponse
    {
        $this->authorize('delete', $ip);

        $ip->delete();

        return redirect()->route('ips.index')->with('success', 'IP entry deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'process' => ['required', 'in:active,in_active,delete,white_list'],
            'tokens' => ['required', 'array'],
            'tokens.*' => ['string'],
        ]);

        $this->authorize($validated['process'] === 'delete' ? 'delete' : 'update', Ip::class);

        $count = Ip::bulkAction($validated['process'], $validated['tokens']);

        return back()->with('success', "{$count} IP entrie(s) updated.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Ip $ip): array
    {
        return [
            'token' => $ip->token,
            'ip_address' => $ip->ip_address,
            'list_type' => $ip->list_type->value,
            'description' => $ip->description,
            'record_status' => $ip->record_status->value,
            'created_at' => $ip->created_at?->toIso8601String(),
        ];
    }
}
