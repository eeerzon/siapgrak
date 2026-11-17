<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Judge\StoreJudgeRequest;
use App\Http\Requests\Judge\UpdateJudgeRequest;
use App\Models\Judge;
use App\Models\Organization;
use App\Services\JudgeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JudgeController extends Controller
{
    public function __construct(protected JudgeService $judges) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Judge::class);

        $organizationIds = $this->judges->assignableOrganizationIds($request->user());

        $judges = Judge::query()
            ->whereIn('organization_id', $organizationIds)
            ->with('organization')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.judges.index', compact('judges'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Judge::class);

        $organizations = Organization::whereIn('id', $this->judges->assignableOrganizationIds($request->user()))
            ->orderBy('name')
            ->get();

        return view('dashboard.judges.create', compact('organizations'));
    }

    public function store(StoreJudgeRequest $request): RedirectResponse
    {
        $judge = $this->judges->create($request->validated());

        return redirect()
            ->route('dashboard.judges.show', $judge)
            ->with('success', 'Juri berhasil ditambahkan.');
    }

    public function show(Judge $judge): View
    {
        $this->authorize('view', $judge);

        return view('dashboard.judges.show', compact('judge'));
    }

    public function edit(Judge $judge): View
    {
        $this->authorize('update', $judge);

        return view('dashboard.judges.edit', compact('judge'));
    }

    public function update(UpdateJudgeRequest $request, Judge $judge): RedirectResponse
    {
        $this->judges->update($judge, $request->validated());

        return redirect()
            ->route('dashboard.judges.show', $judge)
            ->with('success', 'Data juri berhasil diperbarui.');
    }

    public function regenerateAccessCode(Judge $judge): RedirectResponse
    {
        $this->authorize('update', $judge);

        $judge->access_code = Judge::generateAccessCode();
        $judge->save();

        return redirect()
            ->route('dashboard.judges.show', $judge)
            ->with('success', 'Kode akses juri diperbarui. Kode lama tidak berlaku lagi.');
    }
}
