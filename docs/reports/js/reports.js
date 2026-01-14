// Data Mock (Nanti akan diganti dengan API dari modul lain)
const mockData = {
    policies: [
        { id: 1, status: 'Active', premium: 500000, client: 'PT ABC' },
        { id: 2, status: 'Pending', premium: 750000, client: 'CV XYZ' },
        { id: 3, status: 'Expired', premium: 300000, client: 'John Doe' },
    ],
    clients: [
        { id: 1, name: 'PT ABC', policies: 3, status: 'Active' },
        { id: 2, name: 'CV XYZ', policies: 2, status: 'Active' },
        { id: 3, name: 'John Doe', policies: 1, status: 'Inactive' },
    ],
    claims: [
        { id: 1, policyId: 1, status: 'Pending', amount: 1000000 },
        { id: 2, policyId: 2, status: 'Approved', amount: 500000 },
    ]
};

// Fungsi untuk mengambil data dari modul lain (akan diimplementasi dengan API)
async function fetchDataFromModules() {
    // Contoh integrasi:
    // 1. Policy Module → GET /api/policies
    // 2. Client Module → GET /api/clients
    // 3. Claims Module → GET /api/claims
    console.log('Fetching data from modules...');
    return mockData; // Untuk sementara pakai mock
}

// Update Quick Stats
async function updateQuickStats() {
    const data = await fetchDataFromModules();
    document.getElementById('totalPolicies').textContent = data.policies.length;
    document.getElementById('totalClients').textContent = data.clients.length;
    document.getElementById('pendingClaims').textContent = 
        data.claims.filter(c => c.status === 'Pending').length;
    const totalPremium = data.policies.reduce((sum, p) => sum + p.premium, 0);
    document.getElementById('totalPremium').textContent = `Rp ${totalPremium.toLocaleString()}`;
}

// Chart.js Integration
function renderCharts(data) {
    // Policy Status Chart
    const policyCtx = document.getElementById('policyChart').getContext('2d');
    const statusCount = {};
    data.policies.forEach(p => {
        statusCount[p.status] = (statusCount[p.status] || 0) + 1;
    });

    new Chart(policyCtx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusCount),
            datasets: [{
                data: Object.values(statusCount),
                backgroundColor: ['#10B981', '#F59E0B', '#EF4444']
            }]
        }
    });

    // Claims Trend Chart
    const claimsCtx = document.getElementById('claimsChart').getContext('2d');
    new Chart(claimsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei'],
            datasets: [{
                label: 'Jumlah Klaim',
                data: [3, 5, 2, 8, 4],
                borderColor: '#3B82F6',
                fill: false
            }]
        }
    });
}

// Inisialisasi
document.addEventListener('DOMContentLoaded', async () => {
    const data = await fetchDataFromModules();
    await updateQuickStats();
    renderCharts(data);
});