<?php

namespace Modules\PublisherStudio\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\PublisherStudio\Models\Publisher;
use Yajra\DataTables\DataTables;

class PublishersController extends Controller
{
    public function index(Request $request)
    {
        $module_action = 'List';
        $filter = ['status' => $request->get('status')];
        return view('publisherstudio::backend.publishers.index', compact('module_action', 'filter'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Publisher::query()->withCount('submissions')->latest();

        if ($request->filled('filter.column_status')) {
            $query->where('status', $request->input('filter.column_status'));
        }

        return $datatable->eloquent($query)
            ->addColumn('company', fn ($d) => $d->company ?: '-')
            ->addColumn('content_focus', fn ($d) => $d->content_focus ?: '-')
            ->addColumn('submissions', fn ($d) => $d->submissions_count)
            ->editColumn('status', fn ($d) => '<span class="badge bg-' . $this->color($d->status) . '">' . ucfirst($d->status) . '</span>')
            ->addColumn('action', fn ($d) => view('publisherstudio::backend.publishers.action', ['data' => $d])->render())
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function show(Publisher $publisher)
    {
        $publisher->loadCount('submissions');
        return view('publisherstudio::backend.publishers.show', compact('publisher'));
    }

    public function approve(Publisher $publisher)
    {
        $publisher->status = 'approved';
        $publisher->approved_at = now();
        $publisher->approved_by = Auth::id();
        $publisher->save();

        return redirect()->route('backend.publishers.show', $publisher->id)->with('status', 'Publisher approved.');
    }

    public function suspend(Publisher $publisher)
    {
        $publisher->status = 'suspended';
        $publisher->save();

        return redirect()->route('backend.publishers.show', $publisher->id)->with('status', 'Publisher suspended.');
    }

    public function reactivate(Publisher $publisher)
    {
        $publisher->status = $publisher->approved_at ? 'approved' : 'pending';
        $publisher->save();

        return redirect()->route('backend.publishers.show', $publisher->id)->with('status', 'Publisher reactivated.');
    }

    private function color(string $status): string
    {
        return ['approved' => 'success', 'pending' => 'warning', 'suspended' => 'danger'][$status] ?? 'secondary';
    }
}
