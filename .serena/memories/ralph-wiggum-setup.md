# Ralph Wiggum Setup - AskPro Gateway

## Quick Reference

### What is Ralph?
Ralph is an autonomous AI agent loop that runs Claude Code repeatedly until all PRD items are complete. Each iteration starts with a fresh context window - memory persists only through Git history, `progress.txt`, and `prd.json`.

### Key Files

```
scripts/ralph/
├── ralph.sh           # Bash loop (backup if plugin unavailable)
├── prompt.md          # Instructions for each iteration
├── prd.json           # User stories with passes status
├── prd.json.example   # Example PRD for reference
└── progress.txt       # Append-only learnings

~/.config/claude-code/skills/
├── prd-laravel/SKILL.md       # PRD generator for Laravel/Filament
└── ralph-converter/SKILL.md   # PRD to prd.json converter
```

### Workflow

```
Phase 1: "Load the prd-laravel skill and create a PRD for [feature]"
         → Generates tasks/prd-[feature].md

Phase 2: "Load the ralph-converter skill and convert tasks/prd-[feature].md to prd.json"
         → Generates scripts/ralph/prd.json

Phase 3: ./scripts/ralph/ralph.sh 25
         OR
         /ralph-loop "Read scripts/ralph/prompt.md and execute" --max-iterations 25 --completion-promise "COMPLETE"
```

### Critical Rules

1. **Story Size**: Each story must fit in ONE context window (~50-100 lines)
2. **Dependencies**: Order stories: migration → model → service → UI
3. **Criteria**: Must be verifiable, not vague ("Works correctly" = BAD)
4. **Quality**: Every story needs `php artisan test passes` + `./vendor/bin/pint --test passes`

### Common Commands

```bash
# Check story status
jq '.userStories[] | {id, title, passes}' scripts/ralph/prd.json

# See learnings
cat scripts/ralph/progress.txt

# Check git history
git log --oneline -10

# Run Ralph
./scripts/ralph/ralph.sh 25
```

### Cost Estimation
- 10-15 stories × 5-10 iterations × $0.10-0.50 per iteration
- Typical: $5-50 per complete feature

### Resources
- [Ralph GitHub](https://github.com/snarktank/ralph)
- [Ryan Carson's Guide](https://x.com/ryancarson/status/2008548371712135632)
- [Geoffrey Huntley's Original](https://ghuntley.com/ralph)
