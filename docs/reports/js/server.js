const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');
const WebSocket = require('ws');
const app = express();

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('public'));

// Sample Claims Data
const claims = [
  {
    id: 1,
    claim_id: "CL-2024-001",
    policy_number: "POL-2024-001",
    client_name: "John Smith",
    client_email: "john@example.com",
    client_phone: "+1 234-567-8900",
    client_address: "123 Main St, New York, NY",
    claim_type: "Vehicle Accident",
    amount: 12500,
    status: "pending",
    priority: "high",
    date_filed: "2024-01-15",
    date_incident: "2024-01-14",
    assigned_to: "Sarah Johnson",
    assigned_email: "sarah@gibsysnet.com",
    adjuster_id: "ADJ-001",
    description: "Rear-end collision at Main Street intersection. Minor injuries reported.",
    location: "Main St & 5th Ave, New York",
    documents: 3,
    created_at: "2024-01-15T10:30:00Z",
    updated_at: "2024-01-15T10:30:00Z"
  },
  {
    id: 2,
    claim_id: "CL-2024-002",
    policy_number: "POL-2024-002",
    client_name: "Emma Davis",
    client_email: "emma@example.com",
    client_phone: "+1 234-567-8901",
    client_address: "456 Oak Ave, Los Angeles, CA",
    claim_type: "Property Damage",
    amount: 8500,
    status: "approved",
    priority: "medium",
    date_filed: "2024-01-12",
    date_incident: "2024-01-10",
    assigned_to: "Mike Wilson",
    assigned_email: "mike@gibsysnet.com",
    adjuster_id: "ADJ-002",
    description: "Water damage from roof leak due to heavy rainfall.",
    location: "Residential property",
    documents: 5,
    created_at: "2024-01-12T14:20:00Z",
    updated_at: "2024-01-16T11:30:00Z"
  },
  {
    id: 3,
    claim_id: "CL-2024-003",
    policy_number: "POL-2024-003",
    client_name: "Robert Brown",
    client_email: "robert@example.com",
    client_phone: "+1 234-567-8902",
    client_address: "789 Pine Rd, Chicago, IL",
    claim_type: "Medical",
    amount: 3200,
    status: "in-review",
    priority: "high",
    date_filed: "2024-01-10",
    date_incident: "2024-01-08",
    assigned_to: "Lisa Anderson",
    assigned_email: "lisa@gibsysnet.com",
    adjuster_id: "ADJ-003",
    description: "Emergency room visit coverage for sudden illness.",
    location: "General Hospital",
    documents: 2,
    created_at: "2024-01-10T09:15:00Z",
    updated_at: "2024-01-15T16:45:00Z"
  },
  {
    id: 4,
    claim_id: "CL-2024-004",
    policy_number: "POL-2024-004",
    client_name: "Maria Garcia",
    client_email: "maria@example.com",
    client_phone: "+1 234-567-8903",
    client_address: "101 Maple Blvd, Miami, FL",
    claim_type: "Travel",
    amount: 1500,
    status: "rejected",
    priority: "low",
    date_filed: "2024-01-08",
    date_incident: "2024-01-05",
    assigned_to: "David Miller",
    assigned_email: "david@gibsysnet.com",
    adjuster_id: "ADJ-004",
    description: "Trip cancellation due to unexpected illness.",
    location: "International",
    documents: 4,
    created_at: "2024-01-08T11:45:00Z",
    updated_at: "2024-01-12T13:20:00Z"
  },
  {
    id: 5,
    claim_id: "CL-2024-005",
    policy_number: "POL-2024-005",
    client_name: "Thomas Taylor",
    client_email: "thomas@example.com",
    client_phone: "+1 234-567-8904",
    client_address: "202 Elm St, Houston, TX",
    claim_type: "Vehicle Theft",
    amount: 25000,
    status: "settled",
    priority: "high",
    date_filed: "2024-01-05",
    date_incident: "2024-01-03",
    assigned_to: "Jennifer Lee",
    assigned_email: "jennifer@gibsysnet.com",
    adjuster_id: "ADJ-005",
    description: "Car theft from shopping mall parking lot.",
    location: "City Mall Parking",
    documents: 6,
    created_at: "2024-01-05T08:30:00Z",
    updated_at: "2024-01-18T10:15:00Z"
  },
  {
    id: 6,
    claim_id: "CL-2024-006",
    policy_number: "POL-2024-006",
    client_name: "Sophia Wilson",
    client_email: "sophia@example.com",
    client_phone: "+1 234-567-8905",
    client_address: "303 Cedar Ln, Phoenix, AZ",
    claim_type: "Property Damage",
    amount: 5200,
    status: "pending",
    priority: "medium",
    date_filed: "2024-01-18",
    date_incident: "2024-01-17",
    assigned_to: "Sarah Johnson",
    assigned_email: "sarah@gibsysnet.com",
    adjuster_id: "ADJ-001",
    description: "Storm damage to roof and windows.",
    location: "Residential property",
    documents: 3,
    created_at: "2024-01-18T14:10:00Z",
    updated_at: "2024-01-18T14:10:00Z"
  },
  {
    id: 7,
    claim_id: "CL-2024-007",
    policy_number: "POL-2024-007",
    client_name: "James Martinez",
    client_email: "james@example.com",
    client_phone: "+1 234-567-8906",
    client_address: "404 Birch Dr, Seattle, WA",
    claim_type: "Medical",
    amount: 4300,
    status: "in-review",
    priority: "high",
    date_filed: "2024-01-16",
    date_incident: "2024-01-15",
    assigned_to: "Lisa Anderson",
    assigned_email: "lisa@gibsysnet.com",
    adjuster_id: "ADJ-003",
    description: "Surgical procedure coverage.",
    location: "Medical Center",
    documents: 5,
    created_at: "2024-01-16T16:20:00Z",
    updated_at: "2024-01-18T09:30:00Z"
  }
];

// Sample Policies Data
const policies = [
  {
    id: 1,
    policy_number: "POL-2024-001",
    client_name: "John Smith",
    client_email: "john@example.com",
    policy_type: "Auto Insurance",
    coverage_amount: 50000,
    premium: 1200,
    status: "active",
    start_date: "2024-01-01",
    end_date: "2025-01-01",
    claims_count: 1,
    agent_name: "Sarah Johnson",
    created_at: "2024-01-01T00:00:00Z"
  },
  {
    id: 2,
    policy_number: "POL-2024-002",
    client_name: "Emma Davis",
    client_email: "emma@example.com",
    policy_type: "Home Insurance",
    coverage_amount: 300000,
    premium: 1800,
    status: "active",
    start_date: "2024-01-01",
    end_date: "2025-01-01",
    claims_count: 1,
    agent_name: "Mike Wilson",
    created_at: "2024-01-01T00:00:00Z"
  },
  {
    id: 3,
    policy_number: "POL-2024-003",
    client_name: "Robert Brown",
    client_email: "robert@example.com",
    policy_type: "Health Insurance",
    coverage_amount: 100000,
    premium: 3500,
    status: "active",
    start_date: "2024-01-01",
    end_date: "2025-01-01",
    claims_count: 1,
    agent_name: "Lisa Anderson",
    created_at: "2024-01-01T00:00:00Z"
  },
  {
    id: 4,
    policy_number: "POL-2024-004",
    client_name: "Maria Garcia",
    client_email: "maria@example.com",
    policy_type: "Travel Insurance",
    coverage_amount: 50000,
    premium: 500,
    status: "expired",
    start_date: "2023-12-01",
    end_date: "2024-01-31",
    claims_count: 1,
    agent_name: "David Miller",
    created_at: "2023-12-01T00:00:00Z"
  },
  {
    id: 5,
    policy_number: "POL-2024-005",
    client_name: "Thomas Taylor",
    client_email: "thomas@example.com",
    policy_type: "Auto Insurance",
    coverage_amount: 75000,
    premium: 1500,
    status: "active",
    start_date: "2024-01-01",
    end_date: "2025-01-01",
    claims_count: 1,
    agent_name: "Jennifer Lee",
    created_at: "2024-01-01T00:00:00Z"
  }
];

// Sample Statistics
const statistics = {
  claims: {
    totalPendingClaims: claims.filter(c => c.status === 'pending').length,
    totalApprovedClaims: claims.filter(c => c.status === 'approved').length,
    totalRejectedClaims: claims.filter(c => c.status === 'rejected').length,
    totalClaimAmount: claims.reduce((sum, claim) => sum + claim.amount, 0),
    pendingTrend: 12.5,
    approvedTrend: 8.3,
    rejectedTrend: -5.2,
    amountTrend: 15.7
  },
  policies: {
    totalActivePolicies: policies.filter(p => p.status === 'active').length,
    totalExpiredPolicies: policies.filter(p => p.status === 'expired').length,
    totalPremiumAmount: policies.reduce((sum, policy) => sum + policy.premium, 0),
    totalCoverageAmount: policies.reduce((sum, policy) => sum + policy.coverage_amount, 0),
    activeTrend: 5.2,
    premiumTrend: 8.7
  }
};

// Sample Activities
const activities = [
  {
    id: 1,
    action: "Claim approved",
    claim_id: "CL-2024-002",
    user: "Sarah Johnson",
    user_role: "Senior Adjuster",
    time: "10 minutes ago",
    icon: "check-circle",
    color: "success",
    timestamp: "2024-01-16T11:30:00Z",
    details: "Claim approved and payment processed"
  },
  {
    id: 2,
    action: "New claim filed",
    claim_id: "CL-2024-006",
    user: "John Doe",
    user_role: "Client",
    time: "25 minutes ago",
    icon: "plus-circle",
    color: "primary",
    timestamp: "2024-01-18T14:10:00Z",
    details: "New property damage claim submitted"
  },
  {
    id: 3,
    action: "Document uploaded",
    claim_id: "CL-2024-001",
    user: "Mike Wilson",
    user_role: "Adjuster",
    time: "1 hour ago",
    icon: "file-upload",
    color: "info",
    timestamp: "2024-01-18T13:15:00Z",
    details: "Police report uploaded"
  },
  {
    id: 4,
    action: "Claim status updated",
    claim_id: "CL-2024-003",
    user: "Lisa Anderson",
    user_role: "Medical Adjuster",
    time: "2 hours ago",
    icon: "sync",
    color: "warning",
    timestamp: "2024-01-18T09:30:00Z",
    details: "Status changed to 'In Review'"
  },
  {
    id: 5,
    action: "Policy renewed",
    policy_id: "POL-2024-001",
    user: "Sarah Johnson",
    user_role: "Agent",
    time: "3 hours ago",
    icon: "redo",
    color: "success",
    timestamp: "2024-01-18T08:45:00Z",
    details: "Auto insurance policy renewed for 1 year"
  }
];

// Sample Timeline
const timeline = [
  {
    id: 1,
    claim_id: "CL-2024-001",
    action: "Claim filed",
    timestamp: "2024-01-15T10:30:00Z",
    user: "John Smith",
    user_role: "Client",
    details: "Initial claim submission online"
  },
  {
    id: 2,
    claim_id: "CL-2024-001",
    action: "Document uploaded",
    timestamp: "2024-01-15T14:45:00Z",
    user: "John Smith",
    user_role: "Client",
    details: "Accident report and photos attached"
  },
  {
    id: 3,
    claim_id: "CL-2024-001",
    action: "Assigned to adjuster",
    timestamp: "2024-01-16T09:15:00Z",
    user: "System",
    user_role: "System",
    details: "Automatically assigned to Sarah Johnson"
  },
  {
    id: 4,
    claim_id: "CL-2024-002",
    action: "Claim approved",
    timestamp: "2024-01-16T11:30:00Z",
    user: "Sarah Johnson",
    user_role: "Senior Adjuster",
    details: "Documentation verified, payment approved"
  },
  {
    id: 5,
    claim_id: "CL-2024-002",
    action: "Payment processed",
    timestamp: "2024-01-16T12:15:00Z",
    user: "System",
    user_role: "System",
    details: "$8,500 transferred to client account"
  }
];

// Helper function to filter data
function filterData(data, filters) {
  let filtered = [...data];
  
  if (filters.status) {
    filtered = filtered.filter(item => item.status === filters.status);
  }
  
  if (filters.priority) {
    filtered = filtered.filter(item => item.priority === filters.priority);
  }
  
  if (filters.policy_type) {
    filtered = filtered.filter(item => item.policy_type === filters.policy_type);
  }
  
  if (filters.date_from) {
    const fromDate = new Date(filters.date_from);
    filtered = filtered.filter(item => new Date(item.date_filed || item.start_date) >= fromDate);
  }
  
  if (filters.date_to) {
    const toDate = new Date(filters.date_to);
    filtered = filtered.filter(item => new Date(item.date_filed || item.start_date) <= toDate);
  }
  
  if (filters.search) {
    const searchTerm = filters.search.toLowerCase();
    filtered = filtered.filter(item => 
      (item.claim_id && item.claim_id.toLowerCase().includes(searchTerm)) ||
      (item.policy_number && item.policy_number.toLowerCase().includes(searchTerm)) ||
      (item.client_name && item.client_name.toLowerCase().includes(searchTerm)) ||
      (item.client_email && item.client_email.toLowerCase().includes(searchTerm))
    );
  }
  
  return filtered;
}

// CLAIMS API ROUTES
app.get('/api/claims', (req, res) => {
  const filters = {
    status: req.query.status,
    priority: req.query.priority,
    date_from: req.query.date_from,
    date_to: req.query.date_to,
    search: req.query.search
  };
  
  const filteredClaims = filterData(claims, filters);
  res.json(filteredClaims);
});

app.get('/api/claims/:id', (req, res) => {
  const claim = claims.find(c => c.id == req.params.id);
  if (claim) {
    res.json(claim);
  } else {
    res.status(404).json({ error: 'Claim not found' });
  }
});

app.post('/api/claims', (req, res) => {
  const newClaim = {
    id: claims.length + 1,
    claim_id: `CL-${new Date().getFullYear()}-${(claims.length + 1).toString().padStart(3, '0')}`,
    ...req.body,
    status: 'pending',
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString()
  };
  
  claims.push(newClaim);
  
  // Update statistics
  statistics.claims.totalPendingClaims = claims.filter(c => c.status === 'pending').length;
  statistics.claims.totalClaimAmount = claims.reduce((sum, claim) => sum + claim.amount, 0);
  
  // Broadcast real-time update
  broadcastUpdate({
    type: 'new_claim',
    claim: newClaim,
    timestamp: new Date().toISOString()
  });
  
  res.status(201).json(newClaim);
});

app.put('/api/claims/:id', (req, res) => {
  const claimIndex = claims.findIndex(c => c.id == req.params.id);
  
  if (claimIndex === -1) {
    return res.status(404).json({ error: 'Claim not found' });
  }
  
  const oldStatus = claims[claimIndex].status;
  const newStatus = req.body.status;
  
  claims[claimIndex] = {
    ...claims[claimIndex],
    ...req.body,
    updated_at: new Date().toISOString()
  };
  
  // Update statistics if status changed
  if (oldStatus !== newStatus) {
    statistics.claims.totalPendingClaims = claims.filter(c => c.status === 'pending').length;
    statistics.claims.totalApprovedClaims = claims.filter(c => c.status === 'approved').length;
    statistics.claims.totalRejectedClaims = claims.filter(c => c.status === 'rejected').length;
    
    // Broadcast status update
    broadcastUpdate({
      type: 'status_update',
      claim_id: claims[claimIndex].claim_id,
      old_status: oldStatus,
      new_status: newStatus,
      timestamp: new Date().toISOString()
    });
  }
  
  res.json(claims[claimIndex]);
});

app.delete('/api/claims/:id', (req, res) => {
  const claimIndex = claims.findIndex(c => c.id == req.params.id);
  
  if (claimIndex === -1) {
    return res.status(404).json({ error: 'Claim not found' });
  }
  
  const deletedClaim = claims.splice(claimIndex, 1)[0];
  
  // Update statistics
  statistics.claims.totalPendingClaims = claims.filter(c => c.status === 'pending').length;
  statistics.claims.totalApprovedClaims = claims.filter(c => c.status === 'approved').length;
  statistics.claims.totalRejectedClaims = claims.filter(c => c.status === 'rejected').length;
  statistics.claims.totalClaimAmount = claims.reduce((sum, claim) => sum + claim.amount, 0);
  
  res.json({ message: 'Claim deleted', claim: deletedClaim });
});

// POLICIES API ROUTES
app.get('/api/policies', (req, res) => {
  const filters = {
    status: req.query.status,
    policy_type: req.query.policy_type,
    date_from: req.query.date_from,
    date_to: req.query.date_to,
    search: req.query.search
  };
  
  const filteredPolicies = filterData(policies, filters);
  res.json(filteredPolicies);
});

app.get('/api/policies/:id', (req, res) => {
  const policy = policies.find(p => p.id == req.params.id);
  if (policy) {
    res.json(policy);
  } else {
    res.status(404).json({ error: 'Policy not found' });
  }
});

// STATISTICS API ROUTES
app.get('/api/statistics', (req, res) => {
  // Recalculate statistics to ensure accuracy
  statistics.claims.totalPendingClaims = claims.filter(c => c.status === 'pending').length;
  statistics.claims.totalApprovedClaims = claims.filter(c => c.status === 'approved').length;
  statistics.claims.totalRejectedClaims = claims.filter(c => c.status === 'rejected').length;
  statistics.claims.totalClaimAmount = claims.reduce((sum, claim) => sum + claim.amount, 0);
  
  statistics.policies.totalActivePolicies = policies.filter(p => p.status === 'active').length;
  statistics.policies.totalExpiredPolicies = policies.filter(p => p.status === 'expired').length;
  statistics.policies.totalPremiumAmount = policies.reduce((sum, policy) => sum + policy.premium, 0);
  statistics.policies.totalCoverageAmount = policies.reduce((sum, policy) => sum + policy.coverage_amount, 0);
  
  res.json(statistics);
});

app.get('/api/statistics/claims', (req, res) => {
  res.json(statistics.claims);
});

app.get('/api/statistics/policies', (req, res) => {
  res.json(statistics.policies);
});

// ACTIVITIES API ROUTES
app.get('/api/activities', (req, res) => {
  const limit = parseInt(req.query.limit) || 10;
  const filteredActivities = [...activities].sort((a, b) => 
    new Date(b.timestamp) - new Date(a.timestamp)
  ).slice(0, limit);
  
  res.json(filteredActivities);
});

// TIMELINE API ROUTES
app.get('/api/timeline', (req, res) => {
  const limit = parseInt(req.query.limit) || 20;
  const statusFilter = req.query.status;
  
  let filteredTimeline = [...timeline];
  
  if (statusFilter && statusFilter !== 'all') {
    // Filter based on status (you would need to enhance this with real data)
    filteredTimeline = filteredTimeline.filter(item => {
      // This is simplified - in real app, you would check claim status
      return true;
    });
  }
  
  filteredTimeline = filteredTimeline.sort((a, b) => 
    new Date(b.timestamp) - new Date(a.timestamp)
  ).slice(0, limit);
  
  res.json(filteredTimeline);
});

// REPORTS API ROUTES
app.post('/api/reports/generate', (req, res) => {
  const { type, filters, format = 'pdf' } = req.body;
  
  // Simulate report generation
  const reportId = `REP-${Date.now()}`;
  const reportUrl = `/api/reports/download/${reportId}`;
  
  setTimeout(() => {
    // Simulate async report generation
    res.json({
      message: 'Report generated successfully',
      reportId,
      downloadUrl: reportUrl,
      estimatedCompletion: new Date(Date.now() + 5000).toISOString()
    });
  }, 1000);
});

app.get('/api/reports/download/:id', (req, res) => {
  // In a real application, this would serve the generated file
  res.json({
    message: 'Report download endpoint',
    id: req.params.id,
    downloadLink: `https://example.com/reports/${req.params.id}.pdf`
  });
});

// AUTHENTICATION API ROUTES (Simplified)
const users = [
  {
    id: 1,
    username: 'admin',
    password: 'password123',
    name: 'Administrator',
    email: 'admin@gibsysnet.com',
    role: 'admin',
    token: 'admin_token_123'
  },
  {
    id: 2,
    username: 'adjuster',
    password: 'adjuster123',
    name: 'Sarah Johnson',
    email: 'sarah@gibsysnet.com',
    role: 'adjuster',
    token: 'adjuster_token_456'
  }
];

app.post('/api/auth/login', (req, res) => {
  const { username, password } = req.body;
  const user = users.find(u => u.username === username && u.password === password);
  
  if (user) {
    res.json({
      success: true,
      token: user.token,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role
      }
    });
  } else {
    res.status(401).json({
      success: false,
      message: 'Invalid credentials'
    });
  }
});

app.post('/api/auth/validate', (req, res) => {
  const token = req.headers.authorization?.replace('Bearer ', '');
  const user = users.find(u => u.token === token);
  
  if (user) {
    res.json({
      valid: true,
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role
      }
    });
  } else {
    res.json({ valid: false });
  }
});

// REAL-TIME UPDATES
let clients = [];

function broadcastUpdate(data) {
  clients.forEach(client => {
    if (client.readyState === 1) { // WebSocket.OPEN
      client.send(JSON.stringify(data));
    }
  });
}

// Serve dashboard pages
app.get('/claims-dashboard', (req, res) => {
  res.sendFile(path.join(__dirname, 'claims-report.html'));
});

app.get('/policies-dashboard', (req, res) => {
  res.sendFile(path.join(__dirname, 'policies-report.html'));
});

app.get('/', (req, res) => {
  res.json({
    message: 'GIBSYSNET API Server',
    version: '2.1.0',
    endpoints: {
      claims: '/api/claims',
      policies: '/api/policies',
      statistics: '/api/statistics',
      activities: '/api/activities',
      timeline: '/api/timeline',
      auth: '/api/auth/login'
    }
  });
});

// Start HTTP Server
const PORT = process.env.PORT || 3000;
const server = app.listen(PORT, () => {
  console.log(`🚀 Server running on http://localhost:${PORT}`);
  console.log(`📊 Claims Dashboard: http://localhost:${PORT}/claims-dashboard`);
  console.log(`📋 Policies Dashboard: http://localhost:${PORT}/policies-dashboard`);
});

// WebSocket Server
const wss = new WebSocket.Server({ server });

wss.on('connection', (ws) => {
  console.log('🔗 New WebSocket connection');
  clients.push(ws);
  
  ws.on('message', (message) => {
    try {
      const data = JSON.parse(message);
      
      if (data.type === 'subscribe') {
        console.log(`📡 Client subscribed to: ${data.channel}`);
        ws.send(JSON.stringify({
          type: 'subscribed',
          channel: data.channel,
          timestamp: new Date().toISOString()
        }));
      }
      
      if (data.type === 'ping') {
        ws.send(JSON.stringify({
          type: 'pong',
          timestamp: new Date().toISOString()
        }));
      }
    } catch (error) {
      console.error('Error parsing WebSocket message:', error);
    }
  });
  
  ws.on('close', () => {
    console.log('🔒 WebSocket connection closed');
    clients = clients.filter(client => client !== ws);
  });
  
  ws.on('error', (error) => {
    console.error('WebSocket error:', error);
  });
  
  // Send initial connection confirmation
  ws.send(JSON.stringify({
    type: 'connected',
    message: 'Connected to real-time updates',
    timestamp: new Date().toISOString()
  }));
});

// Simulate real-time updates
setInterval(() => {
  const updateTypes = [
    'new_claim',
    'status_update',
    'document_uploaded',
    'policy_updated'
  ];
  
  const randomType = updateTypes[Math.floor(Math.random() * updateTypes.length)];
  
  if (clients.length > 0 && Math.random() > 0.5) {
    const update = {
      type: randomType,
      timestamp: new Date().toISOString()
    };
    
    switch (randomType) {
      case 'new_claim':
        const newClaimNum = claims.length + 1;
        update.claim = {
          id: newClaimNum,
          claim_id: `CL-${new Date().getFullYear()}-${newClaimNum.toString().padStart(3, '0')}`,
          client_name: ['Alex Johnson', 'Maria Garcia', 'Robert Chen'][Math.floor(Math.random() * 3)],
          claim_type: ['Vehicle', 'Property', 'Medical'][Math.floor(Math.random() * 3)],
          amount: Math.floor(Math.random() * 20000) + 1000,
          status: 'pending'
        };
        break;
        
      case 'status_update':
        if (claims.length > 0) {
          const randomClaim = claims[Math.floor(Math.random() * claims.length)];
          const statuses = ['pending', 'in-review', 'approved', 'rejected', 'settled'];
          const newStatus = statuses[Math.floor(Math.random() * statuses.length)];
          
          update.claim_id = randomClaim.claim_id;
          update.old_status = randomClaim.status;
          update.new_status = newStatus;
          
          // Update local data
          randomClaim.status = newStatus;
          randomClaim.updated_at = new Date().toISOString();
        }
        break;
    }
    
    broadcastUpdate(update);
    console.log(`📤 Broadcasted: ${randomType}`);
  }
}, 10000); // Every 10 seconds

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    clients: clients.length,
    claims: claims.length,
    policies: policies.length
  });
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Server error:', err);
  res.status(500).json({
    error: 'Internal server error',
    message: err.message
  });
});

// Handle 404
app.use((req, res) => {
  res.status(404).json({
    error: 'Not found',
    message: `Cannot ${req.method} ${req.url}`
  });
});

module.exports = { app, server, wss };