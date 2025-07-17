# Documentation Version Control Guidelines

> 📋 **Version**: 1.0  
> 📅 **Last Updated**: 2025-01-10  
> 👥 **Maintained By**: Documentation Team

## Version Control System

### Git Configuration
```bash
# Documentation-specific Git configuration
git config --local user.name "Documentation Team"
git config --local user.email "docs@askproai.de"

# Auto-convert line endings
git config --local core.autocrlf true

# Diff markdown files as text
git config --local diff.markdown.textconv "cat"
```

### Branch Strategy
```
main
├── docs/update-api-guide
├── docs/fix-typos
├── docs/add-feature-x
└── docs/translate-german
```

**Branch Naming**:
- `docs/` prefix for all documentation branches
- `docs/update-{component}` for updates
- `docs/fix-{issue}` for fixes
- `docs/add-{feature}` for new content
- `docs/translate-{language}` for translations

## Commit Conventions

### Commit Message Format
```
docs(<scope>): <description>

[optional body]

[optional footer]
```

### Types
- `docs`: Documentation only changes
- `docs(api)`: API documentation
- `docs(guide)`: User guides
- `docs(dev)`: Developer documentation
- `docs(ops)`: Operations documentation

### Examples
```bash
# Simple documentation update
git commit -m "docs(api): update authentication section"

# Detailed commit
git commit -m "docs(guide): add troubleshooting section

- Add common error solutions
- Include diagnostic commands
- Add links to support resources

Closes #123"
```

### Commit Guidelines
1. Use present tense ("add" not "added")
2. Keep subject line under 50 characters
3. Reference issues/tickets
4. Group related changes

## Versioning Strategy

### Documentation Versions
Align with software releases:
```
Software v2.1.0 → Documentation v2.1.0
Software v2.1.1 → Documentation v2.1.1 (if needed)
```

### Version Tagging
```bash
# Tag documentation release
git tag -a docs-v2.1.0 -m "Documentation for v2.1.0 release"

# Push tags
git push origin docs-v2.1.0
```

### Version Headers
Every document includes:
```markdown
> 📋 **Version**: 2.1.0  
> 📅 **Last Updated**: 2025-01-10  
> 🔄 **Revision**: 3
```

## File Management

### File History Tracking
```bash
# View file history
git log --follow docs/api/authentication.md

# View specific changes
git show <commit>:docs/api/authentication.md

# Compare versions
git diff v2.0.0..v2.1.0 docs/api/authentication.md
```

### Moving/Renaming Files
```bash
# Use git mv to preserve history
git mv old-name.md new-name.md

# Update all references
grep -r "old-name.md" docs/ | grep -v ".git"
```

### Archiving Old Docs
```bash
# Move to archive with date
git mv outdated-guide.md archive/2025-01-10-outdated-guide.md

# Add deprecation notice
echo "DEPRECATED: See [new guide](../current-guide.md)" > archive/README.md
```

## Change Tracking

### Documentation Changelog
Maintain `docs/CHANGELOG.md`:
```markdown
# Documentation Changelog

## [2.1.0] - 2025-01-10

### Added
- Troubleshooting guide for API errors
- German translation for user guide
- New examples in webhook section

### Changed
- Updated authentication flow diagram
- Improved code examples formatting
- Restructured installation guide

### Fixed
- Broken links in API reference
- Typos in configuration guide
- Incorrect parameter types

### Removed
- Deprecated v1 API documentation
```

### Automated Change Detection
```bash
#!/bin/bash
# Script: check-doc-changes.sh

# Find modified docs since last release
git diff --name-only v2.0.0..HEAD | grep "^docs/"

# Generate change summary
git log v2.0.0..HEAD --oneline --grep="^docs"
```

## Review Process

### Pull Request Template
```markdown
## Documentation Change

### Type of Change
- [ ] New documentation
- [ ] Update existing docs
- [ ] Fix errors/typos
- [ ] Translation
- [ ] Restructuring

### Description
Brief description of changes

### Checklist
- [ ] Spell check passed
- [ ] Links verified
- [ ] Code examples tested
- [ ] Version updated
- [ ] Related docs updated

### Related Issues
Closes #

### Screenshots (if applicable)
```

### Review Workflow
1. Create feature branch
2. Make changes
3. Self-review checklist
4. Create pull request
5. Peer review
6. Technical review (if needed)
7. Merge to main

### Review Criteria
- Technical accuracy
- Clarity and completeness
- Consistent formatting
- Working examples
- No broken links

## Collaboration

### Conflict Resolution
```bash
# Common conflict scenario
<<<<<<< HEAD
## Installation (Updated in v2.1)
=======
## Installation Guide
>>>>>>> feature

# Resolution strategy:
# 1. Discuss with team
# 2. Merge both changes if possible
# 3. Document decision
```

### Co-authoring
```bash
# Multiple authors
git commit -m "docs(api): update webhooks section

Co-authored-by: Name <email@example.com>
Co-authored-by: Other <other@example.com>"
```

## Automated Processes

### Pre-commit Hooks
```bash
#!/bin/bash
# .git/hooks/pre-commit

# Check markdown lint
npx markdownlint docs/**/*.md

# Check for broken links
npx linkcheck docs/**/*.md

# Update last modified dates
./scripts/update-doc-dates.sh
```

### CI/CD Integration
```yaml
# .github/workflows/docs.yml
name: Documentation

on:
  pull_request:
    paths:
      - 'docs/**'

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Lint Markdown
        run: npx markdownlint docs/**/*.md
        
      - name: Check Links
        run: npx linkcheck docs/**/*.md
        
      - name: Spell Check
        run: npx spellchecker docs/**/*.md
```

### Documentation Metrics
```bash
# Track documentation coverage
./scripts/doc-coverage.sh

# Output:
# API Endpoints: 45/50 documented (90%)
# Features: 18/20 documented (90%)
# Config Options: 30/30 documented (100%)
```

## Backup and Recovery

### Backup Strategy
```bash
# Regular backups
0 2 * * * tar -czf docs-backup-$(date +%Y%m%d).tar.gz docs/

# Backup before major changes
./scripts/backup-docs.sh "Pre-restructure backup"
```

### Recovery Procedures
```bash
# Restore specific file version
git checkout <commit> -- docs/specific-file.md

# Restore entire docs from tag
git checkout docs-v2.0.0 -- docs/

# Restore from backup
tar -xzf docs-backup-20250110.tar.gz
```

## Version Comparison

### Diff Visualization
```bash
# Visual diff between versions
git difftool -d v2.0.0 v2.1.0 -- docs/

# Generate HTML diff report
git diff v2.0.0 v2.1.0 docs/ | diff2html > changes.html
```

### Migration Tracking
Document what changed between versions:
```markdown
# Migration from v2.0 to v2.1

## Moved Files
- `api-guide.md` → `api/guide.md`
- `webhooks.md` → `api/webhooks.md`

## Renamed Sections
- "Authentication" → "Security & Authentication"
- "Examples" → "Code Examples"

## New Content
- Added troubleshooting guide
- Added German translations
```

## Long-term Maintenance

### Archive Policy
- Keep last 3 major versions
- Archive older versions
- Maintain upgrade paths
- Preserve breaking change docs

### Documentation Debt
Track and manage:
```markdown
# docs/TECH_DEBT.md

## High Priority
- [ ] Update API examples to v2 format
- [ ] Complete German translations

## Medium Priority
- [ ] Improve search functionality
- [ ] Add more diagrams

## Low Priority
- [ ] Standardize all code examples
- [ ] Add video tutorials
```

### Health Metrics
```yaml
# Documentation health scorecard
metrics:
  coverage: 90%  # Documented features
  freshness: 95% # Updated in last 6 months
  accuracy: 98%  # Technically correct
  clarity: 85%   # Readability score
  languages: 2   # EN, DE
```

---

> 🔄 **Auto-Updated**: This documentation is automatically checked for updates. Last verification: 2025-01-10