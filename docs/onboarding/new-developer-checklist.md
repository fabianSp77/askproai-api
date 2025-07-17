# New Developer Onboarding Checklist

Welcome to the AskProAI team! This checklist will guide you through your first week and ensure you have everything you need to be productive.

## 📅 Day 1: Environment Setup

### System Access
- [ ] **SSH Access**: Verify access to production/staging servers
  ```bash
  ssh hosting215275@hosting215275.ae83d.netcup.net
  ```
- [ ] **Database Access**: Test MySQL connection
  ```bash
  mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db
  ```
- [ ] **Repository Access**: Clone the main repository
  ```bash
  git clone [repository-url]
  cd api-gateway
  ```

### Local Development
- [ ] **Install Dependencies**:
  ```bash
  composer install
  npm install
  ```
- [ ] **Environment Configuration**:
  ```bash
  cp .env.example .env
  # Edit .env with your local settings
  php artisan key:generate
  ```
- [ ] **Database Setup**:
  ```bash
  php artisan migrate --seed
  ```
- [ ] **Run Tests**:
  ```bash
  php artisan test
  ```

### Tools & Services
- [ ] **Slack Access**: Join channels
  - #general
  - #dev-team
  - #support
  - #releases
- [ ] **GitHub/GitLab Access**: Verify permissions
- [ ] **Monitoring Access**: 
  - Grafana dashboard
  - Sentry error tracking
  - Horizon queue dashboard
- [ ] **API Keys** (Request from team lead):
  - Retell.ai sandbox account
  - Cal.com test account
  - Stripe test keys

## 📅 Day 2: Codebase Familiarization

### Documentation Review
- [ ] Read [CLAUDE.md](../../CLAUDE.md) - Project overview
- [ ] Read [Quick Reference](../../CLAUDE_QUICK_REFERENCE.md)
- [ ] Review [Architecture Overview](../architecture/README.md)
- [ ] Study [Phone to Appointment Flow](../../PHONE_TO_APPOINTMENT_FLOW.md)

### Code Structure
- [ ] **Explore Key Directories**:
  ```
  app/
  ├── Http/Controllers/   # API endpoints
  ├── Services/          # Business logic
  ├── Models/           # Data models
  ├── Jobs/             # Background jobs
  └── Filament/         # Admin panel
  ```

### Run the Application
- [ ] Start local server: `php artisan serve`
- [ ] Start queue worker: `php artisan horizon`
- [ ] Access admin panel: http://localhost:8000/admin
- [ ] Make a test API call

### First Code Review
- [ ] Find a recent merged PR and study:
  - What problem it solved
  - Code structure used
  - Tests written
  - Review comments

## 📅 Day 3: Core Features

### Appointment System
- [ ] Create a test appointment via admin panel
- [ ] Trace the appointment creation flow in code
- [ ] Understand the database schema
- [ ] Review appointment-related tests

### Phone Integration (Retell.ai)
- [ ] Read [Retell Integration Guide](../integrations/retell.md)
- [ ] Understand webhook flow
- [ ] Review call processing logic
- [ ] Test webhook endpoint locally

### Calendar Integration (Cal.com)
- [ ] Read [Cal.com Integration Guide](../integrations/calcom.md)
- [ ] Understand event type syncing
- [ ] Review availability checking
- [ ] Test calendar operations

### Multi-tenancy
- [ ] Understand company/branch structure
- [ ] Review TenantScope implementation
- [ ] Test data isolation

## 📅 Day 4: Development Workflow

### Git Workflow
- [ ] Create your first feature branch
- [ ] Understand commit message conventions
- [ ] Learn about our PR process
- [ ] Set up pre-commit hooks:
  ```bash
  git config core.hooksPath .githooks
  ```

### Testing Strategy
- [ ] Run different test suites:
  ```bash
  php artisan test --testsuite=Unit
  php artisan test --testsuite=Feature
  php artisan test --testsuite=Integration
  ```
- [ ] Write your first test
- [ ] Understand mocking external services

### Code Quality
- [ ] Run code quality checks:
  ```bash
  composer quality
  composer pint      # Format code
  composer stan      # Static analysis
  ```
- [ ] Fix any issues in a sample file

### Debugging Tools
- [ ] Install Laravel Debugbar
- [ ] Use `dd()` and `Log::debug()`
- [ ] Access Horizon dashboard
- [ ] Check application logs

## 📅 Day 5: First Contribution

### Find a Starter Task
- [ ] Check issues labeled "good first issue"
- [ ] Or fix a small bug/typo
- [ ] Discuss approach with mentor

### Implementation
- [ ] Create feature branch
- [ ] Implement the change
- [ ] Write/update tests
- [ ] Update documentation if needed

### Submit PR
- [ ] Self-review your code
- [ ] Run all tests locally
- [ ] Create pull request
- [ ] Address review feedback

## 📅 Week 2: Deep Dives

### Advanced Topics
- [ ] **Performance**: Review caching strategies
- [ ] **Security**: Understand authentication flow
- [ ] **Queues**: Learn job processing patterns
- [ ] **APIs**: Study our API design patterns

### Shadow a Senior Developer
- [ ] Pair program on a feature
- [ ] Attend a debugging session
- [ ] Review architecture decisions

### Documentation Contribution
- [ ] Update outdated documentation
- [ ] Add missing documentation
- [ ] Improve code comments

## 📋 Ongoing Learning

### Weekly Goals
- [ ] Attend team standup meetings
- [ ] Complete one code review
- [ ] Fix one bug
- [ ] Learn one new Laravel/PHP feature

### Monthly Goals
- [ ] Lead a feature implementation
- [ ] Present a tech talk
- [ ] Contribute to architecture decisions
- [ ] Mentor a newer developer

## 🔧 Essential Commands Reference

### Daily Commands
```bash
# Start development
php artisan serve
php artisan horizon

# Run tests before committing
php artisan test
composer quality

# Clear caches
php artisan optimize:clear
```

### Debugging Commands
```bash
# Check application status
php artisan about

# View recent logs
tail -f storage/logs/laravel.log

# Interactive shell
php artisan tinker
```

### Database Commands
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh database with seeds
php artisan migrate:fresh --seed
```

## 📚 Resources

### Internal Documentation
- [Documentation Index](../README.md)
- [API Documentation](../api/README.md)
- [Troubleshooting Guide](../../TROUBLESHOOTING_DECISION_TREE.md)

### External Resources
- [Laravel Documentation](https://laravel.com/docs)
- [Filament Documentation](https://filamentphp.com/docs)
- [PHP Standards](https://www.php-fig.org/psr/)

### Getting Help
- **Slack**: #dev-help channel
- **Mentor**: [Assigned mentor name]
- **Wiki**: Internal knowledge base
- **Office Hours**: Tuesdays & Thursdays 2-3 PM

## ✅ Onboarding Complete!

Once you've completed this checklist:
1. Schedule a review with your mentor
2. Get your first real task assigned
3. Join the next sprint planning

Welcome to the team! 🎉