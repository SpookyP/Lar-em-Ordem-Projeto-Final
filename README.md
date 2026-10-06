# 🏡 Lar em Ordem

Introdução

> *Projeto académico produzido na ATEC - Academia de Formação*


## 🌟 Highlights

- Some feature made easy!
- This problem handled
- etc.


## ℹ️ Resumo

Explain the project.


### ✍️ Autores

Este Projeto foi realizado por:
- Madalena Ferreira
- Leonor Soares
- João Ribeiro
- Diogo Jarrais
- Rafael Praça


## 🚀 Usage

*brief show case of the project*

```py
>>> import mypackage
>>> mypackage.do_stuff()
'Oh yeah!'
```


## ⬇️ Instalação

Simple installation instructions

```bash
pip install my-package
```

minimum requirements and software versions.


## 🐍 Scripts Python (extração de PDFs)

Usados pelo Módulo 3 (Cofre Digital, extração da data de validade) e pelo
módulo de extração de faturas. Requerem **Python 3** no ambiente onde o PHP corre.

### Setup (uma vez, por máquina/ambiente)

Na raiz do projeto `lar-em-ordem-api`:

```bash
python3 -m venv .venv
.venv/bin/pip install -r scripts/requirements.txt
```

> Em Windows: `python -m venv .venv` e `.venv\Scripts\pip install -r scripts\requirements.txt`.

**Se usas Lerd** (o PHP corre num container Alpine, sem Python por defeito):

```bash
lerd php:pkg add python3 py3-pip
lerd php:rebuild
lerd shell          # e, dentro do container, os comandos do venv acima
```

O venv fica dentro do projeto (`.venv/`, ignorado pelo git), por isso sobrevive a
reinícios do Lerd. Cria-o sempre no mesmo ambiente onde o PHP corre.

### Executar o script de teste das faturas

```bash
.venv/bin/python scripts/test_InvoiceExtract.py
```

### Sem Python?
O upload de documentos funciona na mesma, mas as datas (`issue_date`,
`expiration_date`) têm de ser preenchidas à mão (a API devolve
`extraction.success = false`).


## 💭 Feedback - Conclusão

*Conclusão*

### Consultar Melhor

https://github.com/banesullivan/README
