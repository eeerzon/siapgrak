<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OrganizationController extends Controller
{
    public function __construct(protected OrganizationService $organizations) {}

    public function index(): View
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = request()->user()->organizations()
            ->wherePivot('status', Status::Active)
            ->orderBy('name')
            ->get();

        return view('dashboard.organizations.index', compact('organizations'));
    }

    public function create(): View
    {
        $this->authorize('create', Organization::class);

        return view('dashboard.organizations.create');
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = $this->organizations->create(
            $request->validated(),
            $request->user(),
        );

        return redirect()
            ->route('dashboard.organizations.show', $organization)
            ->with('success', 'Organisasi berhasil dibuat.');
    }

    public function show(Organization $organization): View
    {
        $this->authorize('view', $organization);

        return view('dashboard.organizations.show', compact('organization'));
    }

    public function edit(Organization $organization): View
    {
        $this->authorize('update', $organization);

        return view('dashboard.organizations.edit', compact('organization'));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->organizations->update($organization, $request->validated());

        return redirect()
            ->route('dashboard.organizations.show', $organization)
            ->with('success', 'Organisasi berhasil diperbarui.');
    }
}
