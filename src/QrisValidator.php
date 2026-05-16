<?php
namespace Ardith666\QrisManualHelper;

class QrisValidator
{
    public static function validatePayload($payload)
    {
        $errors = array();
        $payload = preg_replace('/\s+/', '', (string)$payload);

        if ($payload === '') {
            $errors[] = 'Payload QRIS kosong.';
        }
        if (strpos($payload, '000201') !== 0) {
            $errors[] = 'Payload tidak diawali 000201.';
        }
        $crcPos = strrpos($payload, '6304');
        if ($crcPos === false) {
            $errors[] = 'Tag CRC 6304 tidak ditemukan.';
        } elseif ($crcPos + 8 > strlen($payload)) {
            $errors[] = 'CRC tidak lengkap.';
        }

        return array(
            'ok' => count($errors) === 0,
            'message' => count($errors) === 0 ? 'Payload QRIS valid.' : 'Payload QRIS tidak valid.',
            'errors' => $errors,
        );
    }
}