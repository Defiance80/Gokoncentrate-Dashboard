<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Modules\Subscriptions\Models\Subscription;
use Modules\Frontend\Models\PayPerView;

class InvoiceController extends Controller
{
    public function download(Request $request)
    {
        $data = Subscription::with('plan', 'subscription_transaction', 'user')
        ->find($request->id);

        if (!$data) {
            return response()->json([
                'status' => false,
                'message' => 'Subscription not found'
            ], 404);
        }

        $pdf = PDF::loadView('frontend::components.partials.invoice', compact('data'))
            ->setOptions([
                'defaultFont' => 'Noto Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $fileName = "invoices/invoice_{$data->id}.pdf";
        \Storage::disk('public')->put($fileName, $pdf->output());

        $invoiceUrl = \Storage::disk('public')->url($fileName);

        return response()->json([
            'status' => true,
            'invoice_url' => $invoiceUrl,
        ]);
    }

    public function downloadPayPerViewInvoice(Request $request)
    {
        $ppv = PayPerView::where('user_id', auth()->id())
            ->with(['movie', 'episode', 'video', 'user', 'PayperviewTransaction'])
            ->find($request->id);

        if (!$ppv) {
            return response()->json([
                'status' => false,
                'message' => 'Pay-per-view purchase not found',
            ], 404);
        }

        $pdf = PDF::loadView('frontend::components.partials.pay-per-view', ['ppv' => $ppv])
            ->setOptions([
                'defaultFont' => 'Noto Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);

        $fileName = "invoices/ppv_invoice_{$ppv->id}.pdf";
        Storage::disk('public')->put($fileName, $pdf->output());

        $invoiceUrl = Storage::disk('public')->url($fileName);

        return response()->json([
            'status' => true,
            'invoice_url' => $invoiceUrl,
        ]);
    }
}
