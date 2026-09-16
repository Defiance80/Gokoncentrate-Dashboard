@extends('publisherstudio::layouts.studio')
@section('title', $mode==='create' ? 'New Publication' : 'Edit Publication')
@php
  $active = $mode==='create' ? 'create' : 'submissions';
  $editable = $mode==='create' ? true : $submission->isEditableByPublisher();
  $items = old('item_title') ? array_map(null, old('item_title',[]), old('item_url',[]), old('item_kind',[]))
                             : ($submission->payload['items'] ?? []);
  $formAction = $mode==='create' ? route('studio.submissions.store') : route('studio.submissions.update', $submission);
  $curType = old('type', $submission->type ?: 'veemag');
  $typeHints = [
    'veemag' => 'Add each video segment of the issue — interviews, features, editor\'s note. Order matters.',
    'podcast' => 'Add each episode with its audio/video link.',
    'short_film' => 'Add your film (and any trailer or extras) with its video link.',
  ];
@endphp
@section('content')
<div class="page-head">
  <div>
    <h1>{{ $mode==='create' ? 'New publication' : $submission->title }}</h1>
    <p>{{ $editable ? 'Fill in what you have — you can save a draft and finish later.' : 'This publication is locked while it is being reviewed or after publishing.' }}</p>
  </div>
  <a href="{{ route('studio.submissions.index') }}" class="gk-btn ghost">← Back</a>
</div>

@if($errors->any())<div class="gk-alert err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@if(!$editable && $submission->review_notes)<div class="gk-alert" style="background:rgba(66,165,245,.1);border-color:rgba(66,165,245,.35)"><b>Reviewer note:</b> {{ $submission->review_notes }}</div>@endif

<form method="post" action="{{ $formAction }}" id="pubForm">
  @csrf
  @if($mode!=='create')@method('PUT')@endif
  <fieldset @if(!$editable) disabled @endif style="border:0;padding:0;margin:0">

  <div class="gk-card" style="margin-bottom:1.2rem">
    <div class="gk-field">
      <label class="gk-label">Publication type</label>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap" id="typeChips">
        @foreach(\Modules\PublisherStudio\Models\PublisherSubmission::TYPES as $key=>$label)
          <label class="gk-chip {{ $curType===$key?'active':'' }}">
            <input type="radio" name="type" value="{{ $key }}" {{ $curType===$key?'checked':'' }} style="accent-color:var(--brand)"> {{ $label }}
          </label>
        @endforeach
      </div>
      <p class="gk-muted" id="typeHint" style="font-size:.85rem;margin:.6rem 0 0">{{ $typeHints[$curType] ?? '' }}</p>
    </div>

    <div class="gk-field">
      <label class="gk-label" for="title">Title</label>
      <input class="gk-input" id="title" name="title" value="{{ old('title', $submission->title) }}" required placeholder="e.g. The Independent Era — Vol. 1, Issue 3">
    </div>
    <div class="gk-grid cols-2">
      <div class="gk-field">
        <label class="gk-label" for="category">Category <span class="gk-muted">(optional)</span></label>
        <input class="gk-input" id="category" name="category" value="{{ old('category', $submission->category) }}" placeholder="Music, Culture, Sports…">
      </div>
      <div class="gk-field">
        <label class="gk-label" for="cover_image_url">Cover image URL <span class="gk-muted">(optional)</span></label>
        <input class="gk-input" id="cover_image_url" name="cover_image_url" value="{{ old('cover_image_url', $submission->cover_image_url) }}" placeholder="https://…/cover.jpg">
      </div>
    </div>
    <div class="gk-field">
      <label class="gk-label" for="synopsis">Synopsis / description</label>
      <textarea class="gk-text" id="synopsis" name="synopsis" rows="4" placeholder="What is this publication about?">{{ old('synopsis', $submission->synopsis) }}</textarea>
    </div>
  </div>

  <div class="gk-card" style="margin-bottom:1.2rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem">
      <h2 style="margin:0;font-size:1.1rem">Segments</h2>
      <button type="button" class="gk-btn ghost sm" id="addItem">+ Add segment</button>
    </div>
    <div id="items">
      @forelse($items as $it)
        @php $t=is_array($it)?($it['title']??$it[0]??''):''; $u=is_array($it)?($it['media_url']??$it[1]??''):''; $k=is_array($it)?($it['kind']??$it[2]??''):''; @endphp
        <div class="gk-item-row">
          <input class="gk-input" name="item_title[]" value="{{ $t }}" placeholder="Segment title">
          <input class="gk-input" name="item_url[]" value="{{ $u }}" placeholder="YouTube / Vimeo / media URL">
          <input class="gk-input" name="item_kind[]" value="{{ $k }}" placeholder="Type (interview…)">
          <button type="button" class="gk-btn ghost sm rm">✕</button>
        </div>
      @empty
      @endforelse
    </div>
    <p class="gk-muted" style="font-size:.82rem;margin:.4rem 0 0">Paste a YouTube or Vimeo link and we detect the source automatically on publish.</p>
  </div>

  <div class="gk-field">
    <label class="gk-label" for="notes">Notes to reviewer <span class="gk-muted">(optional)</span></label>
    <textarea class="gk-text" id="notes" name="notes" rows="2">{{ old('notes', $submission->payload['notes'] ?? '') }}</textarea>
  </div>

  @if($editable)
  <div style="display:flex;gap:.7rem;flex-wrap:wrap;margin-top:.4rem">
    <button class="gk-btn ghost" type="submit" name="action" value="draft">Save draft</button>
    <button class="gk-btn" type="submit" name="action" value="submit">Submit for review</button>
  </div>
  @endif
  </fieldset>
</form>

<template id="rowTpl">
  <div class="gk-item-row">
    <input class="gk-input" name="item_title[]" placeholder="Segment title">
    <input class="gk-input" name="item_url[]" placeholder="YouTube / Vimeo / media URL">
    <input class="gk-input" name="item_kind[]" placeholder="Type (interview…)">
    <button type="button" class="gk-btn ghost sm rm">✕</button>
  </div>
</template>

<script>
(function(){
  var items=document.getElementById('items');
  var tpl=document.getElementById('rowTpl');
  function addRow(){ items.appendChild(tpl.content.cloneNode(true)); }
  document.getElementById('addItem').addEventListener('click', addRow);
  items.addEventListener('click', function(e){ if(e.target.classList.contains('rm')){ e.target.closest('.gk-item-row').remove(); }});
  if(!items.querySelector('.gk-item-row')){ addRow(); }
  var hints=@json($typeHints);
  document.getElementById('typeChips').addEventListener('change', function(e){
    if(e.target.name==='type'){
      document.querySelectorAll('#typeChips .gk-chip').forEach(function(c){c.classList.remove('active')});
      e.target.closest('.gk-chip').classList.add('active');
      document.getElementById('typeHint').textContent = hints[e.target.value] || '';
    }
  });
})();
</script>
@endsection
