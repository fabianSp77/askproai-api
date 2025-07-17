# Contribution Guidelines

Thank you for contributing to AskProAI! This guide will help you make effective contributions that align with our team's workflow and standards.

## 🚀 Quick Start

1. **Find an issue** or create one
2. **Fork & clone** the repository  
3. **Create a branch** following our naming convention
4. **Make changes** following our standards
5. **Test thoroughly** including edge cases
6. **Submit PR** with detailed description
7. **Address feedback** promptly

## 📋 Types of Contributions

### 🐛 Bug Fixes
- Check if bug is already reported
- Create issue if not exists
- Reference issue in PR

### ✨ New Features
- Discuss in issue first
- Get approval before implementing
- Update documentation

### 📝 Documentation
- Fix typos anytime
- Update outdated sections
- Add missing examples

### 🧪 Tests
- Add missing test coverage
- Fix flaky tests
- Improve test performance

### ♻️ Refactoring
- Discuss significant changes first
- Ensure tests still pass
- Document reasoning

## 🔀 Workflow

### 1. Before You Start

#### Check Existing Work
```bash
# See if someone is already working on it
git fetch --all
git branch -r | grep feature-name
```

#### Set Up Your Environment
```bash
# Fork the repo on GitHub, then:
git clone https://github.com/YOUR-USERNAME/api-gateway.git
cd api-gateway
git remote add upstream https://github.com/askproai/api-gateway.git
```

### 2. Creating Your Branch

#### Branch Naming Convention
```bash
# Format: type/brief-description
feature/add-sms-notifications
bugfix/fix-timezone-calculation
hotfix/security-patch-xxx
docs/update-installation-guide
refactor/optimize-query-performance
test/add-payment-coverage
```

#### Create and Switch
```bash
git checkout -b feature/your-feature-name
```

### 3. Making Changes

#### Code Style
```bash
# Format your code before committing
composer pint

# Run static analysis
composer stan

# Check all quality metrics
composer quality
```

#### Commit Guidelines

##### Commit Message Format
```
type(scope): subject

body (optional)

footer (optional)
```

##### Examples
```bash
# Simple commit
git commit -m "feat(appointments): add SMS reminder support"

# Detailed commit
git commit -m "fix(webhooks): handle rate limit errors

- Add exponential backoff for retries
- Log rate limit headers for monitoring
- Update documentation with rate limits

Closes #123"
```

##### Commit Types
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only
- `style`: Code style (formatting, semicolons, etc)
- `refactor`: Code change that neither fixes a bug nor adds a feature
- `perf`: Performance improvement
- `test`: Adding missing tests
- `chore`: Changes to build process or auxiliary tools

### 4. Testing Your Changes

#### Run All Tests
```bash
php artisan test
```

#### Run Specific Test Suites
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# Specific test file
php artisan test tests/Feature/AppointmentTest.php

# Specific test method
php artisan test --filter test_user_can_create_appointment
```

#### Test Coverage
```bash
php artisan test --coverage

# Generate HTML coverage report
php artisan test --coverage-html coverage
```

#### Manual Testing Checklist
- [ ] Feature works as expected
- [ ] Edge cases handled
- [ ] Error messages are helpful
- [ ] No console errors
- [ ] Mobile responsive (if UI)
- [ ] Performance acceptable

### 5. Preparing Your PR

#### Update Your Branch
```bash
# Get latest changes
git fetch upstream
git rebase upstream/main

# Resolve any conflicts
git status
# Fix conflicts in files
git add .
git rebase --continue
```

#### Final Checks
```bash
# Run all quality checks
composer quality

# Run all tests
php artisan test

# Check for debugging code
grep -r "dd(" app/
grep -r "dump(" app/
grep -r "console.log" resources/js/
```

### 6. Submitting Your PR

#### Push Your Branch
```bash
git push origin feature/your-feature-name
```

#### PR Title Format
```
type(scope): Brief description

# Examples:
feat(appointments): Add recurring appointment support
fix(api): Handle missing timezone parameter
docs(readme): Update installation instructions
```

#### PR Description Template
```markdown
## Description
Brief description of what this PR does

## Motivation and Context
Why is this change required? What problem does it solve?
If it fixes an open issue, link it here.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## How Has This Been Tested?
Describe the tests that you ran to verify your changes.

## Screenshots (if appropriate)
Add screenshots to help explain your changes

## Checklist
- [ ] My code follows the style guidelines
- [ ] I have performed a self-review
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally
- [ ] Any dependent changes have been merged and published
```

### 7. Code Review Process

#### What Reviewers Look For
1. **Correctness**: Does it work?
2. **Design**: Is it well-structured?
3. **Complexity**: Is it simple as possible?
4. **Tests**: Are edge cases covered?
5. **Naming**: Are names clear?
6. **Comments**: Is complex logic explained?
7. **Style**: Does it follow conventions?
8. **Documentation**: Is it updated?

#### Responding to Feedback
```bash
# Make requested changes
git add .
git commit -m "refactor: address review feedback"

# Or amend if small change
git add .
git commit --amend

# Push changes
git push origin feature/your-feature-name
```

#### Common Review Comments
- "Consider extracting this to a service"
- "This could use eager loading"
- "Add test for edge case X"
- "Update documentation for this change"

## 🎯 Contribution Standards

### Code Quality Standards

#### DRY (Don't Repeat Yourself)
```php
// ❌ Bad
public function sendEmail($user) {
    Mail::to($user->email)->send(...);
}

public function sendReminderEmail($user) {
    Mail::to($user->email)->send(...);
}

// ✅ Good
private function sendUserEmail($user, $mailable) {
    Mail::to($user->email)->send($mailable);
}
```

#### SOLID Principles
```php
// Single Responsibility
class AppointmentService {
    // Only handles appointment logic
}

class NotificationService {
    // Only handles notifications
}
```

#### Clear Naming
```php
// ❌ Bad
$d = calculateDiff($a, $b);

// ✅ Good
$daysBetween = calculateDaysBetween($startDate, $endDate);
```

### Testing Standards

#### Test What Matters
```php
// Test business logic, not framework features
public function test_appointment_cannot_be_booked_in_past()
{
    $response = $this->postJson('/api/appointments', [
        'date' => now()->subDay(),
    ]);
    
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['date']);
}
```

#### Use Factories
```php
// ✅ Good
$user = User::factory()->create();
$appointments = Appointment::factory()->count(3)->create([
    'user_id' => $user->id,
]);

// ❌ Bad
$user = new User();
$user->name = 'Test User';
$user->email = 'test@example.com';
$user->save();
```

### Documentation Standards

#### Keep It Current
- Update docs in same PR as code changes
- Remove outdated information
- Add examples for complex features

#### Be Clear and Concise
```markdown
❌ Bad: "This function does stuff with appointments"
✅ Good: "Validates appointment time slots and creates booking records"
```

## 🚫 What Not to Do

### Don't Break These Rules
1. **Never commit sensitive data** (passwords, API keys)
2. **Never skip tests** for "quick fixes"
3. **Never force push** to main/shared branches
4. **Never merge without review** (except documentation typos)
5. **Never leave debug code** (dd(), console.log)

### Common Mistakes to Avoid
- Massive PRs (keep them focused)
- Mixing features in one PR
- Ignoring CI failures
- Not updating tests
- Forgetting documentation

## 🎉 After Your PR is Merged

### Cleanup
```bash
# Delete local branch
git branch -d feature/your-feature-name

# Delete remote branch (if not auto-deleted)
git push origin --delete feature/your-feature-name

# Update your main branch
git checkout main
git pull upstream main
```

### Celebrate!
- Your contribution is live! 🎉
- Update your resume/portfolio
- Share with the team
- Consider your next contribution

## 📚 Resources

### Internal Resources
- [Development Setup](./new-developer-checklist.md)
- [Best Practices](./best-practices-guide.md)
- [Documentation Tour](./documentation-tour.md)

### External Resources
- [How to Write a Git Commit Message](https://chris.beams.io/posts/git-commit/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Laravel Contributing Guide](https://laravel.com/docs/contributions)

### Getting Help
- **Slack**: #dev-help
- **Documentation**: This guide!
- **Team**: Don't hesitate to ask

## 🙏 Thank You!

Every contribution, no matter how small, helps make AskProAI better. We appreciate your time and effort!

Questions? Reach out in #dev-help on Slack.