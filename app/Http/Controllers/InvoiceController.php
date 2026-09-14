<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    /** Riwayat invoice milik pengguna. Hanya pembayaran lunas yang punya invoice. */
    public function index(Request $request): \Illuminate\View\View
    {
        return view('subscription.invoices', [
            'invoices' => $request->user()->payments()
                ->with('plan')
                ->whereNotNull('invoice_number')
                ->latest('paid_at')
                ->paginate(15),
        ]);
    }

    /** Unduh invoice PDF. Admin boleh mengunduh milik siapa pun, pengguna hanya miliknya. */
    public function download(Request $request, Payment $payment): Response
    {
        abort_if($payment->invoice_number === null, 404, 'Pembayaran ini belum memiliki invoice.');

        $user = $request->user();
        abort_unless($user->isAdmin() || $payment->user_id === $user->id, 403);

        $html = view('export.invoice', [
            'payment' => $payment->load('user', 'plan'),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = str_replace('/', '-', $payment->invoice_number).'.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
