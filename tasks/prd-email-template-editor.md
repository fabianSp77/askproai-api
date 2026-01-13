# PRD: Email Template Editor für Service Gateway

## Introduction/Overview

Der Email Template Editor ermöglicht Admins, eigene Email-Templates für den Service Gateway zu erstellen und zu verwalten. Templates können Variablen wie `{{customer_name}}` oder `{{case_number}}` enthalten, die beim Versand automatisch ersetzt werden. Eine Live-Preview zeigt, wie die Email aussehen wird.

## Goals

- Admins können eigene Email-Templates erstellen, bearbeiten und löschen
- Templates sind company-scoped (Multi-Tenancy)
- Variable-Unterstützung mit Mustache-Syntax `{{variable}}`
- Live-Preview beim Bearbeiten
- Integration mit bestehendem EmailOutputHandler

## User Stories

### US-001: Add email_templates database table
**Description:** As a developer, I need to store custom email templates in the database.

**Acceptance Criteria:**
- [ ] Migration creates email_templates table with columns: id, company_id, name, subject, body_html, variables (JSON), is_active, created_at, updated_at
- [ ] company_id is foreign key to companies table with cascade delete
- [ ] name is VARCHAR(255) NOT NULL
- [ ] subject is VARCHAR(500) NOT NULL
- [ ] body_html is TEXT NOT NULL
- [ ] variables is JSON nullable
- [ ] is_active is BOOLEAN default true
- [ ] Add index on company_id
- [ ] Migration runs: php artisan migrate
- [ ] php artisan test passes
- [ ] ./vendor/bin/pint --test passes

### US-002: Add EmailTemplate Eloquent model
**Description:** As a developer, I need an Eloquent model to interact with email templates.

**Acceptance Criteria:**
- [ ] Model at app/Models/EmailTemplate.php
- [ ] Extends CompanyScopedModel for multi-tenancy
- [ ] Has fillable: ['company_id', 'name', 'subject', 'body_html', 'variables', 'is_active']
- [ ] Has casts: ['variables' => 'array', 'is_active' => 'boolean']
- [ ] Has belongsTo relationship to Company
- [ ] Has scope: scopeActive() for filtering active templates
- [ ] php artisan test passes
- [ ] ./vendor/bin/pint --test passes

### US-003: Add EmailTemplateResource list page
**Description:** As an admin, I want to see all email templates in a list.

**Acceptance Criteria:**
- [ ] Resource at app/Filament/Resources/EmailTemplateResource.php
- [ ] List page shows columns: name (searchable), subject (searchable), is_active (BooleanColumn/IconColumn), created_at
- [ ] List has filter for is_active status
- [ ] Sortable columns: name, created_at
- [ ] Navigation label: 'Email Templates' in Settings group
- [ ] Navigation icon: heroicon-o-envelope
- [ ] Accessible at /admin/email-templates
- [ ] php artisan test passes
- [ ] ./vendor/bin/pint --test passes

### US-004: Add EmailTemplateResource create/edit form
**Description:** As an admin, I want to create and edit email templates.

**Acceptance Criteria:**
- [ ] Create form has: name (TextInput, required, max 255), subject (TextInput, required, max 500), body_html (RichEditor, required), is_active (Toggle, default true)
- [ ] Edit form pre-fills existing data correctly
- [ ] Form validates required fields
- [ ] Success notification on save
- [ ] Redirects to list after create
- [ ] php artisan test passes
- [ ] ./vendor/bin/pint --test passes

### US-005: Add template variables helper section
**Description:** As an admin, I want to see which variables I can use in templates.

**Acceptance Criteria:**
- [ ] Add Placeholder or Section component in form showing available variables
- [ ] Variables listed: customer_name, customer_email, company_name, case_number, case_subject, case_description, case_status, case_priority, created_at
- [ ] Each variable shown with description
- [ ] Hint text explains: "Use {{variable_name}} in subject or body"
- [ ] php artisan test passes
- [ ] ./vendor/bin/pint --test passes

### US-006: Add EmailTemplateService for rendering
**Description:** As the system, I need a service to render templates with actual data.

**Acceptance Criteria:**
- [ ] Service at app/Services/ServiceGateway/EmailTemplateService.php
- [ ] Method: render(EmailTemplate $template, array $data): array
- [ ] Returns ['subject' => string, 'body' => string]
- [ ] Replaces {{variable}} placeholders with values from $data
- [ ] Handles missing variables gracefully (replaces with empty string)
- [ ] Unit test covers: happy path, missing variables
- [ ] php artisan test passes
- [ ] ./vendor/bin/pint --test passes

### US-007: Add template_id to ServiceOutputConfiguration
**Description:** As a developer, I need to link output configurations to custom templates.

**Acceptance Criteria:**
- [ ] Add migration: add template_id nullable column to service_output_configurations
- [ ] template_id is foreign key to email_templates with SET NULL on delete
- [ ] Update ServiceOutputConfiguration model with belongsTo EmailTemplate
- [ ] Migration runs successfully
- [ ] php artisan test passes
- [ ] ./vendor/bin/pint --test passes

### US-008: Integrate EmailTemplateService with EmailOutputHandler
**Description:** As the system, I want emails to use custom templates when configured.

**Acceptance Criteria:**
- [ ] Modify EmailOutputHandler::handle() method
- [ ] If ServiceOutputConfiguration has template_id, load and render that template
- [ ] Pass ServiceCase data as variables to template
- [ ] If no custom template, use existing default Blade template (fallback)
- [ ] Log which template was used
- [ ] php artisan test passes
- [ ] ./vendor/bin/pint --test passes

## Functional Requirements

- FR-1: Email templates are stored per company (multi-tenant)
- FR-2: Templates support Mustache-style variables: {{variable_name}}
- FR-3: Admin can create, read, update, delete templates via Filament
- FR-4: Templates can be activated/deactivated
- FR-5: EmailOutputHandler uses custom template if configured
- FR-6: System falls back to default template if no custom template assigned

## Non-Goals (Out of Scope)

- Visual drag-and-drop template builder
- Template versioning/history
- Template import/export
- A/B testing of templates
- Template scheduling
- Email preview with actual case data (only variable hints)

## Technical Considerations

- Use existing CompanyScopedModel for multi-tenancy
- Reuse Filament RichEditor for HTML editing
- EmailTemplateService uses simple str_replace for variables
- No Blade compilation needed - pure string replacement

## Success Metrics

- Admin can create template in under 2 minutes
- Templates render correctly with all variables
- No regression in existing email functionality
