# Ralph Agent Instructions - AskPro Gateway

You are an autonomous coding agent working on the AskPro Gateway Laravel/Filament project.

## Your Task

1. Read the PRD at `scripts/ralph/prd.json`
2. Read the progress log at `scripts/ralph/progress.txt` (check **Codebase Patterns** section first!)
3. Check you're on the correct branch from PRD `branchName`. If not, check it out or create from main.
4. Pick the **highest priority** user story where `passes: false`
5. Implement that **single** user story
6. Run quality checks (see below)
7. Update AGENTS.md files if you discover reusable patterns
8. If checks pass, commit ALL changes with message: `feat: [Story ID] - [Story Title]`
9. Update the PRD to set `passes: true` for the completed story
10. Append your progress to `scripts/ralph/progress.txt`

## Quality Commands

Run these commands after implementing each story:

```bash
# Run tests (required)
php artisan test --parallel

# Check code style (required)
./vendor/bin/pint --test

# Verify routes exist (if adding routes)
php artisan route:list --compact | grep -i "your-route"

# Clear caches if needed
php artisan config:clear && php artisan cache:clear
```

**A story is NOT complete unless:**
- `php artisan test --parallel` exits with 0
- `./vendor/bin/pint --test` exits with 0

## Laravel/Filament Patterns

Follow these patterns from the existing codebase:

### Models
```
Location: app/Models/{Model}.php
- Extend CompanyScopedModel for tenant-specific data
- Use fillable array for mass assignment
- Define relationships (belongsTo, hasMany, etc.)
- Add casts for dates, enums, JSON fields
```

### Filament Resources
```
Location: app/Filament/Resources/{Model}Resource.php
- Use TextInput, Select, RichEditor for forms
- Use TextColumn, BadgeColumn for tables
- Add filters and search
- Implement authorization with policies
```

### Services
```
Location: app/Services/{Domain}/{Service}Service.php
- Inject dependencies via constructor
- Keep methods focused (single responsibility)
- Throw custom exceptions for errors
- Return typed results
```

### Migrations
```
Location: database/migrations/YYYY_MM_DD_HHMMSS_*.php
- Use foreignId() for foreign keys
- Add indexes for frequently queried columns
- Use nullable() only when necessary
- Include down() method for rollback
```

### Tests
```
Location: tests/Feature/{Domain}/ or tests/Unit/
- Use RefreshDatabase trait
- Create factories for test data
- Test happy path AND error cases
- Use assertDatabaseHas for DB assertions
```

## Progress Report Format

APPEND to `scripts/ralph/progress.txt` (never replace, always append):

```markdown
## [Date/Time] - [Story ID]
- What was implemented
- Files changed/created
- **Learnings for future iterations:**
  - Patterns discovered
  - Gotchas encountered
  - Useful context
---
```

## Consolidate Patterns

If you discover a **reusable pattern** that future iterations should know, add it to the `## Codebase Patterns` section at the TOP of progress.txt:

```markdown
## Codebase Patterns
- Example: CompanyScopedModel auto-filters by tenant
- Example: Use TextColumn::make()->searchable() for searchable columns
- Example: Filament forms use ->required() for validation
```

Only add patterns that are **general and reusable**, not story-specific details.

## Update AGENTS.md Files

Before committing, check if any edited files have learnings worth preserving in nearby AGENTS.md files:

1. Look at which directories you modified
2. Check for existing AGENTS.md files
3. Add valuable learnings like:
   - API patterns specific to that module
   - Gotchas or non-obvious requirements
   - Dependencies between files
   - Testing approaches

**Good additions:**
- "When modifying ServiceCase, also update the activity log"
- "Email templates use Mustache-style variables: {{variable}}"
- "Filament Resources require Policy for authorization"

**Do NOT add:**
- Story-specific implementation details
- Temporary debugging notes
- Information already in progress.txt

## Screenshot Verification (UI Stories)

For stories that change UI, take a screenshot:

```bash
# Example using Playwright (if available)
# Or use browser dev tools screenshot
# Save to tmp/us-XXX-description.png
```

A frontend story is NOT complete until visually verified.

## Stop Condition

After completing a user story, check if ALL stories have `passes: true`.

If ALL stories are complete and passing, reply with:

```
COMPLETE
```

If there are still stories with `passes: false`, end your response normally (another iteration will pick up the next story).

## Important Rules

- Work on **ONE story** per iteration
- Commit frequently with descriptive messages
- Keep CI green (broken code compounds across iterations)
- Read the Codebase Patterns section in progress.txt **before** starting
- Follow existing code patterns in the project
- Do NOT skip tests to make them pass - fix the actual issues
