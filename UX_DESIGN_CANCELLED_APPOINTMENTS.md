# UX Design: Cancelled Appointments in Filament Admin

## Design Overview

**Philosophy**: Progressive disclosure with visual priority on cancelled state.

**Color Strategy**: Orange/Amber (not red) - communicates importance without alarm.

**Navigation Pattern**: Inline links with context, bidirectional call navigation.

---

## Visual Hierarchy

### Priority Levels

1. **Critical**: Cancellation status (banner, badge, icon)
2. **High**: Original appointment details (strikethrough styling)
3. **Medium**: Cancellation metadata (time, person, fee, reason)
4. **Low**: Related call navigation (contextual links)

### Color Coding

```
Status              Color       Usage
─────────────────────────────────────────
Scheduled           Green       Normal state
Completed           Blue        Neutral complete
Cancelled           Orange      Attention needed ⚠️
No Show             Red         Negative outcome
```

**Rationale**: Orange distinguishes "actionable information" from "error state" (red).

---

## Component Breakdown

### 1. List View (Calls Overview)

#### Visual Elements

```
┌──────────────────────────────────────────────┐
│ Call ID │ Customer │ ... │ [⚠️ Cancelled] │
├──────────────────────────────────────────────┤
│ Hover tooltip shows:                         │
│ • Cancellation date/time                     │
│ • Cancelled by (type + name)                 │
│ • Cancellation fee (if > 0)                  │
│ • Reason (truncated)                         │
│ • Link to cancellation call                  │
└──────────────────────────────────────────────┘
```

#### Information Density

**Visible by Default**:
- Badge with icon (⚠️ Cancelled)
- Row background tint (light orange)

**On Hover (Tooltip)**:
- Full cancellation metadata
- Navigation link to related call

#### Implementation Details

- **Badge**: `BadgeColumn` with `warning` color
- **Icon**: `heroicon-o-exclamation-triangle`
- **Tooltip**: Blade template with structured metadata
- **Row Styling**: `recordClasses()` for background tint

---

### 2. Detail View (Call Detail Page)

#### Layout Structure

**Three Sections**:

1. **Cancellation Banner** (Conditional - Top)
   - Prominent orange border/background
   - Original appointment (strikethrough)
   - Cancellation summary
   - Navigation link

2. **Main Content** (Left - 2/3 width)
   - Call details (ID, time, duration, phone)
   - Transcript (collapsible)
   - Function calls (collapsible)

3. **Sidebar** (Right - 1/3 width)
   - Status badge (large, prominent)
   - Appointment metadata
   - Cancellation details section
   - Related calls section

#### Visual Hierarchy

```
Priority 1: Banner (if cancelled)
  ↓
Priority 2: Status badge (sidebar)
  ↓
Priority 3: Cancellation metadata (sidebar)
  ↓
Priority 4: Call details (main content)
```

#### Information Architecture

**Banner Section** (Collapsed Summary):
- Original: Service + DateTime (strikethrough)
- Cancelled: DateTime + By (person/type)
- Fee + Reason (if applicable)
- Navigation link

**Sidebar Section** (Expanded Details):
- Status badge (large)
- Service + Customer (linked)
- Original time (strikethrough if cancelled)

**Cancellation Details** (Conditional Subsection):
- Cancelled at (date/time)
- Cancelled by (type + name)
- Fee (if > 0, highlighted in orange)
- Reason (full text)
- Refund status (if applicable)

**Related Calls** (Contextual Navigation):
- Booking call (green badge)
- Cancellation call (orange badge)
- Reschedule calls (blue badge)
- Each with "View call" link

---

## Trade-offs & Alternatives Considered

### Decision 1: Orange vs Red for Cancelled Status

**Chosen**: Orange/Amber (`warning` color)

**Alternatives**:
- Red (`danger`): Too alarmist, implies error
- Gray (`secondary`): Not prominent enough
- Yellow: Poor contrast, accessibility issues

**Rationale**: Orange communicates "important information requiring attention" without the negative connotation of red. Cancelled ≠ error.

---

### Decision 2: Banner vs Inline Status

**Chosen**: Both (banner + sidebar badge)

**Alternatives**:
- Banner only: Sidebar feels empty, no persistent visual cue
- Badge only: Insufficient prominence for important state

**Rationale**: Progressive disclosure - banner catches attention immediately, badge provides persistent reference while scrolling.

---

### Decision 3: Tooltip vs Popover in List View

**Chosen**: Tooltip with rich HTML content

**Alternatives**:
- Popover (click to open): Extra click, modal interruption
- Inline expansion: Layout shift, table complexity
- Modal: Too heavy, navigation interruption

**Rationale**: Tooltip balances information density with discoverability. Mouseover is low-friction, no layout shift.

---

### Decision 4: Related Calls - Sidebar vs Separate Tab

**Chosen**: Sidebar section (always visible)

**Alternatives**:
- Separate tab: Hidden by default, low discoverability
- Timeline view: Complex, over-engineered for 2-3 calls
- Modal: Interrupts primary content

**Rationale**: Sidebar keeps navigation visible without requiring tab switching. Most appointments have 1-2 related calls, doesn't warrant separate tab.

---

### Decision 5: Strikethrough vs Opacity for Cancelled Dates

**Chosen**: Strikethrough + reduced opacity (60%)

**Alternatives**:
- Opacity only: Unclear why it's faded
- Strikethrough only: Still feels "active"
- Hidden entirely: Loss of context

**Rationale**: Combined approach clearly signals "cancelled" while preserving historical context.

---

## Accessibility Considerations

### WCAG 2.1 AA Compliance

**Color Contrast**:
- Orange badge: 4.5:1 minimum (text vs background)
- Strikethrough text: Maintains 4.5:1 even at 60% opacity
- Dark mode variants tested

**Keyboard Navigation**:
- All links focusable with Tab
- Tooltip trigger on focus (not just mouseover)
- Skip links for banner-to-content

**Screen Reader Support**:
- Badge includes `aria-label="Status: Cancelled, view details"`
- Strikethrough includes `aria-label="Original appointment (cancelled)"`
- Related calls marked as `<nav aria-label="Related calls">`

**Focus Indicators**:
- All interactive elements have visible focus ring
- Focus ring color: Blue (distinct from orange status)

---

## Performance Considerations

### Database Queries

**List View**:
- Eager load: `appointment.cancellation`
- Avoid N+1 on related calls (check via `relatedCalls()->exists()`)

**Detail View**:
- Single query for appointment + cancellation + related calls
- Use `with()` for eager loading

### Caching Strategy

**Tooltip Content**:
- Cache rendered tooltip HTML (5 min TTL)
- Key: `tooltip:cancellation:{appointment_id}`
- Invalidate on cancellation update

**Related Calls**:
- Cache per call (1 hour TTL)
- Key: `call:{id}:related`
- Invalidate on new appointment/cancellation

---

## Implementation Checklist

### Phase 1: List View (Est: 2-3 hours)
- [ ] Update CallResource table columns
- [ ] Add BadgeColumn for status with icon
- [ ] Create tooltip Blade template
- [ ] Add row styling for cancelled state
- [ ] Test tooltip rendering and navigation

### Phase 2: Detail View (Est: 4-5 hours)
- [ ] Create cancellation banner Blade template
- [ ] Update ViewCall infolist schema
- [ ] Add sidebar cancellation details section
- [ ] Implement related calls section
- [ ] Add conditional visibility logic

### Phase 3: Backend Support (Est: 2-3 hours)
- [ ] Add `getRelatedCallsWithContext()` to Call model
- [ ] Add `getCancellationSummaryAttribute()` to Appointment
- [ ] Create AppointmentCancellation model/migration
- [ ] Add relationships (originalCall, cancellation)

### Phase 4: Styling & Polish (Est: 2-3 hours)
- [ ] Add custom CSS animations
- [ ] Test dark mode variants
- [ ] Verify responsive behavior (mobile)
- [ ] Accessibility audit (WCAG 2.1 AA)

### Phase 5: Testing (Est: 2-3 hours)
- [ ] Unit tests for model methods
- [ ] E2E tests for list view navigation
- [ ] E2E tests for detail view rendering
- [ ] Accessibility testing (keyboard, screen reader)

**Total Estimate**: 12-17 hours

---

## Mobile Responsiveness

### Breakpoints

**Desktop (>768px)**:
- Three-column layout (detail view)
- Full tooltip width (24rem)
- Banner in two-column grid

**Tablet (481-768px)**:
- Two-column layout (sidebar stacks below)
- Reduced tooltip width (18rem)
- Banner in single column

**Mobile (<480px)**:
- Single column layout
- Tooltip at 90vw width
- Banner padding reduced
- Navigation links full width

### Touch Interactions

**List View**:
- Tap badge to show tooltip (not just hover)
- Tooltip stays open until tap outside
- Swipe to dismiss tooltip

**Detail View**:
- Banner sticky on scroll (mobile)
- Collapsible sections auto-collapse on mobile
- Related calls as vertical list (not horizontal)

---

## Future Enhancements (Out of Scope)

### V2 Features

**Timeline View**:
- Visual timeline of appointment lifecycle
- Booking → Confirmation → (Cancellation) nodes
- Call recordings inline

**Batch Operations**:
- Bulk refund for cancelled appointments
- Export cancelled appointments report
- Filter by cancellation reason

**Analytics**:
- Cancellation rate by service/staff
- Average cancellation fee
- Cancellation reason trends

**Notifications**:
- Real-time toast on cancellation (admin panel)
- Email digest of daily cancellations
- SMS alerts for high-value cancellations

---

## Documentation & Training

### Admin User Guide

**List View Usage**:
1. Cancelled appointments show orange badge with ⚠️ icon
2. Hover over badge to see cancellation details
3. Click "View cancellation call" to navigate

**Detail View Usage**:
1. Orange banner appears at top if appointment cancelled
2. Sidebar shows full cancellation metadata
3. "Related Calls" section links to booking/cancellation calls

### Developer Notes

**Adding New Cancellation Fields**:
1. Update `AppointmentCancellation` migration
2. Add to `$fillable` array
3. Update tooltip template
4. Update banner template

**Customizing Colors**:
1. Modify status color map in `CallResource.php`
2. Update CSS custom properties
3. Test dark mode variants

---

## Appendix: Filament Components Reference

### Components Used

**List View**:
- `Tables\Columns\BadgeColumn` - Status badge
- `Tables\Columns\IconColumn` - Related calls indicator
- `Tables\Columns\TextColumn` - All other columns
- `recordClasses()` - Row background styling

**Detail View**:
- `Infolists\Components\Section` - Container sections
- `Infolists\Components\TextEntry` - All text fields
- `Infolists\Components\RepeatableEntry` - Related calls list
- `Infolists\Components\Group` - Column layout

**Custom**:
- Blade templates for banner and tooltip
- CSS animations and transitions

### Filament Best Practices Applied

1. **Use Native Components**: Prefer Filament components over custom HTML
2. **Conditional Visibility**: Use `visible()` method, not Blade `@if`
3. **Relationship Eager Loading**: Use `with()` in queries
4. **Color System**: Use Filament's color palette (`success`, `warning`, etc.)
5. **Responsive Columns**: Use `columnSpan()` for layout control
6. **Icons**: Use Heroicons via `heroicon-o-*` format

---

**Created**: 2025-11-20
**Version**: 1.0
**Status**: Design Complete, Ready for Implementation
