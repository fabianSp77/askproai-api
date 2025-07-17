# Notion Documentation System

A comprehensive documentation automation system for AskProAI that integrates with Notion for collaborative documentation management.

## 🚀 Features

### 1. **Automatic Documentation Sync**
- Monitors code changes and updates Notion automatically
- Syncs system status indicators in real-time
- Creates backups before each sync
- Updates timestamps and metadata

### 2. **Documentation Templates**
- **Feature Documentation**: Complete template for new features
- **API Endpoint**: RESTful API documentation template
- **Integration Guide**: External service integration docs
- **Troubleshooting**: Problem-solution documentation
- **Release Notes**: Version release documentation

### 3. **Monitoring Dashboard**
- Documentation coverage metrics (target: 80%+)
- Outdated documentation alerts
- Missing documentation finder
- Team contribution tracking
- Health score calculation

### 4. **Advanced Search**
- Tag-based categorization
- Glossary of terms with definitions
- Command index with descriptions
- FAQ database
- Full-text search with highlighting

### 5. **Team Onboarding**
- New developer checklist
- Interactive documentation tour
- Best practices guide
- Contribution guidelines

## 📋 Quick Start

### 1. Installation

```bash
# Run the setup script
./scripts/notion-sync/setup-notion-automation.sh
```

### 2. Configuration

Add to your `.env` file:
```env
# Notion Integration
NOTION_API_KEY=secret_xxxxxxxxxxxxx
NOTION_DATABASE_ID=xxxxxxxxxxxxx
NOTION_STATUS_PAGE_ID=xxxxxxxxxxxxx
```

### 3. Initial Run

```bash
# Generate search indexes
php scripts/notion-sync/search-optimizer.php

# Create monitoring dashboard
php scripts/notion-sync/monitoring-dashboard.php

# Sync with Notion
php scripts/notion-sync/notion-sync.php
```

## 🔧 Usage

### Command Line Tools

```bash
# Check documentation health
php artisan docs:check-updates

# Search documentation
php artisan docs:search "webhook"

# Force sync with Notion
php scripts/notion-sync/notion-sync.php

# Regenerate search index
php scripts/notion-sync/search-optimizer.php
```

### Web Interfaces

- **Search Interface**: `/docs/search.html`
- **Monitoring Dashboard**: `/docs/dashboard.html`
- **Admin Widget**: Available in Filament admin panel

### Automated Processes

The system runs automatically via cron:
- **Hourly**: Notion sync for changed files
- **Daily 9 AM**: Documentation health monitoring
- **Daily 2 AM**: Search index optimization

## 📁 File Structure

```
scripts/notion-sync/
├── notion-sync.php              # Main sync script
├── monitoring-dashboard.php     # Health monitoring
├── search-optimizer.php         # Search indexing
├── doc-mapping.json            # File-to-Notion mapping
└── setup-notion-automation.sh   # Setup script

docs/
├── templates/                   # Documentation templates
│   ├── feature-documentation-template.md
│   ├── api-endpoint-template.md
│   ├── integration-guide-template.md
│   ├── troubleshooting-template.md
│   └── release-notes-template.md
├── onboarding/                  # Team onboarding
│   ├── new-developer-checklist.md
│   ├── documentation-tour.md
│   ├── best-practices-guide.md
│   └── contribution-guidelines.md
├── search/                      # Search indexes
│   ├── index.json              # Main search index
│   ├── tags.json               # Tag system
│   ├── glossary.json           # Terms glossary
│   ├── commands.json           # Command reference
│   └── faq.json                # FAQ database
├── GLOSSARY.md                 # Auto-generated glossary
├── COMMANDS.md                 # Auto-generated commands
└── FAQ.md                      # Auto-generated FAQ

public/docs/
├── search.html                  # Search interface
└── dashboard.html              # Monitoring dashboard
```

## 🎯 Documentation Standards

### Coverage Goals
- **Overall Coverage**: 80%+
- **Controllers**: 90%+
- **Services**: 95%+
- **Models**: 85%+
- **Critical Services**: 100%

### Documentation Requirements

#### For Classes
```php
/**
 * Brief description of the class purpose.
 * 
 * Detailed explanation if needed.
 *
 * @package App\Services
 */
class ExampleService
{
    /**
     * Method description.
     *
     * @param array $data Description
     * @return Model Description
     * @throws Exception When/why thrown
     */
    public function method(array $data): Model
    {
        // Implementation
    }
}
```

#### For Features
- Purpose and business value
- Architecture overview
- Configuration requirements
- Usage examples
- Testing approach
- Troubleshooting guide

## 📊 Metrics & Monitoring

### Health Score Calculation
- **Coverage** (40%): Percentage of documented files
- **Freshness** (30%): How up-to-date docs are
- **Completeness** (20%): Quality of documentation
- **Activity** (10%): Recent contributions

### Alert Types
- **🔴 Critical**: Coverage < 60% or critical services undocumented
- **🟡 Warning**: Outdated documentation or low activity
- **🔵 Info**: Suggestions for improvement

### Team Metrics
- Commits per contributor
- Files touched
- Recent activity (week/month)
- Documentation quality score

## 🔄 Workflow Integration

### Git Hooks
Pre-commit hooks check for:
- Service file changes
- API route modifications
- Migration additions
- Configuration updates

### CI/CD Pipeline
```yaml
# .github/workflows/documentation.yml
- name: Check Documentation Health
  run: php artisan docs:check-updates --json
  
- name: Fail if Critical
  run: |
    health=$(cat docs-health.json | jq '.health_score')
    if [ $health -lt 40 ]; then
      echo "Documentation health critical: $health%"
      exit 1
    fi
```

### Pull Request Integration
- Auto-comment with documentation impact
- Suggest documentation updates
- Block merge if critical docs missing

## 🎨 Customization

### Adding New Templates
1. Create template in `docs/templates/`
2. Follow naming convention: `*-template.md`
3. Include all standard sections
4. Add to template index

### Extending Search
1. Modify `SearchOptimizer::processFile()`
2. Add new extraction methods
3. Update search interface
4. Regenerate indexes

### Custom Monitoring
1. Add metrics to `DocumentationMonitor`
2. Update dashboard generation
3. Create new alert types
4. Modify health calculation

## 🚨 Troubleshooting

### Common Issues

#### "Notion API Error"
```bash
# Check API key
echo $NOTION_API_KEY

# Test connection
curl -H "Authorization: Bearer $NOTION_API_KEY" \
     -H "Notion-Version: 2022-06-28" \
     https://api.notion.com/v1/users/me
```

#### "Search not working"
```bash
# Regenerate search index
php scripts/notion-sync/search-optimizer.php

# Check file permissions
ls -la public/docs/search.html
ls -la docs/search/
```

#### "Monitoring dashboard empty"
```bash
# Run monitoring manually
php scripts/notion-sync/monitoring-dashboard.php

# Check for errors
tail -f storage/logs/doc-monitoring.log
```

## 🔐 Security

### API Key Storage
- Store in `.env` file only
- Never commit to repository
- Use read-only Notion integration

### Access Control
- Dashboard requires admin authentication
- Search interface is public (configurable)
- Notion sync runs as system user

## 📈 Future Enhancements

### Planned Features
- [ ] AI-powered documentation suggestions
- [ ] Automatic API documentation from routes
- [ ] Integration with Swagger/OpenAPI
- [ ] Documentation versioning
- [ ] Multi-language support
- [ ] Video tutorial integration
- [ ] Interactive examples
- [ ] Documentation analytics

### Integration Ideas
- Slack notifications for outdated docs
- JIRA ticket creation for missing docs
- GitHub Actions for automated PRs
- VS Code extension for inline docs

## 🤝 Contributing

### Adding Documentation
1. Use appropriate template
2. Follow naming conventions
3. Update mapping file
4. Run sync script

### Improving System
1. Fork repository
2. Create feature branch
3. Add tests for new features
4. Submit pull request

## 📚 Resources

### Internal
- [Main Documentation](../CLAUDE.md)
- [Quick Reference](../CLAUDE_QUICK_REFERENCE.md)
- [Development Process](../DEVELOPMENT_PROCESS_2025.md)

### External
- [Notion API Docs](https://developers.notion.com/)
- [MkDocs](https://www.mkdocs.org/)
- [Docusaurus](https://docusaurus.io/)

## 📞 Support

- **Slack**: #documentation channel
- **Email**: docs-team@askproai.de
- **Wiki**: Internal knowledge base

---

Remember: Good documentation is as important as good code. Keep it updated! 📚