<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Invoice;

class InvoiceController extends Controller
{
    //Store temporario para teste de scipt PDFplumber
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $validated = $request->validate([
            'property_id'             => 'required|exists:properties,id',
            'invoice_number'          => 'required|string',
            'issue_date'              => 'required|date',
            'period_start'            => 'required|date',
            'period_end'              => 'required|date',
            'total_amount'            => 'required|numeric',
            'supplier'                => 'required|string',
            'file_path'               => 'nullable|string',
            
            'consumptions'                  => 'nullable|array',
            'consumptions.*.consumption_type_id' => 'required|exists:consumption_types,id',
            'consumptions.*.amount'         => 'required|numeric',
            'consumptions.*.cost'           => 'nullable|numeric',
        ]);

        $consumptionsData = $validated['consumptions'] ?? [];
        unset($validated['consumptions']);
        $validated['user_id'] = $user->id;

        $invoice = DB::transaction(function () use ($validated, $consumptionsData) {
            $invoice = Invoice::create($validated);

            if (!empty($consumptionsData)) {
                $formattedConsumptions = array_map(function ($item) use ($invoice) {
                    return [
                        'property_id'         => $invoice->property_id,
                        'consumption_type_id' => $item['consumption_type_id'],
                        'period_start'        => $invoice->period_start,
                        'period_end'          => $invoice->period_end,
                        'amount'              => $item['amount'],
                        'cost'                => $item['cost'] ?? null,
                    ];
                }, $consumptionsData);

                $invoice->consumptions()->createMany($formattedConsumptions);
            }

            return $invoice;
        });

        return response()->json([
            'message' => 'Fatura e consumos registados com sucesso!',
            'invoice' => $invoice->load('consumptions')
        ], 201);
    }
}
