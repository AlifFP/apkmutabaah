<?php
// KONFIGURASI SERVER: IZINKAN EKSEKUSI LAMA
set_time_limit(600); // 10 menit batas waktu
error_reporting(0);  // Sembunyikan error PHP kecil agar log bersih

// HEADER UNTUK BYPASS CORS DAN CONTENT TYPE
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain");

// --- 1. TANGKAP INPUT (VIA GET/REQUEST) ---
$usr = $_REQUEST['username'] ?? '';
$pwd = $_REQUEST['password'] ?? '';
$gen = $_REQUEST['gender'] ?? 'male';
$h_start = $_REQUEST['haid_start'] ?? '';
$h_end = $_REQUEST['haid_end'] ?? '';

if (!$usr || !$pwd) die("ERROR: Kredensial kosong.");

// --- 2. KONFIGURASI URL TARGET ---
$BASE = 'https://smpitnurhidayah.dsd.co.id/gobit/';
$URL_LOGIN = $BASE . 'index.php'; 
$URL_SUBMIT = $BASE . 'mutabaah_input.php';

// --- 3. PERSIAPAN CURL (USER AGENT CHROME) ---
$ch = curl_init();
$cookie = tempnam(sys_get_temp_dir(), 'COOKIE_GOBIT'); 
$agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36';

$headers_std = [
    'User-Agent: ' . $agent,
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
    'Connection: keep-alive',
    'Content-Type: application/x-www-form-urlencoded' // Penting untuk POST
];

// --- 4. FUNGSI LOGIN ---
function login($ch, $url, $usr, $pwd, $cookie, $headers) {
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['username' => $usr, 'password' => $pwd, 'login' => '1']),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true 
    ]);
    
    $res = curl_exec($ch);
    $url_akhir = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    
    if (strpos($url_akhir, 'mutabaah') !== false || strpos($res, 'Dashboard') !== false) {
        echo "[LOGIN] BERHASIL! Masuk ke sistem.\n";
        return true;
    }
    echo "[LOGIN] GAGAL. Cek username/password atau koneksi. Effective URL: $url_akhir\n";
    return false;
}

// --- 5. FUNGSI DEBUG: BRUTE-FORCE NILAI PAYLOAD ---
function debugSubmitPayload($ch, $url, $headers) {
    echo "\n\n--- DEBUG MODE AKTIF: MENGUJI NILAI 1-5 UNTUK 'Subuh' ---\n";
    $date = (new DateTime())->format('Y-m-d');
    $found_value = "BELUM DITEMUKAN";

    // Kita coba nilai 1 sampai 5 untuk Subuh (Asumsi nilai tertinggi adalah 5)
    for ($test_val = 1; $test_val <= 5; $test_val++) {
        $test_str = (string)$test_val;
        
        // PAYLOAD TEST (Hanya Subuh yang diubah, sisanya minimal/0 agar tidak terjadi conflict)
        $payload = [
            'tanggal' => $date,
            'action' => 'save_data', 
            
            // --- FIELD YANG DITES (Subuh) ---
            'Subuh' => $test_str, 
            
            // --- FIELD LAIN DIBUAT AMAN (0/TIDAK) ---
            'Dhuhur' => '0', 'Ashar' => '0', 'Maghrib' => '0', 'Isya' => '0',
            'Tarawih/Tahajud' => '0', 'Dhuha' => '0', 'Rawatib' => '0', 'PuasaWajib/Sunnah' => '0',
            'TadarusAlQuran' => '0', 'BacaBukuAgama' => '0', 'BacaBukuPelajaran' => '0', 'DzikirAlMatsurat' => '0',
            'Masuksekolahtepatwaktu' => '0', 'Membantuorangtua' => '0', 'Shadaqoh' => '0', 'infaq' => '0',
            'Berhalangan/Haid' => '0', 'status_haid' => '0',
            'catatan' => "DEBUG TEST VAL: $test_str"
        ];
       
        curl_setopt($ch, CURLOPT_URL => $url);
        curl_setopt($ch, CURLOPT_POST => true);
        curl_setopt($ch, CURLOPT_POSTFIELDS => http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER => true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION => false); 
        curl_setopt($ch, CURLOPT_HEADER => false);       
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       
        if (strpos($response, 'berhasil') !== false || strpos($response, 'sukses') !== false || strpos($response, 'Data tersimpan') !== false) {
            $found_value = $test_str;
            echo "\n[[KUNCI DITEMUKAN!]] Nilai Sukses untuk 'Subuh' (Berjamaah) adalah: $test_str\n";
            break; 
        } else {
            echo "[TEST VAL $test_str] GAGAL (HTTP Code: $http_code). Mencoba nilai berikutnya...\n";
        }
        usleep(100000); 
    }

    if ($found_value === "BELUM DITEMUKAN") {
        echo "\n!!! FATAL: Tidak ada nilai 1-5 yang diterima server. KITA PERLU MENGUJI NILAI LAIN (MISALNYA 6-10)!\n";
    }
    return $found_value;
}
// --- AKHIR FUNGSI DEBUG ---


// --- 6. EKSEKUSI UTAMA (DEBUG MODE) ---
if (login($ch, $URL_LOGIN, $usr, $pwd, $cookie, $headers_std)) {
    
    $kunci_subuh = debugSubmitPayload($ch, $URL_SUBMIT, $headers_std);

    echo "\n[SISTEM] Hasil Kunci Subuh (Berjamaah) adalah: $kunci_subuh\n";
    
    if ($kunci_subuh !== "BELUM DITEMUKAN") {
        echo "\nKEY FOUND! KIRIM NILAI '$kunci_subuh' INI KEPADAKU AGAR KITA BISA MEMBUAT SCRIPT FINALNYA.\n";
    }
}

// Bersihkan
@unlink($cookie);
curl_close($ch);
?>
