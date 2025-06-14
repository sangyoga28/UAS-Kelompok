<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// URL endpoint API Flask
$api_url = 'http://localhost:5000/beras';

// Inisialisasi variabel
$data = []; // Inisialisasi sebagai array kosong
$error = null;

// Inisialisasi cURL
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Eksekusi permintaan cURL
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Dekode respons JSON
if ($response !== false && $http_code === 200) {
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = 'Gagal mendekode data JSON: ' . json_last_error_msg();
    }
} else {
    $error = 'Gagal mengambil data beras: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
}

// Fungsi untuk menambah data beras
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_harga'])) {
    $data_to_send = ['harga' => floatval($_POST['add_harga'])];
    $api_url_post = 'http://localhost:5000/beras';
    $ch = curl_init($api_url_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_to_send));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 201) {
        $success = "Data beras berhasil ditambahkan.";
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response !== false && $http_code === 200) {
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'Gagal mendekode data JSON setelah penambahan: ' . json_last_error_msg();
            }
        } else {
            $error = 'Gagal mengambil data beras setelah penambahan: ' . ($http_code ? "HTTP $http_code" : 'Koneksi gagal');
        }
    } else {
        $error = "Gagal menambahkan data beras: HTTP $http_code";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Harga Beras</title>
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
        /* Modal Animation */
        .modal {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .modal-hidden {
            opacity: 0;
            transform: scale(0.95);
        }
        .modal-visible {
            opacity: 1;
            transform: scale(1);
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
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="card bg-white rounded-xl shadow-lg p-6 fade-in">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-seedling text-2xl text-green-600"></i>
                    <h1 class="text-2xl font-extrabold text-gray-900">Data Harga Beras</h1>
                </div>
                <div class="flex gap-3">
                    <a href="dashboard.php" class="bg-gray-800 text-white font-semibold px-4 py-2 rounded-lg hover:bg-gray-900 transition flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                    <button onclick="openAddModal()" class="btn-gradient text-white font-semibold px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i> Tambah Data
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 p-4 mb-6 rounded-lg flex items-center fade-in">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php elseif (isset($success)): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 p-4 mb-6 rounded-lg flex items-center fade-in">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-600">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 font-semibold">ID</th>
                            <th class="px-4 py-3 font-semibold">Harga (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data) && is_array($data)): ?>
                            <?php foreach ($data as $record): ?>
                                <tr class="border-b hover:bg-gray-50 table-row">
                                    <td class="px-4 py-3"><?= htmlspecialchars($record['id']); ?></td>
                                    <td class="px-4 py-3"><?= number_format($record['harga'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="px-4 py-4 text-center text-gray-500">
                                    <i class="fas fa-info-circle text-2xl mb-2 block"></i>
                                    Tidak ada data ditemukan
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="addModal" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50">
        <div class="modal bg-white rounded-xl p-6 w-full max-w-md modal-hidden">
            <h2 class="text-xl font-extrabold text-gray-900 text-center mb-6">Tambah Data Beras</h2>
            <form id="addForm" onsubmit="submitAddForm(event)" class="space-y-6">
                <div>
                    <label for="add_harga" class="block text-sm font-semibold text-gray-700 mb-2">Harga (Rp)</label>
                    <div class="relative">
                        <input
                            type="number"
                            id="add_harga"
                            name="add_harga"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                            placeholder="Masukkan harga"
                            step="0.01"
                            required
                        >
                        <i class="fas fa-money-bill absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="submit" class="btn-gradient text-white font-semibold px-5 py-2 rounded-lg flex items-center">
                        <i class="fas fa-save mr-2"></i> Simpan
                    </button>
                    <button type="button" onclick="closeAddModal()" class="bg-gray-800 text-white font-semibold px-5 py-2 rounded-lg hover:bg-gray-900 flex items-center">
                        <i class="fas fa-times mr-2"></i> Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('add_harga').value = '';
            const modal = document.getElementById('addModal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.querySelector('.modal').classList.remove('modal-hidden'), 10);
        }

        function closeAddModal() {
            const modal = document.getElementById('addModal');
            modal.querySelector('.modal').classList.add('modal-hidden');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function submitAddForm(event) {
            event.preventDefault();
            const harga = document.getElementById('add_harga').value;
            if (!harga || isNaN(harga)) {
                alert("Harap masukkan harga yang valid!");
                return;
            }
            fetch('http://localhost:5000/beras', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ harga: parseFloat(harga) })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Gagal menyimpan data');
                }
                return response.json();
            })
            .then(result => {
                if (result.message === "Beras created successfully") {
                    alert("Data beras berhasil ditambahkan!");
                    location.reload();
                } else {
                    throw new Error(result.message || 'Error tidak diketahui');
                }
            })
            .catch(error => {
                alert("Terjadi kesalahan: " + error.message);
            });
        }
    </script>
</body>
</html>