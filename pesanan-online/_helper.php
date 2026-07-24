<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/config.php';
require_once __DIR__ . '/../helpers/koneksi.php';
require_once __DIR__ . '/../helpers/fungsi.php';
require_once __DIR__ . '/../helpers/pajak.php';

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('po_esc')) {
    function po_esc($value): string
    {
        if (function_exists('esc')) {
            return esc((string) $value);
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('po_base_url')) {
    function po_base_url(string $path = ''): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        /*
        |--------------------------------------------------------------------------
        | Ambil base project sebelum folder /pesanan-online/
        |--------------------------------------------------------------------------
        | Contoh:
        | /HARGA_POKOK_PRODUKSI/pesanan-online/cetak.php
        |
        | Menjadi:
        | /HARGA_POKOK_PRODUKSI
        |
        | Dengan cara ini, po_url() akan aman dipanggil dari:
        | - index.php
        | - simpan.php
        | - sukses.php
        | - cek.php
        | - cetak.php
        |--------------------------------------------------------------------------
        */
        $pos = strpos($scriptName, '/pesanan-online/');

        if ($pos !== false) {
            $base = substr($scriptName, 0, $pos);
        } else {
            $base = rtrim(dirname($scriptName), '/');
        }

        $base = rtrim($base, '/');

        if ($path === '') {
            return $base;
        }

        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('po_url')) {
    function po_url(string $path = ''): string
    {
        return po_base_url('pesanan-online/' . ltrim($path, '/'));
    }
}

if (!function_exists('po_id_entitas')) {
    function po_id_entitas(): int
    {
        $id = (int) ($_GET['entitas'] ?? $_POST['id_entitas'] ?? 1);

        return $id > 0 ? $id : 1;
    }
}

if (!function_exists('po_uang')) {
    function po_uang($value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('po_qty')) {
    function po_qty($value): string
    {
        $value = (float) $value;

        if (floor($value) == $value) {
            return number_format($value, 0, ',', '.');
        }

        return number_format($value, 2, ',', '.');
    }
}

if (!function_exists('po_entitas')) {
    function po_entitas(int $id_entitas): ?object
    {
        return Capsule::table('tb_entitas')
            ->where('id_entitas', $id_entitas)
            ->first();
    }
}

if (!function_exists('po_produk_image_url')) {
    function po_produk_image_url(?string $gambar): string
    {
        $gambar = trim((string) $gambar);

        if ($gambar === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $gambar)) {
            return $gambar;
        }

        $gambar = str_replace('\\', '/', $gambar);
        $gambar = ltrim($gambar, '/');

        /*
        |--------------------------------------------------------------------------
        | Jika database sudah menyimpan path lengkap relatif project.
        | Contoh: uploads/produk/a.jpg
        |--------------------------------------------------------------------------
        */
        if (file_exists(__DIR__ . '/../' . $gambar)) {
            return po_base_url($gambar);
        }

        /*
        |--------------------------------------------------------------------------
        | Jika database hanya menyimpan nama file.
        |--------------------------------------------------------------------------
        */
        $candidates = [
            'uploads/produk/' . basename($gambar),
            'upload/produk/' . basename($gambar),
            'assets/img/produk/' . basename($gambar),
            'assets/images/produk/' . basename($gambar),
            'administrator/uploads/produk/' . basename($gambar),
            'administrator/assets/img/produk/' . basename($gambar),
        ];

        foreach ($candidates as $path) {
            if (file_exists(__DIR__ . '/../' . $path)) {
                return po_base_url($path);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback terakhir: tetap pakai isi database sebagai path.
        |--------------------------------------------------------------------------
        */
        return po_base_url($gambar);
    }
}



if (!function_exists('po_table_has_column')) {
    function po_table_has_column(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $cache[$key] = Capsule::schema()->hasColumn($table, $column);
        } catch (Throwable $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }
}

if (!function_exists('po_payment_setting')) {
    function po_payment_setting(int $id_entitas): object
    {
        $default = (object) [
            'nama_bank' => '',
            'nomor_rekening' => '',
            'atas_nama_rekening' => '',
            'qris_static_payload' => '',
            'qris_nama_merchant' => '',
            'qris_image_path' => '',
            'catatan_pembayaran' => '',
        ];

        try {
            // Prioritas baru: ambil rekening/QRIS dari akun bank di Mapping Akun/COA.
            // Jika kolom belum ada atau belum diisi, fallback ke tabel pengaturan pembayaran online lama.
            if (Capsule::schema()->hasTable('tb_coa')
                && Capsule::schema()->hasColumn('tb_coa', 'nomor_rekening')
                && Capsule::schema()->hasColumn('tb_coa', 'qris_static_payload')) {
                $hasQrisImage = Capsule::schema()->hasColumn('tb_coa', 'qris_image_path');
                $bankQuery = Capsule::table('tb_coa')
                    ->where('id_entitas', $id_entitas)
                    ->where('status_aktif', 1)
                    ->where('boleh_transaksi', 1)
                    ->where(function ($query) use ($hasQrisImage) {
                        $query->where('is_akun_qris', 1)
                            ->orWhere('nomor_rekening', '<>', '')
                            ->orWhere('qris_static_payload', '<>', '')
                            ->orWhere('nama_bank', '<>', '')
                            ->orWhere('nama_coa', 'like', '%bank%');
                        if ($hasQrisImage) {
                            $query->orWhere('qris_image_path', '<>', '');
                        }
                    })
                    ->orderByDesc('is_akun_qris')
                    ->orderByRaw("CASE WHEN nomor_rekening IS NULL OR nomor_rekening = '' THEN 1 ELSE 0 END")
                    ->orderByRaw("CASE WHEN nama_bank IS NULL OR nama_bank = '' THEN 1 ELSE 0 END")
                    ->orderByRaw("CASE WHEN qris_static_payload IS NULL OR qris_static_payload = '' THEN 1 ELSE 0 END");
                if ($hasQrisImage) {
                    $bankQuery->orderByRaw("CASE WHEN qris_image_path IS NULL OR qris_image_path = '' THEN 1 ELSE 0 END");
                }
                $bank = $bankQuery
                    ->orderBy('kode_coa')
                    ->first();

                if ($bank) {
                    $fromCoa = (object) $default;
                    $fromCoa->nama_bank = (string) ($bank->nama_bank ?? $bank->nama_coa ?? '');
                    $fromCoa->nomor_rekening = (string) ($bank->nomor_rekening ?? '');
                    $fromCoa->atas_nama_rekening = (string) ($bank->atas_nama_rekening ?? '');
                    $fromCoa->qris_static_payload = (string) ($bank->qris_static_payload ?? '');
                    $fromCoa->qris_nama_merchant = (string) ($bank->qris_nama_merchant ?? $bank->atas_nama_rekening ?? '');
                    $fromCoa->qris_image_path = (string) ($bank->qris_image_path ?? '');
                    $fromCoa->catatan_pembayaran = 'Data rekening/QRIS diambil dari akun bank pada Mapping Akun.';
                    return $fromCoa;
                }
            }

            if (!Capsule::schema()->hasTable('tb_pengaturan_pembayaran_online')) {
                return $default;
            }

            $row = Capsule::table('tb_pengaturan_pembayaran_online')
                ->where('id_entitas', $id_entitas)
                ->first();

            if (!$row) {
                return $default;
            }

            foreach ($default as $key => $value) {
                if (!isset($row->{$key})) {
                    $row->{$key} = $value;
                }
            }

            return $row;
        } catch (Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('po_crc16_qris')) {
    function po_crc16_qris(string $payload): string
    {
        $crc = 0xFFFF;
        $len = strlen($payload);
        for ($c = 0; $c < $len; $c++) {
            $crc ^= (ord($payload[$c]) << 8);
            for ($i = 0; $i < 8; $i++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}

if (!function_exists('po_qris_dynamic_payload')) {
    function po_qris_dynamic_payload(string $staticPayload, float $amount): string
    {
        $payload = preg_replace('/\s+/', '', trim($staticPayload));
        if ($payload === '' || $amount <= 0) {
            return '';
        }

        $tags = [];
        $pos = 0;
        $length = strlen($payload);
        while ($pos + 4 <= $length) {
            $tag = substr($payload, $pos, 2);
            $tagLength = substr($payload, $pos + 2, 2);
            if (!ctype_digit($tag) || !ctype_digit($tagLength)) {
                break;
            }
            $valueLength = (int) $tagLength;
            $value = substr($payload, $pos + 4, $valueLength);
            if (strlen($value) !== $valueLength) {
                break;
            }
            $pos += 4 + $valueLength;
            if ($tag === '63') {
                break;
            }
            if ($tag === '54') {
                continue;
            }
            if ($tag === '01') {
                $value = '12';
            }
            $tags[] = [$tag, $value];
        }

        if (count($tags) === 0) {
            return '';
        }

        $has01 = false;
        foreach ($tags as $tagData) {
            if ($tagData[0] === '01') {
                $has01 = true;
                break;
            }
        }
        if (!$has01) {
            array_splice($tags, 1, 0, [['01', '12']]);
        }

        $amountText = number_format($amount, 2, '.', '');
        $result = '';
        $amountInserted = false;
        foreach ($tags as $tagData) {
            [$tag, $value] = $tagData;
            if (!$amountInserted && in_array($tag, ['55', '56', '57', '58', '59', '60', '61', '62'], true)) {
                $result .= '54' . str_pad((string) strlen($amountText), 2, '0', STR_PAD_LEFT) . $amountText;
                $amountInserted = true;
            }
            $result .= $tag . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
        }
        if (!$amountInserted) {
            $result .= '54' . str_pad((string) strlen($amountText), 2, '0', STR_PAD_LEFT) . $amountText;
        }

        $result .= '6304';
        return $result . po_crc16_qris($result);
    }
}

if (!function_exists('po_qris_image_url')) {
    function po_qris_image_url(string $payload, int $size = 260): string
    {
        $payload = trim($payload);
        if ($payload === '') {
            return '';
        }
        return 'https://quickchart.io/qr?size=' . (int) $size . '&margin=1&text=' . rawurlencode($payload);
    }
}



if (!function_exists('po_public_file_url')) {
    function po_public_file_url(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') return '';
        if (preg_match('/^https?:\/\//i', $path)) return $path;
        return po_base_url(ltrim(str_replace('\\', '/', $path), '/'));
    }
}

if (!function_exists('po_payment_status_label')) {
    function po_payment_status_label(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        return match ($status) {
            'lunas', 'paid', 'settlement', 'capture' => 'Lunas',
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'menunggu_bayar', 'pending' => 'Menunggu Bayar',
            'ditolak' => 'Ditolak',
            'belum_bayar', '' => 'Belum Bayar',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }
}

if (!function_exists('po_latest_payment_proof')) {
    function po_latest_payment_proof(int $idPesanan): ?object
    {
        try {
            if (!Capsule::schema()->hasTable('tb_pembayaran_online_bukti')) return null;

            $pesanan = Capsule::table('tb_pesanan_penjualan')
                ->where('id_pesanan_penjualan', $idPesanan)
                ->first();
            if (!$pesanan) return null;

            $metode = strtolower(trim((string) ($pesanan->metode_pembayaran_online ?? 'cod')));
            if (!in_array($metode, ['transfer', 'qris'], true)) return null;

            $query = Capsule::table('tb_pembayaran_online_bukti')
                ->where('id_entitas', (int) ($pesanan->id_entitas ?? po_id_entitas()))
                ->where('id_pesanan_penjualan', $idPesanan)
                ->where('metode_pembayaran', $metode);

            if (Capsule::schema()->hasColumn('tb_pembayaran_online_bukti', 'id_pelanggan')) {
                $query->where('id_pelanggan', (int) ($pesanan->id_pelanggan ?? 0));
            }

            return $query
                ->orderBy('id_bukti_pembayaran_online', 'desc')
                ->first();
        } catch (Throwable $e) { return null; }
    }
}

if (!function_exists('po_chat_rows')) {
    function po_chat_rows(int $idPesanan, ?int $idPelanggan = null)
    {
        try {
            if (!Capsule::schema()->hasTable('tb_pesanan_online_chat')) return collect();
            if (!Capsule::schema()->hasColumn('tb_pesanan_online_chat', 'id_pelanggan')) return collect();

            $query = Capsule::table('tb_pesanan_online_chat')
                ->where('id_pesanan_penjualan', $idPesanan);

            if ($idPelanggan !== null) {
                $query->where('id_pelanggan', $idPelanggan);
            }

            return $query
                ->orderBy('id_chat', 'asc')
                ->get();
        } catch (Throwable $e) { return collect(); }
    }
}





if (!function_exists('po_ipaymu_setting')) {
    function po_ipaymu_setting(int $id_entitas): object
    {
        $default = (object) [
            'provider' => 'ipaymu',
            'mode' => 'sandbox',
            'is_active' => 0,
            'va_number' => '',
            'api_key' => '',
            'payment_method' => 'qris',
            'payment_channel' => 'qris',
            'redirect_url' => 'https://sandbox.ipaymu.com/api/v2/payment/redirect',
            'direct_url' => 'https://sandbox.ipaymu.com/api/v2/payment/direct',
        ];

        try {
            if (!Capsule::schema()->hasTable('tb_payment_gateway_setting')) {
                return $default;
            }

            $row = Capsule::table('tb_payment_gateway_setting')
                ->where('id_entitas', $id_entitas)
                ->where('provider', 'ipaymu')
                ->first();

            if (!$row) {
                return $default;
            }

            $mode = strtolower((string) ($row->mode ?? 'sandbox')) === 'production' ? 'production' : 'sandbox';
            $row->mode = $mode;
            $row->va_number = trim((string) ($row->merchant_id ?? ''));
            $row->api_key = trim((string) ($row->server_key ?? ''));
            $row->payment_method = 'qris';
            $row->payment_channel = strtolower(trim((string) ($row->enabled_payments ?? 'qris'))) ?: 'qris';
            $row->redirect_url = $mode === 'production'
                ? 'https://my.ipaymu.com/api/v2/payment/redirect'
                : 'https://sandbox.ipaymu.com/api/v2/payment/redirect';
            $row->direct_url = $mode === 'production'
                ? 'https://my.ipaymu.com/api/v2/payment/direct'
                : 'https://sandbox.ipaymu.com/api/v2/payment/direct';

            return $row;
        } catch (Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('po_ipaymu_enabled')) {
    function po_ipaymu_enabled(int $id_entitas): bool
    {
        $setting = po_ipaymu_setting($id_entitas);
        return (int) ($setting->is_active ?? 0) === 1
            && trim((string) ($setting->va_number ?? '')) !== ''
            && trim((string) ($setting->api_key ?? '')) !== '';
    }
}

if (!function_exists('po_ipaymu_signature')) {
    function po_ipaymu_signature(string $method, string $va, array $body, string $apiKey): string
    {
        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES);
        $bodyHash = strtolower(hash('sha256', (string) $bodyJson));
        $stringToSign = strtoupper($method) . ':' . $va . ':' . $bodyHash . ':' . $apiKey;
        return hash_hmac('sha256', $stringToSign, $apiKey);
    }
}

if (!function_exists('po_ipaymu_http_post_json')) {
    function po_ipaymu_http_post_json(string $url, string $va, string $apiKey, array $payload): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi cURL PHP belum aktif. Aktifkan cURL di Laragon/hosting untuk memakai iPaymu.');
        }

        $bodyJson = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = po_ipaymu_signature('POST', $va, $payload, $apiKey);
        $timestamp = date('YmdHis');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $bodyJson,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'va: ' . $va,
                'signature: ' . $signature,
                'timestamp: ' . $timestamp,
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error !== '') {
            throw new RuntimeException('Gagal menghubungi iPaymu: ' . $error);
        }

        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            throw new RuntimeException('Respons iPaymu tidak valid. HTTP ' . $status . ': ' . substr((string) $body, 0, 180));
        }

        if ($status < 200 || $status >= 300) {
            $message = (string) ($json['Message'] ?? $json['message'] ?? $json['StatusDesc'] ?? $json['status_desc'] ?? ('HTTP ' . $status));
            throw new RuntimeException('iPaymu menolak transaksi: ' . $message);
        }

        return $json;
    }
}

if (!function_exists('po_ipaymu_create_transaction')) {
    function po_ipaymu_create_transaction(int $id_entitas, int $idPesanan, string $orderId, float $grossAmount, array $items, array $customer): array
    {
        $setting = po_ipaymu_setting($id_entitas);
        if ((int) ($setting->is_active ?? 0) !== 1) {
            throw new RuntimeException('Gateway iPaymu belum aktif di Mapping Akun.');
        }

        $va = trim((string) ($setting->va_number ?? ''));
        $apiKey = trim((string) ($setting->api_key ?? ''));
        if ($va === '' || $apiKey === '') {
            throw new RuntimeException('VA Number dan API Key iPaymu wajib diisi.');
        }

        $amount = (int) round($grossAmount);
        if ($amount <= 0) {
            throw new RuntimeException('Nominal pembayaran iPaymu tidak valid.');
        }

        $products = [];
        $qtys = [];
        $prices = [];
        $descriptions = [];
        foreach ($items as $item) {
            $subtotal = (int) round((float) ($item['subtotal'] ?? 0));
            if ($subtotal <= 0) {
                continue;
            }
            $products[] = substr((string) ($item['nama_produk'] ?? 'Produk'), 0, 80);
            $qtys[] = 1;
            $prices[] = $subtotal;
            $descriptions[] = 'Pesanan online';
        }
        if (empty($products)) {
            $products[] = 'Pesanan ' . $orderId;
            $qtys[] = 1;
            $prices[] = $amount;
            $descriptions[] = 'Pesanan online';
        }

        $nama = trim((string) ($customer['nama'] ?? 'Pelanggan')) ?: 'Pelanggan';
        $email = trim((string) ($customer['email'] ?? ''));
        if ($email === '') {
            $email = 'pelanggan-' . preg_replace('/[^0-9]/', '', (string) ($customer['no_hp'] ?? '0')) . '@example.local';
        }
        $phone = preg_replace('/[^0-9]/', '', (string) ($customer['no_hp'] ?? '')) ?: '0800000000';

        $payload = [
            'account' => $va,
            'name' => $nama,
            'email' => $email,
            'phone' => $phone,
            'amount' => $amount,
            'paymentMethod' => 'qris',
            'paymentChannel' => strtolower(trim((string) ($setting->payment_channel ?? 'qris'))) ?: 'qris',
            'notifyUrl' => po_base_url('pesanan-online/ipaymu_callback.php'),
            'returnUrl' => po_url('sukses.php?id=' . $idPesanan . '&no=' . urlencode($orderId) . '&entitas=' . $id_entitas),
            'cancelUrl' => po_url('sukses.php?id=' . $idPesanan . '&no=' . urlencode($orderId) . '&entitas=' . $id_entitas),
            'referenceId' => $orderId,
            'description' => 'Pembayaran Pesanan Online ' . $orderId,
            'product' => $products,
            'qty' => $qtys,
            'price' => $prices,
            'weight' => array_fill(0, count($products), 1),
            'length' => array_fill(0, count($products), 1),
            'width' => array_fill(0, count($products), 1),
            'height' => array_fill(0, count($products), 1),
            'expired' => 24,
        ];

        return po_ipaymu_http_post_json((string) $setting->direct_url, $va, $apiKey, $payload);
    }
}

if (!function_exists('po_ipaymu_extract_url')) {
    function po_ipaymu_extract_url(array $response): string
    {
        $candidates = [
            $response['Data']['Url'] ?? null,
            $response['Data']['url'] ?? null,
            $response['Data']['PaymentUrl'] ?? null,
            $response['Data']['paymentUrl'] ?? null,
            $response['url'] ?? null,
            $response['paymentUrl'] ?? null,
            $response['payment_url'] ?? null,
        ];
        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }
}

if (!function_exists('po_ipaymu_extract_session')) {
    function po_ipaymu_extract_session(array $response): string
    {
        $candidates = [
            $response['Data']['SessionID'] ?? null,
            $response['Data']['SessionId'] ?? null,
            $response['Data']['sid'] ?? null,
            $response['SessionID'] ?? null,
            $response['sid'] ?? null,
        ];
        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }
}

if (!function_exists('po_ipaymu_update_order_status')) {
    function po_ipaymu_update_order_status(string $orderId, string $statusText, string $transactionId = '', string $responseJson = ''): void
    {
        if (!Capsule::schema()->hasTable('tb_pesanan_penjualan')) {
            return;
        }

        $normalized = strtolower(trim($statusText));
        $paymentStatus = match ($normalized) {
            'berhasil', 'success', 'paid', 'lunas', '1', '200' => 'lunas',
            'pending', '0', '' => 'menunggu_bayar',
            'expired', 'cancel', 'canceled', 'failed', 'gagal', '2' => 'ditolak',
            default => str_contains($normalized, 'success') || str_contains($normalized, 'berhasil') ? 'lunas' : 'menunggu_bayar',
        };

        $update = ['tanggal_diubah' => date('Y-m-d H:i:s')];
        if (po_table_has_column('tb_pesanan_penjualan', 'status_pembayaran_online')) {
            $update['status_pembayaran_online'] = $paymentStatus;
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'ipaymu_status')) {
            $update['ipaymu_status'] = $statusText;
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'ipaymu_transaction_id') && $transactionId !== '') {
            $update['ipaymu_transaction_id'] = $transactionId;
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'ipaymu_paid_at') && $paymentStatus === 'lunas') {
            $update['ipaymu_paid_at'] = date('Y-m-d H:i:s');
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'ipaymu_response_json') && $responseJson !== '') {
            $update['ipaymu_response_json'] = $responseJson;
        }

        Capsule::table('tb_pesanan_penjualan')
            ->where(function ($query) use ($orderId) {
                $query->where('no_pesanan_penjualan', $orderId);
                if (po_table_has_column('tb_pesanan_penjualan', 'ipaymu_order_id')) {
                    $query->orWhere('ipaymu_order_id', $orderId);
                }
            })
            ->update($update);
    }
}

if (!function_exists('po_ipaymu_extract_qr_string')) {
    function po_ipaymu_extract_qr_string(array $response): string
    {
        $candidates = [
            $response['Data']['QrString'] ?? null,
            $response['Data']['qrString'] ?? null,
            $response['Data']['QRString'] ?? null,
            $response['Data']['QrCode'] ?? null,
            $response['Data']['qrCode'] ?? null,
            $response['Data']['QRCode'] ?? null,
            $response['Data']['qr_string'] ?? null,
            $response['Data']['qris'] ?? null,
            $response['QrString'] ?? null,
            $response['qrString'] ?? null,
            $response['QRString'] ?? null,
            $response['QrCode'] ?? null,
            $response['qrCode'] ?? null,
            $response['QRCode'] ?? null,
            $response['qr_string'] ?? null,
            $response['qris'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

if (!function_exists('po_qr_image_url')) {
    function po_qr_image_url(string $data, int $size = 420): string
    {
        $data = trim($data);
        if ($data === '') {
            return '';
        }

        $size = max(240, min(700, $size));

        return 'https://api.qrserver.com/v1/create-qr-code/?size='
            . $size . 'x' . $size
            . '&margin=12&data=' . rawurlencode($data);
    }
}

if (!function_exists('po_duitku_setting')) {
    function po_duitku_setting(int $id_entitas): object
    {
        $default = (object) [
            'provider' => 'duitku',
            'mode' => 'sandbox',
            'is_active' => 0,
            'merchant_code' => '',
            'api_key' => '',
            'payment_method' => 'SP',
            'inquiry_url' => 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry',
            'status_url' => 'https://sandbox.duitku.com/webapi/api/merchant/transactionStatus',
        ];

        try {
            if (!Capsule::schema()->hasTable('tb_payment_gateway_setting')) {
                return $default;
            }

            $row = Capsule::table('tb_payment_gateway_setting')
                ->where('id_entitas', $id_entitas)
                ->where('provider', 'duitku')
                ->first();

            if (!$row) {
                return $default;
            }

            $mode = strtolower((string) ($row->mode ?? 'sandbox')) === 'production' ? 'production' : 'sandbox';
            $row->mode = $mode;
            $row->merchant_code = trim((string) ($row->merchant_id ?? ''));
            $row->api_key = trim((string) ($row->server_key ?? ''));
            $row->payment_method = trim((string) ($row->enabled_payments ?? 'SP')) ?: 'SP';
            $row->inquiry_url = $mode === 'production'
                ? 'https://passport.duitku.com/webapi/api/merchant/v2/inquiry'
                : 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry';
            $row->status_url = $mode === 'production'
                ? 'https://passport.duitku.com/webapi/api/merchant/transactionStatus'
                : 'https://sandbox.duitku.com/webapi/api/merchant/transactionStatus';

            return $row;
        } catch (Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('po_duitku_enabled')) {
    function po_duitku_enabled(int $id_entitas): bool
    {
        $setting = po_duitku_setting($id_entitas);
        return (int) ($setting->is_active ?? 0) === 1
            && trim((string) ($setting->merchant_code ?? '')) !== ''
            && trim((string) ($setting->api_key ?? '')) !== '';
    }
}

if (!function_exists('po_duitku_http_post_json')) {
    function po_duitku_http_post_json(string $url, array $payload): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi cURL PHP belum aktif. Aktifkan cURL di Laragon/hosting untuk memakai Duitku.');
        }

        $bodyJson = json_encode($payload);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $bodyJson,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen((string) $bodyJson),
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error !== '') {
            throw new RuntimeException('Gagal menghubungi Duitku: ' . $error);
        }

        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            throw new RuntimeException('Respons Duitku tidak valid. HTTP ' . $status . ': ' . substr((string) $body, 0, 180));
        }

        if ($status < 200 || $status >= 300) {
            $message = (string) ($json['Message'] ?? $json['message'] ?? $json['statusMessage'] ?? ('HTTP ' . $status));
            throw new RuntimeException('Duitku menolak transaksi: ' . $message);
        }

        return $json;
    }
}

if (!function_exists('po_duitku_create_transaction')) {
    function po_duitku_create_transaction(int $id_entitas, int $idPesanan, string $orderId, float $grossAmount, array $items, array $customer): array
    {
        $setting = po_duitku_setting($id_entitas);
        if ((int) ($setting->is_active ?? 0) !== 1) {
            throw new RuntimeException('Gateway Duitku belum aktif di Mapping Akun.');
        }

        $merchantCode = trim((string) ($setting->merchant_code ?? ''));
        $apiKey = trim((string) ($setting->api_key ?? ''));
        if ($merchantCode === '' || $apiKey === '') {
            throw new RuntimeException('Merchant Code dan API Key Duitku wajib diisi.');
        }

        $amount = (int) round($grossAmount);
        if ($amount <= 0) {
            throw new RuntimeException('Nominal pembayaran Duitku tidak valid.');
        }

        $itemDetails = [];
        foreach ($items as $item) {
            $lineAmount = (int) round((float) ($item['subtotal'] ?? 0));
            if ($lineAmount <= 0) {
                continue;
            }
            $itemDetails[] = [
                'name' => substr((string) ($item['nama_produk'] ?? 'Produk'), 0, 50),
                'price' => $lineAmount,
                'quantity' => 1,
            ];
        }
        if (empty($itemDetails)) {
            $itemDetails[] = ['name' => 'Pesanan ' . $orderId, 'price' => $amount, 'quantity' => 1];
        }

        $nama = trim((string) ($customer['nama'] ?? 'Pelanggan')) ?: 'Pelanggan';
        $email = trim((string) ($customer['email'] ?? ''));
        if ($email === '') {
            $email = 'pelanggan-' . preg_replace('/[^0-9]/', '', (string) ($customer['no_hp'] ?? '0')) . '@example.local';
        }
        $phone = trim((string) ($customer['no_hp'] ?? ''));
        $alamat = trim((string) ($customer['alamat'] ?? '')) ?: '-';
        $customerVaName = substr(preg_replace('/[^A-Za-z0-9 ]/', '', $nama), 0, 20) ?: 'Pelanggan';

        $signature = hash_hmac('sha256', $merchantCode . $orderId . $amount, $apiKey);
        $callbackUrl = po_base_url('pesanan-online/duitku_callback.php');
        $returnUrl = po_url('sukses.php?id=' . $idPesanan . '&no=' . urlencode($orderId) . '&entitas=' . $id_entitas);

        $payload = [
            'merchantCode' => $merchantCode,
            'paymentAmount' => $amount,
            'paymentMethod' => trim((string) ($setting->payment_method ?? 'SP')) ?: 'SP',
            'merchantOrderId' => $orderId,
            'productDetails' => 'Pembayaran Pesanan Online ' . $orderId,
            'additionalParam' => '',
            'merchantUserInfo' => $phone,
            'customerVaName' => $customerVaName,
            'email' => $email,
            'phoneNumber' => $phone,
            'itemDetails' => $itemDetails,
            'customerDetail' => [
                'firstName' => $nama,
                'lastName' => '',
                'email' => $email,
                'phoneNumber' => $phone,
                'billingAddress' => [
                    'firstName' => $nama,
                    'lastName' => '',
                    'address' => $alamat,
                    'city' => 'Banjarmasin',
                    'postalCode' => '70100',
                    'phone' => $phone,
                    'countryCode' => 'ID',
                ],
                'shippingAddress' => [
                    'firstName' => $nama,
                    'lastName' => '',
                    'address' => $alamat,
                    'city' => 'Banjarmasin',
                    'postalCode' => '70100',
                    'phone' => $phone,
                    'countryCode' => 'ID',
                ],
            ],
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'signature' => $signature,
            'expiryPeriod' => 60,
        ];

        return po_duitku_http_post_json((string) $setting->inquiry_url, $payload);
    }
}

if (!function_exists('po_duitku_update_order_status')) {
    function po_duitku_update_order_status(string $orderId, string $resultCode, string $reference = '', string $responseJson = ''): void
    {
        $status = match ((string) $resultCode) {
            '00' => 'lunas',
            '01' => 'ditolak',
            '02' => 'ditolak',
            default => 'menunggu_bayar',
        };

        $update = ['tanggal_diubah' => date('Y-m-d H:i:s')];
        if (po_table_has_column('tb_pesanan_penjualan', 'status_pembayaran_online')) {
            $update['status_pembayaran_online'] = $status;
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'duitku_status_code')) {
            $update['duitku_status_code'] = $resultCode;
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'duitku_reference') && $reference !== '') {
            $update['duitku_reference'] = $reference;
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'duitku_status_message')) {
            $update['duitku_status_message'] = $status === 'lunas' ? 'SUCCESS' : strtoupper($status);
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'duitku_paid_at') && $status === 'lunas') {
            $update['duitku_paid_at'] = date('Y-m-d H:i:s');
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'duitku_response_json') && $responseJson !== '') {
            $update['duitku_response_json'] = $responseJson;
        }

        Capsule::table('tb_pesanan_penjualan')
            ->where(function ($query) use ($orderId) {
                $query->where('no_pesanan_penjualan', $orderId);
                if (po_table_has_column('tb_pesanan_penjualan', 'duitku_order_id')) {
                    $query->orWhere('duitku_order_id', $orderId);
                }
            })
            ->update($update);
    }
}

if (!function_exists('po_midtrans_setting')) {
    function po_midtrans_setting(int $id_entitas): object
    {
        $default = (object) [
            'provider' => 'midtrans',
            'mode' => 'sandbox',
            'is_active' => 0,
            'server_key' => '',
            'client_key' => '',
            'merchant_id' => '',
            'enabled_payments' => 'qris',
            'snap_js_url' => 'https://app.sandbox.midtrans.com/snap/snap.js',
            'snap_api_url' => 'https://app.sandbox.midtrans.com/snap/v1/transactions',
        ];

        try {
            if (!Capsule::schema()->hasTable('tb_payment_gateway_setting')) {
                return $default;
            }

            $row = Capsule::table('tb_payment_gateway_setting')
                ->where('id_entitas', $id_entitas)
                ->where('provider', 'midtrans')
                ->first();

            if (!$row) {
                return $default;
            }

            foreach ($default as $key => $value) {
                if (!isset($row->{$key}) || $row->{$key} === null) {
                    $row->{$key} = $value;
                }
            }

            $mode = strtolower((string) ($row->mode ?? 'sandbox')) === 'production' ? 'production' : 'sandbox';
            $row->mode = $mode;
            $row->snap_js_url = $mode === 'production'
                ? 'https://app.midtrans.com/snap/snap.js'
                : 'https://app.sandbox.midtrans.com/snap/snap.js';
            $row->snap_api_url = $mode === 'production'
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            return $row;
        } catch (Throwable $e) {
            return $default;
        }
    }
}

if (!function_exists('po_midtrans_enabled')) {
    function po_midtrans_enabled(int $id_entitas): bool
    {
        $setting = po_midtrans_setting($id_entitas);
        return (int) ($setting->is_active ?? 0) === 1
            && trim((string) ($setting->server_key ?? '')) !== ''
            && trim((string) ($setting->client_key ?? '')) !== '';
    }
}

if (!function_exists('po_midtrans_http_post_json')) {
    function po_midtrans_http_post_json(string $url, string $serverKey, array $payload): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi cURL PHP belum aktif. Aktifkan cURL di Laragon/hosting untuk memakai Midtrans.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':'),
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error !== '') {
            throw new RuntimeException('Gagal menghubungi Midtrans: ' . $error);
        }

        $json = json_decode((string) $body, true);
        if (!is_array($json)) {
            throw new RuntimeException('Respons Midtrans tidak valid. HTTP ' . $status . ': ' . substr((string) $body, 0, 180));
        }

        if ($status < 200 || $status >= 300) {
            $message = (string) ($json['error_messages'][0] ?? $json['status_message'] ?? $json['message'] ?? ('HTTP ' . $status));
            throw new RuntimeException('Midtrans menolak transaksi: ' . $message);
        }

        return $json;
    }
}

if (!function_exists('po_midtrans_create_snap_token')) {
    function po_midtrans_create_snap_token(int $id_entitas, string $orderId, float $grossAmount, array $items, array $customer): array
    {
        $setting = po_midtrans_setting($id_entitas);
        if ((int) ($setting->is_active ?? 0) !== 1) {
            throw new RuntimeException('Payment gateway Midtrans belum aktif di Mapping Akun.');
        }

        $serverKey = trim((string) ($setting->server_key ?? ''));
        if ($serverKey === '') {
            throw new RuntimeException('Server Key Midtrans belum diisi.');
        }

        $gross = (int) round($grossAmount);
        if ($gross <= 0) {
            throw new RuntimeException('Nominal Midtrans tidak valid.');
        }

        $itemDetails = [];
        $itemTotal = 0;
        foreach ($items as $item) {
            $price = (int) round((float) ($item['harga'] ?? 0));
            $qty = (float) ($item['qty'] ?? 0);
            if ($price <= 0 || $qty <= 0) {
                continue;
            }
            $qtyForMidtrans = max(1, (int) round($qty));
            // Jika qty desimal, gabungkan nilai ke price agar total tetap mendekati subtotal.
            if (abs($qty - $qtyForMidtrans) > 0.0001) {
                $qtyForMidtrans = 1;
                $price = (int) round((float) ($item['subtotal'] ?? ($qty * $price)));
            }
            $itemTotal += $price * $qtyForMidtrans;
            $itemDetails[] = [
                'id' => (string) ($item['id_produk'] ?? ''),
                'price' => $price,
                'quantity' => $qtyForMidtrans,
                'name' => substr((string) ($item['nama_produk'] ?? 'Produk'), 0, 50),
            ];
        }

        if ($itemTotal !== $gross) {
            $diff = $gross - $itemTotal;
            $itemDetails[] = [
                'id' => 'ADJ',
                'price' => $diff,
                'quantity' => 1,
                'name' => $diff >= 0 ? 'PPN / Penyesuaian' : 'Diskon / Penyesuaian',
            ];
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $gross,
            ],
            'enabled_payments' => ['qris'],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => substr((string) ($customer['nama'] ?? 'Pelanggan'), 0, 50),
                'email' => trim((string) ($customer['email'] ?? '')) !== '' ? (string) $customer['email'] : null,
                'phone' => (string) ($customer['no_hp'] ?? ''),
                'billing_address' => [
                    'first_name' => substr((string) ($customer['nama'] ?? 'Pelanggan'), 0, 50),
                    'phone' => (string) ($customer['no_hp'] ?? ''),
                    'address' => substr((string) ($customer['alamat'] ?? ''), 0, 200),
                ],
            ],
            'expiry' => [
                'unit' => 'hour',
                'duration' => 24,
            ],
        ];

        // Hilangkan email null agar JSON bersih.
        if ($payload['customer_details']['email'] === null) {
            unset($payload['customer_details']['email']);
        }

        return po_midtrans_http_post_json((string) $setting->snap_api_url, $serverKey, $payload);
    }
}

if (!function_exists('po_midtrans_update_order_status')) {
    function po_midtrans_update_order_status(string $orderId, string $transactionStatus, string $fraudStatus = ''): void
    {
        if (!Capsule::schema()->hasTable('tb_pesanan_penjualan')) {
            return;
        }

        $paymentStatus = match (strtolower($transactionStatus)) {
            'capture' => strtolower($fraudStatus) === 'challenge' ? 'menunggu_bayar' : 'lunas',
            'settlement' => 'lunas',
            'pending' => 'menunggu_bayar',
            'deny', 'cancel', 'expire', 'failure' => 'gagal',
            default => 'menunggu_bayar',
        };

        $update = ['tanggal_diubah' => date('Y-m-d H:i:s')];
        if (po_table_has_column('tb_pesanan_penjualan', 'status_pembayaran_online')) {
            $update['status_pembayaran_online'] = $paymentStatus;
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'midtrans_transaction_status')) {
            $update['midtrans_transaction_status'] = strtolower($transactionStatus);
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'midtrans_fraud_status')) {
            $update['midtrans_fraud_status'] = strtolower($fraudStatus);
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'midtrans_paid_at') && $paymentStatus === 'lunas') {
            $update['midtrans_paid_at'] = date('Y-m-d H:i:s');
        }

        Capsule::table('tb_pesanan_penjualan')
            ->where(function ($query) use ($orderId) {
                $query->where('no_pesanan_penjualan', $orderId);
                if (po_table_has_column('tb_pesanan_penjualan', 'midtrans_order_id')) {
                    $query->orWhere('midtrans_order_id', $orderId);
                }
            })
            ->update($update);
    }
}

if (!function_exists('po_payment_method_label')) {
    function po_payment_method_label(?string $method): string
    {
        $method = strtolower(trim((string) $method));
        return match ($method) {
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'cod' => 'Bayar di Tempat',
            default => $method !== '' ? ucfirst($method) : '-',
        };
    }
}

if (!function_exists('po_status_label')) {
    function po_status_label(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            'draft' => 'Pesanan Diterima',
            'terkonfirmasi' => 'Pesanan Dikonfirmasi',
            'diproses' => 'Pesanan Sedang Diproses',
            'selesai' => 'Pesanan Selesai - Silakan Ambil di Toko',
            'batal' => 'Pesanan Dibatalkan',
            default => ucfirst($status ?: '-'),
        };
    }
}

if (!function_exists('po_status_class')) {
    function po_status_class(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            'draft' => 'status-draft',
            'terkonfirmasi' => 'status-confirmed',
            'diproses' => 'status-process',
            'selesai' => 'status-done',
            'batal' => 'status-cancel',
            default => 'status-draft',
        };
    }
}

if (!function_exists('po_produk_list')) {
    function po_produk_list(int $id_entitas)
    {
        return Capsule::table('tb_produk as p')
            ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
            ->leftJoin('tb_kategori_produk as kp', 'kp.id_kategori_produk', '=', 'p.id_kategori_produk')
            ->join('tb_saldo_stok as ss', function ($join) {
                $join->on('ss.id_referensi_barang', '=', 'p.id_produk')
                    ->where('ss.jenis_barang', '=', 'produk');
            })
            ->where('p.id_entitas', $id_entitas)
            ->where('ss.id_entitas', $id_entitas)
            ->where('p.status_produk', 1)
            ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi'])
            ->groupBy([
                'p.id_produk',
                'p.id_entitas',
                'p.kode_produk',
                'p.nama_produk',
                'p.harga_jual',
                'p.hpp_standar',
                'p.berat_bersih',
                'p.satuan_berat_bersih',
                'p.gambar_produk',
                's.nama_satuan',
                'kp.nama_kategori_produk',
            ])
            ->havingRaw('SUM(ss.qty_saldo) > 0')
            ->select([
                'p.id_produk',
                'p.id_entitas',
                'p.kode_produk',
                'p.nama_produk',
                'p.harga_jual',
                'p.hpp_standar',
                'p.berat_bersih',
                'p.satuan_berat_bersih',
                'p.gambar_produk',
                's.nama_satuan',
                'kp.nama_kategori_produk',
            ])
            ->selectRaw('SUM(ss.qty_saldo) as stok_tersedia')
            ->orderBy('p.nama_produk', 'asc')
            ->get();
    }
}

if (!function_exists('po_generate_nomor_pesanan')) {
    function po_generate_nomor_pesanan(int $id_entitas): string
    {
        $prefix = 'PJO-' . date('Ym') . '-';

        $last = Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('no_pesanan_penjualan', 'like', $prefix . '%')
            ->orderBy('no_pesanan_penjualan', 'desc')
            ->value('no_pesanan_penjualan');

        $next = 1;

        if ($last) {
            $lastNumber = (int) substr((string) $last, -4);
            $next = $lastNumber + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('po_generate_kode_pelanggan')) {
    function po_generate_kode_pelanggan(int $id_entitas): string
    {
        $prefix = 'PLG-WEB-' . date('Ym') . '-';

        $last = Capsule::table('tb_pelanggan')
            ->where('id_entitas', $id_entitas)
            ->where('kode_pelanggan', 'like', $prefix . '%')
            ->orderBy('kode_pelanggan', 'desc')
            ->value('kode_pelanggan');

        $next = 1;

        if ($last) {
            $lastNumber = (int) substr((string) $last, -4);
            $next = $lastNumber + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('po_get_or_create_pelanggan')) {
    function po_get_or_create_pelanggan(int $id_entitas, string $nama, string $no_hp, string $email, string $alamat): int
    {
        $nama = trim($nama);
        $no_hp = trim($no_hp);
        $email = trim($email);
        $alamat = trim($alamat);

        $query = Capsule::table('tb_pelanggan')
            ->where('id_entitas', $id_entitas)
            ->where('status_aktif', 1);

        if ($no_hp !== '') {
            $query->where('no_hp', $no_hp);
        } elseif ($email !== '') {
            $query->where('email', $email);
        } else {
            $query->where('nama_pelanggan', $nama)
                ->where('alamat', $alamat);
        }

        $pelanggan = $query->first();

        if ($pelanggan) {
            Capsule::table('tb_pelanggan')
                ->where('id_pelanggan', (int) $pelanggan->id_pelanggan)
                ->update([
                    'nama_pelanggan' => $nama,
                    'alamat' => $alamat,
                    'no_hp' => $no_hp !== '' ? $no_hp : $pelanggan->no_hp,
                    'email' => $email !== '' ? $email : $pelanggan->email,
                    'tanggal_diubah' => date('Y-m-d H:i:s'),
                    'diubah_oleh' => null,
                ]);

            return (int) $pelanggan->id_pelanggan;
        }

        return (int) Capsule::table('tb_pelanggan')->insertGetId([
            'id_entitas' => $id_entitas,
            'kode_pelanggan' => po_generate_kode_pelanggan($id_entitas),
            'nama_pelanggan' => $nama,
            'alamat' => $alamat,
            'no_hp' => $no_hp !== '' ? $no_hp : null,
            'email' => $email !== '' ? $email : null,
            'jenis_pelanggan' => 'online',
            'batas_piutang' => 0,
            'tempo_hari' => 0,
            'status_aktif' => 1,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => null,
            'tanggal_diubah' => null,
            'diubah_oleh' => null,
        ]);
    }
}
if (!function_exists('po_order_detail_url')) {
    function po_order_detail_url(int $id_pesanan_penjualan, ?int $id_entitas = null): string
    {
        $id_pesanan_penjualan = max(0, $id_pesanan_penjualan);
        $id_entitas = $id_entitas !== null ? max(1, $id_entitas) : po_id_entitas();

        return po_url('sukses.php?id=' . $id_pesanan_penjualan . '&entitas=' . $id_entitas);
    }
}
