<?php

if (! function_exists('money')) {
    /**
     * Format an amount in cents, e.g. 4250000 => "$42,500" (or "$42,500.00" with $withCents).
     */
    function money(int|float|null $cents, bool $withCents = false): string
    {
        return '$'.number_format(((float) $cents) / 100, $withCents ? 2 : 0);
    }
}

if (! function_exists('miles')) {
    function miles(int|float|null $miles): string
    {
        return number_format((float) $miles).' mi';
    }
}

if (! function_exists('to_cents')) {
    /**
     * Parse user input like "1,299.99" or "$450" into cents.
     */
    function to_cents(string|int|float|null $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return (int) round(((float) str_replace([',', '$', ' '], '', (string) $amount)) * 100);
    }
}

if (! function_exists('clean_amount')) {
    /**
     * Strip what people naturally type around numbers ("$48,990 ") so validation sees "48990".
     */
    function clean_amount(?string $amount): string
    {
        return str_replace([',', '$', ' '], '', (string) $amount);
    }
}
