// charts.js - Chart.js configurations for reports

// Ensure Chart.js is loaded (assuming it's included via CDN or local file)

function createChart(canvasId, type, data, options) {
    const ctx = document.getElementById(canvasId);
    if (ctx) {
        new Chart(ctx, {
            type: type,
            data: data,
            options: options
        });
    }
}

// Example usage in specific report pages
function initPoliciesChart() {
    const data = {
        labels: ['January', 'February', 'March'],
        datasets: [{
            label: 'Policies',
            data: [10, 20, 30],
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    };
    createChart('policiesChart', 'bar', data, {});
}

function initClientsChart() {
    // Similar for clients
}

function initClaimsChart() {
    // Similar for claims
}

function initFinancialChart() {
    // Similar for financial
}

// Call init functions based on page