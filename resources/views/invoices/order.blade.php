<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Facture #{{ $invoiceNumber }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #333;
        }
        .company-details, .customer-details {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f8f8;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="invoice-header">
            <div>
                <h1 class="invoice-title">FACTURE</h1>
                <div class="company-details">
                    <strong>Marketplace</strong><br>
                    123 Rue du Commerce<br>
                    75000 Paris<br>
                    Email: contact@marketplace.com<br>
                    Tél: +33 1 23 45 67 89
                </div>
            </div>
            <div>
                <p><strong>Facture #:</strong> {{ $invoiceNumber }}</p>
                <p><strong>Date:</strong> {{ $date }}</p>
                <p><strong>Statut:</strong> {{ ucfirst($order->status) }}</p>
            </div>
        </div>

        <div class="customer-details">
            <h3>Facturé à:</h3>
            <p>
                <strong>{{ $order->user->name }}</strong><br>
                {{ $order->user->email }}<br>
                @if($order->user->address)
                    {{ $order->user->address }}<br>
                @endif
                @if($order->user->phone)
                    Tél: {{ $order->user->phone }}
                @endif
            </p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $subtotal = 0; @endphp
                @foreach($order->items as $item)
                    @php 
                        // Skip if merchant and not their product
                        if ($isMerchant && $item->product->user_id !== auth()->id()) continue;
                        $subtotal += $item->total_price;
                    @endphp
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->unit_price, 0, ',', ' ') }} F</td>
                        <td class="text-right">{{ number_format($item->total_price, 0, ',', ' ') }} F</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>Sous-total:</strong></td>
                    <td class="text-right">{{ number_format($subtotal, 0, ',', ' ') }} F</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right"><strong>TVA (0%):</strong></td>
                    <td class="text-right">0 F</td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right">{{ number_format($subtotal, 0, ',', ' ') }} F</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>Merci pour votre confiance!</p>
            @if($isMerchant)
                <p><em>Cette facture concerne uniquement les produits vendus par votre entreprise.</em></p>
            @endif
        </div>
    </div>
</body>
</html>
