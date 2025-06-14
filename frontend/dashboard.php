<?php
date_default_timezone_set('Asia/Jakarta');

// Ambil data pembayaran dari API
$pembayaran_data = [];
$pembayaran_error = null;
$api_url_pembayaran = 'http://localhost:5000/pembayaran';
$ch = curl_init($api_url_pembayaran);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $pembayaran_data = json_decode($response, true);
    // Urutkan data berdasarkan tanggal_bayar secara descending
    usort($pembayaran_data, function($a, $b) {
        return strtotime($b['tanggal_bayar']) - strtotime($a['tanggal_bayar']);
    });
} else {
    $pembayaran_error = 'Gagal mengambil data: HTTP ' . $http_code;
}

$total_pembayaran = 0;
$jumlah_transaksi = count($pembayaran_data);
$tanggal_terbaru = '-';

if ($jumlah_transaksi > 0) {
    foreach ($pembayaran_data as $item) {
        $total_pembayaran += floatval($item['total_bayar']);
    }
    $tanggal_terbaru = $pembayaran_data[0]['tanggal_bayar']; // Ambil tanggal terbaru setelah diurutkan
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembayaran Zakat</title>
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
        /* Gradient Backgrounds */
        .bg-gradient-green {
            background: linear-gradient(to bottom, #10b981, #34d399);
        }
        .bg-gradient-red {
            background: linear-gradient(to right,rgb(37, 145, 107),rgb(37, 145, 107));
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
            background: linear-gradient(to right,rgb(103, 9, 9), #dc2626);
            transition: background 0.3s ease;
        }
        .btn-gradient:hover {
            background: linear-gradient(to right, #dc2626, #b91c1c);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Navbar -->
    <nav class="bg-white shadow-sm sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-green-600">Zakat Dashboard</h1>
            <div class="flex gap-4">
                <a href="index.php" class="text-gray-600 hover:text-green-600 font-medium flex items-center transition duration-200">
                    <i class="fas fa-home mr-2"></i> Beranda
                </a>
                <a href="pembayaran.php" class="text-gray-600 hover:text-green-600 font-medium flex items-center transition duration-200">
                    <i class="fas fa-plus mr-2"></i> Tambah
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 bg-gradient-green text-white rounded-xl shadow-lg">
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-3xl font-extrabold">Dashboard Pembayaran Zakat</h2>
            <p class="text-gray-200 mt-2">Pantau pembayaran zakat dengan mudah dan cepat.</p>
        </div>

        <!-- Error Message -->
        <?php if ($pembayaran_error): ?>
            <div class="bg-red-100 border border-red-200 text-red-800 p-4 mb-8 rounded-lg flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($pembayaran_error); ?>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <!-- Total Pembayaran -->
            <div class="bg-white rounded-xl shadow-sm p-6 card text-gray-900">
                <div class="flex items-center">
                    <div class="bg-green-100 p-3 rounded-full mr-4">
                        <i class="fas fa-wallet text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Total Pembayaran</p>
                        <p class="text-2xl font-bold">Rp <?= number_format($total_pembayaran, 2); ?></p>
                    </div>
                </div>
            </div>
            <!-- Jumlah Transaksi -->
            <div class="bg-white rounded-xl shadow-sm p-6 card text-gray-900">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-3 rounded-full mr-4">
                        <i class="fas fa-exchange-alt text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Jumlah Transaksi</p>
                        <p class="text-2xl font-bold"><?= $jumlah_transaksi; ?></p>
                    </div>
                </div>
            </div>
            <!-- Tanggal Terbaru -->
            <div class="bg-white rounded-xl shadow-sm p-6 card text-gray-900">
                <div class="flex items-center">
                    <div class="bg-yellow-100 p-3 rounded-full mr-4">
                        <i class="fas fa-calendar-alt text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium">Tanggal Terbaru</p>
                        <p class="text-2xl font-bold"><?= htmlspecialchars($tanggal_terbaru); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Pembayaran -->
        <div class="bg-white text-black rounded-xl shadow-lg p-6 mb-10">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Pembayaran Terbaru</h3>
                <a href="index.php" class="text-green-600 hover:text-green-700 font-medium flex items-center">
                    Lihat Semua <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <?php if ($jumlah_transaksi === 0): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                    <p>Belum ada data pembayaran.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gradient-red text-white">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Nama</th>
                                <th class="px-4 py-3 font-semibold">Jenis Zakat</th>
                                <th class="px-4 py-3 font-semibold text-right">Total (Rp)</th>
                                <th class="px-4 py-3 font-semibold">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($pembayaran_data, 0, 5) as $data): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3"><?= htmlspecialchars($data['nama']); ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($data['jenis_zakat']); ?></td>
                                    <td class="px-4 py-3 text-right"><?= number_format($data['total_bayar'], 2); ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($data['tanggal_bayar']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex flex-wrap justify-center gap-4">
            <a href="pembayaran.php" class="btn-gradient text-white font-semibold px-6 py-3 rounded-full flex items-center text-sm">
                <i class="fas fa-plus mr-2"></i> Tambah Pembayaran
            </a>
            <a href="index.php" class="btn-gradient text-white font-semibold px-6 py-3 rounded-full flex items-center text-sm">
                <i class="fas fa-history mr-2"></i> Riwayat Pembayaran
            </a>
            <a href="beras.php" class="btn-gradient text-white font-semibold px-6 py-3 rounded-full flex items-center text-sm">
                <i class="fas fa-seedling mr-2"></i> Data Beras
            </a>
        </div>
    </div>
</body>
</html>