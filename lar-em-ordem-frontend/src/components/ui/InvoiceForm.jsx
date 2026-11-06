import React, { useState, useEffect, useRef } from "react";
import {
  Camera,
  CheckCircle2,
  AlertCircle,
  Plus,
  Trash2,
  Loader2,
} from "lucide-react";

import axiosClient from "../../services/api/axiosClient";

// Obtém o ID da casa atualmente selecionada.
function getActivePropertyId() {
  if (typeof window === "undefined") {
    return "";
  }

  try {
    const stored = window.localStorage.getItem("activeHouse");

    if (!stored) {
      return "";
    }

    try {
      const parsed = JSON.parse(stored);

      if (typeof parsed === "string") {
        return parsed;
      }

      if (parsed && typeof parsed === "object") {
        return parsed.id ?? "";
      }

      return "";
    } catch {
      // Também aceita o ID guardado diretamente, sem JSON.
      return stored;
    }
  } catch {
    return "";
  }
}

// Estrutura inicial de um item adicional da fatura.
const emptyItem = () => ({
  name: "",
  category: "consumption",
  amount: "",
  unit: "",
  cost: "",
});

// Converte valores para números, evitando NaN.
const numberValue = (value) =>
  value === "" || value == null
    ? 0
    : Number.isFinite(Number(value))
      ? Number(value)
      : 0;

// Obtém os itens extraídos a partir dos vários formatos possíveis.
function getExtractedItems(consumption) {
  const possibleItems = [
    consumption?.items,
    consumption?.consumption_items,
    consumption?.consumptionItems,
    consumption?.details,
    consumption?.lines,
  ];

  return (
    possibleItems.find(
      (items) => Array.isArray(items) && items.length > 0
    ) ??
    possibleItems.find(Array.isArray) ??
    []
  );
}

export default function InvoiceForm({ onInvoiceSaved }) {
  // Tipos de consumo disponíveis.
  const [consumptionTypes, setConsumptionTypes] = useState([
    { id: 1, name: "Eletricidade", unit_of_measure: "kWh" },
    { id: 2, name: "Água", unit_of_measure: "m³" },
    { id: 3, name: "Gás", unit_of_measure: "kWh" },
  ]);

  // Estado dos serviços selecionados e respetivos consumos.
  const [selectedTypeIds, setSelectedTypeIds] = useState([]);
  const [consumptionsData, setConsumptionsData] = useState({});
  const [showItems, setShowItems] = useState({});
  const [itemsData, setItemsData] = useState({});

  // Estado do carregamento e registo da fatura.
  const [uploading, setUploading] = useState(false);
  const [fileId, setFileId] = useState(null);
  const [uploadSuccess, setUploadSuccess] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [formMessage, setFormMessage] = useState(null);

  // Referências para impedir pedidos simultâneos.
  const uploadLock = useRef(false);
  const submitLock = useRef(false);

  // Dados principais da fatura.
  const [formData, setFormData] = useState({
    supplier: "",
    invoice_number: "",
    issue_date: "",
    period_start: "",
    period_end: "",
    total_amount: "",
  });

  // Carrega os tipos de consumo disponibilizados pela API.
  useEffect(() => {
    axiosClient
      .get("/api/v1/consumption-types")
      .then((res) => {
        const data = res.data?.data || res.data;

        if (Array.isArray(data) && data.length) {
          setConsumptionTypes(
            data.map((type) => ({
              ...type,
              id: Number(type.id),
              unit_of_measure:
                Number(type.id) === 3
                  ? "kWh"
                  : type.unit_of_measure,
            }))
          );
        }
      })
      .catch((err) => {
        console.log(
          "A usar tipos predefinidos locais:",
          err
        );
      });
  }, []);

  // Processamento do PDF ou imagem e preenchimento automático.
  const handleFileUpload = async (e) => {
    const input = e.currentTarget;
    const file = input.files?.[0];

    if (
      !file ||
      uploadLock.current ||
      submitLock.current
    ) {
      return;
    }

    uploadLock.current = true;

    setUploading(true);
    setUploadSuccess(false);
    setFormMessage(null);
    setFileId(null);

    try {
      const data = new FormData();
      data.append("file", file);

      const res = await axiosClient.post(
        "/api/v1/invoices/upload",
        data
      );

      const result = res.data?.data || res.data;

      if (!result?.file_id) {
        throw new Error("A API não devolveu file_id.");
      }

      setFileId(result.file_id);
      setUploadSuccess(true);

      // Utiliza os dados extraídos pela API, quando disponíveis.
      const extracted =
        result.extracted || result.invoice_data;

      if (extracted) {
        setFormData((prev) => ({
          ...prev,
          supplier:
            extracted.supplier ?? prev.supplier,
          invoice_number:
            extracted.invoice_number ?? prev.invoice_number,
          issue_date:
            extracted.issue_date ?? prev.issue_date,
          period_start:
            extracted.period_start ?? prev.period_start,
          period_end:
            extracted.period_end ?? prev.period_end,
          total_amount:
            extracted.total_amount ?? prev.total_amount,
        }));

        // Preenche também os consumos e os respetivos itens.
        if (Array.isArray(extracted.consumptions)) {
          const selected = [];
          const consumptionValues = {};
          const detailsVisible = {};
          const details = {};

          extracted.consumptions.forEach((consumption) => {
            const id = Number(
              consumption.consumption_type_id
            );

            if (!Number.isFinite(id) || id <= 0) {
              return;
            }

            selected.push(id);

            consumptionValues[id] = {
              amount: consumption.amount ?? "",
              cost: consumption.cost ?? "",
            };

            const extractedItems =
              getExtractedItems(consumption);

            details[id] = extractedItems.map((item) => ({
              name: String(item.name ?? ""),
              category: item.category ?? "other",
              amount: item.amount ?? "",
              unit: String(item.unit ?? ""),
              cost: item.cost ?? "",
            }));

            detailsVisible[id] = details[id].length > 0;
          });

          setSelectedTypeIds([...new Set(selected)]);
          setConsumptionsData(consumptionValues);
          setShowItems(detailsVisible);
          setItemsData(details);
        }

        setFormMessage({
          type: "success",
          text: "Fatura processada! Os dados foram preenchidos automaticamente.",
        });
      } else {
        setFormMessage({
          type: "success",
          text: "Ficheiro carregado com sucesso. Preenche os dados manualmente.",
        });
      }
    } catch (err) {
      console.error("Erro no upload:", err);

      setFormMessage({
        type: "error",
        text:
          err.response?.data?.message ||
          err.message ||
          "Erro ao carregar o ficheiro.",
      });
    } finally {
      uploadLock.current = false;
      setUploading(false);

      // Permite selecionar novamente o mesmo ficheiro.
      input.value = "";
    }
  };

  // Adiciona ou remove um tipo de consumo da fatura.
  const toggleType = (rawId) => {
    const id = Number(rawId);

    if (selectedTypeIds.includes(id)) {
      setSelectedTypeIds((prev) =>
        prev.filter((value) => value !== id)
      );

      setConsumptionsData((prev) => {
        const next = { ...prev };
        delete next[id];
        return next;
      });

      setShowItems((prev) => {
        const next = { ...prev };
        delete next[id];
        return next;
      });

      setItemsData((prev) => {
        const next = { ...prev };
        delete next[id];
        return next;
      });
    } else {
      setSelectedTypeIds((prev) => [...prev, id]);

      setConsumptionsData((prev) => ({
        ...prev,
        [id]: {
          amount: "",
          cost: "",
        },
      }));

      setShowItems((prev) => ({
        ...prev,
        [id]: false,
      }));

      setItemsData((prev) => ({
        ...prev,
        [id]: [],
      }));
    }
  };

  // Atualiza os valores principais de um consumo.
  const changeConsumption = (id, field, value) => {
    setConsumptionsData((prev) => ({
      ...prev,
      [id]: {
        ...(prev[id] || {}),
        [field]: value,
      },
    }));
  };

  // Atualiza um campo de um item adicional.
  const changeItem = (id, index, field, value) => {
    setItemsData((prev) => {
      const list = [...(prev[id] || [])];

      list[index] = {
        ...(list[index] || emptyItem()),
        [field]: value,
      };

      return {
        ...prev,
        [id]: list,
      };
    });
  };

  // Adiciona uma nova linha de detalhe.
  const addItem = (id) => {
    setItemsData((prev) => ({
      ...prev,
      [id]: [
        ...(prev[id] || []),
        emptyItem(),
      ],
    }));
  };

  // Remove uma linha de detalhe.
  const removeItem = (id, index) => {
    setItemsData((prev) => ({
      ...prev,
      [id]: (prev[id] || []).filter(
        (_, itemIndex) => itemIndex !== index
      ),
    }));
  };

  // Valida os dados e guarda a fatura e os seus consumos.
  const handleSubmit = async (e) => {
    e.preventDefault();

    if (
      submitLock.current ||
      uploadLock.current
    ) {
      return;
    }

    // Lê a casa ativa no momento de guardar a fatura.
    const propertyId = getActivePropertyId();

    if (!propertyId) {
      setFormMessage({
        type: "error",
        text: "Seleciona uma casa antes de guardar a fatura.",
      });

      return;
    }

    submitLock.current = true;

    setSubmitting(true);
    setFormMessage(null);

    try {
      // Prepara os consumos selecionados.
      const consumptions = selectedTypeIds.map((id) => {
        const entry = {
          consumption_type_id: id,
          amount: numberValue(consumptionsData[id]?.amount),
          cost: numberValue(consumptionsData[id]?.cost),
        };

        // Inclui os itens adicionais quando essa opção está ativa.
        if (showItems[id]) {
          entry.items = (itemsData[id] || [])
            .filter((item) =>
              String(item.name ?? "").trim()
            )
            .map((item) => ({
              name: String(item.name ?? "").trim(),
              category: item.category || "other",
              amount: numberValue(item.amount),
              unit: String(item.unit ?? "").trim(),
              cost: numberValue(item.cost),
            }));
        }

        return entry;
      });

      // Dados enviados para a API.
      const payload = {
        property_id: propertyId,
        invoice_number: formData.invoice_number,
        issue_date: formData.issue_date,
        period_start: formData.period_start,
        period_end: formData.period_end,
        total_amount: numberValue(formData.total_amount),
        supplier: formData.supplier,
        file_id: fileId || null,
        consumptions,
      };

      await axiosClient.post(
        "/api/v1/invoices",
        payload
      );

      // Atualiza as faturas apresentadas na página.
      onInvoiceSaved?.();

      setFormMessage({
        type: "success",
        text: "Fatura e consumos registados com sucesso!",
      });

      // Limpa os dados principais depois de guardar.
      setFormData({
        supplier: "",
        invoice_number: "",
        issue_date: "",
        period_start: "",
        period_end: "",
        total_amount: "",
      });

      setSelectedTypeIds([]);
      setConsumptionsData({});
      setShowItems({});
      setItemsData({});
      setFileId(null);
      setUploadSuccess(false);
    } catch (err) {
      setFormMessage({
        type: "error",
        text:
          err.response?.data?.message ||
          "Erro ao guardar a fatura.",
      });
    } finally {
      submitLock.current = false;
      setSubmitting(false);
    }
  };

  // Classes comuns aos campos e respetivas etiquetas.
  const inputClass =
    "w-full bg-white border border-slate-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:border-teal-600";

  const labelClass =
    "text-[11px] text-slate-500 block mb-1";

  return (
    <section className="w-full min-w-0 max-w-full box-border bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-slate-100 space-y-8">

      {/* Cabeçalho do formulário. */}
      <div>
        <h2 className="text-lg font-bold text-slate-800">
          Registar Nova Fatura
        </h2>

        <p className="text-xs text-slate-400">
          Carrega o PDF para preencher automaticamente ou introduz os dados manualmente.
        </p>
      </div>

      {/* Área interativa para carregar o PDF ou imagem. */}
      <label
        className={`border-2 border-dashed rounded-2xl p-6 flex flex-col items-center justify-center cursor-pointer transition ${
          uploadSuccess
            ? "border-emerald-500 bg-emerald-50/30"
            : "border-slate-200 hover:border-teal-600 bg-slate-50/50"
        }`}
      >
        <input
          type="file"
          accept="application/pdf,image/*"
          className="hidden"
          onChange={handleFileUpload}
          disabled={uploading || submitting}
        />

        <div
          className={`w-12 h-12 rounded-full flex items-center justify-center mb-3 ${
            uploadSuccess
              ? "bg-emerald-100 text-emerald-700"
              : "bg-slate-100 text-slate-600"
          }`}
        >
          {uploading ? (
            <Loader2 className="w-6 h-6 animate-spin text-teal-700" />
          ) : uploadSuccess ? (
            <CheckCircle2 className="w-6 h-6" />
          ) : (
            <Camera className="w-6 h-6" />
          )}
        </div>

        <p className="font-semibold text-slate-800 text-sm">
          {uploading
            ? "A analisar fatura com IA..."
            : uploadSuccess
              ? "Fatura carregada e extraída com sucesso!"
              : "Carregar fatura (PDF / imagem)"}
        </p>

        <p className="text-xs text-slate-400 mt-1">
          {uploading
            ? "A preencher campos..."
            : "Opcional: anexa o documento original."}
        </p>
      </label>

      {/* Formulário de introdução e edição dos dados da fatura. */}
      <form
        onSubmit={handleSubmit}
        className="space-y-6 pt-4 border-t border-slate-100"
      >
        <h3 className="text-sm font-bold text-slate-800 uppercase tracking-wider">
          Dados principais da fatura
        </h3>

        {/* Mensagens de sucesso ou erro. */}
        {formMessage && (
          <div
            role="alert"
            className={`p-3 rounded-xl text-xs font-semibold flex items-center gap-2 ${
              formMessage.type === "success"
                ? "bg-emerald-50 text-emerald-700 border border-emerald-200"
                : "bg-red-50 text-red-700 border border-red-200"
            }`}
          >
            {formMessage.type === "success" ? (
              <CheckCircle2 className="w-4 h-4 shrink-0" />
            ) : (
              <AlertCircle className="w-4 h-4 shrink-0" />
            )}

            {formMessage.text}
          </div>
        )}

        {/* Fornecedor, número e valor total. */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className={labelClass}>
              Fornecedor
            </label>

            <input
              required
              value={formData.supplier}
              onChange={(e) =>
                setFormData((prev) => ({
                  ...prev,
                  supplier: e.target.value,
                }))
              }
              placeholder="Ex.: Endesa, EPAL"
              className={inputClass}
            />
          </div>

          <div>
            <label className={labelClass}>
              Número da fatura
            </label>

            <input
              required
              value={formData.invoice_number}
              onChange={(e) =>
                setFormData((prev) => ({
                  ...prev,
                  invoice_number: e.target.value,
                }))
              }
              className={inputClass}
            />
          </div>

          <div>
            <label className={labelClass}>
              Valor total (€)
            </label>

            <input
              required
              type="number"
              step="0.01"
              value={formData.total_amount}
              onChange={(e) =>
                setFormData((prev) => ({
                  ...prev,
                  total_amount: e.target.value,
                }))
              }
              className={inputClass}
            />
          </div>
        </div>

        {/* Datas de emissão e período de faturação. */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {[
            ["issue_date", "Data de emissão"],
            ["period_start", "Início do período"],
            ["period_end", "Fim do período"],
          ].map(([key, title]) => (
            <div key={key}>
              <label className={labelClass}>
                {title}
              </label>

              <input
                required
                type="date"
                value={formData[key]}
                onChange={(e) =>
                  setFormData((prev) => ({
                    ...prev,
                    [key]: e.target.value,
                  }))
                }
                className={inputClass}
              />
            </div>
          ))}
        </div>

        {/* Seleção dos tipos de consumo incluídos na fatura. */}
        <div className="space-y-3">
          <h3 className="text-sm font-bold text-slate-800 uppercase tracking-wider">
            Consumos da fatura
          </h3>

          <div className="flex flex-wrap gap-2">
            {consumptionTypes.map((type) => {
              const id = Number(type.id);
              const selected = selectedTypeIds.includes(id);

              return (
                <button
                  key={id}
                  type="button"
                  onClick={() => toggleType(id)}
                  className={`px-4 py-2 rounded-xl text-xs font-semibold border ${
                    selected
                      ? "bg-teal-700 text-white border-teal-700"
                      : "bg-slate-50 text-slate-700 border-slate-200"
                  }`}
                >
                  {selected ? "✓ " : "+ "}
                  {type.name} ({type.unit_of_measure})
                </button>
              );
            })}
          </div>
        </div>

        {/* Campos específicos de cada consumo selecionado. */}
        {selectedTypeIds.length > 0 && (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {selectedTypeIds.map((id) => {
              const type = consumptionTypes.find(
                (item) => Number(item.id) === id
              );

              if (!type) {
                return null;
              }

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

                  {/* Quantidade e custo total do consumo. */}
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className={labelClass}>
                        Quantidade ({type.unit_of_measure})
                      </label>

                      <input
                        required
                        type="number"
                        step="any"
                        value={consumptionsData[id]?.amount ?? ""}
                        onChange={(e) =>
                          changeConsumption(
                            id,
                            "amount",
                            e.target.value
                          )
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
                        value={consumptionsData[id]?.cost ?? ""}
                        onChange={(e) =>
                          changeConsumption(
                            id,
                            "cost",
                            e.target.value
                          )
                        }
                        className={inputClass}
                      />
                    </div>
                  </div>

                  {/* Ativação da informação adicional por consumo. */}
                  <label className="flex items-start gap-2 cursor-pointer text-sm text-slate-700 border-t border-slate-200 pt-3">
                    <input
                      type="checkbox"
                      className="mt-1 accent-teal-700"
                      checked={Boolean(showItems[id])}
                      onChange={(e) =>
                        setShowItems((prev) => ({
                          ...prev,
                          [id]: e.target.checked,
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

                  {/* Gestão dos itens individuais da fatura. */}
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

                      {/* Linhas individuais dos itens. */}
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
                              onClick={() => removeItem(id, index)}
                              className="text-slate-400 hover:text-red-600"
                              aria-label={`Remover linha ${index + 1}`}
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
                              onChange={(e) =>
                                changeItem(
                                  id,
                                  index,
                                  "name",
                                  e.target.value
                                )
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
                                onChange={(e) =>
                                  changeItem(
                                    id,
                                    index,
                                    "category",
                                    e.target.value
                                  )
                                }
                                className={inputClass}
                              >
                                <option value="consumption">
                                  Consumo
                                </option>

                                <option value="fixed_fee">
                                  Tarifa fixa
                                </option>

                                <option value="tax">
                                  Imposto / taxa
                                </option>

                                <option value="other">
                                  Outro
                                </option>
                              </select>
                            </div>

                            <div>
                              <label className={labelClass}>
                                Unidade
                              </label>

                              <input
                                value={item.unit}
                                onChange={(e) =>
                                  changeItem(
                                    id,
                                    index,
                                    "unit",
                                    e.target.value
                                  )
                                }
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
                                onChange={(e) =>
                                  changeItem(
                                    id,
                                    index,
                                    "amount",
                                    e.target.value
                                  )
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
                                onChange={(e) =>
                                  changeItem(
                                    id,
                                    index,
                                    "cost",
                                    e.target.value
                                  )
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
            ? "A guardar fatura..."
            : uploading
              ? "A aguardar o carregamento..."
              : "Guardar fatura e consumos"}
        </button>
      </form>
    </section>
  );
}
