---
description: Commit changes to the Git repository with a descriptive message
---

# LLM Workflow: Commit Changes to Repository

**Objective:** Stage and commit all modified files in the project directory to the local Git repository.

---

## Instructions

### 1. Check Git Status

Run the following command to see which files have been modified:

```powershell
git status
```

Review the output to understand what will be committed.

---

### 2. Stage All Modified Files

```powershell
git add -A
```

> **Note:** Use `git add <file>` to stage specific files instead of all changes.

---

### 3. Commit with a Descriptive Message

// turbo

```powershell
git commit -m "<type>: <short description>

- <bullet point 1>
- <bullet point 2>
- <bullet point 3>"
```

**Commit Message Types:**

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation changes |
| `style` | Formatting, whitespace (no code change) |
| `refactor` | Code restructuring (no feature change) |
| `test` | Adding or updating tests |
| `chore` | Maintenance tasks |

---

### 4. Verify the Commit

// turbo

```powershell
git log -1 --oneline
```

Confirm the commit hash and message are correct.

---

### 5. (Optional) Push to Remote

If a remote repository is configured and you want to push:

```powershell
git push origin <branch-name>
```

> Replace `<branch-name>` with your current branch (e.g., `main`, `master`, `develop`).

---

## Example Usage

```powershell
git status
git add -A
git commit -m "docs: update system diagrams formatting

- Removed excessive indentation from ASCII diagrams
- Left-aligned FDD and CDM for better readability
- Fixed minor corruption in CDM section"
git log -1 --oneline
git push origin main
```

---

## Notes

- Always review `git status` before committing to avoid staging unintended files.
- Use `.gitignore` to exclude files that should not be tracked.
- If the commit message is complex, consider using `git commit` without `-m` to open an editor.
