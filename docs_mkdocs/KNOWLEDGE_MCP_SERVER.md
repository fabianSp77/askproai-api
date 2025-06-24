# Knowledge MCP Server Documentation

## Overview

The `KnowledgeMCPServer` provides a comprehensive knowledge management system for the AskProAI platform. It enables companies to maintain their own knowledge base, which can be used by AI agents to provide accurate, company-specific responses to customer inquiries.

## Features

### 1. Multi-Tenant Knowledge Management
- Each company has its own isolated knowledge base
- Documents are automatically filtered by `company_id`
- Support for company-specific customization

### 2. Industry Templates
Pre-configured templates for different industries:
- **Medical**: Appointment booking guides, FAQs
- **Beauty**: Service descriptions, treatment information
- **Veterinary**: Pet care instructions, appointment guidelines
- **Legal**: Consultation information, fee structures

### 3. Document Versioning
- Automatic version tracking for all document changes
- Complete history of modifications
- Rollback capabilities (future enhancement)

### 4. AI Context Generation
- Automatically selects relevant documents for AI conversations
- Combines company-specific and industry-standard knowledge
- Optimized context length for efficient AI processing

### 5. Full-Text Search
- MySQL full-text search capabilities
- Relevance scoring
- Search highlighting
- Analytics tracking

### 6. Caching Layer
- Redis-based caching for performance
- Configurable TTL (default: 1 hour)
- Automatic cache invalidation on updates

## Available Methods

### `getCompanyKnowledge(array $params)`
Retrieves all knowledge documents for a specific company.

**Parameters:**
- `company_id` (required): The company ID
- `category`: Filter by category slug
- `status`: Document status (default: 'published')
- `limit`: Maximum documents to return (default: 20, max: 100)
- `offset`: Pagination offset

**Example:**
```php
$result = $mcp->getCompanyKnowledge([
    'company_id' => 1,
    'category' => 'appointments',
    'limit' => 10
]);
```

### `searchKnowledge(array $params)`
Search across knowledge documents using full-text search.

**Parameters:**
- `query` (required): Search query (min 3 characters)
- `company_id`: Filter by company
- `categories`: Array of category slugs
- `tags`: Array of tag slugs
- `limit`: Maximum results (default: 20, max: 50)

**Example:**
```php
$result = $mcp->searchKnowledge([
    'query' => 'Terminvereinbarung',
    'company_id' => 1,
    'tags' => ['appointments', 'booking']
]);
```

### `updateKnowledge(array $params)`
Update an existing knowledge document.

**Parameters:**
- `document_id` (required): Document to update
- `company_id` (required): Company ID for verification
- `user_id`: User making the update
- `title`: New title
- `content`: New content
- `excerpt`: New excerpt
- `status`: New status
- `tags`: Array of tag names

**Example:**
```php
$result = $mcp->updateKnowledge([
    'document_id' => 123,
    'company_id' => 1,
    'user_id' => 5,
    'content' => 'Updated content here...',
    'tags' => ['updated', 'important']
]);
```

### `getContextForAI(array $params)`
Generate optimized context for AI conversations.

**Parameters:**
- `company_id` (required): Company ID
- `context`: Current conversation context
- `industry`: Industry type for template selection
- `max_documents`: Maximum documents to include (default: 5, max: 10)

**Example:**
```php
$result = $mcp->getContextForAI([
    'company_id' => 1,
    'context' => 'Customer wants to book appointment',
    'industry' => 'medical',
    'max_documents' => 3
]);
```

### `getCategoryKnowledge(array $params)`
Get all documents in a specific category.

**Parameters:**
- `company_id` (required): Company ID
- `category_slug` (required): Category slug
- `include_subcategories`: Include documents from subcategories (default: true)

**Example:**
```php
$result = $mcp->getCategoryKnowledge([
    'company_id' => 1,
    'category_slug' => 'services',
    'include_subcategories' => true
]);
```

### `createFromTemplate(array $params)`
Create a new document from an industry template.

**Parameters:**
- `company_id` (required): Company ID
- `template_id`: Specific template ID
- `industry`: Industry type (if not using template_id)
- `custom_data`: Custom values for personalization

**Example:**
```php
$result = $mcp->createFromTemplate([
    'company_id' => 1,
    'industry' => 'medical',
    'custom_data' => [
        'title' => 'Our Appointment Process',
        'category_id' => 5
    ]
]);
```

### `getStatistics(array $params)`
Get comprehensive statistics about knowledge usage.

**Parameters:**
- `company_id` (required): Company ID
- `period`: Time period ('7days', '30days', '90days', 'year')

**Example:**
```php
$result = $mcp->getStatistics([
    'company_id' => 1,
    'period' => '30days'
]);
```

## Database Schema

### knowledge_documents
- `id`: Primary key
- `company_id`: Tenant identifier
- `title`: Document title
- `slug`: URL-friendly identifier
- `content`: Processed content (HTML)
- `raw_content`: Original content (Markdown)
- `excerpt`: Short description
- `category_id`: Category reference
- `status`: draft/published/archived
- `order`: Display order
- `metadata`: JSON additional data
- `view_count`: Number of views
- `helpful_count`: Positive feedback count
- `not_helpful_count`: Negative feedback count

### knowledge_versions
- `id`: Primary key
- `document_id`: Reference to document
- `version_number`: Sequential version
- `title`: Title at time of version
- `content`: Content at time of version
- `created_by`: User who created version
- `change_summary`: Description of changes

## Configuration

Add to `config/knowledge.php`:

```php
return [
    'cache' => [
        'ttl' => 3600, // 1 hour
        'prefix' => 'mcp:knowledge'
    ],
    'search' => [
        'min_length' => 3,
        'max_results' => 50
    ],
    'ai' => [
        'max_context_documents' => 10,
        'max_context_length' => 4000
    ]
];
```

## Usage in Retell AI Integration

The Knowledge MCP Server can be integrated with Retell AI to provide context-aware responses:

```php
// In your Retell webhook handler
$knowledgeMCP = new KnowledgeMCPServer();

// Get AI context based on customer query
$context = $knowledgeMCP->getContextForAI([
    'company_id' => $company->id,
    'context' => $customerQuery,
    'industry' => $company->industry
]);

// Pass context to Retell AI
$retellResponse = $retellService->generateResponse([
    'query' => $customerQuery,
    'context' => $context['documents'],
    'company_info' => $context['company_context']
]);
```

## Best Practices

1. **Document Organization**
   - Use clear, descriptive titles
   - Assign appropriate categories
   - Tag with relevant keywords
   - Keep content concise and focused

2. **AI Optimization**
   - Tag documents with 'ai-context' for AI priority
   - Use structured content (headings, lists)
   - Include common questions and answers
   - Keep critical information at the beginning

3. **Performance**
   - Documents are cached for 1 hour
   - Use pagination for large result sets
   - Consider indexing frequently searched fields

4. **Multi-Tenancy**
   - Always include company_id in queries
   - Verify company ownership before updates
   - Use tenant-specific categories and tags

## Testing

Run the test script to verify functionality:

```bash
php test-knowledge-mcp.php
```

This will:
- Create sample documents from templates
- Test search functionality
- Demonstrate AI context generation
- Show statistics capabilities

## Future Enhancements

1. **Markdown Processing**
   - Convert Markdown to HTML
   - Syntax highlighting for code blocks
   - Table of contents generation

2. **Advanced Search**
   - Elasticsearch integration
   - Fuzzy matching
   - Synonym support

3. **Collaboration**
   - Document comments
   - Change approval workflow
   - Team notifications

4. **Analytics**
   - Detailed usage reports
   - Search term analysis
   - Content gap identification