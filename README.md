# QRIS Manual Helper

Bundle PHP plug-n-play untuk membuat **QRIS dinamis bernominal** dari **payload QRIS statis**.

Cocok dipakai di project PHP native, CodeIgniter lama, Laravel non-Composer integration, atau app kecil yang butuh QRIS manual tanpa payment gateway.

## Fitur

- PHP 7.4 - 8.4+ compatible.
- Tanpa dependency Composer.
- Validasi basic payload QRIS.
- Parse/build TLV QRIS.
- Konversi QRIS statis `tag 01 = 11` menjadi dinamis `tag 01 = 12`.
- Inject nominal ke `tag 54`.
- Rebuild CRC `tag 63`.
- Extract merchant name/city (`tag 59`/`60`).

## Struktur

```text
qris-manual-helper/
├── src/
│   ├── autoload.php
│   ├── QrisException.php
│   ├── QrisValidator.php
│   └── QrisService.php
├── examples/
│   └── example.php
├── tests/
│   └── smoke.php
├── composer.json
└── README.md
```

## Instalasi

### Via Composer (Recommended)

```bash
composer require ardith666/qris-manual-helper
```

Jika package belum tersedia di Packagist (belum disubmit atau perlu development branch):

```bash
composer require ardith666/qris-manual-helper:dev-main
```

Atau dengan repository GitHub langsung di `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ardith666/QRis-Manual-Helper"
        }
    ],
    "require": {
        "ardith666/qris-manual-helper": "*"
    }
}
```

### Manual Repository Install (Tanpa Composer / Non-Composer)

Copy bundle ke project, contoh:

```text
your-project/
├── vendor-local/
│   └── qris-manual-helper/
│       └── src/
└── ...
```

Atau:

```text
your-project/
├── app/
│   └── libraries/
│       └── qris-manual-helper/
│           └── src/
└── ...
```

## Penggunaan

### Via Composer (Namespace)

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Ardith666\QrisManualHelper\QrisService;
use Ardith666\QrisManualHelper\QrisException;
use Ardith666\QrisManualHelper\QrisValidator;

$staticPayload = 'GANTI_DENGAN_PAYLOAD_QRIS_STATIS_KAMU';
$amount = 50000;

try {
    $dynamicPayload = QrisService::generateDynamic($staticPayload, $amount);
    echo "Dynamic QRIS payload for Rp{$amount}:" . PHP_EOL;
    echo $dynamicPayload . PHP_EOL;
} catch (QrisException $e) {
    echo 'QRIS error: ' . $e->getMessage() . PHP_EOL;
}
```

### Via Composer - Tanpa Namespace (Backward Compatible)

Class global aliases tersedia otomatis via Composer autoload:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$staticPayload = 'GANTI_DENGAN_PAYLOAD_QRIS_STATIS_KAMU';
$amount = 50000;

try {
    $dynamicPayload = QrisService::generateDynamic($staticPayload, $amount);
    echo $dynamicPayload . PHP_EOL;
} catch (QrisException $e) {
    echo 'QRIS error: ' . $e->getMessage() . PHP_EOL;
}
```

### Manual / Non-Composer

```php
<?php
require_once __DIR__ . '/../vendor-local/qris-manual-helper/src/autoload.php';

$staticPayload = 'GANTI_DENGAN_PAYLOAD_QRIS_STATIS_KAMU';
$amount = 50000;

try {
    $dynamicPayload = QrisService::generateDynamic($staticPayload, $amount);
    echo $dynamicPayload . PHP_EOL;
} catch (QrisException $e) {
    echo 'QRIS error: ' . $e->getMessage() . PHP_EOL;
}
```

## Simpan Payload QRIS Statis

Ambil payload QRIS statis dari QR merchant. Biasanya payload didapat dari:

- upload/screenshot QR lalu decode QR,
- scanner QR,
- input manual textarea admin.

Simpan ke DB/config, contoh:

```php
$staticPayload = $settings['qris_static_payload'];
```

## Render QR di Browser pakai qrcodejs

```html
<div id="qris-canvas" data-qris="<?= htmlspecialchars($dynamicPayload, ENT_QUOTES, 'UTF-8') ?>"></div>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
var el = document.getElementById('qris-canvas');
new QRCode(el, {
  text: el.dataset.qris,
  width: 220,
  height: 220,
  correctLevel: QRCode.CorrectLevel.M
});
</script>
```

## Render QR dengan Image Overlay / Logo Tengah

Core PHP bundle ini tetap fokus generate payload QRIS. Untuk preview QR di browser dengan logo/favicon/app logo di tengah, bundle menyediakan helper opsional:

```text
assets/qris-manual-overlay.js
```

Helper ini bekerja bersama `qrcodejs` dan mendukung:

- `imageUrl` / `logoUrl` dari favicon, app logo, CDN, atau data URL upload.
- `logoSizeRatio` default `0.20` atau 20% sisi QR.
- background putih rounded di belakang logo.
- error correction `H` agar QR lebih tahan overlay.
- upload preview via `FileReader` tanpa upload server.

Contoh HTML:

```html
<div id="qris-preview"></div>
<input id="qris-logo-upload" type="file" accept="image/*">

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="vendor-local/qris-manual-helper/assets/qris-manual-overlay.js"></script>
<script>
  const payload = "HASIL_DARI_QrisService_generateDynamic";
  let overlayImage = "/assets/favicon.png";

  function renderQris() {
    QrisManualOverlay.render("#qris-preview", payload, {
      imageUrl: overlayImage,
      logoSizeRatio: 0.20,
      backgroundPadding: 12,
      correctLevel: "H"
    });
  }

  QrisManualOverlay.bindUpload("#qris-logo-upload", function (dataUrl) {
    overlayImage = dataUrl;
    renderQris();
  });

  renderQris();
</script>
```

Catatan scan:

- Pakai error correction `H`.
- Jaga logo sekitar 18–22% dari sisi QR.
- Selalu test scan setelah mengganti logo.
- Jangan commit payload QRIS merchant production ke repo public.

Lihat juga:

```text
examples/overlay-preview.html
```

## Render QR via CLI untuk Testing

Jika ada `qrencode`:

```bash
qrencode -o qris.png "PAYLOAD_QRIS_DYNAMIC"
```

## Validasi

### Via Composer

```bash
composer test
```

Atau langsung:

```bash
php tests/smoke.php
```

Expected:

```text
OK QRIS Manual Helper smoke test
```

### Manual / Non-Composer

```bash
php tests/smoke.php
```

### Lint

```bash
php -l src/QrisService.php
php -l src/QrisValidator.php
php -l src/QrisException.php
php -l src/autoload.php
php -l examples/example.php
php -l tests/smoke.php
```

## Contoh Lengkap

Lihat:

```text
examples/example.php
```

Jalankan:

```bash
php examples/example.php
```

## PHP Version Support

- **Minimum**: PHP 7.4
- **Tested**: PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4+

## Flow (Ringkas)

### 1) Flow Integrasi/Setup (End-to-End)

```mermaid
flowchart TD
  A[Copy bundle ke project / composer require] --> B[require autoload.php]
  B --> C[Simpan payload QRIS statis
(DB/config)]
  C --> D[Generate payload QRIS dinamis
(QrisService::generateDynamic)]
  D --> E[Render jadi QR image
(web/print)]
  E --> F[Customer bayar via scan]
  F --> G[Verifikasi manual
(mutasi/bukti bayar)]
  G --> H[Update status invoice
manual]
```

Intinya: helper ini hanya mengubah **payload statis → dinamis bernominal**, setelah itu proses pembayaran tetap **manual** (tidak ada callback otomatis).

### 2) Flow Internal Generate (Cara Kerja Payload)

```mermaid
flowchart TD
  A[Input: static payload + amount] --> B[Clean & validate payload]
  B --> C[Strip CRC final (tag 63) jika ada]
  C --> D[Parse payload ke TLV map]
  D --> E[Ubah tag 01: 11 (statis) -> 12 (dinamis)]
  E --> F[Insert/update tag 54 (amount)]
  F --> G[Rebuild payload TLV (tanpa CRC)]
  G --> H[Hitung CRC (tag 63)]
  H --> I[Output: dynamic payload final]
```

Catatan praktis:
- `tag 01`: indikator statis/dinamis (dibuat `12`).
- `tag 54`: nominal transaksi.
- `tag 63`: CRC wajib dihitung ulang setelah payload diubah.

## API Reference

### QrisService

```php
use Ardith666\QrisManualHelper\QrisService;

// Bersihkan payload (hapus whitespace/newline)
$clean = QrisService::clean($payload);

// Parse TLV QRIS
$fields = QrisService::parseTlv($payload);

// Build TLV dari array
$tlv = QrisService::buildTlv($fields);

// Format field tag+length+value
$field = QrisService::formatField('54', '50000');

// Hitung CRC16
$crc = QrisService::crc16($payload);

// Extract nama merchant dari tag 59
$name = QrisService::extractMerchantName($payload);

// Extract kota merchant dari tag 60
$city = QrisService::extractMerchantCity($payload);

// Generate QRIS dinamis dari payload statis
$dynamic = QrisService::generateDynamic($staticPayload, 50000);
```

### QrisValidator

```php
use Ardith666\QrisManualHelper\QrisValidator;

$result = QrisValidator::validatePayload($payload);
// $result = ['ok' => bool, 'message' => string, 'errors' => array]
```

### QrisException

```php
use Ardith666\QrisManualHelper\QrisException;

try {
    $dynamic = QrisService::generateDynamic($invalidPayload, 50000);
} catch (QrisException $e) {
    echo $e->getMessage();
}
```

## Catatan Penting

- Ini **QRIS Manual**, bukan payment gateway.
- Tidak ada webhook/callback otomatis.
- Status invoice **tidak otomatis lunas**.
- Admin tetap perlu cek mutasi/bukti bayar lalu verifikasi manual.