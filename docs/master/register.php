<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Produksi Pialang Asuransi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(226, 232, 240, 0.8); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div id="app" class="max-w-7xl mx-auto">
        <!-- Header -->
        <header class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Manajemen Produksi PT SAU</h1>
                <p class="text-slate-500">Pencatatan Produksi Pialang Asuransi Berdasarkan Laporan Bulanan</p>
            </div>
            <div class="flex gap-2">
                <button onclick="toggleForm()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-all flex items-center shadow-sm">
                    <span class="mr-2">+</span> Tambah Produksi
                </button>
                <button onclick="exportData()" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-lg font-medium hover:bg-slate-50 transition-all shadow-sm">
                    Ekspor CSV
                </button>
            </div>
        </header>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" id="stats-container">
            <!-- Stats injected by JS -->
        </div>

        <!-- Main Content Section -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Table Section -->
            <div class="lg:col-span-12 glass-card rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4">
                    <h2 class="text-lg font-semibold text-slate-800">Daftar Produksi</h2>
                    <div class="flex gap-2 w-full md:w-auto">
                        <input type="text" id="searchInput" onkeyup="filterData()" placeholder="Cari tertanggung/polis..." class="w-full md:w-64 px-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <select id="monthFilter" onchange="filterData()" class="px-4 py-2 rounded-lg border border-slate-200 focus:outline-none text-sm">
                            <option value="">Semua Bulan</option>
                            <option value="April">April</option>
                            <option value="Mei">Mei</option>
                            <option value="Juni">Juni</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 uppercase text-xs font-bold tracking-wider">
                                <th class="px-6 py-4">Tertanggung</th>
                                <th class="px-6 py-4">Polis / Register</th>
                                <th class="px-6 py-4 text-right">Premi (IDR)</th>
                                <th class="px-6 py-4 text-right">Komisi Bersih</th>
                                <th class="px-6 py-4">Asuransi</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="productionTableBody" class="divide-y divide-slate-100 text-sm text-slate-700">
                            <!-- Data rows injected by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Form Modal -->
        <div id="formModal" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50 p-4">
            <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center sticky top-0 bg-white z-10">
                    <h3 class="text-xl font-bold text-slate-800">Form Produksi Baru</h3>
                    <button onclick="toggleForm()" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
                </div>
                <form id="productionForm" class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Basic Info -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Tahun</label>
                            <input type="number" name="tahun" value="2025" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nama Tertanggung</label>
                            <input type="text" name="tertanggung" required placeholder="PT. Nama Perusahaan" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nomor Polis</label>
                            <input type="text" name="polis" required class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">No. Register Sistem</label>
                            <input type="text" name="register" placeholder="P/M/SAU-..." class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                         <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Bulan Produksi</label>
                            <select name="bulan" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option>April</option>
                                <option>Mei</option>
                                <option>Juni</option>
                            </select>
                        </div>
                        
                        <!-- Dates -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Periode Mulai</label>
                            <input type="date" name="awal" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Periode Akhir</label>
                            <input type="date" name="akhir" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Asuransi</label>
                            <input type="text" name="asuransi" list="asuransiList" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                            <datalist id="asuransiList">
                                <option value="PT Asuransi Tugu Pratama Indonesia">
                                <option value="PT Asuransi Bintang">
                                <option value="PT Asuransi Allianz Utama Indonesia">
                                <option value="PT Asuransi Takaful Keluarga">
                            </datalist>
                        </div>

                        <!-- Financials -->
                        <div class="md:col-span-3 border-t border-slate-100 pt-6 mt-2">
                            <h4 class="font-bold text-slate-800 mb-4">Detail Keuangan</h4>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Nilai Premi (IDR)</label>
                            <input type="number" name="premi" id="input_premi" oninput="calculateFinancials()" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none font-semibold text-blue-700">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Komisi Dasar (Gross)</label>
                            <input type="number" name="komisi" id="input_komisi" oninput="calculateFinancials()" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">PPh 2% (Otomatis)</label>
                            <input type="number" name="pph" id="output_pph" readonly class="w-full px-4 py-2 rounded-lg border border-slate-100 bg-slate-50 outline-none text-slate-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Komisi Agen (Pihak 3)</label>
                            <input type="number" name="komisi_agen" id="input_agen" oninput="calculateFinancials()" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Komisi Bersih SAU</label>
                            <input type="number" name="bersih" id="output_bersih" readonly class="w-full px-4 py-2 rounded-lg border border-slate-100 bg-blue-50 font-bold text-blue-800 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Keterangan</label>
                            <select name="keterangan" class="w-full px-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option>Paid</option>
                                <option>Outstanding</option>
                                <option>Partial Paid</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-8 flex gap-3 justify-end">
                        <button type="button" onclick="toggleForm()" class="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" class="px-8 py-2 rounded-lg bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Mock Data based on the uploaded CSV structure
        let productions = [
            {
                id: 1, tahun: 2025, tertanggung: "PT Nawakara Perkasa Nusantara", polis: "03250000002871", asuransi: "PT Asuransi Tugu Pratama Indonesia",
                bulan: "April", premi: 32592000, komisi: 4074000, pph: 81480, agen: 1197756, bersih: 2794764, status: "Paid", register: "P/M/25/IV/061/SAU-CGL"
            },
            {
                id: 2, tahun: 2025, tertanggung: "PT Jasadirgantara Ekacatra", polis: "P10201115470000", asuransi: "PT Asuransi Bintang",
                bulan: "Juni", premi: 98132, komisi: 14719, pph: 294, agen: 4327, bersih: 10097, status: "Paid", register: "P/M/25/VI/097/SAU-MC"
            },
            {
                id: 3, tahun: 2025, tertanggung: "PT. Rupa Aestetika Teknologi Aktual", polis: "71900000001533-PRM-004", asuransi: "PT. Asuransi Takaful Keluarga",
                bulan: "Mei", premi: 1064470050, komisi: 159670507, pph: 3193410, agen: 0, bersih: 156477097, status: "Paid", register: "P/M/SAU-V/25/HLT/59"
            }
        ];

        function formatIDR(num) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(num);
        }

        function calculateFinancials() {
            const komisi = parseFloat(document.getElementById('input_komisi').value) || 0;
            const agen = parseFloat(document.getElementById('input_agen').value) || 0;
            
            const pph = komisi * 0.02;
            const bersih = komisi - pph - agen;
            
            document.getElementById('output_pph').value = Math.round(pph);
            document.getElementById('output_bersih').value = Math.round(bersih);
        }

        function toggleForm() {
            const modal = document.getElementById('formModal');
            modal.classList.toggle('hidden');
            modal.classList.toggle('flex');
        }

        function renderStats() {
            const totalPremi = productions.reduce((sum, p) => sum + p.premi, 0);
            const totalKomisi = productions.reduce((sum, p) => sum + p.bersih, 0);
            const totalPolis = productions.length;
            const paidCount = productions.filter(p => p.status === 'Paid').length;

            const stats = [
                { title: "Total Premi Bruto", value: formatIDR(totalPremi), icon: "💰", color: "blue" },
                { title: "Total Komisi Bersih", value: formatIDR(totalKomisi), icon: "📈", color: "emerald" },
                { title: "Total Produksi (Polis)", value: totalPolis, icon: "📄", color: "amber" },
                { title: "Sudah Terbayar", value: paidCount, icon: "✅", color: "indigo" }
            ];

            const container = document.getElementById('stats-container');
            container.innerHTML = stats.map(s => `
                <div class="glass-card p-6 rounded-2xl shadow-sm transition-transform hover:scale-[1.02]">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-2xl">${s.icon}</span>
                        <span class="text-xs font-bold px-2 py-1 bg-${s.color}-50 text-${s.color}-600 rounded-full uppercase tracking-tighter">${s.title.split(' ')[0]}</span>
                    </div>
                    <div class="text-sm font-medium text-slate-500 mb-1">${s.title}</div>
                    <div class="text-xl font-bold text-slate-800">${s.value}</div>
                </div>
            `).join('');
        }

        function renderTable(data = productions) {
            const tbody = document.getElementById('productionTableBody');
            tbody.innerHTML = data.map(p => `
                <tr class="hover:bg-slate-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-800">${p.tertanggung}</div>
                        <div class="text-xs text-slate-400">${p.bulan} ${p.tahun}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-xs font-mono text-slate-600 bg-slate-100 px-2 py-1 rounded w-fit mb-1">${p.polis}</div>
                        <div class="text-[10px] text-slate-400 font-medium">${p.register}</div>
                    </td>
                    <td class="px-6 py-4 text-right font-medium">${formatIDR(p.premi)}</td>
                    <td class="px-6 py-4 text-right font-bold text-blue-600">${formatIDR(p.bersih)}</td>
                    <td class="px-6 py-4">
                        <span class="text-xs font-medium text-slate-500 truncate max-w-[150px] inline-block">${p.asuransi}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase ${p.status === 'Paid' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'}">
                            ${p.status}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button onclick="deleteRow(${p.id})" class="text-slate-300 hover:text-red-500 transition-colors">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </td>
                </tr>
            `).join('');
            renderStats();
        }

        function deleteRow(id) {
            if(confirm('Hapus data produksi ini?')) {
                productions = productions.filter(p => p.id !== id);
                renderTable();
            }
        }

        function filterData() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const monthTerm = document.getElementById('monthFilter').value;

            const filtered = productions.filter(p => {
                const matchSearch = p.tertanggung.toLowerCase().includes(searchTerm) || p.polis.toLowerCase().includes(searchTerm);
                const matchMonth = monthTerm === "" || p.bulan === monthTerm;
                return matchSearch && matchMonth;
            });

            renderTable(filtered);
        }

        function exportData() {
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Tertanggung,Polis,Premi,Komisi_Bersih,Bulan\n";
            productions.forEach(p => {
                csvContent += `${p.tertanggung},${p.polis},${p.premi},${p.bersih},${p.bulan}\n`;
            });
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "laporan_produksi_sau.csv");
            document.body.appendChild(link);
            link.click();
        }

        document.getElementById('productionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            const newEntry = {
                id: Date.now(),
                tahun: formData.get('tahun'),
                tertanggung: formData.get('tertanggung'),
                polis: formData.get('polis'),
                asuransi: formData.get('asuransi'),
                bulan: formData.get('bulan'),
                premi: parseFloat(formData.get('premi')),
                komisi: parseFloat(formData.get('komisi')),
                pph: parseFloat(formData.get('pph')),
                agen: parseFloat(formData.get('komisi_agen')) || 0,
                bersih: parseFloat(formData.get('bersih')),
                status: formData.get('keterangan'),
                register: formData.get('register')
            };

            productions.unshift(newEntry);
            this.reset();
            toggleForm();
            renderTable();
        });

        // Initialize
        window.onload = () => {
            renderTable();
        };
    </script>
</body>
</html>