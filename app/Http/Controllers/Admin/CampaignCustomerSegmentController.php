<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\CampaignCustomerSegmentResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCampaignCustomerSegmentRequest;
use App\Http\Requests\Admin\UpdateCampaignCustomerSegmentRequest;
use App\Models\CampaignCustomerSegment;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class CampaignCustomerSegmentController extends Controller
{
    public function index(): View
    {
        $segments = CampaignCustomerSegment::query()
            ->latest()
            ->paginate(15);

        return view('admin.campaign-customer-segments.index', [
            'segments' => $segments,
            'columns' => CampaignCustomerSegmentResource::tableColumns(),
        ]);
    }

    public function create(): View
    {
        return view('admin.campaign-customer-segments.create', [
            'schema' => CampaignCustomerSegmentResource::formSchema(),
        ]);
    }

    public function store(StoreCampaignCustomerSegmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $filters = CampaignCustomerSegmentResource::decodeFilters($request->input('filters'));
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['filters' => $exception->getMessage()]);
        }

        $segment = CampaignCustomerSegment::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'filters' => $filters,
            'is_active' => $request->boolean('is_active'),
        ]);

        $segment->recalculateMatchCount();

        return redirect()
            ->route('admin.campaign-customer-segments.edit', $segment)
            ->with('status', __('admin.campaign_customer_segments.status.created'));
    }

    public function edit(CampaignCustomerSegment $campaignCustomerSegment): View
    {
        return view('admin.campaign-customer-segments.edit', [
            'segment' => $campaignCustomerSegment,
            'schema' => CampaignCustomerSegmentResource::formSchema(),
            'filtersJson' => json_encode($campaignCustomerSegment->normalizedFilters(), JSON_PRETTY_PRINT),
            'filtersPreview' => CampaignCustomerSegmentResource::filtersPreview($campaignCustomerSegment->normalizedFilters()),
        ]);
    }

    public function update(UpdateCampaignCustomerSegmentRequest $request, CampaignCustomerSegment $campaignCustomerSegment): RedirectResponse
    {
        $data = $request->validated();

        try {
            $filters = CampaignCustomerSegmentResource::decodeFilters($request->input('filters'));
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['filters' => $exception->getMessage()]);
        }

        $campaignCustomerSegment->fill([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'filters' => $filters,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        $campaignCustomerSegment->recalculateMatchCount();

        return redirect()
            ->route('admin.campaign-customer-segments.edit', $campaignCustomerSegment)
            ->with('status', __('admin.campaign_customer_segments.status.updated'));
    }

    public function destroy(CampaignCustomerSegment $campaignCustomerSegment): RedirectResponse
    {
        $campaignCustomerSegment->delete();

        return redirect()
            ->route('admin.campaign-customer-segments.index')
            ->with('status', __('admin.campaign_customer_segments.status.deleted'));
    }

    public function recalculate(CampaignCustomerSegment $campaignCustomerSegment): RedirectResponse
    {
        $campaignCustomerSegment->recalculateMatchCount();

        return back()->with('status', __('admin.campaign_customer_segments.status.recalculated'));
    }
}
