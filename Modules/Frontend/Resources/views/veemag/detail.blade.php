@extends('frontend::layouts.master')

@section('title'){{ $issue->title }} — VeeMag @endsection

@push('after-styles')
<style>
  .vm-hero{position:relative;min-height:78vh;display:flex;align-items:flex-end;padding:4rem 0;background-size:cover;background-position:center;}
  .vm-hero::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.35) 0%,rgba(0,0,0,.5) 45%,#0c0b11 100%),linear-gradient(90deg,rgba(0,0,0,.75) 0%,transparent 60%);z-index:0;}
  .vm-hero__inner{position:relative;z-index:1;max-width:52rem;}
  .vm-eyebrow{font-family:ui-monospace,monospace;letter-spacing:.22em;text-transform:uppercase;font-size:.8rem;color:var(--bs-primary);margin-bottom:1rem;}
  .vm-pub{font-weight:600;letter-spacing:.14em;text-transform:uppercase;font-size:.95rem;color:#cfcfd6;margin-bottom:.35rem;}
  .vm-issueline{font-family:ui-monospace,monospace;letter-spacing:.16em;text-transform:uppercase;font-size:.8rem;color:#9a95a3;margin-bottom:1.2rem;}
  .vm-title{font-weight:800;letter-spacing:-.02em;line-height:1.02;font-size:clamp(2.4rem,6vw,4.6rem);text-transform:uppercase;margin:0 0 1rem;}
  .vm-sub{font-size:clamp(1.05rem,1.6vw,1.35rem);color:#c9c6cf;max-width:40ch;margin-bottom:2rem;}
  .vm-actions{display:flex;flex-wrap:wrap;gap:.8rem;margin-bottom:1.4rem;}
  .vm-meta{font-family:ui-monospace,monospace;font-size:.8rem;letter-spacing:.08em;color:#9a95a3;}
  .vm-contents{padding:3.5rem 0;}
  .vm-contents__head{font-family:ui-monospace,monospace;letter-spacing:.2em;text-transform:uppercase;font-size:.8rem;color:var(--bs-primary);margin-bottom:1.6rem;}
  .vm-row{display:grid;grid-template-columns:3rem 12rem 1fr auto;gap:1.2rem;align-items:center;padding:1.1rem 0;border-top:1px solid rgba(255,255,255,.08);text-decoration:none;color:inherit;transition:background .15s;}
  .vm-row:last-child{border-bottom:1px solid rgba(255,255,255,.08);}
  .vm-row:hover{background:rgba(255,255,255,.03);}
  .vm-row__num{font-family:ui-monospace,monospace;color:var(--bs-primary);font-size:.85rem;}
  .vm-row__thumb{width:12rem;aspect-ratio:16/9;object-fit:cover;border-radius:.4rem;background:#1a1822;}
  .vm-row__type{font-family:ui-monospace,monospace;font-size:.7rem;letter-spacing:.14em;text-transform:uppercase;color:#9a95a3;margin-bottom:.2rem;}
  .vm-row__title{font-weight:600;font-size:1.05rem;}
  .vm-row__time{font-family:ui-monospace,monospace;font-size:.85rem;color:#9a95a3;white-space:nowrap;}
  .vm-print__price{font-family:ui-monospace,monospace;font-size:.85rem;opacity:.75;margin-left:.5rem;}
  .vm-print__note{font-family:ui-monospace,monospace;font-size:.75rem;letter-spacing:.08em;color:#79747f;margin-bottom:1rem;}
  @media(max-width:640px){.vm-row{grid-template-columns:2.2rem 1fr auto;}.vm-row__thumb{display:none;}}
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Str;
  $fmt = function($s){ $s=(int)$s; $m=intdiv($s,60); $sec=$s%60; return $m.':'.str_pad($sec,2,'0',STR_PAD_LEFT); };
  $totalMin = $issue->runtime_seconds ? ceil($issue->runtime_seconds/60) : null;
  $printPrice = $issue->print_price_effective;
  $printCurrency = strtoupper((string) GetcurrentCurrency() ?: 'USD');
  $printSymbol = $printCurrency === 'USD' ? '$' : $printCurrency . ' ';
@endphp

<section class="vm-hero" style="background-image:url('{{ $issue->hero_url ?: $issue->cover_url }}')">
  <div class="container-fluid">
    <div class="vm-hero__inner">
      <div class="vm-eyebrow">VeeMag</div>
      <div class="vm-pub">{{ $issue->publication->title ?? '' }}</div>
      <div class="vm-issueline">{{ $issue->issue_label }}</div>
      <h1 class="vm-title">{{ $issue->title }}</h1>
      @if($issue->subtitle)<p class="vm-sub">{{ $issue->subtitle }}</p>@endif
      <div class="vm-actions">
        <a href="{{ route('veemag.watch', $issue->slug) }}" class="btn btn-primary btn-lg px-4">
          <i class="ph ph-play-fill me-2"></i>Play Issue
        </a>
        <a href="#vm-contents" class="btn btn-outline-light btn-lg px-4">Contents</a>
        @if($issue->print_enabled)
          {{-- Print companion: opens a Stripe checkout for this issue and
               mails the reader the same link. Price excludes shipping. --}}
          <form method="POST" action="{{ route('veemag.print.checkout', $issue->slug) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-light btn-lg px-4 vm-print">
              <i class="ph ph-printer me-2"></i>{{ __('frontend.print_issue') }}
              <span class="vm-print__price">{{ $printSymbol }}{{ number_format($printPrice, 2) }}</span>
            </button>
          </form>
        @endif
      </div>
      @if($issue->print_enabled)
        <div class="vm-print__note">{{ __('frontend.print_shipping_note') }}</div>
      @endif
      <div class="vm-meta">
        @if($totalMin){{ $totalMin }} min • @endif VeeMag • {{ optional($issue->release_date)->format('F Y') }}
      </div>
    </div>
  </div>
</section>

<section class="vm-contents" id="vm-contents">
  <div class="container-fluid">
    <div class="vm-contents__head">Contents</div>
    @foreach($sections as $i => $s)
      <a class="vm-row" href="{{ route('veemag.watch', ['slug' => $issue->slug]) }}?section={{ $s->id }}">
        <div class="vm-row__num">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</div>
        <img class="vm-row__thumb" src="{{ $s->thumbnail_url ?: $issue->cover_url }}" alt="" loading="lazy">
        <div>
          <div class="vm-row__type">{{ $s->type_label }}</div>
          <div class="vm-row__title">{{ $s->title }}</div>
        </div>
        <div class="vm-row__time">{{ $fmt($s->runtime_seconds) }}</div>
      </a>
    @endforeach
  </div>
</section>
@endsection
