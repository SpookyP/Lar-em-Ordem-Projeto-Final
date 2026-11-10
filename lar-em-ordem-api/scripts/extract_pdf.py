#!/usr/bin/env python3
"""
Extração inteligente de dados de documentos de habitação (PDF).

Uso:  python pdf_extractor.py /caminho/ficheiro.pdf [--full-text]
"""
import sys
import json
import re
import unicodedata
from datetime import date

import pdfplumber

MAX_PAGES = 20
MIN_TEXT_CHARS = 30  # abaixo disto assumimos PDF digitalizado (precisa de OCR)

# ----------------------------------------------------------------------------
# Utilitários de texto
# ----------------------------------------------------------------------------

def normalize(text: str) -> str:
    text = text.replace("\u00a0", " ")
    text = re.sub(r"[ \t]+", " ", text)
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()


def fold(text: str) -> str:
    """Minúsculas e sem acentos, MANTENDO o mesmo comprimento do original."""
    return "".join(unicodedata.normalize("NFKD", c)[0] for c in text).lower()


def search(pattern, f, o, group=1):
    m = re.search(pattern, f)
    if not m or m.group(group) is None:
        return None
    return o[m.start(group):m.end(group)].strip() or None


def prune(obj):
    if isinstance(obj, dict):
        out = {k: prune(v) for k, v in obj.items()}
        return {k: v for k, v in out.items() if v not in (None, "", [], {})}
    if isinstance(obj, list):
        return [x for x in (prune(i) for i in obj) if x not in (None, "", [], {})]
    return obj


AMOUNT = r"(\d{1,3}(?:[. ]\d{3})*,\d{2}|\d+,\d{2})"

def to_float(s: str) -> float:
    return float(s.replace(" ", "").replace(".", "").replace(",", "."))

def labelled_amount(f, label_regex):
    m = re.search(r"(?:" + label_regex + r")[^\d\n]{0,40}" + AMOUNT, f)
    return to_float(m.group(1)) if m else None

def all_amounts(f):
    found = re.findall(AMOUNT + r"\s?(?:€|eur\b)", f)
    return [to_float(x) for x in found]


# ----------------------------------------------------------------------------
# Validadores
# ----------------------------------------------------------------------------

def valid_nif(nif: str) -> bool:
    nif = re.sub(r"\D", "", nif)
    if len(nif) != 9 or nif[0] not in "125689" + "3":
        return False
    total = sum(int(nif[i]) * (9 - i) for i in range(8))
    check = 11 - total % 11
    return (0 if check >= 10 else check) == int(nif[8])

def valid_iban(iban: str) -> bool:
    iban = re.sub(r"\s", "", iban).upper()
    if not re.fullmatch(r"PT\d{23}", iban):
        return False
    rearranged = iban[4:] + iban[:4]
    numeric = "".join(str(int(c, 36)) for c in rearranged)
    return int(numeric) % 97 == 1


# ----------------------------------------------------------------------------
# Datas
# ----------------------------------------------------------------------------

MONTHS = {
    "janeiro": 1, "fevereiro": 2, "marco": 3, "abril": 4, "maio": 5, "junho": 6,
    "julho": 7, "agosto": 8, "setembro": 9, "outubro": 10, "novembro": 11, "dezembro": 12,
}

IGNORE_DATE_CONTEXT = [
    "cartao de cidadao", "cartao do cidadao", "bilhete de identidade",
    "passaporte", "titulo de residencia", "autorizacao de residencia",
    "carta de conducao", "cedula", "identificacao civil",
    "data de nascimento", "nascid", "natural de",
    "decreto-lei", "decreto lei", "portaria n", "lei n",
    "diario da republica", "regulamento n", "alvara de construcao n",
]

DATE_LABELS = {
    "expiration_date": [
        ("validade do certificado", 3), ("validade do documento", 3),
        ("fim do contrato", 3), ("termo do contrato", 3), ("fim de vigencia", 3),
        ("data de validade", 2), ("data de vencimento", 2), ("data de termo", 2),
        ("valido ate", 1), ("valida ate", 1), ("validade", 1),
        ("expira", 1), ("caduca", 1), ("termo", 1), ("vencimento", 1),
    ],
    "due_date": [
        ("data limite de pagamento", 3), ("limite de pagamento", 2),
        ("pagar ate", 2), ("data limite", 1),
    ],
    "start_date": [
        ("inicio de vigencia", 3), ("inicio do contrato", 3),
        ("produz efeitos a partir de", 3), ("com inicio em", 2),
        ("a partir de", 1), ("inicio", 1),
    ],
    "issue_date": [
        ("data de emissao", 3), ("data do documento", 3), ("data da fatura", 3),
        ("data do contrato", 3), ("feito e assinado", 3),
        ("celebrado em", 2), ("outorgado em", 2), ("lavrado em", 2),
        ("emitid", 1), ("emissao", 1), ("lavrad", 1),
        ("outorgad", 1), ("celebrad", 1), ("passad", 1), ("assinad", 1),
    ],
}

NO_EXPIRATION_DOC_TYPES = {
    "escritura", "caderneta_predial", "licenca_utilizacao", "ficha_tecnica_habitacao"
}


def make_date(y, m, d):
    try:
        if 1900 <= y <= 2100:
            return date(y, m, d)
    except ValueError:
        pass
    return None


def find_dates(f):
    """Devolve uma lista de tuplos: (start_pos, end_pos, date_obj)."""
    found = []
    for m in re.finditer(r"\b(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})\b", f):
        d = make_date(int(m.group(3)), int(m.group(2)), int(m.group(1)))
        if d: found.append((m.start(), m.end(), d))

    for m in re.finditer(r"\b(\d{4})-(\d{2})-(\d{2})\b", f):
        d = make_date(int(m.group(1)), int(m.group(2)), int(m.group(3)))
        if d: found.append((m.start(), m.end(), d))

    for m in re.finditer(r"\b(\d{1,2})\s+de\s+([a-z]+)\s+de\s+(\d{4})\b", f):
        month = MONTHS.get(m.group(2))
        if month:
            d = make_date(int(m.group(3)), month, int(m.group(1)))
            if d: found.append((m.start(), m.end(), d))

    return sorted(found, key=lambda x: x[0])


def get_context_window(f: str, pos: int, prev_date_end: int, max_back: int = 120) -> str:
    """Extrai texto seguro para trás, parando em barreiras para evitar misturar contextos."""
    start = max(prev_date_end, pos - max_back)

    # Parar em ponto final real (. seguido de espaço ou de nova linha)
    last_dot = max(f.rfind(". ", start, pos), f.rfind(".\n", start, pos))
    if last_dot != -1 and (pos - last_dot) > 15:
        start = last_dot + 2

    # Parar noutro parágrafo (dupla quebra de linha)
    last_para = f.rfind("\n\n", start, pos)
    if last_para != -1:
        start = last_para

    # Transforma quebras de linha em espaços para manter as frases unidas
    return f[start:pos].replace("\n", " ")


def is_ignored_date_context(window_before: str, context_after: str) -> bool:
    full_context = window_before + " " + context_after
    for bad_kw in IGNORE_DATE_CONTEXT:
        if bad_kw in full_context:
            return True

    if re.search(r"\b(?:cc|bi|nif|nic)\b.{0,45}(?:valido|validade)", window_before):
        return True

    return False


def classify_date(window_before: str):
    best_field, best_score, best_idx = None, 0, -1
    for field, kw_list in DATE_LABELS.items():
        for kw, weight in kw_list:
            i = window_before.rfind(kw)
            if i != -1:
                distance = len(window_before) - (i + len(kw))
                distance_bonus = 2 if distance <= 15 else (1 if distance <= 35 else 0)
                score = (weight * 10) + distance_bonus
                if score > best_score or (score == best_score and i > best_idx):
                    best_field, best_score, best_idx = field, score, i
    return best_field, best_score


def add_years(d: date, years: int) -> date:
    try:
        return d.replace(year=d.year + years)
    except ValueError:
        return d.replace(year=d.year + years, day=28)


def add_months(d: date, months: int) -> date:
    total_months = (d.month - 1) + months
    year = d.year + total_months // 12
    month = total_months % 12 + 1
    try:
        return d.replace(year=year, month=month)
    except ValueError:
        for day in (30, 29, 28):
            try:
                return d.replace(year=year, month=month, day=day)
            except ValueError:
                continue
        return d


def extract_dates(f: str, doc_type: str = "desconhecido"):
    dates = find_dates(f)
    best_by_field = {}
    unclassified = []

    text_len = max(1, len(f))
    prev_date_end = 0

    for start_pos, end_pos, d in dates:
        context_after = f[end_pos:min(len(f), end_pos + 30)].replace("\n", " ")
        window_before = get_context_window(f, start_pos, prev_date_end, 120)

        prev_date_end = end_pos # A próxima data já não pode ler antes disto

        if is_ignored_date_context(window_before, context_after):
            continue

        field, score = classify_date(window_before)

        if field:
            # Bónus para datas no final de contratos/escrituras
            if start_pos / text_len >= 0.70:
                score += 2

            prev = best_by_field.get(field)
            if not prev or score >= prev[0]:
                best_by_field[field] = (score, start_pos, d)
        else:
            unclassified.append((start_pos, d))

    by_field = {field: val[2] for field, val in best_by_field.items()}

    # Fallback seguro para issue_date
    if "issue_date" not in by_field and unclassified:
        if doc_type in ("contrato_arrendamento", "escritura", "condominio"):
            by_field["issue_date"] = unclassified[-1][1]
        else:
            by_field["issue_date"] = unclassified[0][1]

    # Impedir validações falsas
    if doc_type in NO_EXPIRATION_DOC_TYPES:
        by_field.pop("expiration_date", None)

    return by_field, [d.isoformat() for _, _, d in dates][:15]


# ----------------------------------------------------------------------------
# Classificação do tipo de documento
# ----------------------------------------------------------------------------

DOC_TYPES = {
    "certificado_energetico": ["certificado energetico", "sce", "classe energetica", "adene",
                               "desempenho energetico"],
    "caderneta_predial": ["caderneta predial", "valor patrimonial tributario", "artigo matricial",
                          "matriz predial", "servicos de financas"],
    "certidao_registo_predial": ["certidao permanente", "conservatoria do registo predial",
                                 "descricao predial", "registo predial", "inscricoes"],
    "escritura": ["escritura", "compra e venda", "notario", "outorgantes", "cartorio notarial",
                  "documento particular autenticado"],
    "contrato_arrendamento": ["contrato de arrendamento", "senhorio", "arrendatario",
                              "renda mensal", "caucao"],
    "apolice_seguro": ["apolice", "seguro multirriscos", "seguradora", "capital seguro",
                       "tomador do seguro", "premio"],
    "imi": ["imposto municipal sobre imoveis", "imi", "nota de cobranca"],
    "licenca_utilizacao": ["licenca de utilizacao", "alvara de utilizacao", "camara municipal",
                           "alvara"],
    "ficha_tecnica_habitacao": ["ficha tecnica da habitacao"],
    "condominio": ["condominio", "administracao do condominio", "ata", "permilagem", "quotas",
                   "assembleia de condominos"],
    "fatura_eletricidade": ["eletricidade", "kwh", "potencia contratada", "energia ativa", "cpe"],
    "fatura_gas": ["gas natural", "cui", "gas"],
    "fatura_agua": ["abastecimento de agua", "saneamento", "tarifa de agua", "residuos", "agua"],
    "fatura_telecom": ["fibra", "internet", "televisao", "telemovel", "telecomunicacoes"],
    "garantia_equipamento": ["garantia", "prazo de garantia", "numero de serie"],
}

DOC_LABELS = {
    "certificado_energetico": "Certificado Energético",
    "caderneta_predial": "Caderneta Predial",
    "certidao_registo_predial": "Certidão Permanente",
    "escritura": "Escritura",
    "contrato_arrendamento": "Contrato de Arrendamento",
    "apolice_seguro": "Apólice de Seguro",
    "imi": "IMI",
    "licenca_utilizacao": "Licença de Utilização",
    "ficha_tecnica_habitacao": "Ficha Técnica da Habitação",
    "condominio": "Condomínio",
    "fatura_eletricidade": "Fatura Eletricidade",
    "fatura_gas": "Fatura Gás",
    "fatura_agua": "Fatura Água",
    "fatura_telecom": "Fatura Telecomunicações",
    "garantia_equipamento": "Garantia de Equipamento",
    "desconhecido": "Documento",
}


def classify(f):
    scores = {}
    for doc_type, kws in DOC_TYPES.items():
        scores[doc_type] = sum(1 for k in kws if re.search(r"\b" + re.escape(k) + r"\b", f))
    best = max(scores, key=scores.get)
    if scores[best] == 0:
        return "desconhecido", 0.0
    return best, round(min(1.0, scores[best] / 3), 2)


# ----------------------------------------------------------------------------
# Entidades comuns (todos os tipos)
# ----------------------------------------------------------------------------

PROVIDERS = {
    "seguradoras": ["fidelidade", "tranquilidade", "ageas", "allianz", "zurich", "generali",
                    "liberty", "mapfre", "lusitania", "ok teleseguros", "una seguros", "logo"],
    "energia_agua_telecom": ["edp", "endesa", "iberdrola", "galp", "goldenergy", "repsol",
                             "su eletricidade", "luzboa", "epal", "simas", "aguas do porto",
                             "aguas de gaia", "nos", "meo", "vodafone", "nowo"],
}


def find_provider(f):
    for group in PROVIDERS.values():
        for name in group:
            if re.search(r"\b" + re.escape(name) + r"\b", f):
                return name.title() if len(name) > 3 else name.upper()
    return None


def extract_identifiers(f, o):
    nifs = []
    for m in re.finditer(r"(?:nif|nipc|contribuinte)\W{0,25}?(\d{3} ?\d{3} ?\d{3})\b", f):
        nif = re.sub(r"\D", "", m.group(1))
        if valid_nif(nif) and nif not in nifs:
            nifs.append(nif)

    ibans = []
    for m in re.finditer(r"pt\d{2}(?: ?\d){21}", f):
        iban = re.sub(r"\s", "", o[m.start():m.end()]).upper()
        if valid_iban(iban) and iban not in ibans:
            ibans.append(iban)

    return {"nifs": nifs, "ibans": ibans}


def extract_property(f, o):
    cps = re.findall(r"\b\d{4}-\d{3}\b", f)
    return {
        "codigo_postal": cps[0] if cps else None,
        "freguesia": search(r"freguesia(?: de| da| do)?[:\s]+([^\n,]{3,60})", f, o),
        "concelho": search(r"concelho(?: de| da| do)?[:\s]+([^\n,]{3,40})", f, o),
        "artigo_matricial": search(r"artigo(?: matricial)?\W{0,6}(?:n[ºo.]*\W{0,3})?(\d{1,6}(?:-[a-z])?)\b", f, o),
        "descricao_predial": search(r"descricao(?: predial)?\W{0,6}(?:n[ºo.]*\W{0,3})?(\d{1,6}(?:/\d{4,8})?)", f, o),
        "fracao": search(r"fracao(?: autonoma)?\W{0,4}([a-z]{1,3})\b", f, o),
        "tipologia": search(r"tipologia\W{0,6}(t\d(?:\+\d)?)", f, o),
        "area_bruta_m2": search(r"area bruta[^\d\n]{0,40}(\d+(?:[.,]\d+)?)\s?m2", f, o),
        "area_util_m2": search(r"area (?:util|privativa)[^\d\n]{0,40}(\d+(?:[.,]\d+)?)\s?m2", f, o),
        "area_terreno_m2": search(r"area (?:do terreno|total do terreno|de implantacao)[^\d\n]{0,40}(\d+(?:[.,]\d+)?)\s?m2", f, o),
        "ano_construcao": search(r"ano de (?:construcao|conclusao)\W{0,10}(\d{4})", f, o),
    }


# ----------------------------------------------------------------------------
# Extratores específicos por tipo
# ----------------------------------------------------------------------------

def ex_certificado_energetico(f, o):
    return {
        "numero_certificado": search(r"\b(sce\d{6,})\b", f, o),
        "classe_energetica": (search(r"classe energetica[^a-z0-9]{0,15}([a-f][+-]?)(?![a-z0-9])", f, o) or "").upper() or None,
        "validade_anos": 10,
    }


def ex_caderneta_predial(f, o):
    return {
        "valor_patrimonial_tributario": labelled_amount(f, r"valor patrimonial tributario"),
        "afetacao": search(r"afetacao\W{0,5}(habitacao|comercio|servicos|industria|arrumos|estacionamento|garagem)", f, o),
        "ano_inscricao_matriz": search(r"(?:inscricao|inscrito) na matriz[^\d\n]{0,25}(\d{4})", f, o),
        "pisos": search(r"n[ºo.]* de pisos\W{0,5}(\d{1,3})", f, o),
        "divisoes": search(r"n[ºo.]* de divisoes\W{0,5}(\d{1,3})", f, o),
    }


def ex_certidao_registo_predial(f, o):
    codes = re.findall(r"\b\d{4}-\d{4}-\d{4}\b", f)
    return {
        "codigo_acesso_certidao": codes[0] if codes else None,
        "tem_hipoteca": bool(re.search(r"\bhipoteca\b", f)),
        "tem_penhora": bool(re.search(r"\bpenhora\b", f)),
        "tem_usufruto": bool(re.search(r"\busufruto\b", f)),
        "conservatoria": search(r"conservatoria do registo predial (?:de |do |da )?([^\n]{3,40})", f, o),
    }


def ex_escritura(f, o):
    return {
        "preco": labelled_amount(f, r"(?:pelo )?preco de"),
        "tipo_ato": search(r"(compra e venda|doacao|permuta|hipoteca|partilha)", f, o),
        "cartorio_ou_notario": search(r"notari[oa]\W{1,5}([a-z][^\n]{4,60})", f, o),
    }


def ex_contrato_arrendamento(f, o):
    return {
        "renda_mensal": labelled_amount(f, r"renda(?: mensal)?"),
        "caucao": labelled_amount(f, r"cau[cç]ao"),
        "duracao": search(r"(?:prazo|duracao) de (\d+\s?(?:anos?|meses))", f, o),
        "dia_pagamento": search(r"ate ao dia (\d{1,2})", f, o),
    }


def ex_apolice_seguro(f, o):
    apolice = search(r"apolice\W{0,4}(?:n[ºo.°]*\W{0,4})?([a-z0-9][a-z0-9./-]{4,24})", f, o)
    if apolice and not any(c.isdigit() for c in apolice):
        apolice = None
    return {
        "numero_apolice": apolice,
        "seguradora": find_provider(f),
        "capital_edificio": labelled_amount(f, r"capital (?:seguro )?(?:do )?(?:edificio|imovel)"),
        "capital_recheio": labelled_amount(f, r"capital (?:seguro )?(?:do )?(?:recheio|conteudo)"),
        "premio_total": labelled_amount(f, r"premio total|premio anual|premio"),
        "periodicidade": search(r"(anual|semestral|trimestral|mensal)", f, o),
    }


def ex_imi(f, o):
    return {
        "ano_imposto": search(r"\bimi\b\W{0,10}(\d{4})", f, o) or search(r"ano\W{0,5}(20\d{2})", f, o),
        "valor_total": labelled_amount(f, r"(?:total a pagar|valor total|total)"),
        "valor_patrimonial_tributario": labelled_amount(f, r"valor patrimonial tributario"),
        "taxa": search(r"taxa\W{0,10}(\d+(?:[,.]\d+)?\s?%)", f, o),
    }


def ex_licenca_utilizacao(f, o):
    return {
        "numero_licenca": search(r"(?:licenca|alvara)(?: de utilizacao)?\W{0,6}n[ºo.]*\W{0,3}(\d+/\d{2,4})", f, o),
        "camara_municipal": search(r"camara municipal d[eoa]s? ([^\n,]{3,40})", f, o),
        "fim_autorizado": search(r"(habitacao|comercio|servicos|industria)", f, o),
    }


def ex_condominio(f, o):
    return {
        "permilagem": search(r"permilagem\W{0,10}(\d+(?:[.,]\d+)?)", f, o),
        "quota_mensal": labelled_amount(f, r"quota(?: mensal)?"),
        "administracao": search(r"administra[cç]ao\W{1,5}([a-z][^\n]{3,50})", f, o),
    }


def _fatura_comum(f, o):
    return {
        "fornecedor": find_provider(f),
        "numero_fatura": search(r"\bfatura(?:-recibo)?\b\W{0,6}(?:n[ºo.°]*)?\W{0,4}([a-z]{1,4} ?\d{2,4}/\d{2,})", f, o),
        "total_a_pagar": labelled_amount(f, r"(?:total a pagar|valor a pagar|montante a pagar|total da fatura)"),
        "entidade_multibanco": search(r"entidade\W{0,5}(\d{5})\b", f, o),
        "referencia_multibanco": search(r"referencia\W{0,5}(\d{3}(?: ?\d{3}){2})\b", f, o),
        "periodo_faturacao": search(r"(?:periodo|consumo)[^\n]{0,15}?(\d{2}[/.-]\d{2}[/.-]\d{4}\s?(?:a|-|ate)\s?\d{2}[/.-]\d{2}[/.-]\d{4})", f, o),
    }


def ex_fatura_eletricidade(f, o):
    d = _fatura_comum(f, o)
    d.update({
        "cpe": search(r"cpe\W{0,10}(pt ?\d{4}(?: ?\d{4}){4} ?[a-z]{2})", f, o),
        "potencia_contratada_kva": search(r"potencia contratada\W{0,10}(\d+(?:[.,]\d+)?)\s?kva", f, o),
        "consumo_kwh": search(r"(\d+(?:[.,]\d+)?)\s?kwh", f, o),
        "tarifa": search(r"(simples|bi-?horario|tri-?horario)", f, o),
    })
    return d


def ex_fatura_gas(f, o):
    d = _fatura_comum(f, o)
    d.update({
        "cui": search(r"cui\W{0,10}(pt ?\d{16} ?[a-z]{2})", f, o),
        "consumo_m3": search(r"(\d+(?:[.,]\d+)?)\s?m3", f, o),
        "escalao": search(r"escalao\W{0,5}(\d)", f, o),
    })
    return d


def ex_fatura_agua(f, o):
    d = _fatura_comum(f, o)
    d.update({
        "consumo_m3": search(r"(\d+(?:[.,]\d+)?)\s?m3", f, o),
        "numero_contador": search(r"contador\W{0,8}n?[ºo.]*\W{0,3}([a-z0-9]{5,15})", f, o),
    })
    return d


def ex_fatura_telecom(f, o):
    d = _fatura_comum(f, o)
    d.update({"numero_cliente": search(r"(?:n[ºo.]* de cliente|cliente n[ºo.]*)\W{0,5}(\d{5,12})", f, o)})
    return d


def ex_garantia(f, o):
    return {
        "numero_serie": search(r"numero de serie\W{0,5}([a-z0-9\-]{5,30})", f, o),
        "prazo_garantia": search(r"garantia de (\d+\s?(?:anos?|meses))", f, o),
        "marca_modelo": search(r"modelo\W{0,5}([a-z0-9][^\n]{2,40})", f, o),
    }


EXTRACTORS = {
    "certificado_energetico": ex_certificado_energetico,
    "caderneta_predial": ex_caderneta_predial,
    "certidao_registo_predial": ex_certidao_registo_predial,
    "escritura": ex_escritura,
    "contrato_arrendamento": ex_contrato_arrendamento,
    "apolice_seguro": ex_apolice_seguro,
    "imi": ex_imi,
    "licenca_utilizacao": ex_licenca_utilizacao,
    "condominio": ex_condominio,
    "fatura_eletricidade": ex_fatura_eletricidade,
    "fatura_gas": ex_fatura_gas,
    "fatura_agua": ex_fatura_agua,
    "fatura_telecom": ex_fatura_telecom,
    "garantia_equipamento": ex_garantia,
}

DEFAULT_VALIDITY_YEARS = {"certificado_energetico": 10}


def infer_expiration_from_duration(base_date: date, duration_str: str) -> date:
    """Calcula a data de validade a partir de strings como '3 anos' ou '24 meses'."""
    if not base_date or not duration_str:
        return None
    m = re.search(r"(\d+)\s*(anos?|meses)", duration_str.lower())
    if not m:
        return None
    val = int(m.group(1))
    unit = m.group(2)
    return add_years(base_date, val) if unit.startswith("ano") else add_months(base_date, val)


# ----------------------------------------------------------------------------
# Análise
# ----------------------------------------------------------------------------

def analyze_text(text: str, today: date = None) -> dict:
    today = today or date.today()
    o = normalize(text)
    f = fold(o)

    doc_type, confidence = classify(f)
    by_field, all_dates = extract_dates(f, doc_type)

    extractor = EXTRACTORS.get(doc_type)
    details = extractor(f, o) if extractor else {}

    issue = by_field.get("issue_date")
    start = by_field.get("start_date")
    expiration = by_field.get("expiration_date")
    expiration_inferred = False

    # Inferência por validade fixa do tipo de documento
    if not expiration and issue and doc_type in DEFAULT_VALIDITY_YEARS:
        expiration = add_years(issue, DEFAULT_VALIDITY_YEARS[doc_type])
        expiration_inferred = True

    # Inferência por prazo explícito no texto (Contratos ou Garantias)
    if not expiration:
        duration_str = details.get("duracao") or details.get("prazo_garantia")
        base_for_duration = start or issue
        inferred = infer_expiration_from_duration(base_for_duration, duration_str)
        if inferred:
            expiration = inferred
            expiration_inferred = True

    alerts, days_left = [], None
    if expiration:
        days_left = (expiration - today).days
        if days_left < 0:
            alerts.append("documento_expirado")
        elif days_left <= 90:
            alerts.append("expira_em_menos_de_90_dias")

    provider = details.get("seguradora") or details.get("fornecedor") or find_provider(f)
    prop = extract_property(f, o)

    title_parts = [DOC_LABELS[doc_type]]
    if provider:
        title_parts.append(provider)
    if issue:
        title_parts.append(str(issue.year))

    return prune({
        "document_type": doc_type,
        "document_type_confidence": confidence,
        "suggested_title": " - ".join(title_parts),
        "issue_date": issue.isoformat() if issue else None,
        "expiration_date": expiration.isoformat() if expiration else None,
        "expiration_inferred": expiration_inferred or None,
        "start_date": start.isoformat() if start else None,
        "due_date": by_field.get("due_date").isoformat() if "due_date" in by_field else None,
        "days_to_expiration": days_left,
        "alerts": alerts,
        "provider": provider,
        "identifiers": extract_identifiers(f, o),
        "property": prop,
        "details": details,
        "amounts_found": sorted(set(all_amounts(f)), reverse=True)[:10],
        "all_dates": all_dates,
    })


def extract_data(pdf_path: str, full_text: bool = False) -> str:
    try:
        with pdfplumber.open(pdf_path) as pdf:
            pages_text = []
            for page in pdf.pages[:MAX_PAGES]:
                extracted = page.extract_text()
                if extracted:
                    pages_text.append(extracted)
            text = "\n".join(pages_text)

            result = {
                "status": "success",
                "pages": len(pdf.pages),
                "extracted_text": normalize(text)[:200],
            }

            if len(text.strip()) < MIN_TEXT_CHARS:
                result["needs_ocr"] = True
                result["warning"] = "PDF sem texto selecionável (provavelmente digitalizado)."
                return json.dumps(result, ensure_ascii=False)

            result.update(analyze_text(text))
            if full_text:
                result["full_text"] = normalize(text)
            return json.dumps(result, ensure_ascii=False)

    except Exception as e:
        return json.dumps({"status": "error", "message": str(e)}, ensure_ascii=False)


if __name__ == "__main__":
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8")
    if len(sys.argv) > 1:
        print(extract_data(sys.argv[1], full_text="--full-text" in sys.argv))
    else:
        print(json.dumps({"status": "error", "message": "Caminho do PDF em falta."}))
