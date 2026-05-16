<?php
require_once __DIR__ . '/../src/autoload.php';

use Ardith666\QrisManualHelper\QrisService;
use Ardith666\QrisManualHelper\QrisValidator;
use Ardith666\QrisManualHelper\QrisException;

function assertTrue($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

function tlvMap($payload)
{
    $withoutCrc = substr($payload, 0, -8);
    $fields = QrisService::parseTlv($withoutCrc);
    $map = array();
    foreach ($fields as $field) {
        $map[$field['tag']] = $field['value'];
    }
    return $map;
}

$merchantAccount = '';
$merchantAccount .= QrisService::formatField('00', 'ID.CO.QRIS.WWW');
$merchantAccount .= QrisService::formatField('01', '1234567890');

$body = '';
$body .= QrisService::formatField('00', '01');
$body .= QrisService::formatField('01', '11');
$body .= QrisService::formatField('27', $merchantAccount);
$body .= QrisService::formatField('52', '5499');
$body .= QrisService::formatField('53', '999');
$body .= QrisService::formatField('58', 'ID');
$body .= QrisService::formatField('59', 'TEST MERCHANT');
$body .= QrisService::formatField('60', 'JKT');
$staticPayload = $body . '6304' . QrisService::crc16($body . '6304');

$validation = QrisValidator::validatePayload($staticPayload);
assertTrue($validation['ok'], 'static payload valid: ' . implode(' ', $validation['errors']));

$dynamic = QrisService::generateDynamic($staticPayload, 500);
$map = tlvMap($dynamic);

assertTrue(isset($map['01']) && $map['01'] === '12', 'tag 01 converted to dynamic 12');
assertTrue(isset($map['54']) && $map['54'] === '500', 'tag 54 amount injected');
assertTrue((bool)preg_match('/6304[0-9A-F]{4}$/', $dynamic), 'CRC tag exists');
assertTrue(QrisValidator::validatePayload($dynamic)['ok'], 'dynamic payload valid');

echo "OK QRIS Manual Helper smoke test\n";