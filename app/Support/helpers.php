<?php

if (! function_exists('money')) {
    /**
     * Format a whole-dollar amount for display, e.g. 425000 => "$425,000".
     */
    function money(int|float|null $amount): string
    {
        return '$'.number_format((float) $amount);
    }
}
