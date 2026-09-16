<?php

return [
    'low_stock_default' => (int) env('STOCKLY_LOW_STOCK_DEFAULT', 5),
    'tax_rate' => (float) env('STOCKLY_TAX_RATE', 0.22),
    'currency' => env('STOCKLY_CURRENCY', 'EUR'),
];
