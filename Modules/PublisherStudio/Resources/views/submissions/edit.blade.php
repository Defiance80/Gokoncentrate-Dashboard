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
    'veemag' => "Add each video segment of the issue — interviews, features, editor's note. Order matters.",
    'podcast' => 'Add each episode with its audio/video link.',
    'short_film' => 'Add your film (and any trailer or extras) with its video link.',
  ];
@endphp
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <h3 class="mb-1">{{ $mode==='create' ? 'New publication' : $submission->title }}</h3>
        <p class="text-secondary mb-0">{{ $editable ? 'Fill in what you have — save a draft and finish later.' : 'This publication is locked while it is reviewed or after publishing.' }}</p>
    </div>
    <a href="{{ route('studio.submissions.index') }}" class="btn btn-outline-secondary">&larr; Back</a>
</div>

@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif
@if(!$editable && $submission->review_notes)
    <div class="alert alert-info"><strong>Reviewer note:</strong> {{ $submission->review_notes }}</div>
@endif

<form method="post" action="{{ $formAction }}" id="pubForm">
  @csrf
  @if($mode!=='create')@method('PUT')@endif
  <fieldset @if(!$editable) disabled @endif class="border-0 p-0 m-0">

  <div class="card mb-3">
    <div class="card-body">
      <label class="form-label">Publication type</label>
      <div class="d-flex flex-wrap gap-2 mb-2" id="typeChips">
        @foreach(\Modules\PublisherStudio\Models\PublisherSubmission::TYPES as $key=>$label)
          <input type="radio" class="btn-check" name="type" id="type_{{ $key }}" value="{{ $key }}" {{ $curType===$key?'checked':'' }} autocomplete="off">
          <label class="btn btn-outline-primary" for="type_{{ $key }}">{{ $label }}</label>
        @endforeach
      </div>
      <p class="text-secondary small mb-4" id="typeHint">{{ $typeHints[$curType] ?? '' }}</p>

      <div class="mb-3">
        <label class="form-label" for="title">Title</label>
        <input class="form-control" id="title" name="title" value="{{ old('title', $submission->title) }}" required placeholder="e.g. The Independent Era — Vol. 1, Issue 3">
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label" for="category">Category <span class="text-secondary">(optional)</span></label>
          <input class="form-control" id="category" name="category" value="{{ old('category', $submission->category) }}" placeholder="Music, Culture, Sports…">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label" for="cover_image_url">Cover image URL <span class="text-secondary">(optional)</span></label>
          <input class="form-control" id="cover_image_url" name="cover_image_url" value="{{ old('cover_image_url', $submission->cover_image_url) }}" placeholder="https://…/cover.jpg">
        </div>
      </div>
      <div class="mb-0">
        <label class="form-label" for="synopsis">Synopsis / description</label>
        <textarea class="form-control" id="synopsis" name="synopsis" rows="4" placeholder="What is this publication about?">{{ old('synopsis', $submission->synopsis) }}</textarea>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Segments</h5>
        <button type="button" class="btn btn-outline-primary btn-sm" id="addItem"><i class="ph ph-plus me-1"></i>Add segment</button>
      </div>
      <div id="items" class="vstack gap-2">
        @foreach($items as $it)
          @php $t=is_array($it)?($it['title']??$it[0]??''):''; $u=is_array($it)?($it['media_url']??$it[1]??''):''; $k=is_array($it)?($it['kind']??$it[2]??''):''; @endphp
          <div class="row g-2 align-items-center item-row">
            <div class="col-md-3"><input class="form-control" name="item_title[]" value="{{ $t }}" placeholder="Segment title"></div>
            <div class="col-md-5"><input class="form-control" name="item_url[]" value="{{ $u }}" placeholder="YouTube / Vimeo / media URL"></div>
            <div class="col-md-3"><input class="form-control" name="item_kind[]" value="{{ $k }}" placeholder="Type (interview…)"></div>
            <div class="col-md-1 text-end"><button type="button" class="btn btn-outline-danger btn-sm rm"><i class="ph ph-x"></i></button></div>
          </div>
        @endforeach
      </div>
      <p class="text-secondary small mb-0 mt-2">Paste a YouTube or Vimeo link and we detect the source automatically on publish.</p>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label" for="notes">Notes to reviewer <span class="text-secondary">(optional)</span></label>
    <textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $submission->payload['notes'] ?? '') }}</textarea>
  </div>

  @if($editable)
  <div class="d-flex flex-wrap gap-2">
    <button class="btn btn-outline-secondary" type="submit" name="action" value="draft">Save draft</button>
    <button class="btn btn-primary" type="submit" name="action" value="submit">Submit for review</button>
  </div>
  @endif
  </fieldset>
</form>

<template id="rowTpl">
  <div class="row g-2 align-items-center item-row">
    <div class="col-md-3"><input class="form-control" name="item_title[]" placeholder="Segment title"></div>
    <div class="col-md-5"><input class="form-control" name="item_url[]" placeholder="YouTube / Vimeo / media URL"></div>
    <div class="col-md-3"><input class="form-control" name="item_kind[]" placeholder="Type (interview…)"></div>
    <div class="col-md-1 text-end"><button type="button" class="btn btn-outline-danger btn-sm rm"><i class="ph ph-x"></i></button></div>
  </div>
</template>

@push('after-scripts')
<script>
(function(){
  var items=document.getElementById('items'), tpl=document.getElementById('rowTpl');
  function addRow(){ items.appendChild(tpl.content.cloneNode(true)); }
  document.getElementById('addItem').addEventListener('click', addRow);
  items.addEventListener('click', function(e){ var b=e.target.closest('.rm'); if(b){ b.closest('.item-row').remove(); }});
  if(!items.querySelector('.item-row')){ addRow(); }
  var hints=@json($typeHints);
  document.getElementById('typeChips').addEventListener('change', function(e){
    if(e.target.name==='type'){ document.getElementById('typeHint').textContent = hints[e.target.value] || ''; }
  });
})();
</script>
@endpush
@endsection
