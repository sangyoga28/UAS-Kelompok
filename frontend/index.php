<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Impor namespace PhpSpreadsheet (dipindahkan ke atas)
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// URL of the Flask API endpoint
$api_url = 'http://localhost:5000/pembayaran'; // Sesuaikan dengan URL yang digunakan

// Initialize cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Execute cURL request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Decode JSON response
$data = json_decode($response, true);
$error = null;
if ($http_code !== 200 || json_last_error() !== JSON_ERROR_NONE) {
    $error = 'Gagal mengambil data pembayaran: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
}

// Initialize variables with defaults
$total_pembayaran = 0.00;
$jumlah_transaksi = 0;
$tanggal_terbaru = 'Tidak ada data';
if (is_array($data) && !empty($data)) {
    $jumlah_transaksi = count($data);
    $total_pembayaran = array_sum(array_column($data, 'total_bayar'));
    $tanggal_terbaru = max(array_column($data, 'tanggal_bayar'));
}

// Fungsi untuk memperbarui data pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $data = [
        'nama' => $_POST['nama'],
        'jumlah_jiwa' => intval($_POST['jumlah_jiwa']),
        'jenis_zakat' => $_POST['jenis_zakat'],
        'metode_pembayaran' => $_POST['metode_pembayaran'],
        'total_bayar' => floatval($_POST['total_bayar']),
        'nominal_dibayar' => floatval($_POST['nominal_dibayar']),
        'kembalian' => floatval($_POST['kembalian']),
        'keterangan' => $_POST['keterangan'],
        'tanggal_bayar' => $_POST['tanggal_bayar']
    ];

    $api_url_put = "http://localhost:5000/pembayaran/$id";
    $ch = curl_init($api_url_put);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $success = "Data pembayaran berhasil diperbarui.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
        if (is_array($data) && !empty($data)) {
            $jumlah_transaksi = count($data);
            $total_pembayaran = array_sum(array_column($data, 'total_bayar'));
            $tanggal_terbaru = max(array_column($data, 'tanggal_bayar'));
        }
    } else {
        $error = "Gagal memperbarui data pembayaran: HTTP $http_code";
    }
}

// Fungsi untuk menghapus data pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $api_url_delete = "http://localhost:5000/pembayaran/$id";
    $ch = curl_init($api_url_delete);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $success = "Data pembayaran berhasil dihapus.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $data = json_decode($response, true);
        if (is_array($data) && !empty($data)) {
            $jumlah_transaksi = count($data);
            $total_pembayaran = array_sum(array_column($data, 'total_bayar'));
            $tanggal_terbaru = max(array_column($data, 'tanggal_bayar'));
        }
    } else {
        $error = "Gagal menghapus data pembayaran: HTTP $http_code";
    }
}

// Fungsi untuk generate Excel
if (isset($_GET['generate_excel']) && !$error && !empty($data)) {
    require 'vendor/autoload.php'; // Pastikan path ke autoload.php benar

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Jumlah Jiwa');
    $sheet->setCellValue('C1', 'Jenis Zakat');
    $sheet->setCellValue('D1', 'Nama');
    $sheet->setCellValue('E1', 'Metode Pembayaran');
    $sheet->setCellValue('F1', 'Total Bayar');
    $sheet->setCellValue('G1', 'Nominal Dibayar');
    $sheet->setCellValue('H1', 'Kembalian');
    $sheet->setCellValue('I1', 'Keterangan');
    $sheet->setCellValue('J1', 'Tanggal Bayar');

    // Data
    $row = 2;
    foreach ($data as $record) {
        $sheet->setCellValue('A' . $row, $record['id']);
        $sheet->setCellValue('B' . $row, $record['jumlah_jiwa']);
        $sheet->setCellValue('C' . $row, $record['jenis_zakat']);
        $sheet->setCellValue('D' . $row, $record['nama']);
        $sheet->setCellValue('E' . $row, $record['metode_pembayaran']);
        $sheet->setCellValue('F' . $row, $record['total_bayar']);
        $sheet->setCellValue('G' . $row, $record['nominal_dibayar']);
        $sheet->setCellValue('H' . $row, $record['kembalian']);
        $sheet->setCellValue('I' . $row, $record['keterangan']);
        $sheet->setCellValue('J' . $row, $record['tanggal_bayar']);
        $row++;
    }

    // Styling
    $sheet->getStyle('A1:J1')->getFont()->setBold(true);
    $sheet->getStyle('A1:J' . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

    // Unduh file
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="pembayaran_zakat_' . date('Ymd_His') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pembayaran Zakat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #10b981;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #059669;
        }
        /* Card Hover Effect */
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
        }
        /* Table Row Hover Effect */
        .table-row {
            transition: background-color 0.2s ease;
        }
        /* Gradient Button */
        .btn-gradient {
            background: linear-gradient(to right, #10b981, #34d399);
            transition: background 0.3s ease;
        }
        .btn-gradient:hover {
            background: linear-gradient(to right, #059669, #10b981);
        }
        /* Fade-in Animation */
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-green-600">Zakat Dashboard</h1>
            <div class="flex gap-4">
                <a href="dashboard.php" class="text-gray-600 hover:text-green-600 font-medium flex items-center transition duration-200">
                    <i class="fas fa-home mr-2"></i> Beranda
                </a>
                <a href="pembayaran.php" class="text-gray-600 hover:text-green-600 font-medium flex items-center transition duration-200">
                    <i class="fas fa-plus mr-2"></i> Tambah
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8 fade-in">
            <h1 class="text-3xl font-extrabold text-gray-900">Riwayat Pembayaran Zakat</h1>
            <p class="text-gray-500 mt-2">Lihat semua transaksi zakat yang telah dicatat.</p>
        </div>

        <!-- Error or Success Message -->
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 mb-8 rounded-lg flex items-center fade-in">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($error); ?>
            </div>
        <?php elseif (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 p-4 mb-8 rounded-lg flex items-center fade-in">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <!-- Total Pembayaran -->
            <div class="bg-white rounded-xl shadow-sm p-6 card fade-in">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-full mr-4">
                        <i class="fas fa-wallet text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Pembayaran</p>
                        <p class="text-2xl font-bold text-gray-900">Rp <?= number_format($total_pembayaran, 2); ?></p>
                    </div>
                </div>
            </div>
            <!-- Jumlah Transaksi -->
            <div class="bg-white rounded-xl shadow-sm p-6 card fade-in">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full mr-4">
                        <i class="fas fa-exchange-alt text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Jumlah Transaksi</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $jumlah_transaksi; ?></p>
                    </div>
                </div>
            </div>
            <!-- Tanggal Terbaru -->
            <div class="bg-white rounded-xl shadow-sm p-6 card fade-in">
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <i class="fas fa-calendar-alt text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Tanggal Terbaru</p>
                        <p class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($tanggal_terbaru); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-10 fade-in">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Daftar Semua Pembayaran</h2>
                <a href="?generate_excel" class="text-sm text-green-600 hover:text-green-700 font-medium flex items-center">
                    <i class="fas fa-download mr-2"></i> Unduh Excel
                </a>
            </div>
            <?php if ($jumlah_transaksi === 0): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                    <p>Belum ada data pembayaran.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 font-semibold">ID</th>
                                <th class="px-4 py-3 font-semibold">Nama</th>
                                <th class="px-4 py-3 font-semibold">Jenis Zakat</th>
                                <th class="px-4 py-3 font-semibold text-right">Total Bayar (Rp)</th>
                                <th class="px-4 py-3 font-semibold">Tanggal Bayar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $record): ?>
                                <tr class="border-b hover:bg-gray-50 table-row">
                                    <td class="px-4 py-3"><?= htmlspecialchars($record['id']); ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($record['nama']); ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($record['jenis_zakat']); ?></td>
                                    <td class="px-4 py-3 text-right"><?= number_format($record['total_bayar'], 2); ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($record['tanggal_bayar']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex flex-wrap justify-center gap-4 fade-in">
            <a href="pembayaran.php" class="btn-gradient text-white font-semibold px-6 py-3 rounded-full flex items-center text-sm">
                <i class="fas fa-plus mr-2"></i> Tambah Pembayaran
            </a>
            <a href="dashboard.php" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold px-6 py-3 rounded-full flex items-center text-sm">
                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
            </a>
            <a href="beras.php" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold px-6 py-3 rounded-full flex items-center text-sm">
                <i class="fas fa-seedling mr-2"></i> Data Beras
            </a>
        </div>
    </div>
</body>
</html>