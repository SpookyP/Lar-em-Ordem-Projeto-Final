"""
Extrai dados de faturas portuguesas da Endesa e da EPAL.

Uso:
    python invoice_parser.py Fatura_Endesa.pdf
    python invoice_parser.py Fatura_Epal.pdf
    python invoice_parser.py fatura.pdf --types-map '{"eletricidade": 1, "agua": 2, "gas": 3}'

Dependência: pip install pdfplumber
"""

import argparse
import json
import re
import sys
import unicodedata
from datetime import datetime

import pdfplumber


# Abreviaturas dos meses
MONTHS = {
    "jan": "01", "fev": "02", "mar": "03", "abr": "04",
    "mai": "05", "jun": "06", "jul": "07", "ago": "08",
    "set": "09", "out": "10", "nov": "11", "dez": "12",
}


# Padrões para reconhecer números, valores e datas.
NUMBER_RE = r"[-+]?\d+(?:[.,]\d+)*"
CURRENCY_RE = re.compile(rf"({NUMBER_RE})\s*€", re.IGNORECASE)
DATE_RE = r"\d{1,2}[-/.]\d{1,2}[-/.]\d{4}"
TEXT_DATE_RE = r"\d{1,2}\s+[a-zç]{3}\s+\d{4}"
PERIOD_RE = re.compile(
    rf"({DATE_RE}|{TEXT_DATE_RE})\s+a\s+({DATE_RE}|{TEXT_DATE_RE})",
    re.IGNORECASE,
)


# Remove acentos e converte o texto para minúsculas.
def norm(text):
    if not text:
        return ""
    text = unicodedata.normalize("NFKD", text)
    return "".join(ch for ch in text if not unicodedata.combining(ch)).lower()


# Converte números portugueses para números Python.
def parse_number(value):
    if value is None:
        return 0.0

    text = str(value).strip().replace("€", "").replace("EUR", "")
    text = text.replace(" ", "").replace("|", "")
    negative = text.startswith("-") or (text.startswith("(") and text.endswith(")"))
    text = text.replace("(", "").replace(")", "").replace("-", "").replace("+", "")

    if "," in text:
        text = text.replace(".", "").replace(",", ".")

    try:
        number = float(text)
        return -number if negative else number
    except (ValueError, TypeError):
        return 0.0


# Converte datas para o formato AAAA-MM-DD.
def format_date(value):
    if not value:
        return None

    text = re.sub(r"\s+", " ", value.strip().lower())

    for fmt in ("%d-%m-%Y", "%d/%m/%Y", "%d.%m.%Y"):
        try:
            return datetime.strptime(text, fmt).strftime("%Y-%m-%d")
        except ValueError:
            pass

    match = re.fullmatch(r"(\d{1,2})\s+([a-zç]{3})\s+(\d{4})", text)
    if match:
        day, month_text, year = match.groups()
        month = MONTHS.get(norm(month_text))
        if month:
            return f"{year}-{month}-{day.zfill(2)}"

    return None


# Identifica se um item é imposto, custo fixo ou consumo.
def classify_category(name):
    text = norm(name)

    if any(word in text for word in (
        "iva", "imposto", "taxa", "contribuicao", "mecanismo",
        "dgeg", "audiovisual", "subsolo", "adicional",
    )):
        return "tax"

    if any(word in text for word in (
        "potencia", "termo fixo", "quota servico", "saneamento",
        "residuos urbanos", "disponibilidade", "aluguer",
        "acesso as redes", "abastecimento de agua",
    )):
        return "fixed_fee"

    return "consumption"


# Extrai o texto de todas as páginas do PDF.
def get_all_text(pdf_path):
    with pdfplumber.open(pdf_path) as pdf:
        pages = [
            page.extract_text(layout=True) or page.extract_text() or ""
            for page in pdf.pages
        ]
    return pages, "\n".join(pages)


# Procura o número da fatura.
def extract_invoice_number(text, supplier):
    if supplier == "Endesa":
        patterns = [
            r"N[ºo°]\s*Documento\s*:\s*(\d+)",
            r"N[ºo°]\s*Documento\s+(\d+)",
        ]
    else:
        patterns = [
            r"FATURA\s*n[ºo°]?\s*FT\s*([\d]+/[\d]+)",
            r"FATURA\s*n[ºo°]?\s*FT\s*([\d./]+)",
        ]

    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            return match.group(1).strip()

    return None


# Extrai a data de emissão da fatura.
def extract_issue_date(text):
    patterns = [
        rf"Data\s+de\s+Emiss[aã]o\s*:\s*({TEXT_DATE_RE}|{DATE_RE})",
        rf"FATURA\s*n[ºo°]?\s*FT\s*[\d/]+\s*,?\s*emitida\s+em\s+({DATE_RE}|{TEXT_DATE_RE})",
    ]

    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            result = format_date(match.group(1))
            if result:
                return result

    return None


# Extrai as datas inicial e final do período faturado.
def extract_period(text):
    match = re.search(
        r"Per[ií]odo de Fatura[cç][aã]o(?:\s*\([^)]*\))?\s*:\s*"
        rf"({DATE_RE}|{TEXT_DATE_RE})\s+a\s+({DATE_RE}|{TEXT_DATE_RE})",
        text,
        re.IGNORECASE,
    )

    if match:
        return format_date(match.group(1)), format_date(match.group(2))

    return None, None


# Extrai um valor monetário através de uma expressão regular.
def extract_currency_value(pattern, text):
    match = re.search(pattern, text, re.IGNORECASE | re.DOTALL)
    return parse_number(match.group(1)) if match else None


# Extrai quantidades, unidades e custos das tabelas da Endesa.
def parse_endesa_rows(page_text):
    rows = []

    for line in page_text.splitlines():
        unit_match = re.search(
            r"(?P<qty>\d+(?:[.,]\d+)?)\s*(?P<unit>kWh|dias|meses|m[³3])\b",
            line,
            re.IGNORECASE,
        )

        if not unit_match:
            continue

        after_unit = line[unit_match.end():]
        currency_values = CURRENCY_RE.findall(after_unit)

        if not currency_values:
            continue

        qty = parse_number(unit_match.group("qty"))
        unit_raw = unit_match.group("unit").lower()
        unit = {
            "kwh": "kWh",
            "dias": "dias",
            "meses": "meses",
            "m3": "m³",
            "m³": "m³",
        }.get(unit_raw, unit_raw)

        rows.append({
            "amount": qty,
            "unit": unit,
            "cost": parse_number(currency_values[-1]),
        })

    return rows


# Descrições dos itens de eletricidade e respetivas categorias.
ELECTRICITY_DEFINITIONS = [
    ("Termo de Energia (Real)", "consumption"),
    ("Termo de Energia (Real)", "consumption"),
    ("Termo de Potência (6.90 kVA)", "fixed_fee"),
    ("Termo Fixo Acesso às Redes", "fixed_fee"),
    ("Mecanismo DL 33/2022", "tax"),
    ("Contribuição Audiovisual", "tax"),
    ("Taxa Exploração DGEG (DL-4/93)", "tax"),
    ("Imposto Especial Consumo (Real)", "tax"),
]


# Descrições dos itens de gás e respetivas categorias.
GAS_DEFINITIONS = [
    ("Termo de Energia (Real)", "consumption"),
    ("Termo Fixo (Escalão 2)", "fixed_fee"),
    ("Termo Fixo Acesso às Redes", "fixed_fee"),
    ("Imposto Especial Consumo GN (Real)", "tax"),
    ("Taxa Ocupação Subsolo Variável (Real)", "tax"),
    ("Taxa Ocupação Subsolo Fixa (Real)", "tax"),
]


# Associa as linhas extraídas às descrições dos itens da Endesa.
def build_endesa_items(rows, definitions):
    items = []

    for index, row in enumerate(rows):
        if index >= len(definitions):
            continue

        name, category = definitions[index]
        amount, unit = row["amount"], row["unit"]

        items.append({
            "name": name,
            "category": category,
            "amount": amount,
            "unit": unit,
            "cost": row["cost"],
        })

    return items


# Extrai os consumos de eletricidade e gás das faturas Endesa.
def parse_endesa(pages, text, types_map):
    electricity_page = next((
        p for p in pages
        if re.search(r"\bLUZ\b", p, re.IGNORECASE)
        and "Período de Faturação" in p
    ), "")

    gas_page = next((
        p for p in pages
        if re.search(r"\bGÁS\b", p, re.IGNORECASE)
        and "Período de Faturação" in p
    ), "")

    consumptions = []
    invoice_total = 0.0

    # Processa a eletricidade.
    if electricity_page:
        rows = parse_endesa_rows(electricity_page)
        luz_items = build_endesa_items(rows, ELECTRICITY_DEFINITIONS)
        luz_cost = extract_currency_value(
            r"TOTAL DA FATURA DE LUZ\s*([\d.,]+)", text
        )

        if luz_cost is None or luz_cost <= 0:
            luz_cost = extract_currency_value(
                r"Smqu\s*-\s*Processado[^\n]*?([\d.,]+)\s*€",
                electricity_page,
            ) or 0.0

        luz_qty = sum(
            i["amount"] for i in luz_items
            if i["category"] == "consumption" and i["unit"] == "kWh"
        )

        if luz_items:
            consumptions.append({
                "consumption_type_id": types_map.get("eletricidade", 1),
                "amount": luz_qty or None,
                "cost": luz_cost,
                "consumption_items": luz_items,
            })

        invoice_total += luz_cost

    # Processa o gás.
    if gas_page:
        rows = parse_endesa_rows(gas_page)
        gas_items = build_endesa_items(rows, GAS_DEFINITIONS)
        gas_cost = extract_currency_value(
            r"TOTAL DA FATURA DE GÁS\s*([\d.,]+)", text
        )

        if gas_cost is None or gas_cost <= 0:
            gas_cost = extract_currency_value(
                r"MXKI\s*-\s*Processado[^\n]*?([\d.,]+)\s*€",
                gas_page,
            ) or 0.0

        gas_qty = sum(
            i["amount"] for i in gas_items
            if i["category"] == "consumption"
            and i["unit"] in ("kWh", "m³")
        )

        if gas_items:
            consumptions.append({
                "consumption_type_id": types_map.get("gas", 3),
                "amount": gas_qty or None,
                "cost": gas_cost,
                "consumption_items": gas_items,
            })

        invoice_total += gas_cost

    # Se existir um total global, utiliza-o em vez da soma calculada.
    top_total = extract_currency_value(
        r"TOTAL A DEBITAR\s*([\d.,]+)\s*€", text
    )

    if top_total is None:
        top_total = extract_currency_value(
            r"SALDO ATUAL\s*([\d.,]+)\s*€", text
        )

    if top_total is not None and top_total > 0:
        invoice_total = top_total

    return consumptions, invoice_total


# Extrai os serviços e os impostos detalhados das faturas EPAL.
def parse_epal_detail_items(text):
    lines = text.splitlines()

    # Padrões para identificar os serviços da fatura.
    heading_patterns = [
        (
            re.compile(
                r"EPAL\s*-\s*Abastecimento de [ÁA]gua",
                re.IGNORECASE,
            ),
            "EPAL - Abastecimento de Água (Quota Serviço)",
        ),
        (
            re.compile(r"CMLisboa\s*-\s*Saneamento", re.IGNORECASE),
            "CMLisboa - Saneamento (Tarifa de Disponibilidade)",
        ),
        (
            re.compile(
                r"CMLisboa\s*-\s*Res[ií]duos Urbanos",
                re.IGNORECASE,
            ),
            "CMLisboa - Resíduos Urbanos (Tarifa de Disponibilidade)",
        ),
    ]

    items = []
    seen = set()

    for idx, line in enumerate(lines):
        selected = None

        for pattern, item_name in heading_patterns:
            if pattern.search(line):
                selected = (pattern, item_name)
                break

        if not selected:
            continue

        _, item_name = selected

        # Evita registar o mesmo serviço várias vezes.
        if item_name in seen:
            continue

        # Procura os valores nas linhas seguintes ao título.
        row_data = None

        for candidate in lines[idx + 1:idx + 8]:
            date_match = PERIOD_RE.search(candidate)

            if not date_match:
                continue

            remainder = candidate[date_match.end():]
            remainder = re.sub(r"\s+\d+\s*%.*$", "", remainder)
            values = re.findall(NUMBER_RE, remainder)

            if len(values) >= 3:
                qty = parse_number(values[0])
                rate = parse_number(values[1])
                cost = parse_number(values[-1])
                row_data = (qty, cost)
                break

        if row_data is None:
            continue

        quantity, cost = row_data

        items.append({
            "name": item_name,
            "category": "fixed_fee",
            "amount": quantity,
            "unit": "dias",
            "cost": cost,
        })

        seen.add(item_name)

    # Extrai o IVA discriminado na fatura.
    vat_match = re.search(
        rf"IVA\s+(\d+)\s*%\s*\(\s*({NUMBER_RE})\s*x\s*\d+\s*%\s*\)\s*({NUMBER_RE})",
        text,
        re.IGNORECASE,
    )

    if vat_match:
        vat_rate = int(vat_match.group(1))
        base_amount = parse_number(vat_match.group(2))
        vat_cost = parse_number(vat_match.group(3))

        items.append({
            "name": f"IVA {vat_rate}%",
            "category": "tax",
            "amount": base_amount,
            "unit": "EUR de base",
            "cost": vat_cost,
        })

    return items


# Extrai os dados de consumo de água e o total da fatura EPAL.
def parse_epal(pages, text, types_map):
    items = parse_epal_detail_items(text)
    total = extract_currency_value(r"Valor a pagar\s*([\d.,]+)\s*€?", text)

    if total is None:
        total = extract_currency_value(
            r"PAGAMENTO DO TOTAL DA CONTA CLIENTE.*?Montante\.?\s*([\d.,]+)\s*€",
            text,
        )

    if total is None:
        total = 0.0

    # Procura a quantidade de água indicada em litros.
    usage_match = re.search(
        rf"({NUMBER_RE})\s*litros",
        text,
        re.IGNORECASE,
    )
    water_amount = parse_number(usage_match.group(1)) if usage_match else None

    consumptions = []

    if items:
        consumptions.append({
            "consumption_type_id": types_map.get(
                "agua",
                types_map.get("água", 2),
            ),
            "amount": water_amount,
            "unit": "litros",
            "cost": total,
            "consumption_items": items,
        })

    return consumptions, total


# Identifica o fornecedor e reúne os dados principais da fatura.
def extract(pdf_path, types_map=None):
    types_map = types_map or {}
    pages, text = get_all_text(pdf_path)
    text_norm = norm(text)

    # Identifica o fornecedor pelo conteúdo do documento.
    if "endesa" in text_norm:
        supplier = "Endesa"
    elif (
        "epal" in text_norm
        or "empresa portuguesa das aguas livres" in text_norm
    ):
        supplier = "EPAL"
    else:
        supplier = "Outro"

    invoice_number = extract_invoice_number(text, supplier)
    issue_date = extract_issue_date(text)
    period_start, period_end = extract_period(text)

    # Chama o extrator adequado ao fornecedor.
    if supplier == "Endesa":
        consumptions, total_amount = parse_endesa(pages, text, types_map)
    elif supplier == "EPAL":
        consumptions, total_amount = parse_epal(pages, text, types_map)
    else:
        consumptions = []
        total_amount = extract_currency_value(
            r"Valor a pagar\s*([\d.,]+)\s*€?", text
        ) or 0.0

    return {
        "supplier": supplier,
        "invoice_number": invoice_number,
        "issue_date": issue_date,
        "period_start": period_start,
        "period_end": period_end,
        "total_amount": total_amount,
        "consumptions": consumptions,
    }


# Lê os argumentos, executa a extração e apresenta o resultado em JSON.
def main():
    parser = argparse.ArgumentParser(
        description="Extrai dados de faturas Endesa e EPAL para JSON."
    )
    parser.add_argument("pdf_path", help="Caminho para o PDF da fatura")
    parser.add_argument(
        "--types-map",
        default="{}",
        help='JSON opcional com os IDs, p.ex. \'{"eletricidade":1,"gas":2,"agua":3}\'',
    )

    args = parser.parse_args()

    try:
        types_map = json.loads(args.types_map)

        if not isinstance(types_map, dict):
            raise ValueError("--types-map deve ser um objeto JSON")

        result = extract(args.pdf_path, types_map)
        print(json.dumps(result, ensure_ascii=False, indent=2))

    except (OSError, ValueError, json.JSONDecodeError) as exc:
        print(f"Erro: {exc}", file=sys.stderr)
        sys.exit(1)


# Executa main() apenas quando o ficheiro é iniciado diretamente.
if __name__ == "__main__":
    main()