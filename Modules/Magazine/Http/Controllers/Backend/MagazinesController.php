<?php

namespace Modules\Magazine\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Trait\ModuleTrait;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Modules\Magazine\Models\Magazine;
use Modules\Magazine\Http\Requests\MagazineRequest;
use Yajra\DataTables\DataTables;

class MagazinesController extends Controller
{
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'magazine::title',
            'magazines',
            'fa-solid fa-book'
        );
    }

    public function index(Request $request): View
    {
        $module_action = 'List';
        $filter = ['status' => $request->get('status')];
        return view('magazine::backend.magazine.index', compact('module_action', 'filter'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Magazine::query()->withTrashed();
        if ($request->filled('filter.column_status')) {
            $query->where('status', $request->input('filter.column_status'));
        }
        return $datatable->eloquent($query)
            ->addColumn('action', function ($data) {
                return view('magazine::backend.magazine.action', compact('data'))->render();
            })
            ->editColumn('status', function ($data) {
                return $data->status;
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create(): View
    {
        $module_title = __('magazine::add_series');
        return view('magazine::backend.magazine.create', compact('module_title'));
    }

    public function store(MagazineRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $data['updated_by'] = auth()->id();
        Magazine::create($data);
        return redirect()->route('backend.magazines.index')->with('success', __('messages.save_form', ['form' => __('magazine::series')]));
    }

    public function edit(Magazine $magazine): View
    {
        $module_title = __('magazine::edit_series');
        return view('magazine::backend.magazine.edit', compact('module_title', 'magazine'));
    }

    public function update(MagazineRequest $request, Magazine $magazine): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = auth()->id();
        $magazine->update($data);
        return redirect()->route('backend.magazines.index')->with('success', __('messages.update_form', ['form' => __('magazine::series')]));
    }

    public function destroy(Magazine $magazine)
    {
        $magazine->delete();
        return response()->json(['message' => __('messages.delete_form', ['form' => __('magazine::series')]), 'status' => true], 200);
    }
}
