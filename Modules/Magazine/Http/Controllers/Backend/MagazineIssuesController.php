<?php

namespace Modules\Magazine\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Magazine\Models\Magazine;
use Modules\Magazine\Models\MagazineIssue;
use Modules\Magazine\Models\MagazineIssuePrint;
use Modules\Magazine\Http\Requests\MagazineIssueRequest;

class MagazineIssuesController extends Controller
{
    public function index(Request $request): View
    {
        $magazine = $request->filled('magazine_id') ? Magazine::find($request->magazine_id) : null;
        $module_action = 'List';
        return view('magazine::backend.issue.index', compact('module_action', 'magazine'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = MagazineIssue::query()->withTrashed()->with('magazine');
        if ($request->filled('magazine_id')) {
            $query->where('magazine_id', $request->magazine_id);
        }
        return $datatable->eloquent($query)
            ->addColumn('action', function ($data) {
                return view('magazine::backend.issue.action', compact('data'))->render();
            })
            ->editColumn('release_date', function ($data) {
                return $data->release_date ? $data->release_date->format('Y-m-d') : '—';
            })
            ->editColumn('status', function ($data) {
                return $data->status;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create(Request $request): View
    {
        $module_title = __('magazine::add_issue');
        $magazines = Magazine::whereNull('deleted_at')->orderBy('title')->get();
        $magazineId = $request->get('magazine_id');
        $ctaLabels = ['Order Print Copy', 'Get Collector\'s Print', 'Order Collector Print', 'Buy Print'];
        return view('magazine::backend.issue.create', compact('module_title', 'magazines', 'magazineId', 'ctaLabels'));
    }

    public function store(MagazineIssueRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $printEnabled = $request->boolean('print_enabled');
        $data['created_by'] = $data['updated_by'] = auth()->id();

        unset($data['print_enabled'], $data['magcloud_product_url'], $data['magcloud_viewer_url'], $data['cta_label'], $data['notes_internal']);
        $issue = MagazineIssue::create($data);

        $this->syncPrintConfig($issue, $request, $printEnabled);
        return redirect()->route('backend.magazine-issues.index')->with('success', __('messages.save_form', ['form' => __('magazine::issues')]));
    }

    public function edit(MagazineIssue $magazine_issue): View
    {
        $module_title = __('magazine::edit_issue');
        $magazines = Magazine::whereNull('deleted_at')->orderBy('title')->get();
        $issue = $magazine_issue->load('printConfig');
        $ctaLabels = ['Order Print Copy', 'Get Collector\'s Print', 'Order Collector Print', 'Buy Print'];
        return view('magazine::backend.issue.edit', compact('module_title', 'magazines', 'issue', 'ctaLabels'));
    }

    public function update(MagazineIssueRequest $request, MagazineIssue $magazine_issue): RedirectResponse
    {
        $data = $request->validated();
        $printEnabled = $request->boolean('print_enabled');
        $data['updated_by'] = auth()->id();

        unset($data['print_enabled'], $data['magcloud_product_url'], $data['magcloud_viewer_url'], $data['cta_label'], $data['notes_internal']);
        $magazine_issue->update($data);

        $this->syncPrintConfig($magazine_issue, $request, $printEnabled);
        return redirect()->route('backend.magazine-issues.index')->with('success', __('messages.update_form', ['form' => __('magazine::issues')]));
    }

    public function destroy(MagazineIssue $magazine_issue)
    {
        $magazine_issue->delete();
        return response()->json(['message' => __('messages.delete_form', ['form' => __('magazine::issues')]), 'status' => true], 200);
    }

    private function syncPrintConfig(MagazineIssue $issue, Request $request, bool $printEnabled): void
    {
        $print = $issue->printConfig ?? new MagazineIssuePrint(['issue_id' => $issue->id]);
        $print->issue_id = $issue->id;
        $print->print_enabled = $printEnabled;
        $print->magcloud_product_url = $printEnabled ? $request->input('magcloud_product_url') : null;
        $print->magcloud_viewer_url = $printEnabled ? $request->input('magcloud_viewer_url') : null;
        $print->cta_label = $printEnabled ? ($request->input('cta_label') ?: 'Order Print Copy') : 'Order Print Copy';
        $print->notes_internal = $request->input('notes_internal');
        $print->updated_by = auth()->id();
        $print->save();
    }
}
