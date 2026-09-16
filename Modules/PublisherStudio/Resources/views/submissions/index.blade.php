@extends('publisherstudio::layouts.studio')
@section('title','My Publications')
@php $active='submissions'; @endphp
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <h3 class="mb-1">My Publications</h3>
        <p class="text-secondary mb-0">Drafts, submissions in review, and published work.</p>
    </div>
    <a href="{{ route('studio.submissions.create') }}" class="btn btn-primary"><i class="ph ph-plus-circle me-1"></i> New publication</a>
</div>

<div class="card">
    <div class="card-body">
        @if($submissions->isEmpty())
            <p class="text-secondary mb-0">Nothing here yet. <a href="{{ route('studio.submissions.create') }}">Create your first publication &rarr;</a></p>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                    <tbody>
                    @foreach($submissions as $s)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $s->title }}</div>
                                @if($s->review_notes && in_array($s->status,['changes_requested','rejected']))
                                    <div class="text-secondary small mt-1">Note: {{ \Illuminate\Support\Str::limit($s->review_notes,90) }}</div>
                                @endif
                            </td>
                            <td>{{ $s->type_label }}</td>
                            <td><span class="badge text-bg-{{ $s->status_color }}">{{ $s->status_label }}</span></td>
                            <td class="text-secondary">{{ $s->updated_at?->diffForHumans() }}</td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('studio.submissions.edit', $s) }}">{{ $s->isEditableByPublisher() ? 'Edit' : 'View' }}</a>
                                @if($s->isEditableByPublisher())
                                    <form method="post" action="{{ route('studio.submissions.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Delete this publication?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $submissions->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@endsection
