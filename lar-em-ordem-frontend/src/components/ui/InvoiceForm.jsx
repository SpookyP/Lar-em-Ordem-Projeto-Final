import React, { useState, useEffect, useRef } from 'react';
import {
  Camera,
  CheckCircle2,
  AlertCircle,
  Plus,
  Trash2
} from 'lucide-react';
import axiosClient from '../../services/api/axiosClient';

// Cria uma linha vazia para os detalhes da fatura.
const emptyItem = () => ({
  name: '',
  category: 'consumption',
  amount: '',
  unit: '',
  cost: ''
});

// Converte valores para números válidos.
const numberValue = (v) =>
  v === '' || v == null
    ? 0
    : (Number.isFinite(Number(v)) ? Number(v) : 0);

export default function InvoiceForm({ onInvoiceSaved }) {
  // IDs definidos pela API: 1 eletricidade, 2 água, 3 gás.
  const [consumptionTypes, setConsumptionTypes] = useState([
    { id: 1, name: 'Eletricidade', unit_of_measure: 'kWh' },
    { id: 2, name: 'Água', unit_of_measure: 'm³' },
    { id: 3, name: 'Gás', unit_of_measure: 'kWh' }
  ]);

  const [selectedTypeIds, setSelectedTypeIds] = useState([]);
  const [consumptionsData, setConsumptionsData] = useState({});
  const [showItems, setShowItems] = useState({});
  const [itemsData, setItemsData] = useState({});
  const [uploading, setUploading] = useState(false);

  // Bloqueios síncronos para evitar operações duplicadas ou simultâneas.
  const uploadLock = useRef(false);
  const submitLock = useRef(false);

  const [fileId, setFileId] = useState(null);
  const [uploadSuccess, setUploadSuccess] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [formMessage, setFormMessage] = useState(null);

  // Inicializa os dados principais da fatura.
  const [formData, setFormData] = useState({
    property_id: (() => {
      try {
        return JSON.parse(localStorage.getItem('activeHouse'))?.id || '';
      } catch {
        return '';
      }
    })(),
    supplier: '',
    invoice_number: '',
    issue_date: '',
    period_start: '',
    period_end: '',
    total_amount: ''
  });

  // Carrega os tipos de consumo disponíveis na API.
  useEffect(() => {
    axiosClient.get('/api/v1/consumption-types')
      .then(res => {
        const data = res.data?.data || res.data;

        if (Array.isArray(data) && data.length) {
          setConsumptionTypes(
            data.map(t => ({
              ...t,
              id: Number(t.id),
              unit_of_measure: Number(t.id) === 3
                ? 'kWh'
                : t.unit_of_measure
            }))
          );
        }
      })
      .catch(err =>
        console.log('A usar tipos predefinidos locais:', err)
      );
  }, []);

  // Carrega o PDF ou imagem e preenche os dados reconhecidos pela API.
  const handleFileUpload = async (e) => {
    const input = e.currentTarget;
    const file = input.files?.[0];

    // Impede uploads duplicados ou durante a gravação.
    if (!file || uploadLock.current || submitLock.current) return;

    uploadLock.current = true;
    setUploading(true);
    setUploadSuccess(false);
    setFormMessage(null);
    setFileId(null);

    try {
      const data = new FormData();
      data.append('file', file);

      // Deixa o Axios/browser definir o Content-Type e o boundary do multipart.
      const res = await axiosClient.post(
        '/api/v1/invoices/upload',
        data
      );

      const result = res.data?.data || res.data;

      if (!result?.file_id) {
        throw new Error('A API não devolveu file_id.');
      }

      setFileId(result.file_id);
      setUploadSuccess(true);

      const extracted = result.extracted || result.invoice_data;

      if (extracted) {
        setFormData(prev => ({
          ...prev,
          supplier: extracted.supplier ?? prev.supplier,
          invoice_number: extracted.invoice_number ?? prev.invoice_number,
          issue_date: extracted.issue_date ?? prev.issue_date,
          period_start: extracted.period_start ?? prev.period_start,
          period_end: extracted.period_end ?? prev.period_end,
          total_amount: extracted.total_amount ?? prev.total_amount
        }));

        if (Array.isArray(extracted.consumptions)) {
          const selected = [];
          const consumptionValues = {};
          const detailsVisible = {};
          const details = {};

          extracted.consumptions.forEach(c => {
            const id = Number(c.consumption_type_id);

            selected.push(id);

            consumptionValues[id] = {
              amount: c.amount ?? '',
              cost: c.cost ?? ''
            };

            const extractedItems = Array.isArray(c.items)
              ? c.items
              : c.consumption_items;

            details[id] = Array.isArray(extractedItems)
              ? extractedItems.map(item => ({
                  name: item.name ?? '',
                  category: item.category ?? 'other',
                  amount: item.amount ?? '',
                  unit: item.unit ?? '',
                  cost: item.cost ?? ''
                }))
              : [];

            // Abre automaticamente os detalhes quando existem itens extraídos.
            detailsVisible[id] = details[id].length > 0;
          });

          setSelectedTypeIds([...new Set(selected)]);
          setConsumptionsData(consumptionValues);
          setShowItems(detailsVisible);
          setItemsData(details);
        }
      } else {
        setFormMessage({
          type: 'success',
          text: 'PDF carregado. Preenche os dados manualmente.'
        });
      }
    } catch (err) {
      console.error('Erro no upload:', err);

      if (err.response?.status === 429) {
        setFormMessage({
          type: 'error',
          text: 'Foram feitos demasiados pedidos. Aguarda um pouco antes de tentares novamente.'
        });
      } else {
        setFormMessage({
          type: 'error',
          text:
            err.response?.data?.message ||
            err.message ||
            'Erro ao carregar o ficheiro.'
        });
      }
    } finally {
      uploadLock.current = false;
      setUploading(false);
      input.value = '';
    }
  };

  // Adiciona ou remove um tipo de consumo da fatura.
  const toggleType = (rawId) => {
    const id = Number(rawId);

    if (selectedTypeIds.includes(id)) {
      setSelectedTypeIds(prev => prev.filter(v => v !== id));

      setConsumptionsData(prev => {
        const next = { ...prev };
        delete next[id];
        return next;
      });

      setShowItems(prev => {
        const next = { ...prev };
        delete next[id];
        return next;
      });

      setItemsData(prev => {
        const next = { ...prev };
        delete next[id];
        return next;
      });
    } else {
      setSelectedTypeIds(prev => [...prev, id]);

      setConsumptionsData(prev => ({
        ...prev,
        [id]: { amount: '', cost: '' }
      }));

      setShowItems(prev => ({
        ...prev,
        [id]: false
      }));

      setItemsData(prev => ({
        ...prev,
        [id]: []
      }));
    }
  };

  // Atualiza a quantidade ou o custo de um consumo.
  const changeConsumption = (id, field, value) =>
    setConsumptionsData(prev => ({
      ...prev,
      [id]: {
        ...(prev[id] || {}),
        [field]: value
      }
    }));

  // Atualiza um campo de uma linha de detalhe.
  const changeItem = (id, index, field, value) =>
    setItemsData(prev => {
      const list = [...(prev[id] || [])];

      list[index] = {
        ...(list[index] || emptyItem()),
        [field]: value
      };

      return {
        ...prev,
        [id]: list
      };
    });

  // Adiciona uma nova linha de detalhe.
  const addItem = id =>
    setItemsData(prev => ({
      ...prev,
      [id]: [...(prev[id] || []), emptyItem()]
    }));

  // Remove uma linha de detalhe.
  const removeItem = (id, index) =>
    setItemsData(prev => ({
      ...prev,
      [id]: (prev[id] || []).filter((_, i) => i !== index)
    }));

  // Valida, prepara e envia a fatura para a API.
  const handleSubmit = async (e) => {
    e.preventDefault();

    // Impede submissões duplicadas ou durante um upload.
    if (submitLock.current || uploadLock.current) return;

    submitLock.current = true;
    setSubmitting(true);
    setFormMessage(null);

    try {
      let propertyId = formData.property_id;

      try {
        propertyId =
          JSON.parse(localStorage.getItem('activeHouse'))?.id ||
          propertyId;
      } catch {}

      const consumptions = selectedTypeIds.map(id => {
        const entry = {
          consumption_type_id: id,
          amount: numberValue(consumptionsData[id]?.amount),
          cost: numberValue(consumptionsData[id]?.cost)
        };

        if (showItems[id]) {
          entry.items = (itemsData[id] || [])
            .filter(item => item.name.trim())
            .map(item => ({
              name: item.name.trim(),
              category: item.category || 'other',
              amount: numberValue(item.amount),
              unit: item.unit.trim(),
              cost: numberValue(item.cost)
            }));
        }

        return entry;
      });

      const payload = {
        property_id: propertyId,
        invoice_number: formData.invoice_number,
        issue_date: formData.issue_date,
        period_start: formData.period_start,
        period_end: formData.period_end,
        total_amount: numberValue(formData.total_amount),
        supplier: formData.supplier,
        file_id: fileId || null,
        consumptions
      };

      await axiosClient.post('/api/v1/invoices', payload);

      // Avisa a página para atualizar o gráfico.
      onInvoiceSaved?.();

      setFormMessage({
        type: 'success',
        text: 'Fatura e consumos registados com sucesso!'
      });

      // Limpa os campos depois de guardar a fatura.
      setFormData(prev => ({
        ...prev,
        supplier: '',
        invoice_number: '',
        issue_date: '',
        period_start: '',
        period_end: '',
        total_amount: ''
      }));

      setSelectedTypeIds([]);
      setConsumptionsData({});
      setShowItems({});
      setItemsData({});
      setFileId(null);
      setUploadSuccess(false);
    } catch (err) {
      const errors = err.response?.data?.errors;

      const message = errors
        ? Object.values(errors).flat().join(' ')
        : (
            err.response?.data?.message ||
            'Erro ao guardar a fatura.'
          );

      setFormMessage({
        type: 'error',
        text: message
      });
    } finally {
      submitLock.current = false;
      setSubmitting(false);
    }
  };

  const inputClass =
    'w-full bg-white border border-slate-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:border-teal-600';

  const labelClass =
    'text-[11px] text-slate-500 block mb-1';

  return (
    <section className="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 space-y-8">
      {/* Cabeçalho do formulário. */}
      <div>
        <h2 className="text-lg font-bold text-slate-800">
          Registar Nova Fatura
        </h2>
        <p className="text-xs text-slate-400">
          Carrega o PDF para preencher automaticamente ou introduz os dados manualmente.
        </p>
      </div>

      {/* Área de carregamento do documento. */}
      <label className={`border-2 border-dashed rounded-2xl p-6 flex flex-col items-center justify-center cursor-pointer ${uploadSuccess ? 'border-emerald-500 bg-emerald-50/30' : 'border-slate-200 hover:border-teal-600 bg-slate-50/50'}`}>
        <input
          type="file"
          accept="application/pdf,image/*"
          className="hidden"
          onChange={handleFileUpload}
          disabled={uploading || submitting}
        />

        <div className={`w-12 h-12 rounded-full flex items-center justify-center mb-3 ${uploadSuccess ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
          {uploadSuccess
            ? <CheckCircle2 className="w-6 h-6" />
            : <Camera className="w-6 h-6" />
          }
        </div>

        <p className="font-semibold text-slate-800 text-sm">
          {uploading
            ? 'A carregar PDF...'
            : uploadSuccess
              ? 'Fatura carregada com sucesso!'
              : 'Carregar fatura (PDF / imagem)'
          }
        </p>

        <p className="text-xs text-slate-400 mt-1">
          Opcional: anexa o documento original.
        </p>
      </label>

      {/* Dados principais da fatura. */}
      <form
        onSubmit={handleSubmit}
        className="space-y-6 pt-4 border-t border-slate-100"
      >
        <h3 className="text-sm font-bold text-slate-800 uppercase tracking-wider">
          Dados principais da fatura
        </h3>

        {formMessage && (
          <div className={`p-3 rounded-xl text-xs font-semibold flex items-center gap-2 ${formMessage.type === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'}`}>
            <AlertCircle className="w-4 h-4 shrink-0" />
            {formMessage.text}
          </div>
        )}

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className={labelClass}>Fornecedor</label>
            <input
              required
              value={formData.supplier}
              onChange={e =>
                setFormData({
                  ...formData,
                  supplier: e.target.value
                })
              }
              placeholder="Ex.: Endesa, EPAL"
              className={inputClass}
            />
          </div>

          <div>
            <label className={labelClass}>Número da fatura</label>
            <input
              required
              value={formData.invoice_number}
              onChange={e =>
                setFormData({
                  ...formData,
                  invoice_number: e.target.value
                })
              }
              className={inputClass}
            />
          </div>

          <div>
            <label className={labelClass}>Valor total (€)</label>
            <input
              required
              type="number"
              step="0.01"
              value={formData.total_amount}
              onChange={e =>
                setFormData({
                  ...formData,
                  total_amount: e.target.value
                })
              }
              className={inputClass}
            />
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {[
            ['issue_date', 'Data de emissão'],
            ['period_start', 'Início do período'],
            ['period_end', 'Fim do período']
          ].map(([key, title]) => (
            <div key={key}>
              <label className={labelClass}>{title}</label>
              <input
                required
                type="date"
                value={formData[key]}
                onChange={e =>
                  setFormData({
                    ...formData,
                    [key]: e.target.value
                  })
                }
                className={inputClass}
              />
            </div>
          ))}
        </div>

        {/* Seleção dos tipos de consumo. */}
        <div className="space-y-3">
          <h3 className="text-sm font-bold text-slate-800 uppercase tracking-wider">
            Consumos da fatura
          </h3>

          <div className="flex flex-wrap gap-2">
            {consumptionTypes.map(type => {
              const id = Number(type.id);
              const selected = selectedTypeIds.includes(id);

              return (
                <button
                  key={id}
                  type="button"
                  onClick={() => toggleType(id)}
                  className={`px-4 py-2 rounded-xl text-xs font-semibold border ${selected ? 'bg-teal-700 text-white border-teal-700' : 'bg-slate-50 text-slate-700 border-slate-200'}`}
                >
                  {selected ? '✓ ' : '+ '}
                  {type.name} ({type.unit_of_measure})
                </button>
              );
            })}
          </div>
        </div>

        {/* Quantidades, custos e detalhes dos consumos selecionados. */}
        {selectedTypeIds.length > 0 && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {selectedTypeIds.map(id => {
              const type = consumptionTypes.find(
                t => Number(t.id) === id
              );

              if (!type) return null;

              const items = itemsData[id] || [];

              return (
                <section
                  key={id}
                  className="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-4"
                >
                  <div className="flex justify-between items-center">
                    <strong className="text-sm text-slate-800">
                      {type.name}
                    </strong>
                    <span className="text-[10px] bg-slate-200 px-2 py-1 rounded">
                      {type.unit_of_measure}
                    </span>
                  </div>

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className={labelClass}>
                        Quantidade ({type.unit_of_measure})
                      </label>
                      <input
                        required
                        type="number"
                        step="any"
                        value={consumptionsData[id]?.amount ?? ''}
                        onChange={e =>
                          changeConsumption(id, 'amount', e.target.value)
                        }
                        className={inputClass}
                      />
                    </div>

                    <div>
                      <label className={labelClass}>
                        Custo total (€)
                      </label>
                      <input
                        required
                        type="number"
                        step="0.01"
                        value={consumptionsData[id]?.cost ?? ''}
                        onChange={e =>
                          changeConsumption(id, 'cost', e.target.value)
                        }
                        className={inputClass}
                      />
                    </div>
                  </div>

                  {/* Opção para adicionar detalhes da fatura. */}
                  <label className="flex items-start gap-2 cursor-pointer text-sm text-slate-700 border-t border-slate-200 pt-3">
                    <input
                      type="checkbox"
                      className="mt-1 accent-teal-700"
                      checked={Boolean(showItems[id])}
                      onChange={e =>
                        setShowItems(prev => ({
                          ...prev,
                          [id]: e.target.checked
                        }))
                      }
                    />

                    <span>
                      Adicionar informação adicional
                      <span className="block text-xs text-slate-400 mt-1">
                        Descrição, categoria, quantidade, unidade e custo de cada linha.
                      </span>
                    </span>
                  </label>

                  {showItems[id] && (
                    <div className="space-y-3">
                      <div className="flex justify-between items-center">
                        <h4 className="text-xs font-bold uppercase tracking-wider text-slate-600">
                          Itens da fatura
                        </h4>

                        <button
                          type="button"
                          onClick={() => addItem(id)}
                          className="inline-flex items-center gap-1 text-xs font-semibold text-teal-700"
                        >
                          <Plus className="w-4 h-4" />
                          Adicionar linha
                        </button>
                      </div>

                      {items.length === 0 && (
                        <p className="text-xs text-slate-500 bg-white rounded-lg p-3 border border-dashed border-slate-300">
                          Sem itens. Adiciona uma linha ou carrega um PDF com detalhe.
                        </p>
                      )}

                      {items.map((item, index) => (
                        <div
                          key={`${id}-${index}`}
                          className="bg-white border border-slate-200 rounded-xl p-3 space-y-3"
                        >
                          <div className="flex justify-between items-center">
                            <strong className="text-xs text-slate-600">
                              Linha {index + 1}
                            </strong>

                            <button
                              type="button"
                              aria-label="Remover linha"
                              onClick={() => removeItem(id, index)}
                              className="text-slate-400 hover:text-red-600"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>

                          <div>
                            <label className={labelClass}>
                              Descrição
                            </label>
                            <input
                              value={item.name}
                              onChange={e =>
                                changeItem(id, index, 'name', e.target.value)
                              }
                              placeholder="Ex.: Termo de Energia, IVA 6%"
                              className={inputClass}
                            />
                          </div>

                          <div className="grid grid-cols-2 gap-3">
                            <div>
                              <label className={labelClass}>
                                Categoria
                              </label>
                              <select
                                value={item.category}
                                onChange={e =>
                                  changeItem(id, index, 'category', e.target.value)
                                }
                                className={inputClass}
                              >
                                <option value="consumption">Consumo</option>
                                <option value="fixed_fee">Tarifa fixa</option>
                                <option value="tax">Imposto / taxa</option>
                                <option value="other">Outro</option>
                              </select>
                            </div>

                            <div>
                              <label className={labelClass}>
                                Unidade
                              </label>
                              <input
                                value={item.unit}
                                onChange={e =>
                                  changeItem(id, index, 'unit', e.target.value)
                                }
                                placeholder="kWh, dias, meses..."
                                className={inputClass}
                              />
                            </div>
                          </div>

                          <div className="grid grid-cols-2 gap-3">
                            <div>
                              <label className={labelClass}>
                                Quantidade
                              </label>
                              <input
                                type="number"
                                step="any"
                                value={item.amount}
                                onChange={e =>
                                  changeItem(id, index, 'amount', e.target.value)
                                }
                                className={inputClass}
                              />
                            </div>

                            <div>
                              <label className={labelClass}>
                                Custo (€)
                              </label>
                              <input
                                type="number"
                                step="0.01"
                                value={item.cost}
                                onChange={e =>
                                  changeItem(id, index, 'cost', e.target.value)
                                }
                                className={inputClass}
                              />
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </section>
              );
            })}
          </div>
        )}

        {/* Botão de submissão da fatura. */}
        <button
          type="submit"
          disabled={submitting || uploading}
          className="w-full bg-teal-700 hover:bg-teal-800 text-white font-semibold py-3.5 rounded-xl text-sm disabled:opacity-50"
        >
          {submitting
            ? 'A guardar fatura...'
            : uploading
              ? 'A aguardar o carregamento...'
              : 'Guardar fatura e consumos'
          }
        </button>
      </form>
    </section>
  );
}
