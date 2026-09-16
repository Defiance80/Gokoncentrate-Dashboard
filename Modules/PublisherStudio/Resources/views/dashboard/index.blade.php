@extends('publisherstudio::layouts.studio')
@section('title','Dashboard')
@php $active='dashboard'; @endphp
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <h3 class="mb-1">Welcome, {{ explode(' ', trim($publisher->name))[0] }}</h3>
        <p class="text-secondary mb-0">Your publishing home for VeeMags, podcasts and short films.</p>
    </div>
    <a href="{{ route('studio.submissions.create') }}" class="btn btn-primary"><i class="ph ph-plus-circle me-1"></i> New publication</a>
</div>

@if($publisher->status !== 'approved')
    <div class="alert alert-warning d-flex align-items-center gap-2">
        <i class="ph ph-clock fs-5"></i>
        <div>Your account is <strong>under review</strong>. You can build and submit publications now — they publish once we approve your first piece.</div>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card stat-tile h-100"><div class="card-body"><div class="display-6">{{ $counts['total'] }}</div><div class="text-secondary text-uppercase small">Total</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-tile h-100"><div class="card-body"><div class="display-6">{{ $counts['draft'] }}</div><div class="text-secondary text-uppercase small">Drafts</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-tile h-100"><div class="card-body"><div class="display-6">{{ $counts['submitted'] }}</div><div class="text-secondary text-uppercase small">In review</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-tile h-100"><div class="card-body"><div class="display-6">{{ $counts['approved'] }}</div><div class="text-secondary text-uppercase small">Published</div></div></div></div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Recent publications</h5>
            <a href="{{ route('studio.submissions.index') }}" class="btn btn-link p-0">View all &rarr;</a>
        </div>
        @if($recent->isEmpty())
            <p class="text-secondary mb-0">You haven't created anything yet. <a href="{{ route('studio.submissions.create') }}">Start your first publication &rarr;</a></p>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Title</th><th>Type</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @foreach($recent as $s)
                        <tr>
                            <td class="fw-semibold">{{ $s->title }}</td>
                            <td>{{ $s->type_label }}</td>
                            <td><span class="badge text-bg-{{ $s->status_color }}">{{ $s->status_label }}</span></td>
                            <td class="text-end">
                                <a class="btn btn-outline-secondary btn-sm" href="{{ route('studio.submissions.edit', $s) }}">{{ $s->isEditableByPublisher() ? 'Edit' : 'View' }}</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
