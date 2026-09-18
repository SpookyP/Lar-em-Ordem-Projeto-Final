import sys
import json
import pdfplumber

def extract_data(pdf_path):
    try:
        with pdfplumber.open(pdf_path) as pdf:
            first_page = pdf.pages[0]
            text = first_page.extract_text()

            return json.dumps({
                "status": "success",
                "extracted_text": text[0:200],
                "expiration_date": "2030-12-31"
            })
    except Exception as e:
        return json.dumps({"status": "error", "message": str(e)})

if __name__ == "__main__":
    if len(sys.argv) > 1:
        print(extract_data(sys.argv[1]))
