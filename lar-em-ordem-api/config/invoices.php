<?php

return [
    // Executável Python
    'python_bin' => env('PYTHON_PATH', 'python'),

    // Script de extração, relativo à raiz do projeto
    'extract_script' => 'scripts/Invoice_extract/InvoiceExtract.py',

    // Tempo máximo (segundos) que a extração pode demorar
    'extract_timeout' => 20,
];