---
layout: home

hero:
  name: AskPro API Gateway
  text: Multi-Tenant AI Voice Platform
  tagline: Appointment Management & Service Gateway with Retell.ai Integration
  image:
    src: /logo.svg
    alt: AskPro Logo
  actions:
    - theme: brand
      text: Get Started
      link: /guide/
    - theme: alt
      text: API Reference
      link: /api/
    - theme: alt
      text: Interactive API Docs
      link: /docs/api

features:
  - icon: 🤖
    title: AI Voice Agent
    details: Retell.ai integration for intelligent voice conversations, appointment booking, and service desk interactions.
  - icon: 📅
    title: Cal.com Integration
    details: Seamless scheduling with bidirectional sync, availability management, and team-based booking.
  - icon: 🎫
    title: Service Gateway
    details: Multi-tenant case management with email & webhook outputs, SLA tracking, and escalation rules.
  - icon: 🔒
    title: Enterprise Security
    details: Multi-tenant isolation, SSRF protection, rate limiting, and comprehensive authorization guards.
  - icon: 📊
    title: Real-Time Dashboard
    details: 9-widget Filament dashboard with SLA monitoring, case statistics, and activity tracking.
  - icon: ⚡
    title: High Performance
    details: Redis caching, queue workers, and optimized database queries for fast response times.
---

## Quick Links

<div class="quick-links">

### For Developers
- [Architecture Overview](/guide/architecture)
- [API Authentication](/api/authentication)
- [Webhook Integration](/guide/webhooks)

### For Operators
- [Service Gateway Guide](/guide/service-gateway)
- [Multi-Tenancy Setup](/guide/multi-tenancy)
- [Security Best Practices](/guide/security)

</div>

## Latest Updates

| Version | Date | Changes |
|---------|------|---------|
| 2.0.0 | 2026-01-05 | Service Gateway with Security Hardening |
| 1.9.0 | 2025-12-22 | 2-Phase Delivery Gate Pattern |
| 1.8.0 | 2025-12-14 | Date Hallucination Fix |

---

<style>
.quick-links {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}
</style>
