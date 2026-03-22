# Reclassification Lifecycle

## Primary Flow

1. Faculty prepares draft and submits.
2. Dean reviews and either returns or forwards to HR.
3. HR reviews and either returns or forwards to VPAA.
4. VPAA reviews and either:
   - returns to faculty, or
   - approves to VPAA approved list.
5. VPAA forwards approved list to President.
6. President finalizes approved list.

## Status Progression

- `draft`
- `dean_review`
- `hr_review`
- `vpaa_review`
- `vpaa_approved`
- `president_review`
- `finalized`

Return path:

- `dean_review` or `hr_review` or `vpaa_review` or `vpaa_approved` -> `returned_to_faculty`
- Faculty resubmits -> back to active reviewer stage

## Ownership Rules

- Faculty edits only own application.
- Reviewers can access only their queue stage.
- Dean queue is department-scoped.
- Forward actions are blocked if required workflow checks fail (for example unresolved required comments where enforced).

## Review Comment Behavior (Current Pattern)

- Reviewer creates comments with:
  - visibility: `faculty_visible` or `internal`
  - type: `requires_action` or `info`
- Faculty responds to faculty-visible comments.
- Reviewer marks addressed comments as resolved.
- Threads and status changes are tracked for auditability.
