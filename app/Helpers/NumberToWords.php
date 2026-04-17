<?php

namespace App\Helpers;

class NumberToWords
{
    private static $UNIDADES = [
        '', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
        'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE',
        'VEINTE', 'VEINTIUN', 'VEINTIDOS', 'VEINTITRES', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISEIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE'
    ];

    private static $DECENAS = [
        'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'
    ];

    private static $CENTENAS = [
        'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'
    ];

    public static function convertir($number, $currency = 'SOLES')
    {
        $number = number_format($number, 2, '.', '');
        $parts = explode('.', $number);
        $entero = (int)$parts[0];
        $decimales = $parts[1];

        if ($entero == 0) {
            $resultado = 'CERO';
        } else {
            $resultado = self::convertirGrupo($entero);
        }

        return strtoupper($resultado . ' Y ' . $decimales . '/100 ' . $currency);
    }

    private static function convertirGrupo($n)
    {
        $output = '';

        if ($n == 0) {
            return $output;
        }

        if ($n >= 1000000) {
            $millones = (int)($n / 1000000);
            if ($millones == 1) {
                $output .= 'UN MILLON ';
            } else {
                $output .= self::convertirGrupo($millones) . ' MILLONES ';
            }
            $n %= 1000000;
        }

        if ($n >= 1000) {
            $miles = (int)($n / 1000);
            if ($miles == 1) {
                $output .= 'MIL ';
            } else {
                $output .= self::convertirGrupo($miles) . ' MIL ';
            }
            $n %= 1000;
        }

        if ($n >= 100) {
            $centenas = (int)($n / 100);
            if ($n == 100) {
                $output .= 'CIEN ';
            } else {
                $output .= self::$CENTENAS[$centenas - 1] . ' ';
            }
            $n %= 100;
        }

        if ($n > 0) {
            if ($n < 30) {
                $output .= self::$UNIDADES[$n] . ' ';
            } else {
                $decena = (int)($n / 10);
                $unidad = $n % 10;
                $output .= self::$DECENAS[$decena - 1];
                if ($unidad > 0) {
                    $output .= ' Y ' . self::$UNIDADES[$unidad];
                }
                $output .= ' ';
            }
        }

        return trim($output);
    }
}