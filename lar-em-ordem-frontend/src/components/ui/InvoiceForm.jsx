import { useState, useEffect, useRef } from 'react';
import { CheckCircle2, AlertCircle, Plus, Trash2 } from 'lucide-react';
import { uploadInvoiceFile, createInvoice } from '../../services/api/invoiceService';
import { getConsumptionTypes } from '../../services/api/consumptionService';
import { Checkbox } from '../form/Checkbox';
import { DatePicker } from '../form/DatePicker';
import { FileInput } from '../form/FileInput';
import { SearchSelect } from '../form/SearchSelect';
import { TextArea } from '../form/TextArea';
import { TextInput } from '../form/TextInput';

// Define a estrutura inicial dos itens e as opções disponíveis.
const emptyItem = () => ({
  name: '',
  category: 'consumption',
  amount: '',
  unit: '',
  cost: ''
});

const numberValue = v =>
  v === '' || v == null
    ? 0
    : Number.isFinite(Number(v)) ? Number(v) : 0;

// Obtém o identificador da habitação ativa através do localStorage.
const readActiveHouseId = () => {
  if (typeof window === 'undefined') return '';

  try {
    const stored = window.localStorage.getItem('activeHouse');
    if (!stored) return '';

    try {
      const parsed = JSON.parse(stored);
      if (typeof parsed === 'string') return parsed;
      if (parsed && typeof parsed === 'object') return parsed.id ?? '';
      return '';
    } catch {
      return stored;
    }
  } catch {
    return '';
  }
};

// Define as categorias disponíveis para os itens da fatura.
const itemCategoryOptions = [
  { value: 'consumption', label: 'Consumo' },
  { value: 'fixed_fee', label: 'Tarifa fixa' },
  { value: 'tax', label: 'Imposto / taxa' },
  { value: 'discount', label: 'Desconto' },
  { value: 'other', label: 'Outro' }
];

// Componente principal responsável pelo registo de novas faturas.
export default function InvoiceForm({ onInvoiceSaved }) {
  // Guarda os tipos de consumo disponíveis.
  const [consumptionTypes, setConsumptionTypes] = useState([
    { id: 1, name: 'Eletricidade', unit_of_measure: 'kWh' },
    { id: 2, name: 'Água', unit_of_measure: 'm³' },
    { id: 3, name: 'Gás', unit_of_measure: 'kWh' }
  ]);

  // Guarda os consumos selecionados e os respetivos detalhes.
  const [selectedTypeIds, setSelectedTypeIds] = useState([]);
  const [consumptionsData, setConsumptionsData] = useState({});
  const [showItems, setShowItems] = useState({});
  const [itemsData, setItemsData] = useState({});

  // Controla o carregamento do ficheiro e o envio da fatura.
  const [uploading, setUploading] = useState(false);
  const [selectedFile, setSelectedFile] = useState(null);
  const [fileId, setFileId] = useState(null);
  const [uploadSuccess, setUploadSuccess] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [formMessage, setFormMessage] = useState(null);

  // Impede carregamentos e submissões duplicadas.
  const uploadLock = useRef(false);
  const submitLock = useRef(false);

  // Guarda os dados principais da fatura.
  const [formData, setFormData] = useState({
    property_id: readActiveHouseId(),
    supplier: '',
    invoice_number: '',
    issue_date: '',
    period_start: '',
    period_end: '',
    total_amount: ''
  });

  // Atualiza um campo específico dos dados da fatura.
  const updateFormField = (field, value) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
  };

  // Carrega os tipos de consumo disponíveis através da API.
  useEffect(() => {
    getConsumptionTypes()
      .then(data => {
        if (Array.isArray(data) && data.length) {
          setConsumptionTypes(data.map(t => ({
            ...t,
            id: Number(t.id),
            unit_of_measure: Number(t.id) === 3
              ? 'kWh'
              : t.unit_of_measure
          })));
        }
      })
      .catch(() => {
        // Mantém os tipos predefinidos locais.
      });
  }, []);

  // Carrega o PDF e preenche automaticamente os dados extraídos.
  const handleFileUpload = async file => {
    if (!file) {
      if (uploadLock.current || submitLock.current) return;

      setSelectedFile(null);
      setFileId(null);
      setUploadSuccess(false);
      setFormMessage(null);
      return;
    }

    if (uploadLock.current || submitLock.current) return;

    setSelectedFile(file);
    uploadLock.current = true;
    setUploading(true);
    setUploadSuccess(false);
    setFormMessage(null);
    setFileId(null);

    try {
      const data = new FormData();
      data.append('file', file);

      const body = await uploadInvoiceFile(data);
      const result = body?.data || body;

      if (!result?.file_id) {
        throw new Error('A API não devolveu file_id.');
      }

      setFileId(result.file_id);
      setUploadSuccess(true);

      // Preenche os dados principais com a informação extraída do PDF.
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

        // Preenche os consumos e os respetivos itens extraídos.
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
      // Apresenta uma mensagem caso ocorra um erro no carregamento.
      setFormMessage({
        type: 'error',
        text: err.response?.status === 429
          ? 'Foram feitos demasiados pedidos. Aguarda um pouco antes de tentares novamente.'
          : err.response?.data?.message || err.message || 'Erro ao carregar o ficheiro.'
      });
    } finally {
      uploadLock.current = false;
      setUploading(false);
    }
  };

  // Seleciona ou remove um tipo de consumo e os respetivos dados.
  const toggleType = (rawId, checked) => {
    const id = Number(rawId);

    if (checked) {
      setSelectedTypeIds(prev =>
        prev.includes(id) ? prev : [...prev, id]
      );

      setConsumptionsData(prev => ({
        ...prev,
        [id]: prev[id] || { amount: '', cost: '' }
      }));

      setShowItems(prev => ({
        ...prev,
        [id]: prev[id] ?? false
      }));

      setItemsData(prev => ({
        ...prev,
        [id]: prev[id] || []
      }));

      return;
    }

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

  // Atualiza um campo de um item individual da fatura.
  const changeItem = (id, index, field, value) =>
    setItemsData(prev => {
      const list = [...(prev[id] || [])];

      list[index] = {
        ...(list[index] || emptyItem()),
        [field]: value
      };

      return { ...prev, [id]: list };
    });

  // Adiciona uma nova linha de detalhe ao consumo selecionado.
  const addItem = id =>
    setItemsData(prev => ({
      ...prev,
      [id]: [...(prev[id] || []), emptyItem()]
    }));

  // Remove um item individual da lista de consumos.
  const removeItem = (id, index) =>
    setItemsData(prev => ({
      ...prev,
      [id]: (prev[id] || []).filter((_, i) => i !== index)
    }));

  // Valida os dados e envia a fatura e os consumos para a API.
  const handleSubmit = async e => {
    e.preventDefault();

    if (submitLock.current || uploadLock.current) return;

    submitLock.current = true;
    setSubmitting(true);
    setFormMessage(null);

    try {
      // Verifica se existe uma habitação selecionada.
      const propertyId = readActiveHouseId() || formData.property_id;

      if (!propertyId) {
        setFormMessage({
          type: 'error',
          text: 'Seleciona uma habitação antes de guardar a fatura.'
        });
        return;
      }

      // Valida o preenchimento das datas obrigatórias.
      if (
        !formData.issue_date ||
        !formData.period_start ||
        !formData.period_end
      ) {
        setFormMessage({
          type: 'error',
          text: 'Preenche a data de emissão, o início e o fim do período.'
        });
        return;
      }

      // Prepara os consumos e os respetivos itens para envio.
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

      // Constrói o objeto com todos os dados da fatura.
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

      // Regista a fatura e apresenta uma mensagem de sucesso.
      await createInvoice(payload);
      onInvoiceSaved?.();

      setFormMessage({
        type: 'success',
        text: 'Fatura e consumos registados com sucesso!'
      });

      // Limpa os campos após o registo bem-sucedido.
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
      setSelectedFile(null);
      setFileId(null);
      setUploadSuccess(false);
    } catch (err) {
      // Apresenta os erros devolvidos pela API.
      const errors = err.response?.data?.errors;

      setFormMessage({
        type: 'error',
        text: errors
          ? Object.values(errors).flat().join(' ')
          : err.response?.data?.message || 'Erro ao guardar a fatura.'
      });
    } finally {
      submitLock.current = false;
      setSubmitting(false);
    }
  };

  // Apresenta a estrutura visual e os campos principais do formulário.
  return (
    <section className="space-y-8 rounded-2xl border border-slate-100 bg-white p-6 shadow-sm">
      {/* Cabeçalho e descrição do formulário. */}
      <header
        className="relative -mx-6 -mt-6 mb-8 flex flex-col gap-2 overflow-hidden rounded-t-2xl p-6 text-white"
        style={{ backgroundColor: '#0F766E' }}
      >
        <div className="pointer-events-none absolute -right-8 -top-12 h-32 w-32 rounded-full bg-white/10" />
        <div className="pointer-events-none absolute -bottom-16 right-1/3 h-32 w-32 rounded-full bg-black/10" />

        <div className="relative z-10">
          <h2 className="text-xl font-bold">Registar Nova Fatura</h2>
          <p className="mt-1 text-sm text-white/80">
            Carrega o PDF para preencher automaticamente ou introduz os dados manualmente.
          </p>
        </div>
      </header>

      {/* Campo para anexar o documento PDF. */}
      <FileInput
        label="Documento da fatura"
        hint={
          uploading
            ? 'A carregar PDF...'
            : uploadSuccess
              ? 'Fatura carregada com sucesso!'
              : 'Opcional: anexa o documento original.'
        }
        value={selectedFile}
        onChange={handleFileUpload}
        accept="application/pdf,.pdf"
      />

      {/* Confirmação de que o documento foi carregado. */}
      {uploadSuccess && (
        <div className="-mt-6 flex items-center gap-2 text-sm font-medium text-emerald-700">
          <CheckCircle2 className="h-4 w-4 shrink-0" />
          Documento associado à fatura.
        </div>
      )}

      {/* Formulário com os dados principais da fatura. */}
      <form onSubmit={handleSubmit} className="space-y-6 border-t border-slate-100 pt-4">
        <h3 className="text-base font-bold uppercase tracking-wider text-slate-800">
          Dados principais da fatura
        </h3>

        {/* Apresenta mensagens de sucesso ou erro. */}
        {formMessage && (
          <div
            role={formMessage.type === 'error' ? 'alert' : 'status'}
            className={`flex items-center gap-2 rounded-xl border p-3 text-sm font-semibold ${
              formMessage.type === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : 'border-red-200 bg-red-50 text-red-700'
            }`}
          >
            {formMessage.type === 'success'
              ? <CheckCircle2 className="h-4 w-4 shrink-0" />
              : <AlertCircle className="h-4 w-4 shrink-0" />
            }
            {formMessage.text}
          </div>
        )}

        {/* Campos das datas de emissão e faturação. */}
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <DatePicker
            label="Data de emissão"
            required
            value={formData.issue_date}
            onChange={value => updateFormField('issue_date', value)}
          />

          <DatePicker
            label="Início do período"
            required
            value={formData.period_start}
            onChange={value => updateFormField('period_start', value)}
          />

          <DatePicker
            label="Fim do período"
            required
            value={formData.period_end}
            onChange={value => updateFormField('period_end', value)}
          />
        </div>

        {/* Campos de identificação e valor da fatura. */}
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <TextInput
            label="Fornecedor"
            required
            value={formData.supplier}
            onChange={value => updateFormField('supplier', value)}
            placeholder="Ex.: Endesa, EPAL"
          />

          <TextInput
            label="Número da fatura"
            required
            value={formData.invoice_number}
            onChange={value => updateFormField('invoice_number', value)}
          />

          <TextInput
            label="Valor total (€)"
            required
            type="number"
            step="0.01"
            value={formData.total_amount}
            onChange={value => updateFormField('total_amount', value)}
          />
        </div>

        {/* Seleção dos tipos de consumo incluídos na fatura. */}
        <div className="space-y-3">
          <h3 className="text-base font-bold uppercase tracking-wider text-slate-800">
            Consumos da fatura
          </h3>

          <p className="text-sm text-slate-500">
            Seleciona todos os tipos de consumo incluídos nesta fatura.
          </p>

          <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
            {consumptionTypes.map(type => {
              const id = Number(type.id);

              return (
                <div
                  key={id}
                  className="rounded-xl border border-[#1C3557]/30 bg-[#1C3557]/5 p-3"
                >
                  <Checkbox
                    checked={selectedTypeIds.includes(id)}
                    onChange={checked => toggleType(id, checked)}
                    label={`${type.name} (${type.unit_of_measure})`}
                  />
                </div>
              );
            })}
          </div>
        </div>

        {/* Apresenta os campos dos consumos selecionados. */}
        {selectedTypeIds.length > 0 && (
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            {selectedTypeIds.map(id => {
              const type = consumptionTypes.find(t => Number(t.id) === id);
              if (!type) return null;

              const items = itemsData[id] || [];

              return (
                <section
                  key={id}
                  className="space-y-4 rounded-xl border border-[#1C3557]/30 bg-[#1C3557]/5 p-4"
                >
                  {/* Identificação do consumo e respetiva unidade. */}
                  <div className="flex items-center justify-between">
                    <strong className="text-base font-semibold text-[#1C3557]">
                      {type.name}
                    </strong>
                    <span className="rounded-md bg-[#1C3557]/10 px-2 py-1 text-xs font-medium text-[#1C3557]">
                      {type.unit_of_measure}
                    </span>
                  </div>

                  {/* Campos da quantidade consumida e do custo total. */}
                  <div className="grid grid-cols-2 gap-3">
                    <TextInput
                      label={`Quantidade (${type.unit_of_measure})`}
                      required
                      type="number"
                      step="any"
                      value={consumptionsData[id]?.amount ?? ''}
                      onChange={value => changeConsumption(id, 'amount', value)}
                    />

                    <TextInput
                      label="Custo total (€)"
                      required
                      type="number"
                      step="0.01"
                      value={consumptionsData[id]?.cost ?? ''}
                      onChange={value => changeConsumption(id, 'cost', value)}
                    />
                  </div>

                  {/* Ativa a introdução de informação adicional. */}
                  <div className="rounded-xl border border-[#1C3557]/30 bg-[#1C3557]/5 p-3">
                    <Checkbox
                      checked={Boolean(showItems[id])}
                      onChange={checked => setShowItems(prev => ({
                        ...prev,
                        [id]: checked
                      }))}
                      label="Adicionar informação adicional"
                      hint="Descrição, categoria, quantidade, unidade e custo de cada linha."
                    />
                  </div>

                  {/* Permite gerir os itens individuais de cada consumo. */}
                  {showItems[id] && (
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <h4 className="text-sm font-bold uppercase tracking-wider text-[#1C3557]">
                          Itens da fatura
                        </h4>

                        {/* Adiciona uma nova linha de detalhe. */}
                        <button
                          type="button"
                          onClick={() => addItem(id)}
                          className="inline-flex items-center gap-1 text-sm font-semibold text-[#1C3557] hover:opacity-80"
                        >
                          <Plus className="h-4 w-4" />
                          Adicionar linha
                        </button>
                      </div>

                      {/* Informa quando ainda não existem itens adicionados. */}
                      {items.length === 0 && (
                        <p className="rounded-lg border border-dashed border-[#1C3557]/30 bg-[#1C3557]/5 p-3 text-sm text-slate-500">
                          Sem itens. Adiciona uma linha ou carrega um PDF com detalhe.
                        </p>
                      )}

                      {/* Lista os itens e permite editar ou remover cada linha. */}
                      {items.map((item, index) => (
                        <div
                          key={`${id}-${index}`}
                          className="space-y-3 rounded-xl border border-[#1C3557]/30 bg-[#1C3557]/5 p-3"
                        >
                          <div className="flex items-center justify-between">
                            <strong className="text-sm font-semibold text-[#1C3557]">
                              Linha {index + 1}
                            </strong>

                            {/* Remove a linha selecionada. */}
                            <button
                              type="button"
                              aria-label={`Remover linha ${index + 1}`}
                              onClick={() => removeItem(id, index)}
                              className="text-slate-400 transition hover:text-red-600"
                            >
                              <Trash2 className="h-4 w-4" />
                            </button>
                          </div>

                          {/* Campo para descrever o item da fatura. */}
                          <TextArea
                            label="Descrição"
                            value={item.name}
                            onChange={value => changeItem(id, index, 'name', value)}
                            placeholder="Ex.: Termo de Energia, IVA 6%"
                            rows={2}
                          />

                          {/* Campos da categoria e unidade do item. */}
                          <div className="grid grid-cols-2 gap-3">
                            <SearchSelect
                              label="Categoria"
                              options={itemCategoryOptions}
                              value={item.category}
                              onChange={value => changeItem(id, index, 'category', value)}
                              placeholder="Escolher categoria..."
                            />

                            <TextInput
                              label="Unidade"
                              value={item.unit}
                              onChange={value => changeItem(id, index, 'unit', value)}
                              placeholder="kWh, dias, meses..."
                            />
                          </div>

                          {/* Campos da quantidade e custo do item. */}
                          <div className="grid grid-cols-2 gap-3">
                            <TextInput
                              label="Quantidade"
                              type="number"
                              step="any"
                              value={item.amount}
                              onChange={value => changeItem(id, index, 'amount', value)}
                            />

                            <TextInput
                              label="Custo (€)"
                              type="number"
                              step="0.01"
                              value={item.cost}
                              onChange={value => changeItem(id, index, 'cost', value)}
                            />
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

        {/* Botão para guardar a fatura e os respetivos consumos. */}
        <button
          type="submit"
          disabled={submitting || uploading}
          className="w-full rounded-xl bg-teal-700 py-3.5 text-base font-semibold text-white transition hover:bg-teal-800 disabled:opacity-50"
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
