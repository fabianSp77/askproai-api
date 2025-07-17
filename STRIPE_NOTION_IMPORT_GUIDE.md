# 💳 Stripe Payment System - Notion Import Guide

## 📋 Document Structure for Notion

### Main Page: "💳 Stripe Payment System"
Location: Technical Docs → Integrations → Stripe Payment System

### Sub-pages to Create:

#### 1. 📚 Main Documentation
- **Title**: "Stripe Payment System Documentation"
- **Source**: [STRIPE_PAYMENT_SYSTEM_DOCUMENTATION.md](./STRIPE_PAYMENT_SYSTEM_DOCUMENTATION.md)
- **Key Sections**:
  - System Overview
  - Architecture
  - Core Components
  - Payment Flows
  - Configuration
  - Testing
  - Operations Guide
  - Monitoring

#### 2. 👨‍💻 Developer Setup
- **Title**: "Developer Setup Guide"
- **Source**: [STRIPE_DEVELOPER_SETUP_GUIDE.md](./STRIPE_DEVELOPER_SETUP_GUIDE.md)
- **Key Sections**:
  - Quick Start
  - Local Development Setup
  - Testing Workflows
  - Debugging Tools
  - Common Issues

#### 3. 📋 Operations Manual
- **Title**: "Daily Operations Manual"
- **Source**: [STRIPE_OPERATIONS_MANUAL.md](./STRIPE_OPERATIONS_MANUAL.md)
- **Key Sections**:
  - Daily Checklist
  - Payment Management
  - Incident Response
  - Reporting
  - Customer Support

#### 4. 🔧 Troubleshooting
- **Title**: "Troubleshooting Guide"
- **Source**: [STRIPE_TROUBLESHOOTING_GUIDE.md](./STRIPE_TROUBLESHOOTING_GUIDE.md)
- **Key Sections**:
  - Critical Issues
  - Common Problems
  - Debugging Tools
  - Emergency Procedures

#### 5. 🔐 Security
- **Title**: "Security Best Practices"
- **Source**: [STRIPE_SECURITY_BEST_PRACTICES.md](./STRIPE_SECURITY_BEST_PRACTICES.md)
- **Key Sections**:
  - API Key Management
  - Webhook Security
  - PCI Compliance
  - Access Control
  - Incident Response

#### 6. 🚀 Quick Reference
- **Title**: "Quick Reference Card"
- **Source**: [STRIPE_QUICK_REFERENCE.md](./STRIPE_QUICK_REFERENCE.md)
- **Key Sections**:
  - Essential Commands
  - Common Fixes
  - Test Cards
  - Emergency Procedures

## 🎯 Critical Information Summary

### Webhook Configuration
```
Endpoint: https://api.askproai.de/api/stripe/webhook
Events: checkout.session.completed, payment_intent.succeeded, charge.refunded
```

### Test Mode Control
```bash
# Enable: touch .stripe-test-mode.lock
# Disable: rm .stripe-test-mode.lock
```

### Payment Flows
1. **Manual Topup**: User → Portal → Stripe Checkout → Webhook → Balance Update
2. **Auto-Topup**: Low Balance → Check Settings → Charge Card → Update Balance
3. **Call Charging**: Call Ends → Calculate Cost → Deduct Balance → Check Auto-topup

### Key Services
- `StripeTopupService` - Main payment processing
- `AutoTopupService` - Automatic balance management
- `PrepaidBillingService` - Balance operations
- `CallRefundService` - Refund processing

### Database Tables
- `prepaid_balances` - Company balance management
- `balance_topups` - Payment records
- `balance_transactions` - Transaction log
- `call_charges` - Call billing records

### Environment Variables
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_TEST_MODE=true
PREPAID_BILLING_ENABLED=true
AUTO_TOPUP_ENABLED=true
DEFAULT_BILLING_RATE=0.42
```

### Common SQL Queries
```sql
-- Check failed payments
SELECT * FROM balance_topups 
WHERE status = 'failed' 
AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Low balance companies
SELECT c.name, pb.balance 
FROM prepaid_balances pb 
JOIN companies c ON c.id = pb.company_id 
WHERE pb.balance < pb.low_balance_threshold;

-- Daily revenue
SELECT DATE(created_at) as date, SUM(amount) as revenue
FROM balance_topups
WHERE status = 'succeeded'
GROUP BY DATE(created_at);
```

### Emergency Contacts
- Stripe Dashboard: https://dashboard.stripe.com
- Stripe Status: https://status.stripe.com
- Admin Panel: https://api.askproai.de/admin
- Horizon Queue: https://api.askproai.de/horizon

## 📝 Notion Import Instructions

1. **Create Main Page**:
   - Go to Technical Docs → Integrations
   - Create new page: "💳 Stripe Payment System"
   - Add icon: 💳
   - Add cover image (optional)

2. **Create Sub-pages**:
   - For each document above, create a sub-page
   - Copy content from markdown files
   - Use Notion's markdown import feature
   - Add table of contents to each page

3. **Add Properties**:
   - Type: Documentation
   - Category: Payment Integration
   - Status: Active
   - Last Updated: 2025-07-10

4. **Create Database Views**:
   - Quick Commands (filtered view)
   - Error Codes (table)
   - Test Cards (gallery)
   - Common Issues (kanban by status)

5. **Add Synced Blocks**:
   - Critical URLs
   - Environment Variables
   - Emergency Procedures
   - Daily Checklist

6. **Set Permissions**:
   - Developers: Full access
   - Support Team: View only
   - Management: View + Comment

## 🔗 Related Documentation

- [Retell.ai Integration](./RETELL_INTEGRATION_CRITICAL.md)
- [Cal.com Integration](./CALCOM_INTEGRATION_GUIDE.md)
- [Multi-Tenant Architecture](./MULTI_TENANT_GUIDE.md)
- [API Documentation](./API_DOCUMENTATION.md)

---

**Import Date**: 2025-07-10
**Version**: 1.0
**Total Documents**: 6