@extends('publisherstudio::layouts.studio')
@section('title','My Publications')
@php $active='submissions'; @endphp
@section('content')
<div class="page-head">
  <div><h1>My Publications</h1><p>Drafts, submissions in review, and published work.</p></div>
  <a href="{{ route('studio.submissions.create') }}" class="gk-btn">+ New publication</a>
</div>

<div class="gk-card">
  @if($submissions->isEmpty())
    <p class="gk-muted" style="margin:.4rem 0">Nothing here yet. <a href="{{ route('studio.submissions.create') }}">Create your first publication →</a></p>
  @else
    <table class="gk-table">
      <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Updated</th><th></th></tr></thead>
      <tbody>
      @foreach($submissions as $s)
        <tr>
          <td><b>{{ $s->title }}</b>@if($s->review_notes && in_array($s->status,['changes_requested','rejected']))<div class="gk-muted" style="font-size:.8rem;margin-top:.2rem">Note: {{ \Illuminate\Support\Str::limit($s->review_notes,90) }}</div>@endif</td>
          <td>{{ $s->type_label }}</td>
          <td><span class="gk-badge b-{{ $s->status_color }}">{{ $s->status_label }}</span></td>
          <td class="gk-muted">{{ $s->updated_at?->diffForHumans() }}</td>
          <td style="text-align:right;white-space:nowrap">
            <a class="gk-btn ghost sm" href="{{ route('studio.submissions.edit', $s) }}">{{ $s->isEditableByPublisher() ? 'Edit' : 'View' }}</a>
            @if($s->isEditableByPublisher())
              <form method="post" action="{{ route('studio.submissions.destroy', $s) }}" style="display:inline" onsubmit="return confirm('Delete this publication?')">
                @csrf @method('DELETE')
                <button class="gk-btn ghost sm" type="submit">Delete</button>
              </form>
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
    <div style="margin-top:1rem">{{ $submissions->links() }}</div>
  @endif
</div>
@endsection
