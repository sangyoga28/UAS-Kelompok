<?php
// Set zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Ambil data beras dari API
$beras_data = [];
$beras_error = null;
$api_url_beras = 'http://localhost:5000/beras';
$ch_beras = curl_init($api_url_beras);
curl_setopt($ch_beras, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_beras, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response_beras = curl_exec($ch_beras);
$http_code_beras = curl_getinfo($ch_beras, CURLINFO_HTTP_CODE);
curl_close($ch_beras);
if ($http_code_beras === 200) {
    $beras_data = json_decode($response_beras, true);
} else {
    $beras_error = 'Gagal mengambil data beras: HTTP ' . $http_code_beras;
}

// Proses pengiriman pembayaran
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nama' => $_POST['nama'],
        'jumlah_jiwa' => intval($_POST['jumlah_jiwa']),
        'jenis_zakat' => $_POST['jenis_zakat'],
        'metode_pembayaran' => $_POST['metode_pembayaran'],
        'total_bayar' => floatval($_POST['total_bayar']),
        'nominal_dibayar' => floatval($_POST['nominal_dibayar']),
        'kembalian' => floatval($_POST['kembalian']),
        'tanggal_bayar' => $_POST['tanggal_bayar']
    ];

    // Tambahkan keterangan otomatis
    if ($_POST['jenis_zakat'] === 'beras' && isset($_POST['beras_pilihan']) && !empty($_POST['beras_pilihan'])) {
        $id_beras = $_POST['beras_pilihan'];
        $harga_beras = null;
        foreach ($beras_data as $beras) {
            if ($beras['id'] == $id_beras) {
                $harga_beras = $beras['harga'];
                break;
            }
        }
        if (!$harga_beras) {
            $error = "Error: ID beras tidak valid!";
        } else {
            $total_bayar_beras = 3.5 * floatval($harga_beras) * $data['jumlah_jiwa'];
            $data['total_bayar'] = $total_bayar_beras;
            $data['keterangan'] = "Beras ID $id_beras: " . (3.5 * $data['jumlah_jiwa']) . " Liter";
        }
    } elseif ($_POST['jenis_zakat'] === 'uang' && isset($_POST['pendapatan_tahunan'])) {
        $pendapatan = floatval($_POST['pendapatan_tahunan']);
        $total_bayar_uang = $pendapatan * 0.025;
        $data['total_bayar'] = $total_bayar_uang;
        $data['keterangan'] = "Uang: 2.5% dari Rp " . number_format($pendapatan, 2);
    }

    if (!$error) {
        $api_url = 'http://localhost:5000/pembayaran';
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $error = 'cURL Error: ' . curl_error($ch);
        } else {
            file_put_contents('debug.log', "HTTP Code: $http_code\nResponse: $response\nData Sent: " . json_encode($data) . "\n\n", FILE_APPEND);
        }
        curl_close($ch);

        if ($http_code === 201) {
            $success = "Pembayaran berhasil disimpan.";
        } else {
            $error = "Gagal menyimpan pembayaran: HTTP $http_code\nResponse: $response";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melakukan Pembayaran Zakat</title>
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
        /* Smooth Transitions */
        .form-container {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .form-container:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        /* Gradient Button */
        .btn-gradient {
            background: linear-gradient(to right, #10b981, #34d399);
            transition: background 0.3s ease;
        }
        .btn-gradient:hover {
            background: linear-gradient(to right, #059669, #10b981);
        }
        /* Input Focus Animation */
        .input-icon {
            transition: color 0.3s ease;
        }
        input:focus ~ .input-icon,
        select:focus ~ .input-icon {
            color: #10b981;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const jenisZakat = document.getElementById('jenis_zakat');
            const berasSection = document.getElementById('beras_section');
            const pendapatanSection = document.getElementById('pendapatan_section');
            const berasPilihan = document.getElementById('beras_pilihan');
            const totalBayar = document.getElementById('total_bayar');
            const kembalian = document.getElementById('kembalian');

            if (berasPilihan.options.length > 1) {
                berasPilihan.removeAttribute('disabled');
            }

            function updateTotal() {
                const jumlahJiwa = parseFloat(document.getElementById('jumlah_jiwa').value) || 0;
                const nominalDibayar = parseFloat(document.getElementById('nominal_dibayar').value) || 0;

                if (jenisZakat.value === 'beras' && berasPilihan.value) {
                    const hargaBeras = parseFloat(berasPilihan.options[berasPilihan.selectedIndex].dataset.harga) || 0;
                    const total = jumlahJiwa * 3.5 * hargaBeras;
                    totalBayar.value = total.toFixed(2);
                } else if (jenisZakat.value === 'uang') {
                    const pendapatan = parseFloat(document.getElementById('pendapatan_tahunan').value) || 0;
                    const total = pendapatan * 0.025;
                    totalBayar.value = total.toFixed(2);
                } else {
                    totalBayar.value = '0.00';
                }
                kembalian.value = (nominalDibayar - parseFloat(totalBayar.value) || 0).toFixed(2);
            }

            jenisZakat.addEventListener('change', function() {
                berasSection.classList.toggle('hidden', this.value !== 'beras');
                pendapatanSection.classList.toggle('hidden', this.value !== 'uang');
                if (this.value === 'beras' && berasPilihan.options.length > 1) {
                    berasPilihan.removeAttribute('disabled');
                } else {
                    berasPilihan.setAttribute('disabled', 'disabled');
                }
                updateTotal();
            });

            document.getElementById('jumlah_jiwa').addEventListener('input', updateTotal);
            berasPilihan.addEventListener('change', updateTotal);
            document.getElementById('pendapatan_tahunan').addEventListener('input', updateTotal);
            document.getElementById('nominal_dibayar').addEventListener('input', updateTotal);
        });
    </script>
</head>
<body class="bg-gray-50 font-sans">
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
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="form-container bg-white rounded-2xl shadow-lg p-10">
            <h1 class="text-3xl font-extrabold text-gray-900 text-center mb-4">Tambah Transaksi Zakat</h1>
            <p class="text-gray-600 text-center mb-8 text-sm font-medium">Lengkapi data untuk mencatat pembayaran zakat dengan cepat dan akurat.</p>

            <!-- Messages -->
            <?php if ($beras_error): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-600"></i>
                    <?php echo htmlspecialchars($beras_error); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-600"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-600"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form id="paymentForm" method="post" action="" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nama -->
                <div>
                    <label for="nama" class="block text-sm font-semibold text-gray-700 mb-2">Nama</label>
                    <div class="relative">
                        <input
                            type="text"
                            id="nama"
                            name="nama"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                            required
                        >
                        <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                    </div>
                </div>
                <!-- Jumlah Jiwa -->
                <div>
                    <label for="jumlah_jiwa" class="block text-sm font-semibold text-gray-700 mb-2">Jumlah Jiwa</label>
                    <div class="relative">
                        <input
                            type="number"
                            id="jumlah_jiwa"
                            name="jumlah_jiwa"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                            required
                            min="1"
                        >
                        <i class="fas fa-users absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                    </div>
                </div>
                <!-- Jenis Zakat -->
                <div>
                    <label for="jenis_zakat" class="block text-sm font-semibold text-gray-700 mb-2">Jenis Zakat</label>
                    <div class="relative">
                        <select
                            id="jenis_zakat"
                            name="jenis_zakat"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200 appearance-none"
                            required
                        >
                            <option value="">Pilih Jenis Zakat</option>
                            <option value="beras">Beras</option>
                            <option value="uang">Uang</option>
                        </select>
                        <i class="fas fa-list absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <!-- Beras Section -->
                <div id="beras_section" class="hidden">
                    <label for="beras_pilihan" class="block text-sm font-semibold text-gray-700 mb-2">Jenis Beras</label>
                    <div class="relative">
                        <select
                            id="beras_pilihan"
                            name="beras_pilihan"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200 appearance-none"
                            <?php echo empty($beras_data) ? 'disabled' : ''; ?>
                        >
                            <option value="">Pilih Beras</option>
                            <?php if (!empty($beras_data)): ?>
                                <?php foreach ($beras_data as $beras): ?>
                                    <option value="<?php echo htmlspecialchars($beras['id']); ?>" data-harga="<?php echo htmlspecialchars($beras['harga']); ?>">
                                        <?php echo htmlspecialchars($beras['id']) . ' - Rp ' . number_format($beras['harga'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak ada data beras</option>
                            <?php endif; ?>
                        </select>
                        <i class="fas fa-seedling absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
                <!-- Pendapatan Section -->
                <div id="pendapatan_section" class="hidden">
                    <label for="pendapatan_tahunan" class="block text-sm font-semibold text-gray-700 mb-2">Pendapatan Tahunan (Rp)</label>
                    <div class="relative">
                        <input
                            type="number"
                            id="pendapatan_tahunan"
                            name="pendapatan_tahunan"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                            min="0"
                        >
                        <i class="fas fa-money-bill-wave absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                    </div>
                </div>
                <!-- Metode Pembayaran -->
                <div>
                    <label for="metode_pembayaran" class="block text-sm font-semibold text-gray-700 mb-2">Metode Pembayaran</label>
                    <div class="relative">
                        <input
                            type="text"
                            id="metode_pembayaran"
                            name="metode_pembayaran"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                            required
                        >
                        <i class="fas fa-credit-card absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                    </div>
                </div>
                <!-- Total Bayar -->
                <div>
                    <label for="total_bayar" class="block text-sm font-semibold text-gray-700 mb-2">Total Bayar (Rp)</label>
                    <div class="relative">
                        <input
                            type="number"
                            step="0.01"
                            id="total_bayar"
                            name="total_bayar"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                            readonly
                            required
                        >
                        <i class="fas fa-calculator absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                    </div>
                </div>
                <!-- Nominal Dibayar -->
                <div>
                    <label for="nominal_dibayar" class="block text-sm font-semibold text-gray-700 mb-2">Nominal Dibayar (Rp)</label>
                    <div class="relative">
                        <input
                            type="number"
                            step="0.01"
                            id="nominal_dibayar"
                            name="nominal_dibayar"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                            required
                            min="0"
                        >
                        <i class="fas fa-wallet absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                    </div>
                </div>
                <!-- Kembalian -->
                <div>
                    <label for="kembalian" class="block text-sm font-semibold text-gray-700 mb-2">Kembalian (Rp)</label>
                    <div class="relative">
                        <input
                            type="number"
                            step="0.01"
                            id="kembalian"
                            name="kembalian"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                            readonly
                        >
                        <i class="fas fa-coins absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                    </div>
                </div>
                <!-- Tanggal Bayar -->
                <div>
                    <label for="tanggal_bayar" class="block text-sm font-semibold text-gray-700 mb-2">Tanggal Bayar</label>
                    <div class="relative">
                        <input
                            type="datetime-local"
                            id="tanggal_bayar"
                            name="tanggal_bayar"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                            required
                        >
                        <i class="fas fa-calendar-alt absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 input-icon"></i>
                    </div>
                </div>
                <!-- Buttons -->
                <div class="md:col-span-2 flex justify-center gap-4 mt-8">
                    <a
                        href="dashboard.php"
                        class="bg-gray-800 text-white font-semibold px-6 py-3 rounded-lg hover:bg-gray-900 transition duration-200 flex items-center"
                    >
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                    <button
                        type="submit"
                        class="btn-gradient text-white font-semibold px-6 py-3 rounded-lg flex items-center"
                    >
                        <i class="fas fa-save mr-2"></i> Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>