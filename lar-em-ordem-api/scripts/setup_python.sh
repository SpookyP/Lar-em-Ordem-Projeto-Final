#!/bin/sh
set -e
cd "$(dirname "$0")/.."
python3 -m venv .venv
.venv/bin/pip install -r scripts/requirements.txt
echo "Python pronto: .venv/bin/python"
