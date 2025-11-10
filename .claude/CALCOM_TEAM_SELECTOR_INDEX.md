# Team-Selector Component - Documentation Index

**Project**: AskPro AI Gateway - Cal.com Integration Enhancement
**Date**: 2025-11-10
**Status**: Design Complete - Ready for Implementation

---

## Overview

This documentation suite provides comprehensive UX design, technical specifications, and implementation guidance for enhancing the Cal.com Booking page with an admin-focused Team-Selector component.

**Current State**: Basic dropdown showing team names only
**Future State**: Rich, accessible team selector with service counts, status indicators, search, and mobile optimization

---

## Document Structure

### 1. UX Design Specification
**File**: `CALCOM_TEAM_SELECTOR_UX_DESIGN.md`
**Purpose**: Complete design specification with wireframes, accessibility requirements, and Filament integration

**Contents**:
- Current state UX assessment
- Enhanced component design
- Visual design specification
- User interaction flows
- Component specifications (props, state, events)
- WCAG 2.1 AA accessibility requirements
- Mobile-responsive design
- Filament design system integration
- Implementation recommendations
- Future enhancements roadmap

**Key Sections**:
- Section 1: Current State Analysis
- Section 2: Enhanced Design
- Section 3: User Flows
- Section 4: Component Spec
- Section 5: Mobile Design
- Section 6: Filament Integration
- Section 7: Implementation Guide
- Appendices: Code samples, checklists

**Audience**: Designers, product managers, frontend developers

---

### 2. Visual Flows & Diagrams
**File**: `CALCOM_TEAM_SELECTOR_VISUAL_FLOWS.md`
**Purpose**: ASCII art diagrams and visual representations of flows, states, and interactions

**Contents**:
- User interaction flow diagrams
- State transition diagrams
- Component tree structure
- Data flow architecture
- Responsive layout comparisons
- Badge & indicator system
- Animation specifications
- Error states visualization
- Performance budget charts
- Quick reference card

**Key Diagrams**:
- Complete user flow (START → END)
- State machine diagram
- Component hierarchy tree
- Desktop/Tablet/Mobile layouts
- Keyboard navigation map
- Badge design system
- Animation timeline
- Error state examples

**Audience**: Developers, UX designers, QA testers

---

### 3. Implementation Roadmap
**File**: `CALCOM_TEAM_SELECTOR_IMPLEMENTATION_ROADMAP.md`
**Purpose**: Step-by-step implementation guide with timelines, code samples, and testing requirements

**Contents**:
- Phase 1: Enhanced Data Layer (Backend)
- Phase 2: Core Component (Frontend)
- Phase 3: Advanced Features
- Phase 4: Polish & Production
- Deployment checklist
- Post-launch monitoring
- Success metrics
- Risk mitigation

**Timeline**: 4 weeks (20 working days)

**Key Sections**:
- Detailed task breakdowns
- Code implementation samples
- Testing strategies (unit, E2E, accessibility)
- Performance optimization techniques
- Deployment procedures
- Rollback plans

**Audience**: Developers, DevOps, project managers

---

## Quick Navigation

### For Product Managers / Stakeholders
1. Start with: `CALCOM_TEAM_SELECTOR_UX_DESIGN.md` (Executive Summary, Section 1)
2. Review: User flows (Section 3)
3. Check: Success metrics in `IMPLEMENTATION_ROADMAP.md`

### For Designers
1. Start with: `CALCOM_TEAM_SELECTOR_UX_DESIGN.md` (Sections 2, 5, 6)
2. Review: `VISUAL_FLOWS.md` (Responsive layouts, badge system)
3. Reference: Design tokens and Filament integration

### For Frontend Developers
1. Start with: `IMPLEMENTATION_ROADMAP.md` (Phase 2)
2. Reference: `UX_DESIGN.md` (Sections 4, 7, Appendix A)
3. Use: `VISUAL_FLOWS.md` for interaction patterns

### For Backend Developers
1. Start with: `IMPLEMENTATION_ROADMAP.md` (Phase 1)
2. Reference: `UX_DESIGN.md` (Section 7.2 - Backend Changes)
3. Review: API specification and caching strategy

### For QA Testers
1. Start with: `IMPLEMENTATION_ROADMAP.md` (Phase 4.2 - Testing)
2. Reference: `VISUAL_FLOWS.md` (Error states, edge cases)
3. Use: `UX_DESIGN.md` (Section 4.4 - Accessibility requirements)

---

## Key Features Summary

### User Experience Enhancements
✅ **Information-Rich Selection**
- Service count badges (immediate availability visibility)
- City and staff count metadata
- Integration status indicators
- Default/selected state badges

✅ **Efficient Navigation**
- Real-time search with debounced filtering
- Full keyboard navigation (arrows, home, end, enter)
- Mobile full-screen modal (optimized for touch)
- Session persistence (remembers last selection)

✅ **Accessibility First**
- WCAG 2.1 AA compliant
- Screen reader optimized
- Keyboard-only navigation
- Reduced motion support
- High contrast mode support
- Touch targets ≥48px (mobile)

✅ **Performance Optimized**
- API response <500ms (P95)
- Component render <100ms (P95)
- Search latency <300ms
- Aggressive caching (5-min TTL)
- Code splitting & lazy loading

---

## Technical Architecture

### Backend (Laravel + PostgreSQL)
```
CalcomAtomsController
  ↓
BranchCalcomConfigService
  ↓
Enhanced API: /api/calcom-atoms/config/enhanced
  ↓
Response: {
  teams: [...],
  default_team_id,
  user: {...}
}
  ↓
Redis Cache (5-min TTL)
```

### Frontend (React + Filament)
```
TeamSelector (Container)
  ↓
├─ TeamSelectorTrigger (Button with badge)
├─ TeamSelectorDropdown (Desktop overlay)
├─ TeamSelectorModal (Mobile full-screen)
└─ hooks/
    ├─ useTeamSelector (State management)
    ├─ useTeamSearch (Search logic)
    ├─ useTeamPersistence (localStorage)
    └─ useKeyboardNavigation (A11y)
```

### Integration Points
- **CalcomBookerWidget**: Updates on team selection
- **Livewire Bridge**: Emits 'team-changed' event
- **LocalStorage**: Persists selection (24h TTL)
- **Analytics**: Tracks usage patterns

---

## Design Decisions & Rationale

### Why Enhanced API Endpoint?
**Decision**: Create `/config/enhanced` instead of modifying existing endpoint
**Rationale**:
- Backward compatibility (no breaking changes)
- Performance isolation (caching strategy)
- Future flexibility (can add more metadata)
- Progressive enhancement philosophy

### Why Team-Selector vs Branch-Selector?
**Decision**: New component name "TeamSelector"
**Rationale**:
- Reflects Cal.com terminology (Teams)
- Better semantic clarity for admin users
- Reduces confusion with Branch model
- Allows coexistence during migration

### Why Full-Screen Modal on Mobile?
**Decision**: Mobile displays full-screen modal instead of dropdown
**Rationale**:
- Better touch targets (48px minimum)
- Improved search prominence
- Native mobile app feel
- Prevents viewport issues
- Better keyboard handling (virtual keyboard)

### Why Service Count Badges?
**Decision**: Prominent service count display
**Rationale**:
- Admin testing workflow (need to know availability)
- Immediate feedback (no need to select first)
- Color-coded status (green/yellow/red)
- Reduces trial-and-error clicks

### Why Session Persistence?
**Decision**: Remember last selection for 24 hours
**Rationale**:
- Admin workflow efficiency (testing same branch repeatedly)
- Reduces cognitive load (don't need to remember)
- 24h TTL balances convenience vs stale data
- Graceful degradation (works without localStorage)

---

## Success Criteria Checklist

### UX Quality
- [ ] Service count badges visible on all breakpoints
- [ ] Search filters results in <300ms
- [ ] Mobile modal slides up smoothly (300ms animation)
- [ ] Selection persists across page refreshes
- [ ] Status indicators show correct colors

### Accessibility
- [ ] WCAG 2.1 AA compliant (axe DevTools: 0 violations)
- [ ] Keyboard navigation works (Tab, arrows, Enter, Escape)
- [ ] Screen reader announces changes correctly
- [ ] Focus visible on keyboard navigation only
- [ ] Touch targets ≥48px on mobile
- [ ] Color contrast ≥4.5:1 for text

### Performance
- [ ] API response time <500ms (P95)
- [ ] Component render time <100ms (P95)
- [ ] Search latency <300ms (debounced)
- [ ] Cache hit rate >80%
- [ ] Lighthouse score >90
- [ ] Bundle size increase <50KB

### Functionality
- [ ] Teams load correctly from enhanced API
- [ ] Clicking team updates CalcomBookerWidget
- [ ] Search filters by name, city, and slug
- [ ] Auto-selects single team (if enabled)
- [ ] Handles network errors gracefully
- [ ] Shows empty state for no results

### Browser Compatibility
- [ ] Chrome/Edge (latest 2 versions)
- [ ] Firefox (latest 2 versions)
- [ ] Safari (latest 2 versions)
- [ ] Mobile Safari (iOS 14+)
- [ ] Chrome Mobile (Android 10+)

### Testing Coverage
- [ ] Unit tests >80% coverage
- [ ] E2E tests passing (desktop + mobile)
- [ ] Accessibility tests passing (jest-axe)
- [ ] Visual regression tests passing
- [ ] Performance tests meeting budget

---

## Implementation Timeline

```
┌─────────────────────────────────────────────────────────────┐
│                    4-WEEK TIMELINE                          │
└─────────────────────────────────────────────────────────────┘

Week 1: Backend Foundation
├─ Day 1-2: Enhanced data layer (BranchCalcomConfigService)
├─ Day 3-4: API endpoint & caching (CalcomAtomsController)
└─ Day 5:   Testing & documentation

Week 2: Core Frontend Component
├─ Day 1-2: Component structure (TeamSelector + hooks)
├─ Day 3-4: Integration with CalcomBookerWidget
└─ Day 5:   Testing & bug fixes

Week 3: Advanced Features
├─ Day 1-2: Search & keyboard navigation
├─ Day 3-4: Mobile full-screen modal
└─ Day 5:   Session persistence & testing

Week 4: Production Polish
├─ Day 1-2: Performance optimization & code splitting
├─ Day 3:   Complete testing suite (unit + E2E + a11y)
├─ Day 4:   Documentation & API docs
└─ Day 5:   Deployment & post-launch monitoring
```

---

## Risk Assessment

| Risk | Probability | Impact | Mitigation Strategy |
|------|-------------|--------|---------------------|
| API performance degradation | Medium | High | Aggressive caching, query optimization, monitoring alerts |
| Accessibility regressions | Low | High | Automated tests in CI/CD, manual screen reader testing |
| Mobile UX issues | Medium | Medium | Test on real devices, progressive enhancement approach |
| Browser compatibility bugs | Low | Medium | Use Filament's proven patterns, cross-browser testing |
| User adoption resistance | Low | Low | Clear documentation, gradual rollout, training materials |
| Cache invalidation issues | Medium | Low | Event-driven cache clearing, conservative TTL (5 min) |

---

## Dependencies

### Backend
- Laravel 11.x
- PostgreSQL (existing)
- Redis (existing)
- Laravel Sanctum (auth)

### Frontend
- React 18.x (existing)
- Filament 3.x UI components
- @calcom/atoms (existing)
- Tailwind CSS (existing)
- Optional: @tanstack/react-virtual (for 50+ teams)
- Optional: use-debounce (search optimization)

### Development Tools
- Jest + React Testing Library (unit tests)
- Laravel Dusk (E2E tests)
- jest-axe (accessibility tests)
- Lighthouse CI (performance tests)

---

## Open Questions & Decisions Needed

### Product Decisions
- [ ] Should team favorites/pinning be in MVP or Phase 2?
- [ ] Display inactive teams in dropdown (grayed out) or hide completely?
- [ ] Auto-open dropdown on page load for first-time users?
- [ ] Include team avatar/logo if available?

### Technical Decisions
- [ ] Use Headless UI for dropdown or build custom?
- [ ] Implement virtualization for >50 teams or wait for need?
- [ ] Store search history in localStorage?
- [ ] Add analytics to track most-selected teams?

### Design Decisions
- [ ] Badge design: pill shape vs rectangular?
- [ ] Status indicator: dot vs icon vs text?
- [ ] Mobile header: back arrow vs X button vs both?
- [ ] Empty state illustration: generic or custom?

---

## Resources & References

### Internal Documentation
- Cal.com Integration: `/var/www/api-gateway/claudedocs/02_BACKEND/Calcom/`
- Filament Resources: `/var/www/api-gateway/app/Filament/Resources/`
- React Components: `/var/www/api-gateway/resources/js/components/calcom/`

### External References
- [Filament Documentation](https://filamentphp.com/docs)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
- [Headless UI](https://headlessui.com/) (potential dependency)
- [React ARIA](https://react-spectrum.adobe.com/react-aria/) (accessibility patterns)

### Design Systems
- [Tailwind UI](https://tailwindui.com/) (inspiration for patterns)
- [Radix UI](https://www.radix-ui.com/) (accessibility patterns)
- [Material Design](https://m3.material.io/) (mobile touch guidelines)

---

## Change Log

| Date | Version | Author | Changes |
|------|---------|--------|---------|
| 2025-11-10 | 1.0 | Claude (UX Expert) | Initial comprehensive design suite created |

---

## Approval Sign-Off

**Design Review**:
- [ ] UX Designer: _________________ Date: _______
- [ ] Product Manager: _____________ Date: _______
- [ ] Tech Lead: __________________ Date: _______

**Technical Review**:
- [ ] Backend Lead: _______________ Date: _______
- [ ] Frontend Lead: ______________ Date: _______
- [ ] QA Lead: ___________________ Date: _______

**Final Approval**:
- [ ] Project Manager: ____________ Date: _______
- [ ] Stakeholder: _______________ Date: _______

---

## Next Steps

1. **Immediate** (This Week):
   - [ ] Review all three documents
   - [ ] Schedule design review meeting
   - [ ] Assign technical leads
   - [ ] Set up project tracking (Jira/Linear)

2. **Short-Term** (Week 1):
   - [ ] Obtain approvals from all stakeholders
   - [ ] Create feature branch (`feature/team-selector-enhancement`)
   - [ ] Set up development environment
   - [ ] Begin Phase 1 implementation

3. **Medium-Term** (Weeks 2-4):
   - [ ] Follow implementation roadmap phases
   - [ ] Conduct weekly progress reviews
   - [ ] Track metrics and adjust timeline
   - [ ] Prepare deployment plan

4. **Long-Term** (Post-Launch):
   - [ ] Monitor success metrics
   - [ ] Collect user feedback
   - [ ] Plan Phase 2 enhancements
   - [ ] Document lessons learned

---

**For Questions or Clarifications**:
- UX/Design: Reference `CALCOM_TEAM_SELECTOR_UX_DESIGN.md`
- Visual Flows: Reference `CALCOM_TEAM_SELECTOR_VISUAL_FLOWS.md`
- Implementation: Reference `CALCOM_TEAM_SELECTOR_IMPLEMENTATION_ROADMAP.md`
- General: Contact project lead or refer to this index

**Documentation Maintained By**: Development Team
**Last Review**: 2025-11-10
**Next Review**: After Phase 1 completion
