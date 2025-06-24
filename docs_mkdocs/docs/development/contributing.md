# Contributing Guide

## Overview

Thank you for your interest in contributing to AskProAI! This guide will help you get started with contributing to the project. We welcome contributions of all kinds, including bug fixes, feature additions, documentation improvements, and more.

## Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive environment for all contributors. We pledge to:

- Be respectful and considerate
- Welcome newcomers and help them get started
- Focus on what is best for the community
- Show empathy towards other community members

### Expected Behavior

- Use welcoming and inclusive language
- Be respectful of differing viewpoints and experiences
- Gracefully accept constructive criticism
- Focus on collaboration over competition

## Getting Started

### Prerequisites

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/your-username/askproai.git
   cd askproai
   ```

3. Add the upstream repository:
   ```bash
   git remote add upstream https://github.com/askproai/askproai.git
   ```

4. Set up your development environment following the [Development Setup Guide](setup.md)

### Development Workflow

1. **Create a branch** for your feature or fix:
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/issue-description
   ```

2. **Make your changes** following our [Coding Standards](standards.md)

3. **Write/update tests** for your changes

4. **Run tests** to ensure everything works:
   ```bash
   php artisan test
   npm run test
   ```

5. **Commit your changes** using conventional commits:
   ```bash
   git commit -m "feat: add new feature"
   # or
   git commit -m "fix: resolve issue with booking"
   ```

6. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

7. **Create a Pull Request** on GitHub

## Pull Request Process

### Before Submitting

- [ ] Code follows the [Coding Standards](standards.md)
- [ ] All tests pass
- [ ] New features have tests
- [ ] Documentation is updated
- [ ] Commit messages follow conventional commits
- [ ] Branch is up to date with main

### Pull Request Template

```markdown
## Description
Brief description of what this PR does.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Related Issue
Fixes #(issue number)

## Testing
- [ ] Unit tests pass
- [ ] Integration tests pass
- [ ] Manual testing completed

## Checklist
- [ ] My code follows the style guidelines
- [ ] I have performed a self-review
- [ ] I have commented my code where necessary
- [ ] I have updated the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix/feature works
- [ ] New and existing unit tests pass locally
```

### Review Process

1. **Automated Checks**: CI/CD pipeline runs tests and code quality checks
2. **Code Review**: At least one maintainer reviews the code
3. **Testing**: Changes are tested in a staging environment
4. **Approval**: Maintainer approves the PR
5. **Merge**: PR is merged using squash and merge

## Contribution Types

### Bug Reports

#### Before Submitting

1. Check if the issue already exists
2. Verify the bug in the latest version
3. Collect relevant information:
   - Steps to reproduce
   - Expected behavior
   - Actual behavior
   - Environment details

#### Bug Report Template

```markdown
## Bug Description
A clear and concise description of the bug.

## To Reproduce
Steps to reproduce the behavior:
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

## Expected Behavior
What you expected to happen.

## Actual Behavior
What actually happened.

## Screenshots
If applicable, add screenshots.

## Environment
- OS: [e.g. Ubuntu 20.04]
- PHP Version: [e.g. 8.2.1]
- Browser: [e.g. Chrome 91]
- Version: [e.g. 1.0.0]

## Additional Context
Any other relevant information.
```

### Feature Requests

#### Before Submitting

1. Check if the feature already exists or is planned
2. Consider if it aligns with the project goals
3. Think about implementation details

#### Feature Request Template

```markdown
## Feature Description
A clear and concise description of the feature.

## Problem Statement
What problem does this feature solve?

## Proposed Solution
How would you implement this feature?

## Alternatives Considered
What other solutions have you considered?

## Additional Context
Any mockups, examples, or additional information.
```

### Documentation

We welcome documentation improvements! This includes:

- Fixing typos and grammar
- Improving clarity
- Adding examples
- Creating tutorials
- Translating documentation

#### Documentation Standards

```markdown
# Page Title

## Overview
Brief introduction to the topic.

## Concept/Feature Name

### Description
Detailed explanation of the concept.

### Example
```language
// Code example
```

### Best Practices
- Best practice 1
- Best practice 2

## Related Topics
- [Link to related topic](link)
```

## Development Guidelines

### Git Workflow

#### Branch Naming

- Features: `feature/short-description`
- Bugs: `fix/issue-number-description`
- Documentation: `docs/what-is-being-documented`
- Refactoring: `refactor/what-is-being-refactored`

#### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>

<body>

<footer>
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Test additions/changes
- `chore`: Maintenance tasks

Examples:
```bash
feat(appointments): add recurring appointment support

Add ability to create recurring appointments with customizable
frequency and end date. Includes support for weekly and monthly
recurrence patterns.

Closes #123

fix(webhooks): handle missing signature header

The webhook processor was throwing an exception when the signature
header was missing. Now it returns a 401 response instead.

Fixes #456
```

### Code Style

#### PHP

Follow PSR-12 standards:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Models\Appointment;

class AppointmentService implements ServiceInterface
{
    public function __construct(
        private readonly CalendarService $calendar
    ) {
    }
    
    public function createAppointment(array $data): Appointment
    {
        // Implementation
    }
}
```

#### JavaScript/TypeScript

Use ES6+ features and follow Airbnb style guide:

```javascript
// Good
const calculateTotal = (items) => {
    return items.reduce((sum, item) => sum + item.price, 0);
};

// Good
class AppointmentService {
    constructor(apiClient) {
        this.apiClient = apiClient;
    }
    
    async createAppointment(data) {
        try {
            const response = await this.apiClient.post('/appointments', data);
            return response.data;
        } catch (error) {
            console.error('Failed to create appointment:', error);
            throw error;
        }
    }
}
```

### Testing Requirements

#### Unit Tests

Test individual components in isolation:

```php
/** @test */
public function it_calculates_appointment_duration_correctly()
{
    // Arrange
    $service = new AppointmentService();
    $startTime = Carbon::parse('2025-07-01 14:00');
    $endTime = Carbon::parse('2025-07-01 15:30');
    
    // Act
    $duration = $service->calculateDuration($startTime, $endTime);
    
    // Assert
    $this->assertEquals(90, $duration);
}
```

#### Integration Tests

Test component interactions:

```php
/** @test */
public function it_creates_appointment_and_sends_notification()
{
    // Arrange
    Notification::fake();
    $customer = Customer::factory()->create();
    
    // Act
    $response = $this->postJson('/api/appointments', [
        'customer_id' => $customer->id,
        'date' => '2025-07-01',
        'time' => '14:00',
    ]);
    
    // Assert
    $response->assertCreated();
    Notification::assertSentTo($customer, AppointmentConfirmation::class);
}
```

### Documentation Requirements

1. **Code Documentation**: Add PHPDoc blocks for all public methods
2. **API Documentation**: Update OpenAPI/Swagger specs for API changes
3. **User Documentation**: Update user guides for feature changes
4. **Developer Documentation**: Update technical docs for architectural changes

## Release Process

### Version Numbering

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR.MINOR.PATCH** (e.g., 2.1.3)
- **MAJOR**: Breaking changes
- **MINOR**: New features (backwards compatible)
- **PATCH**: Bug fixes (backwards compatible)

### Release Checklist

- [ ] All tests pass
- [ ] Documentation is updated
- [ ] CHANGELOG.md is updated
- [ ] Version number is bumped
- [ ] Release notes are prepared
- [ ] Migration guide (for breaking changes)

## Community

### Getting Help

- **Documentation**: Start with our [documentation](/)
- **Issues**: Check existing [issues](https://github.com/askproai/askproai/issues)
- **Discussions**: Join our [discussions](https://github.com/askproai/askproai/discussions)
- **Discord**: Join our [Discord server](https://discord.gg/askproai)

### Ways to Contribute

1. **Code Contributions**
   - Fix bugs
   - Add features
   - Improve performance
   - Refactor code

2. **Documentation**
   - Fix typos
   - Improve clarity
   - Add examples
   - Create tutorials

3. **Testing**
   - Report bugs
   - Test new features
   - Improve test coverage
   - Performance testing

4. **Community**
   - Answer questions
   - Review pull requests
   - Share experiences
   - Write blog posts

## Recognition

### Contributors

We value all contributions! Contributors are recognized in:

- CONTRIBUTORS.md file
- Release notes
- Project README
- Annual contributor report

### Becoming a Maintainer

Active contributors may be invited to become maintainers. Maintainers have:

- Write access to the repository
- Ability to review and merge PRs
- Voice in project direction
- Access to maintainer resources

## Legal

### License

By contributing to AskProAI, you agree that your contributions will be licensed under the project's license.

### Developer Certificate of Origin

By making a contribution, you certify that:

1. The contribution was created by you
2. You have the right to submit it under the project license
3. You understand it will be public and may be redistributed

## Resources

- [Development Setup](setup.md)
- [Coding Standards](standards.md)
- [Testing Guide](testing.md)
- [API Documentation](../api/)
- [Architecture Overview](../architecture/)

## Questions?

If you have questions about contributing:

1. Check the documentation
2. Search existing issues
3. Ask in discussions
4. Contact maintainers

Thank you for contributing to AskProAI! 🎉