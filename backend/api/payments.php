// Example API endpoints structure
const PAYMENT_API = {
    // GET endpoints
    GET_PAYMENTS: '/api/payments',
    GET_PAYMENT: '/api/payments/:id',
    GET_PENDING_PAYMENTS: '/api/payments/pending',
    GET_CLIENT_PAYMENTS: '/api/payments/client/:clientId',
    GET_COMMISSION_REPORT: '/api/payments/commission-report',
    
    // POST endpoints
    CREATE_PAYMENT: '/api/payments',
    BULK_CREATE_PAYMENTS: '/api/payments/bulk',
    MARK_AS_PAID: '/api/payments/:id/pay',
    SEND_REMINDERS: '/api/payments/send-reminders',
    
    // PUT endpoints
    UPDATE_PAYMENT: '/api/payments/:id',
    UPDATE_STATUS: '/api/payments/:id/status',
    
    // DELETE endpoints
    DELETE_PAYMENT: '/api/payments/:id'
};