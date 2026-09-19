@extends('frontend::layouts.master')

@section('title'){{ __('frontend.print_order_title') }} @endsection

@section('content')
@php
    $paid = $order && $order->status === 'paid';
    $symbol = $order && $order->currency === 'USD' ? '$' : (($order->currency ?? '') . ' ');
@endphp

<section class="container-fluid" style="padding:6rem 0;min-height:70vh;">
    <div style="max-width:40rem;">
        <div style="font-family:ui-monospace,monospace;letter-spacing:.22em;text-transform:uppercase;
                    font-size:.8rem;color:var(--bs-primary);margin-bottom:1rem;">
            VeeMag &middot; {{ __('frontend.print_edition') }}
        </div>

        <h1 class="mb-3" style="font-weight:800;letter-spacing:-.02em;">
            {{ $paid ? __('frontend.print_thanks') : __('frontend.print_pending_title') }}
        </h1>

        @if($paid)
            <p class="mb-4" style="color:#c9c6cf;font-size:1.05rem;line-height:1.6;">
                {{ __('frontend.print_thanks_body') }}
            </p>
            @if($issue)
                <div class="mb-4" style="border-top:1px solid rgba(255,255,255,.08);
                                          border-bottom:1px solid rgba(255,255,255,.08);padding:1.2rem 0;">
                    <div style="font-family:ui-monospace,monospace;font-size:.75rem;letter-spacing:.14em;
                                text-transform:uppercase;color:#9a95a3;margin-bottom:.35rem;">
                        {{ $issue->publication->title ?? '' }} &middot; {{ $issue->issue_label }}
                    </div>
                    <div style="font-weight:600;font-size:1.15rem;">{{ $issue->title }}</div>
                    <div style="font-family:ui-monospace,monospace;font-size:.85rem;color:#9a95a3;margin-top:.5rem;">
                        {{ __('frontend.print_total') }}:
                        {{ $symbol }}{{ number_format((float) $order->amount, 2) }}
                        &middot; {{ __('frontend.print_order_ref') }} #{{ $order->id }}
                    </div>
                </div>
            @endif
        @else
            <p class="mb-4" style="color:#c9c6cf;font-size:1.05rem;line-height:1.6;">
                {{ __('frontend.print_pending_body') }}
            </p>
        @endif

        <a href="{{ $issue ? route('veemag.detail', $issue->slug) : url('/') }}"
            class="btn btn-outline-light px-4">
            {{ $issue ? __('frontend.print_back_to_issue') : __('frontend.back_to_home') }}
        </a>
    </div>
</section>
@endsection
