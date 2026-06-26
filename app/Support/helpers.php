<?php

if (! function_exists('colones')) {
    function colones(int $monto): string
    {
        return '₡'.number_format($monto, 0, ',', '.');
    }
}
