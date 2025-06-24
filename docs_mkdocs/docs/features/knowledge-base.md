# Knowledge Base

## Overview

The AskProAI Knowledge Base provides a comprehensive FAQ and information system that AI agents can reference during calls to provide accurate, company-specific information to customers.

## Features

### Company-Specific Knowledge
- Custom FAQ entries per company
- Branch-specific information
- Service descriptions and pricing
- Business hours and policies

### AI Integration
The knowledge base is automatically integrated with Retell.ai agents:
- Real-time access during calls
- Context-aware responses
- Multi-language support

## Managing Knowledge Base

### Adding FAQ Entries
```php
// Create new FAQ entry
$faq = new CompanyFAQ([
    'company_id' => $company->id,
    'question' => 'What are your opening hours?',
    'answer' => 'We are open Monday to Friday, 9 AM to 6 PM',
    'category' => 'general',
    'is_active' => true
]);
$faq->save();
```

### Categories
- General Information
- Services & Pricing
- Policies & Procedures
- Location & Directions
- Appointments & Booking

### Knowledge Base API
```php
// Retrieve knowledge for AI agent
$knowledge = app(KnowledgeBaseService::class)
    ->forCompany($company)
    ->getRelevantAnswers($query);
```

## Best Practices

1. **Keep Answers Concise**: AI agents work best with clear, concise information
2. **Regular Updates**: Review and update knowledge base monthly
3. **Branch Variations**: Use branch-specific entries for location details
4. **Multi-Language**: Provide translations for international customers

## Integration with Retell.ai

The knowledge base is automatically synced with Retell.ai agents:
```json
{
  "custom_knowledge": {
    "faqs": [...],
    "services": [...],
    "policies": [...]
  }
}
```

## Related Documentation
- [Retell.ai Integration](../integrations/retell.md)
- [Company Configuration](../configuration/services.md)