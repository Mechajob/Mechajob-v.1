<?php
// --- SIMULASI MENGAMBIL VARIABEL LINGKUNGAN ---
$merchantCode = 'D99999'; 
$apiKey = '9a8b7c6d5e4f3g2h1i0j'; 
$serverUrl = 'https://api.mechajob.com'; 
$duitkuApiBase = 'https://sandbox.duitku.com/webapi/api';

// --- DATA TRANSAKSI (Asumsi diterima dari request POST aplikasi mobile) ---
$data_input = json_decode(file_get_contents('php://input'), true);

$orderId = 'MJ-' . uniqid(); // ID unik pesanan Anda
$amount = $data_input['amount'] ?? 15000; // Default Rp 15.000
$customerEmail = $data_input['email'] ?? 'test@mechajob.com';
$customerName = $data_input['name'] ?? 'Pelanggan Mechajob';
$productDetails = $data_input['product'] ?? 'Langganan Premium';
$paymentMethod = $data_input['method'] ?? 'VA'; // Contoh: Virtual Account

// --- 1. MEMBUAT SIGNATURE ---
// Format: MD5(merchantCode + orderId + amount + apiKey)
$signature = md5($merchantCode . $orderId . $amount . $apiKey);

// --- 2. DATA UNTUK DIKIRIM KE DUITKU ---
$data_request = [
    'merchantCode' => $merchantCode,
    'paymentAmount' => $amount,
    'merchantOrderId' => $orderId,
    'productDetails' => $productDetails,
    'email' => $customerEmail,
    'customerVaName' => $customerName,
    'returnUrl' => $serverUrl . '/return_handler.php?order=' . $orderId, // URL kembali ke app
    'callbackUrl' => $serverUrl . '/callback_handler.php', // URL notifikasi status
    'signature' => $signature,
    'expiryPeriod' => 10, // 10 menit
    'paymentMethod' => $paymentMethod
];

// --- 3. PANGGIL API DUITKU ---
$url = $duitkuApiBase . '/inquiry/checkout';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_request));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// --- 4. KIRIM RESPON KE APLIKASI MOBILE ---
header('Content-Type: application/json');
if (isset($result['paymentUrl'])) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'orderId' => $orderId,
        'amount' => $amount,
        'paymentUrl' => $result['paymentUrl'] // Ini yang akan dibuka di WebView
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal membuat transaksi: ' . ($result['message'] ?? 'Error tidak diketahui')
    ]);
}
?>

