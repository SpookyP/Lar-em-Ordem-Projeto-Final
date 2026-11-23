<?php

return [
    // Python executable
    'python_bin' => env('PYTHON_PATH', 'python'),

    // extraction script, relative to the project root
    'extract_script' => 'scripts/Invoice_extract/InvoiceExtract.py',

    // seconds the extraction may take; if it times out, the user fills the form by hand
    'extract_timeout' => 20,
];