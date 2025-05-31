<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * Generate an invoice for an order
     *
     * @param int $orderId
     * @param bool $isMerchant
     * @return string URL to the generated invoice
     */
    public function generateInvoice($orderId, $isMerchant = false)
    {
        // Get the order with its items and related data
        $orderQuery = Order::with(['items.product', 'user']);
        
        // If it's a merchant, we need to filter items to only show their products
        if ($isMerchant) {
            $orderQuery->with(['items' => function($query) {
                $query->whereHas('product', function($query) {
                    $query->where('user_id', auth()->id());
                });
                $query->with('product');
            }]);
        }
        
        $order = $orderQuery->findOrFail($orderId);
        
        // Generate a unique filename for the invoice
        $filename = 'invoice_' . $order->id . '_' . Str::random(8) . '.pdf';
        $path = 'invoices/' . $filename;
        
        // Generate the PDF content
        $pdf = $this->generatePdf($order, $isMerchant);
        
        // Store the PDF
        Storage::put('public/' . $path, $pdf->output());
        
        // Return the URL to the invoice
        return Storage::url($path);
    }
    
    /**
     * Generate the PDF content
     *
     * @param Order $order
     * @param bool $isMerchant
     * @return \Barryvdh\DomPDF\PDF
     */
    protected function generatePdf(Order $order, $isMerchant)
    {
        // Create PDF using Laravel's PDF package (requires barryvdh/laravel-dompdf)
        $pdf = app()->make('dompdf.wrapper');
        
        // Generate the HTML content for the invoice
        $html = view('invoices.order', [
            'order' => $order,
            'isMerchant' => $isMerchant,
            'date' => now()->format('d/m/Y'),
            'invoiceNumber' => 'INV-' . str_pad($order->id, 6, '0', STR_PAD_LEFT),
        ])->render();
        
        $pdf->loadHTML($html);
        
        return $pdf;
    }
}
