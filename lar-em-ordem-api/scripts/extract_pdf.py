import sys
import json
import re
import pdfplumber # Uma biblioteca especializada na extração de texto e dados de ficheiros PDF

def extract_data(pdf_path):
    try:
        with pdfplumber.open(pdf_path) as pdf: # Abre o PDF localizado no caminho (pdf_path)
            first_page = pdf.pages[0] # Acede a primeira página do documento
            text = first_page.extract_text() # Extrai o texto legível dessa primeira página

            # Expressão regular para encontrar datas no formato DD/MM/AAAA ou DD-MM-AAAA
            date_pattern = r'\b(\d{2})[-/](\d{2})[-/](\d{4})\b'
            dates_found = re.findall(date_pattern, text)

            expiration_date = None

            # Se encontrar datas, converte a primeira correspondência para o padrão do MySQL (YYYY-MM-DD)
            if dates_found:
                day, month, year = dates_found[0]
                expiration_date = f"{year}-{month}-{day}"

            return json.dumps({
                "status": "success",  # Estado
                "extracted_text": text[0:200], # Os primeiros 200 caracteres do texto extraído
                "expiration_date": expiration_date # Data de validade
            })
    except Exception as e:
        return json.dumps({"status": "error", "message": str(e)})

if __name__ == "__main__":
    if len(sys.argv) > 1:
        print(extract_data(sys.argv[1]))
