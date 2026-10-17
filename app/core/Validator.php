<?php
/**
 * Validator ringan. Rule dipisah "|" : required, nullable, min:n, max:n, in:a,b, email, int,
 * username, phone, kode, date. Nilai non-string dianggap tidak valid.
 */
final class Validator
{
    public static function run(array $data, array $rules, array $labels = []): array
    {
        $errors = [];
        foreach ($rules as $field => $ruleStr) {
            $raw = $data[$field] ?? '';
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $rs = explode('|', $ruleStr);
            if (!is_scalar($raw)) {
                $errors[$field] = "$label tidak valid.";
                continue;
            }
            $val = trim((string)$raw);
            if ($val === '') {
                if (in_array('required', $rs, true)) {
                    $errors[$field] = "$label wajib diisi.";
                }
                continue;
            }
            foreach ($rs as $r) {
                $parts = explode(':', $r, 2);
                $name = $parts[0];
                $arg = $parts[1] ?? '';
                $len = mb_strlen($val);
                $err = null;
                switch ($name) {
                    case 'min':
                        if ($len < (int)$arg) $err = "$label minimal $arg karakter.";
                        break;
                    case 'max':
                        if ($len > (int)$arg) $err = "$label maksimal $arg karakter.";
                        break;
                    case 'in':
                        if (!in_array($val, explode(',', $arg), true)) $err = "$label tidak valid.";
                        break;
                    case 'email':
                        if (!filter_var($val, FILTER_VALIDATE_EMAIL)) $err = "$label tidak valid.";
                        break;
                    case 'int':
                        if (!ctype_digit($val)) $err = "$label harus berupa angka.";
                        break;
                    case 'username':
                        if (!preg_match('/^[A-Za-z0-9_.]+$/', $val)) $err = "$label hanya boleh huruf, angka, titik, dan garis bawah.";
                        break;
                    case 'phone':
                        if (!preg_match('/^[0-9+\-\s]{8,20}$/', $val)) $err = "$label tidak valid (8-20 digit).";
                        break;
                    case 'kode':
                        if (!preg_match('/^[A-Za-z0-9\-_.]+$/', $val)) $err = "$label hanya boleh huruf, angka, strip, titik, dan garis bawah.";
                        break;
                    case 'date':
                        $d = DateTime::createFromFormat('Y-m-d', $val);
                        if (!$d || $d->format('Y-m-d') !== $val) $err = "$label tidak valid.";
                        break;
                }
                if ($err !== null) {
                    $errors[$field] = $err;
                    break;
                }
            }
        }
        return $errors;
    }
}
