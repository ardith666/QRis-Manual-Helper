<?php
namespace Ardith666\QrisManualHelper;

class QrisService
{
    public static function clean($payload)
    {
        return preg_replace('/[\r\n\t]+/', '', trim((string)$payload));
    }

    public static function parseTlv($payload)
    {
        $payload = self::clean($payload);
        $fields = array();
        $i = 0;
        $len = strlen($payload);

        while ($i < $len) {
            if ($i + 4 > $len) {
                throw new QrisException('TLV tidak valid di posisi ' . $i);
            }
            $tag = substr($payload, $i, 2);
            $lengthText = substr($payload, $i + 2, 2);
            if (!ctype_digit($tag) || !ctype_digit($lengthText)) {
                throw new QrisException('Tag/length TLV tidak valid di posisi ' . $i);
            }
            $fieldLength = (int)$lengthText;
            $valueStart = $i + 4;
            if ($valueStart + $fieldLength > $len) {
                throw new QrisException('Panjang TLV melebihi payload pada tag ' . $tag);
            }
            $value = substr($payload, $valueStart, $fieldLength);
            $raw = substr($payload, $i, 4 + $fieldLength);
            $fields[] = array('tag' => $tag, 'length' => $fieldLength, 'value' => $value, 'raw' => $raw);
            $i = $valueStart + $fieldLength;
        }

        return $fields;
    }

    public static function buildTlv(array $fields)
    {
        $out = '';
        foreach ($fields as $field) {
            $out .= self::formatField($field['tag'], $field['value']);
        }
        return $out;
    }

    public static function formatField($tag, $value)
    {
        $value = (string)$value;
        return (string)$tag . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    public static function crc16($payload)
    {
        $crc = 0xFFFF;
        $payload = (string)$payload;
        $length = strlen($payload);
        for ($i = 0; $i < $length; $i++) {
            $crc ^= ord($payload[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    public static function extractMerchantName($payload)
    {
        return self::extractTag($payload, '59');
    }

    public static function extractMerchantCity($payload)
    {
        return self::extractTag($payload, '60');
    }

    private static function extractTag($payload, $tag)
    {
        $withoutCrc = self::stripCrc(self::clean($payload));
        try {
            $fields = self::parseTlv($withoutCrc);
            foreach ($fields as $field) {
                if ($field['tag'] === $tag) {
                    return $field['value'];
                }
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    public static function generateDynamic($staticQris, $amount)
    {
        $payload = self::clean($staticQris);
        $validation = QrisValidator::validatePayload($payload);
        if (!$validation['ok']) {
            throw new QrisException(implode(' ', $validation['errors']));
        }

        if (!is_numeric($amount) || (int)$amount <= 0) {
            throw new QrisException('Nominal harus angka lebih dari 0.');
        }
        $amount = (string)((int)$amount);

        $withoutCrc = self::stripCrc($payload);
        $fields = self::parseTlv($withoutCrc);
        $rebuilt = array();

        foreach ($fields as $field) {
            if ($field['tag'] === '54' || $field['tag'] === '63') {
                continue;
            }
            if ($field['tag'] === '01' && $field['value'] === '11') {
                $field['value'] = '12';
                $field['length'] = 2;
                $field['raw'] = self::formatField('01', '12');
            }
            $rebuilt[] = $field;
        }

        $amountField = array('tag' => '54', 'length' => strlen($amount), 'value' => $amount, 'raw' => self::formatField('54', $amount));
        $inserted = false;
        for ($i = 0; $i < count($rebuilt); $i++) {
            if (strcmp($rebuilt[$i]['tag'], '54') > 0) {
                array_splice($rebuilt, $i, 0, array($amountField));
                $inserted = true;
                break;
            }
        }
        if (!$inserted) {
            $rebuilt[] = $amountField;
        }
        $body = self::buildTlv($rebuilt);
        $payloadForCrc = $body . '6304';
        return $payloadForCrc . self::crc16($payloadForCrc);
    }

    private static function stripCrc($payload)
    {
        $payload = self::clean($payload);
        if (strlen($payload) < 8 || substr($payload, -8, 4) !== '6304') {
            throw new QrisException('Tag CRC 6304 tidak ditemukan di akhir payload.');
        }
        return substr($payload, 0, -8);
    }
}