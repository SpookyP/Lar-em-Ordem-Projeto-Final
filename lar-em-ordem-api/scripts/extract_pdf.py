import sys
import json
import re
from pypdf import PdfReader # Biblioteca pura em Python, sem binários problemáticos

def extract_data(pdf_path):
    try:
        reader = PdfReader(pdf_path)
        first_page = reader.pages[0]
        text = first_page.extract_text() or ""

        # Expressão regular para encontrar datas no formato DD/MM/AAAA ou DD-MM-AAAA
        date_pattern = r'\b(\d{2})[-/](\d{2})[-/](\d{4})\b'
        dates_found = re.findall(date_pattern, text)

        issue_date = None
        expiration_date = None

        if dates_found:
            day1, month1, year1 = dates_found[0]
            issue_date = f"{year1}-{month1}-{day1}"

            if len(dates_found) > 1:
                day2, month2, year2 = dates_found[1]
                expiration_date = f"{year2}-{month2}-{day2}"

        return json.dumps({
            "status": "success",
            "extracted_text": text[0:200],
            "issue_date": issue_date,
            "expiration_date": expiration_date
        })
    except Exception as e:
        return json.dumps({"status": "error", "message": str(e)})

if __name__ == "__main__":
    if len(sys.argv) > 1:
        print(extract_data(sys.argv[1]))
