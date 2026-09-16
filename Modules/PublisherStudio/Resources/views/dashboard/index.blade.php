@extends('publisherstudio::layouts.studio')
@section('title','Dashboard')
@php $active='dashboard'; @endphp
@section('content')
<div class="page-head">
  <div>
    <h1>Welcome, {{ explode(' ', trim($publisher->name))[0] }}</h1>
    <p>Your publishing home for VeeMags, podcasts and short films.</p>
  </div>
  <a href="{{ route('studio.submissions.create') }}" class="gk-btn">+ New publication</a>
</div>

@if($publisher->status !== 'approved')
  <div class="gk-alert" style="background:rgba(245,166,35,.1);border-color:rgba(245,166,35,.35)">
    Your account is <b>under review</b>. You can build and submit publications now — they publish once our team approves your first piece.
  </div>
@endif

<div class="gk-grid cols-4" style="margin-bottom:1.6rem">
  <div class="gk-stat"><b>{{ $counts['total'] }}</b><span>Total</span></div>
  <div class="gk-stat"><b>{{ $counts['draft'] }}</b><span>Drafts</span></div>
  <div class="gk-stat"><b>{{ $counts['submitted'] }}</b><span>In review</span></div>
  <div class="gk-stat"><b>{{ $counts['approved'] }}</b><span>Published</span></div>
</div>

<div class="gk-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
    <h2 style="margin:0;font-size:1.1rem">Recent publications</h2>
    <a href="{{ route('studio.submissions.index') }}" class="gk-muted" style="font-size:.9rem">View all →</a>
  </div>
  @if($recent->isEmpty())
    <p class="gk-muted" style="margin:.4rem 0">You haven't created anything yet. <a href="{{ route('studio.submissions.create') }}">Start your first publication →</a></p>
  @else
    <table class="gk-table">
      <thead><tr><th>Title</th><th>Type</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @foreach($recent as $s)
        <tr>
          <td><b>{{ $s->title }}</b></td>
          <td>{{ $s->type_label }}</td>
          <td><span class="gk-badge b-{{ $s->status_color }}">{{ $s->status_label }}</span></td>
          <td style="text-align:right">
            @if($s->isEditableByPublisher())
              <a class="gk-btn ghost sm" href="{{ route('studio.submissions.edit', $s) }}">Edit</a>
            @else
              <a class="gk-btn ghost sm" href="{{ route('studio.submissions.edit', $s) }}">View</a>
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection
