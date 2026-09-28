import json
import re
import pdfplumber
import requests

# --- CONFIGURAÇÕES ---
API_URL = "http://127.0.0.1:8000/api/invoices"
# Por enquanto introdução de Bearer Token e File Paths Manual para testes (File Paths disponiveis = fatura_teste.pdf e fatura_gas_teste_pdf)
BEARER_TOKEN = " "
PDF_FILE_PATH = "fatura_teste.pdf"


def format_date(date_str):
  """Converte datas de DD/MM/AAAA para o formato de base de dados AAAA-MM-DD."""
  if not date_str:
    return None
  if "/" in date_str:
    parts = date_str.strip().split("/")
    return f"{parts[2]}-{parts[1]}-{parts[0]}"
  return date_str


def parse_money(value_str):
  """Converte strings numéricas em formato monetário (pt-PT) para float."""
  if not value_str:
    return 0.0
  clean_str = (
      str(value_str).strip().replace("|", "").replace("EUR", "").replace("€", "")
  )
  if "," in clean_str and "." in clean_str:
    clean_str = clean_str.replace(".", "").replace(",", ".")
  elif "," in clean_str:
    clean_str = clean_str.replace(",", ".")
  try:
    return float(clean_str)
  except ValueError:
    return 0.0


def extract_invoice_data(pdf_path):
  """Extrai o cabeçalho e as linhas de consumo reais do PDF de forma dinâmica."""
  extracted_text = ""

  # 1. Leitura integral do texto contido no PDF
  with pdfplumber.open(pdf_path) as pdf:
    for page in pdf.pages:
      text = page.extract_text()
      if text:
        extracted_text += text + "\n"

  lines = [line.strip() for line in extracted_text.split("\n") if line.strip()]
  supplier = lines[0] if lines else "LUSO ENERGIA, S.A."

  # --- CABEÇALHO E METADADOS DA FATURA ---
  inv_match = re.search(r"N\.º\s*Fatura:\s*([^\n]+)", extracted_text, re.IGNORECASE)
  invoice_number = (
      inv_match.group(1).strip() if inv_match else "DESCONHECIDO"
  )

  issue_match = re.search(
      r"Data\s*Emissão:\s*(\d{2}/\d{2}/\d{4})", extracted_text, re.IGNORECASE
  )
  issue_date = format_date(issue_match.group(1)) if issue_match else None

  period_match = re.search(
      r"Período:\s*(\d{2}/\d{2}/\d{4})\s*a\s*(\d{2}/\d{2}/\d{4})",
      extracted_text,
      re.IGNORECASE,
  )
  period_start = format_date(period_match.group(1)) if period_match else issue_date
  period_end = format_date(period_match.group(2)) if period_match else issue_date

  total_match = re.search(
      r"TOTAL\s*A\s*PAGAR:?[\s\n\|]*([\d\.,]+)", extracted_text, re.IGNORECASE
  )
  total_amount = parse_money(total_match.group(1)) if total_match else 0.0

  # --- EXTRAÇÃO DE CONSUMOS REAIS ---
  consumptions = []

  for line in lines:
    line_lower = line.lower()

    # Ignora elementos irrelevantes (cabeçalhos, subtotais, impostos e taxas)
    ignored_terms = [
        "descrição",
        "subtotal",
        "iva",
        "total a pagar",
        "potência contratada",
        "imposto",
        "iec",
    ]
    if any(term in line_lower for term in ignored_terms):
      continue

    # Processa apenas linhas com unidades de consumo de recursos
    if "kwh" in line_lower or "m3" in line_lower or "m³" in line_lower:
      # Remove texto entre parênteses (ex: "Escalão 2") para evitar números falsos na quantidade
      line_clean_text = re.sub(r"\([^)]*\)", "", line)
      
      numbers = re.findall(r"[\d\.,]+", line_clean_text)
      valid_nums = [parse_money(n) for n in numbers if parse_money(n) > 0]

      if len(valid_nums) >= 2:
        amount = valid_nums[0]  # Quantidade consumida limpa
        cost = valid_nums[-1]  # Custo total da linha

        # Determinação correta do tipo de consumo
        if "gás" in line_lower or "gas" in line_lower:
          type_id = 3
        elif "água" in line_lower or "m3" in line_lower or "m³" in line_lower:
          type_id = 2
        else:
          type_id = 1  # Eletricidade

        consumptions.append({
            "consumption_type_id": type_id,
            "period_start": period_start,
            "period_end": period_end,
            "amount": amount,
            "cost": cost,
        })

  return {
      "property_id": 1,
      "invoice_number": invoice_number,
      "issue_date": issue_date,
      "period_start": period_start,
      "period_end": period_end,
      "total_amount": total_amount,
      "supplier": supplier,
      "file_path": pdf_path,
      "consumptions": consumptions,
  }


# --- EXECUÇÃO E TESTE DE ENVIO ---
if __name__ == "__main__":
  print("1. A ler o PDF e a extrair consumos...")
  payload = extract_invoice_data(PDF_FILE_PATH)

  print("\nDados extraídos e limpos:")
  print(json.dumps(payload, indent=2, ensure_ascii=False))

  print("\n2. A enviar para a API Laravel...")
  headers = {
      "Authorization": f"Bearer {BEARER_TOKEN}",
      "Accept": "application/json",
      "Content-Type": "application/json",
  }

  response = requests.post(API_URL, json=payload, headers=headers)

  print(f"\nStatus Code: {response.status_code}")
  print("Resposta da API:")
  try:
    print(response.json())
  except Exception:
    print(response.text)