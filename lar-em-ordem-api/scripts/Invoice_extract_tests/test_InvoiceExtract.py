"""Extrai os dados de uma fatura em PDF e escreve JSON no stdout (chamado pelo Laravel).
  Uso: python script.py <pdf>    Stdin: JSON com os tipos de consumo, ex.: {"eletricidade": 1}
  Os campos que não encontra ficam null."""

import json
import re
import sys
import unicodedata  

import pdfplumber

# No Windows o stdin/stdout não são UTF-8 por defeito
sys.stdin.reconfigure(encoding="utf-8")
sys.stdout.reconfigure(encoding="utf-8")

# Linhas que não são consumos. Palavras inteiras, para "iva" não apanhar "ativa".
IGNORED = re.compile(
    r"\b(descricao|subtotal|iva|total a pagar|potencia contratada|imposto|iec)\b"
)


def norm(text):
  """Minúsculas e sem acentos: 'Gás' -> 'gas', 'm³' -> 'm3'."""
  decomposed = unicodedata.normalize("NFKD", text)
  return decomposed.encode("ascii", "ignore").decode("ascii").lower()


def format_date(date_str):
  """Converte datas de DD/MM/AAAA para AAAA-MM-DD."""
  if not date_str:
    return None
  day, month, year = date_str.strip().split("/")
  return f"{year}-{month}-{day}"


def parse_number(value_str):
  """Converte números em formato pt-PT ('1.234,56' ou '22,50') para float."""
  clean = str(value_str).strip().replace("|", "").replace("EUR", "").replace("€", "")
  if "," in clean and "." in clean:
    clean = clean.replace(".", "").replace(",", ".")
  elif "," in clean:
    clean = clean.replace(",", ".")
  # Só com ponto, é lido como decimal: "22.50" = 22,5 mas "1.234" = 1,234
  try:
    return float(clean)
  except ValueError:
    return 0.0


def consumption_type(line_norm):
  """Nome do tipo de consumo de uma linha (chave em types)."""
  if re.search(r"\bgas\b", line_norm):
    return "gas"
  if "agua" in line_norm or "m3" in line_norm:
    return "agua"
  return "eletricidade"


def extract(pdf_path, types):
  # 1. Texto completo do PDF
  text = ""
  with pdfplumber.open(pdf_path) as pdf:
    for page in pdf.pages:
      text += (page.extract_text() or "") + "\n"

  lines = [line.strip() for line in text.split("\n") if line.strip()]

  # 2. Cabeçalho (None se não encontrar)
  def find(pattern):
    match = re.search(pattern, text, re.IGNORECASE)
    return match.groups() if match else None

  invoice_number = find(r"N\.º\s*Fatura:\s*([^\n]+)")
  issue_date = find(r"Data\s*Emissão:\s*(\d{2}/\d{2}/\d{4})")
  period = find(r"Período:\s*(\d{2}/\d{2}/\d{4})\s*a\s*(\d{2}/\d{2}/\d{4})")
  total = find(r"TOTAL\s*A\s*PAGAR:?[\s\n\|]*([\d\.,]+)")

  # 3. Linhas de consumo
  consumptions = []
  for line in lines:
    line_norm = norm(line)

    if IGNORED.search(line_norm):
      continue
    if "kwh" not in line_norm and "m3" not in line_norm:
      continue

    # Sem o texto entre parênteses (ex.: "(Escalão 2)"), que não é quantidade
    numbers = re.findall(r"[\d\.,]+", re.sub(r"\([^)]*\)", "", line))
    values = [parse_number(n) for n in numbers if parse_number(n) > 0]
    if len(values) < 2:
      continue

    consumptions.append({
        "consumption_type_id": types.get(consumption_type(line_norm)),  # null se o tipo não existe
        "amount": values[0],      # quantidade consumida
        "cost": values[-1],       # custo total da linha
        "description": line,      # linha original, para o utilizador conferir
    })

  return {
      "supplier": lines[0] if lines else None,  # assume a primeira linha do PDF
      "invoice_number": invoice_number[0].strip() if invoice_number else None,
      "issue_date": format_date(issue_date[0]) if issue_date else None,
      "period_start": format_date(period[0]) if period else None,
      "period_end": format_date(period[1]) if period else None,
      "total_amount": parse_number(total[0]) if total else None,
      "consumptions": consumptions,
  }


if __name__ == "__main__":
  if len(sys.argv) != 2:
    sys.exit("Uso: extract_invoice.py <caminho_do_pdf>")

  # Tipos enviados pelo Laravel, com os nomes sem acentos
  types = {norm(name): type_id for name, type_id in json.loads(sys.stdin.read() or "{}").items()}

  print(json.dumps(extract(sys.argv[1], types), ensure_ascii=False))
  