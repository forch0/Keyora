# Module F27 — Responsive Design & Mobile

| Field | Value |
|---|---|
| **Module** | F27 |
| **Name** | Responsive Design & Mobile |
| **Dependencies** | All prior |
| **Status** | Complete |

---

## Objective

Ensure the entire app works on mobile devices and tablets. While Keyora is primarily a desktop tool (internal staff), it should be usable on mobile for emergency access.

---

## Tasks

### F27.1 Responsive audit

- [ ] Test every page at breakpoints: 320px, 768px, 1024px, 1440px
- [ ] Identify layout issues, overflow, touch target sizes
- [ ] Document issues per page

### F27.2 Sidebar responsive behavior

- [ ] Desktop (1024px+): persistent sidebar
- [ ] Tablet (768-1023px): collapsible sidebar with overlay
- [ ] Mobile (<768px): hamburger menu, slide-in drawer

### F27.3 Table responsive behavior

- [ ] Convert tables to card layout on mobile
- [ ] Or horizontal scroll with sticky first column
- [ ] Apply to: member list, activity logs, access grants, teams

### F27.4 Form responsive behavior

- [ ] Stack form fields vertically on mobile
- [ ] Full-width inputs on mobile
- [ ] Touch-friendly button sizes (min 44px height)

### F27.5 Modal responsive behavior

- [ ] Full-screen modals on mobile
- [ ] Centered dialog on desktop
- [ ] Bottom sheet pattern for mobile (optional)

### F27.6 Touch optimization

- [ ] Ensure all interactive elements are at least 44x44px
- [ ] Add active states for touch feedback
- [ ] Remove hover-only interactions (provide tap alternatives)

---

## API Endpoints Used

None — this module is purely UI/UX.

---

## Acceptance Criteria

- [ ] All pages render correctly at 320px, 768px, 1024px, 1440px
- [ ] Sidebar adapts to mobile drawer
- [ ] Tables transform to cards on mobile
- [ ] Forms are usable on mobile
- [ ] Modals are full-screen on mobile
- [ ] All touch targets are at least 44x44px
- [ ] No horizontal scroll on mobile

---

## What This Module Does NOT Include

- Native mobile app (not in scope)
- PWA / offline support (can be added later)
