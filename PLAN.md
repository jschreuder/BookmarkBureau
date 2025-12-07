# Implementation Plan: Invalid Token Error Handling

## Problem Statement
1. When JWT secret changes (or token becomes invalid), users get silent 401 errors in admin
2. Token persists in localStorage with no way for users to clear it except dev tools
3. No feedback to user that they need to re-authenticate
4. No manual logout option in UI

## Solution Overview

Implement a three-part solution:
1. **InvalidTokenDialogService** - Shows dialog when 401 occurs (any cause)
2. **Logout button in sidebar** - Let users manually clear invalid tokens
3. **Clear token on any 401** - Treat any 401 as sign of invalid token

## Implementation Details

### Part 1: InvalidTokenDialogService
**File**: `frontend/src/app/core/services/invalid-token-dialog.service.ts`

Injections: MatDialog, AuthService, Router

Methods:
- `showInvalidTokenDialog()` - Opens Material Dialog
  - Shows icon + message about session expiration
  - Has single button: "Go to Login"
  - On close: calls logout() and navigates to /login
  - Uses a flag to prevent multiple dialogs opening simultaneously

Dialog content:
- Title: "Session Expired"
- Message: "Your session is no longer valid. Please log in again."
- Button: "Go to Login" (primary/raised button)

### Part 2: Modify auth.interceptor.ts
**Current flow**:
```
401 error
  → if refreshing, queue request
  → else start refresh
    → if refresh succeeds, retry request
    → if refresh fails, logout (but error still propagates)
```

**New flow**:
```
401 error
  → Call InvalidTokenDialogService.showInvalidTokenDialog()
  → Return NEVER (prevent error propagation to components)
  → Dialog handles logout and navigation
```

**Changes**:
- Inject InvalidTokenDialogService
- In catch(401): 
  - Call `invalidTokenDialogService.showInvalidTokenDialog()`
  - Return `NEVER` observable (prevents component-level error handling)
  - This stops the error from reaching the component's error handler
- Remove the refresh logic complexity - any 401 means token is invalid

**Rationale**: Any 401 means the token is invalid/expired. No need to try refresh—just show dialog and let user re-login.

### Part 3: Add Logout Button to Admin Sidebar
**File**: `frontend/src/app/admin/admin-layout/admin-layout.component.ts`

Location: Add button above "Back to dashboards" button in sidebar

Button:
- Icon: "logout" or "exit_to_app"
- Label: "Logout"
- On click: calls `AuthService.logout()` and navigates to `/login`
- Styling: consistent with existing buttons

This gives users a manual way to clear bad tokens.

## File Structure
```
frontend/src/app/
├── core/services/
│   ├── invalid-token-dialog.service.ts
│   └── invalid-token-dialog.service.spec.ts
├── shared/components/
│   └── (reuse existing Material Dialog, no new component needed)
└── admin/admin-layout/
    └── admin-layout.component.ts (modified)
```

## Implementation Steps

1. **Create InvalidTokenDialogService**
   - Inject MatDialog, AuthService, Router
   - Implement `showInvalidTokenDialog()` method
   - Use a BehaviorSubject flag to prevent duplicate dialogs
   - On dialog close: logout and navigate to /login

2. **Update auth.interceptor.ts**
   - Inject InvalidTokenDialogService
   - Simplify 401 handling: remove refresh queuing complexity
   - When 401 occurs: show dialog, return NEVER
   - Remove the complex Promise-based refresh queuing logic

3. **Add logout button to admin-layout.component.ts**
   - Find sidebar section with "Back to dashboards" button
   - Add logout button above it
   - Call `authService.logout()` on click
   - Navigate to `/login`

4. **Write tests**
   - InvalidTokenDialogService: verify dialog opens, logout called, navigation triggered
   - auth.interceptor: verify showInvalidTokenDialog called on 401
   - admin-layout: verify logout button navigates to login

## Edge Cases Handled
- Multiple concurrent 401s → Dialog only opens once
- User navigates manually while dialog pending → Dialog still shows
- Logout button clicked manually → Clears token, redirects to login
- 401 during any API call → Same behavior (no special refresh logic)

## Success Criteria
✅ Backend JWT secret changes
✅ User tries to access admin with old token
✅ Dialog appears: "Session Expired - Please log in again"
✅ Token cleared from localStorage
✅ User redirected to login page
✅ Manual logout button available in sidebar
✅ Clicking logout button clears token and goes to login
✅ No component-level error messages appear
✅ User can re-login normally
