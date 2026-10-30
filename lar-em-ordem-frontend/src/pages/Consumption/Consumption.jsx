import React, { useState } from 'react';
import DetailedConsumptionChart from '../../components/ui/DetailedConsumptionChart';
import InvoiceForm from '../../components/ui/InvoiceForm';

export default function Consumption() {
  const [invoiceRefreshKey, setInvoiceRefreshKey] = useState(0);
  const [propertyId] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem('activeHouse'))?.id || '';
    } catch {
      return '';
    }
  });

  return (
    <div className="max-w-7xl mx-auto space-y-8 pb-12">
      <div>
        <h1 className="text-3xl font-bold text-slate-900">
          Consumos Inteligentes
        </h1>
        <p className="text-slate-500 text-sm mt-1">
          Decomposição das faturas · Jul 2025 – Jul 2026
        </p>
      </div>

      <div className="py-4">
        <DetailedConsumptionChart
          propertyId={propertyId}
          refreshKey={invoiceRefreshKey}
        />
      </div>

      <InvoiceForm
        onInvoiceSaved={() =>
          setInvoiceRefreshKey(value => value + 1)
        }
      />
    </div>
  );
}
