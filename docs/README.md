# Documentation Guide

This folder is organized by topic so docs are easier to maintain and find.

## Structure

- `architecture/`
  - `system-architecture.md` - full architecture, domain model, data flow, role flow
  - `simple-layered-architecture.md` - simplified layered view
- `deployment/`
  - `render-postgres-setup.md` - Render + PostgreSQL deployment setup
- `workflows/`
  - `reclassification-lifecycle.md` - stage transitions and ownership
- `operations/`
  - `troubleshooting.md` - common runtime/deployment issues and fixes

## Reading Order (Recommended)

1. `workflows/reclassification-lifecycle.md`
2. `architecture/simple-layered-architecture.md`
3. `architecture/system-architecture.md`
4. `deployment/render-postgres-setup.md`
5. `operations/troubleshooting.md`

## Documentation Conventions

- Keep docs task-oriented and role-aware.
- Prefer short headings and checklists.
- Add concrete file paths when referencing implementation.
- Update docs in the same PR whenever behavior changes.
