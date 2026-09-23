import sys
import json
import pdfplumber """ Uma biblioteca especializada na extração de texto e dados de ficheiros PDF """

def extract_data(pdf_path):
    try:
        with pdfplumber.open(pdf_path) as pdf: """ Abre o PDF localizado no caminho (pdf_path) """
            first_page = pdf.pages[0] """ Acede a primeira página do documento """
            text = first_page.extract_text() """ Extrai todo o texto legível dessa primeira página """

            return json.dumps({
                "status": "success",  """ Estado """
                "extracted_text": text[0:200], """ Os primeiros 200 caracteres do texto extraído """
                "expiration_date": "2030-12-31" """ Uma data de validade simulada (placeholder) """
            })
    except Exception as e:
        return json.dumps({"status": "error", "message": str(e)})

if __name__ == "__main__":
    if len(sys.argv) > 1:
        print(extract_data(sys.argv[1]))
