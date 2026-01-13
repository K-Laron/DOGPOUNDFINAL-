---
description: Update all .md documentation files to match the latest system implementation
---

# Update Documentation Workflow

Follow these phases to update ALL markdown documentation files to accurately reflect the current system state.

---

## PHASE 1: DISCOVERY

Scan the entire codebase to understand the current implementation.

### 1.1 Find all .md files

```bash
find . -name "*.md" -type f
```

### 1.2 Identify key source files

- **Backend**: Controllers, Models, Routes, Middleware
- **Frontend**: Pages, Components, API calls
- **Database**: Schema, Migrations

### 1.3 Create inventory

Document what exists:

- Routes/endpoints
- Database tables/columns
- Features implemented
- User roles and permissions

---

## PHASE 2: ANALYSIS

Compare documentation against actual implementation.

### 2.1 For each .md file, check

- [ ] Are all features documented?
- [ ] Are deprecated features removed?
- [ ] Are code examples accurate?
- [ ] Are file paths correct?
- [ ] Is version information current?

### 2.2 Identify gaps

List:

- New features NOT in docs
- Old features that no longer exist
- Incorrect code samples
- Outdated diagrams/flowcharts

---

## PHASE 3: UPDATE

Make documentation match reality.

### 3.1 Priority files (update first)

1. `README.md` - Project overview, setup, features
2. `CHANGELOG.md` - Version history
3. API documentation - Endpoints, params, responses
4. Database docs - Schema, relationships
5. System design docs - Architecture, diagrams

### 3.2 For each update

```markdown
<!-- Before updating, verify in source code -->
File: [path/to/source]
Line: [line number]
Current behavior: [description]
```

### 3.3 Update checklist per file

- [ ] Feature list matches implementation
- [ ] Installation steps work
- [ ] Code snippets are copy-paste ready
- [ ] Links are not broken
- [ ] Screenshots match current UI (if applicable)

---

## PHASE 4: VERIFICATION

Validate all changes.

### 4.1 Cross-reference

For every claim in docs, verify:

```javascript
// Example: If docs say "API supports pagination"
// Verify in: backend/controllers/AnimalController.php
// Look for: $page, $per_page, pagination logic
```

### 4.2 Test code samples

Run any code examples in documentation to ensure they work.

### 4.3 Final checklist

- [ ] All .md files updated
- [ ] No references to removed features
- [ ] Version numbers consistent
- [ ] Dates updated where applicable

---

## OUTPUT FORMAT

For each file updated, provide:

```markdown
### [filename.md]
**Changes:**
1. [Change description]
2. [Change description]

**Lines modified:** X-Y
```

---

## CONSTRAINTS

- Do NOT add features that don't exist
- Do NOT remove documentation for existing features
- Preserve original formatting style
- Keep language consistent (formal/informal)
- Update timestamps only if substantive changes made
