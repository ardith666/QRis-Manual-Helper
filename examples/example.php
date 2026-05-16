<?php
require_once __DIR__ . '/../src/autoload.php';

use Ardith666\QrisManualHelper\QrisService;
use Ardith666\QrisManualHelper\QrisException;

// Ganti string di bawah dengan payload QRIS statis merchant kamu sendiri.
// Jangan commit payload QRIS production/merchant asli ke repo public.
$staticPayload = 'GANTI_DENGAN_PAYLOAD_QRIS_STATIS_KAMU';
$amount = 500;

try {
    if ($staticPayload === 'GANTI_DENGAN_PAYLOAD_QRIS_STATIS_KAMU') {
        throw new QrisException('Isi $staticPayload dengan payload QRIS statis kamu dulu.');
    }

    $dynamicPayload = QrisService::generateDynamic($staticPayload, $amount);

    echo "Dynamic QRIS payload for Rp{$amount}:" . PHP_EOL;
    echo $dynamicPayload . PHP_EOL;
} catch (QrisException $e) {
    fwrite(STDERR, 'QRIS error: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
