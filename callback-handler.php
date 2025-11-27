<?php
// --- SIMULASI MENGAMBIL VARIABEL LINGKUNGAN ---
$apiKey = '6402386a69d598d6bce8f7603c95e7bb'; 

// --- 1. BACA DATA CALLBACK DARI DUITKU ---
$data_json = file_get_contents('php://input');
$transaction = json_decode($data_json, true);

$merchantCode = $transaction['merchantCode'] ?? '';
$amount = $transaction['amount'] ?? 0;
$orderId = $transaction['merchantOrderId'] ?? '';
$resultCode = $transaction['resultCode'] ?? ''; // '00' = Sukses
$signatureDuitku = $transaction['signature'] ?? '';

// --- 2. VALIDASI SIGNATURE CALLBACK ---
// Format: MD5(merchantCode + amount + orderId + resultCode + apiKey)
$mySignature = md5($merchantCode . $amount . $orderId . $resultCode . $apiKey);

if ($mySignature !== $signatureDuitku) {
    // Tanda tangan tidak valid, abaikan
    http_response_code(400);
    die('Invalid Signature');
}

// --- 3. PROSES UPDATE STATUS PESANAN DI DATABASE ---
// Di sini Anda akan terhubung ke Database MySQL/PostgreSQL Anda

function update_order_status($orderId, $status) {
    // SIMULASI: Di lingkungan nyata, Anda akan menjalankan query UPDATE ke database
    $log_message = date('Y-m-d H:i:s') . " | Order ID: $orderId | Status: $status\n";
    file_put_contents('db_log.txt', $log_message, FILE_APPEND);
}

if ($resultCode === '00') {
    // Transaksi Sukses
    update_order_status($orderId, 'PAID');
} else if ($resultCode === '01') {
    // Transaksi Gagal/Kadaluwarsa
    update_order_status($orderId, 'FAILED_EXPIRED');
} else {
    // Status lain
    update_order_status($orderId, 'OTHER_CODE_' . $resultCode);
}

// --- 4. WAJIB: Beri Respons OK ke Duitku ---
http_response_code(200);
echo 'OK';
?>
