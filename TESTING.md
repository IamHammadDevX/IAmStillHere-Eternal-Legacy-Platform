# IAmStillHere - Comprehensive Test Cases

## Test Environment Setup
- Browser: Chrome, Firefox, Safari, Edge
- PHP Version: 8.x
- Database: PostgreSQL or MySQL
- Test Data: Admin account + 3 test user accounts

---

## 1. Authentication & User Management Tests

### Test Case 1.1: User Registration
**Objective:** Verify new users can register successfully

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigate to `/frontend/register.php` | Registration form displays |
| 2 | Fill in: Username: `testuser1`, Email: `test1@example.com`, Password: `Password123!`, Full Name: `Test User One` | All fields accept input |
| 3 | Click "Register" | Success message displays |
| 4 | Check database | New user record exists with hashed password |
| 5 | Verify email format | Invalid email shows error |
| 6 | Test password < 8 chars | Error message shows |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 1.2: User Login
**Objective:** Verify users can login with correct credentials

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigate to `/frontend/login.php` | Login form displays |
| 2 | Enter admin/admin123 | Login successful, redirect to admin.php |
| 3 | Enter testuser1/Password123! | Login successful, redirect to dashboard.php |
| 4 | Enter wrong password | Error: "Invalid username or password" |
| 5 | Check session | Session ID regenerated after login |
| 6 | Verify activity log | Login action logged with IP address |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 1.3: Session Management
**Objective:** Verify session timeout and security

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as testuser1 | Session created |
| 2 | Wait 61 minutes (or modify timeout) | Session expires |
| 3 | Try to access dashboard | Redirected to login |
| 4 | Login again | New session ID generated |
| 5 | Open in private window | Separate session created |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 1.4: Logout
**Objective:** Verify logout functionality

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as testuser1 | Dashboard displays |
| 2 | Click "Logout" | Success message, redirect to home |
| 3 | Check session | Session destroyed |
| 4 | Try to access dashboard | Redirected to login |
| 5 | Verify activity log | Logout action logged |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## 2. Profile & Memorial Configuration Tests

### Test Case 2.1: Profile Editing
**Objective:** Verify users can update their profile

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login and go to profile.php?user_id=2 | Profile page displays |
| 2 | Click "Edit Profile" | Modal opens |
| 3 | Upload profile photo (JPEG, 2MB) | File accepted |
| 4 | Upload cover photo (PNG, 3MB) | File accepted |
| 5 | Update bio: "This is my memorial page" | Text saved |
| 6 | Set date of birth: 1980-05-15 | Date saved |
| 7 | Click "Save Changes" | Success message, page reloads |
| 8 | Verify changes | Profile shows new photos and bio |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 2.2: Memorial Settings
**Objective:** Verify users can configure memorial settings

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Click "Configure Memorial" | Modal opens |
| 2 | Set "Enable Memorial Mode" to "Yes" | Option selected |
| 3 | Set date of passing: 2024-01-15 | Date saved |
| 4 | Set tribute permission: "Everyone" | Option selected |
| 5 | Click "Save Memorial Settings" | Success message |
| 6 | Check database | is_memorial=true, date_of_passing set |
| 7 | Verify activity log | Memorial settings update logged |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 2.3: Public Memorial Listing
**Objective:** Verify memorial pages display correctly

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Enable memorial mode for testuser1 | Memorial active |
| 2 | Upload public memory | Memory visible |
| 3 | Navigate to `/frontend/memorials.php` | Memorial list displays |
| 4 | Verify testuser1 appears | Card shows profile photo, name, dates |
| 5 | Click "View Memorial" | Redirects to profile.php |
| 6 | Test with no public content | Memorial not listed |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## 3. Memory Management Tests

### Test Case 3.1: Photo Upload
**Objective:** Verify photo upload functionality

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login and go to dashboard | Dashboard displays |
| 2 | Click "Upload" in Memories card | Modal opens |
| 3 | Enter title: "Family Reunion 2020" | Text accepted |
| 4 | Enter description: "Great day with family" | Text accepted |
| 5 | Upload JPEG image (5MB) | File accepted |
| 6 | Set memory date: 2020-07-15 | Date accepted |
| 7 | Set privacy: "Public" | Option selected |
| 8 | Click "Upload" | Success message |
| 9 | Verify in Memories tab | Photo displays correctly |
| 10 | Check file system | File saved to /data/uploads/photos/ |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 3.2: Video Upload
**Objective:** Verify video upload functionality

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Click "Upload" memory | Modal opens |
| 2 | Upload MP4 video (20MB) | File accepted |
| 3 | Set privacy: "Family" | Option selected |
| 4 | Upload | Success message |
| 5 | Verify video plays | Video player works |
| 6 | Check file location | Saved to /data/uploads/videos/ |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 3.3: Document Upload
**Objective:** Verify PDF document upload

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Upload PDF document (10MB) | File accepted |
| 2 | Set privacy: "Private" | Option selected |
| 3 | Upload | Success message |
| 4 | Check file location | Saved to /data/uploads/documents/ |
| 5 | Verify icon display | Document icon shows |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 3.4: File Upload Validation
**Objective:** Verify file type and size restrictions

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Try to upload .exe file | Error: "Invalid file type" |
| 2 | Try to upload 60MB file | Error: "File size exceeds maximum" |
| 3 | Upload without title | Error: "Title and file are required" |
| 4 | Upload valid image | Success |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## 4. Timeline & Milestones Tests

### Test Case 4.1: Create Milestone
**Objective:** Verify milestone creation

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Click "Add" in Milestones card | Modal opens |
| 2 | Enter title: "Graduated College" | Text accepted |
| 3 | Enter description: "BSc Computer Science" | Text accepted |
| 4 | Set date: 2005-06-15 | Date accepted |
| 5 | Set category: "Education" | Text accepted |
| 6 | Set privacy: "Public" | Option selected |
| 7 | Click "Add Milestone" | Success message |
| 8 | Check Timeline tab | Milestone displays in order |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 4.2: Timeline Display
**Objective:** Verify timeline sorting and display

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Create milestone: Birth (1980-05-15) | Added |
| 2 | Create milestone: Graduation (2005-06-15) | Added |
| 3 | Create milestone: Marriage (2010-08-20) | Added |
| 4 | View Timeline tab | Events in chronological order |
| 5 | Verify visual | Timeline connects events with line |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## 5. Scheduled Events Tests

### Test Case 5.1: Schedule Future Message
**Objective:** Verify event scheduling

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Click "Schedule" in Events card | Modal opens |
| 2 | Enter title: "Birthday Message" | Text accepted |
| 3 | Enter message: "Happy Birthday!" | Text accepted |
| 4 | Set date: tomorrow 10:00 AM | Future date accepted |
| 5 | Set privacy: "Family" | Option selected |
| 6 | Click "Schedule" | Success message |
| 7 | Check database | Event status = "scheduled" |
| 8 | Try to set past date | Error: "Date must be in future" |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## 6. Privacy & Access Control Tests

### Test Case 6.1: Public Content Visibility
**Objective:** Verify public content is visible to all

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as testuser1 | Logged in |
| 2 | Upload memory with privacy="public" | Uploaded |
| 3 | Logout | Logged out |
| 4 | View testuser1's profile | Public content visible |
| 5 | View memories | Public memories display |
| 6 | Verify tributes | Tribute form shows |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 6.2: Family-Only Content
**Objective:** Verify family privacy restrictions

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as testuser1 | Logged in |
| 2 | Upload memory with privacy="family" | Uploaded |
| 3 | Add testuser2 as family member | Added to family_members table |
| 4 | Login as testuser2 | Logged in |
| 5 | View testuser1's profile | Family content visible |
| 6 | Login as testuser3 (non-family) | Logged in |
| 7 | View testuser1's profile | Family content NOT visible |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 6.3: Private Content
**Objective:** Verify private content restrictions

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Upload memory with privacy="private" | Uploaded |
| 2 | Logout and view as public | Content NOT visible |
| 3 | Login as different user | Content NOT visible |
| 4 | Login as admin | Content visible (admin override) |
| 5 | Login as owner | Content visible |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## 7. Tribute System Tests

### Test Case 7.1: Post Public Tribute
**Objective:** Verify tribute posting

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigate to memorial profile | Profile displays |
| 2 | Verify tribute form shows | Form visible |
| 3 | Enter name: "John Doe" | Text accepted |
| 4 | Enter email: "john@example.com" | Email accepted |
| 5 | Enter message: "Rest in peace" | Text accepted |
| 6 | Click "Post Tribute" | Success message |
| 7 | Check Tributes tab | Tribute displays |
| 8 | Verify database | IP address logged |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 7.2: Tribute Access Control
**Objective:** Verify tribute visibility based on memorial privacy

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | User has NO public content | Memorial not accessible |
| 2 | Try to view tributes | Error: "No permission" |
| 3 | Try to post tribute | Error: "Memorial does not accept tributes" |
| 4 | Add public content | Memorial accessible |
| 5 | Post tribute | Success |
| 6 | Verify tribute displays | Tribute visible |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## 8. Admin Dashboard Tests

### Test Case 8.1: User Management
**Objective:** Verify admin can manage users

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as admin | Admin dashboard accessible |
| 2 | Navigate to Admin panel | Users list displays |
| 3 | View all users | Table shows all users |
| 4 | Change testuser1 status to "Suspended" | Status updated |
| 5 | Logout and login as testuser1 | Login fails (suspended) |
| 6 | Login as admin, set to "Active" | Status updated |
| 7 | testuser1 can login again | Login succeeds |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 8.2: Admin Access Control
**Objective:** Verify only admins can access admin panel

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login as regular user | Logged in |
| 2 | Navigate to /frontend/admin.php | Access denied / redirected |
| 3 | Try admin API endpoints | 403 Forbidden |
| 4 | Login as admin | Full access granted |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## 9. Security Tests

### Test Case 9.1: SQL Injection Prevention
**Objective:** Verify SQL injection protection

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Login field: `admin' OR '1'='1` | Login fails, no SQL error |
| 2 | Search: `'; DROP TABLE users; --` | No database damage |
| 3 | Memory title: `<script>alert('xss')</script>` | Script not executed |
| 4 | All inputs sanitized | No injection possible |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 9.2: XSS Prevention
**Objective:** Verify cross-site scripting protection

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Bio: `<script>alert('XSS')</script>` | Rendered as text, not executed |
| 2 | Tribute: `<img src=x onerror=alert('XSS')>` | Image tag escaped |
| 3 | Title: `<a href="javascript:alert()">Link</a>` | JavaScript blocked |
| 4 | All user inputs escaped | No XSS execution |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 9.3: Session Fixation Prevention
**Objective:** Verify session regeneration

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Check session ID before login | ID recorded |
| 2 | Login successfully | New session ID generated |
| 3 | Verify session_regenerate_id() called | Session ID changed |
| 4 | Old session ID invalid | Cannot access with old ID |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## 10. Performance Tests

### Test Case 10.1: Page Load Time
**Objective:** Verify acceptable load times

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Measure homepage load | < 2 seconds |
| 2 | Measure dashboard load | < 3 seconds |
| 3 | Measure profile with 50 memories | < 4 seconds |
| 4 | Check database queries | Optimized with indexes |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

### Test Case 10.2: Concurrent Users
**Objective:** Test multiple simultaneous users

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | 10 users upload files simultaneously | All succeed |
| 2 | 20 users browse memorials | No performance degradation |
| 3 | Check database connections | No connection errors |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## 11. Mobile Responsive Tests

### Test Case 11.1: Mobile Layout
**Objective:** Verify mobile responsiveness

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Open on iPhone (375px width) | Layout adapts |
| 2 | Navigation menu | Hamburger menu shows |
| 3 | Profile page | Cover photo responsive |
| 4 | Cards | Stack vertically |
| 5 | Forms | Inputs full width |
| 6 | Modals | Scroll on small screens |

**Status:** ✅ Pass / ❌ Fail / ⚠️ Partial

---

## Test Execution Summary

| Category | Total Tests | Passed | Failed | Partial |
|----------|-------------|--------|--------|---------|
| Authentication | 4 | - | - | - |
| Profile & Memorial | 3 | - | - | - |
| Memory Management | 4 | - | - | - |
| Timeline & Milestones | 2 | - | - | - |
| Scheduled Events | 1 | - | - | - |
| Privacy & Access | 3 | - | - | - |
| Tributes | 2 | - | - | - |
| Admin Dashboard | 2 | - | - | - |
| Security | 3 | - | - | - |
| Performance | 2 | - | - | - |
| Mobile | 1 | - | - | - |
| **TOTAL** | **27** | **-** | **-** | **-** |

---

## Bug Tracking Template

| Bug ID | Title | Severity | Steps to Reproduce | Expected | Actual | Status |
|--------|-------|----------|-------------------|----------|--------|--------|
| BUG-001 | | Critical/High/Medium/Low | | | | Open/Fixed/Closed |

---

## Test Data

### Test Users
1. **Admin**: admin / admin123
2. **Test User 1**: testuser1 / Password123!
3. **Test User 2**: testuser2 / Password123!
4. **Test User 3**: testuser3 / Password123!

### Test Files
- **Photo**: sample_photo.jpg (2MB, JPEG)
- **Video**: sample_video.mp4 (15MB, MP4)
- **Document**: sample_doc.pdf (5MB, PDF)

---

## Automated Test Scripts (Future)

```bash
# Run all tests
npm test

# Run specific test suite
npm test -- authentication
npm test -- privacy
npm test -- security

# Generate coverage report
npm run test:coverage
```

---

**Testing Notes:**
- Run tests in multiple browsers
- Test with different network speeds
- Verify database integrity after each test
- Clear cache between test runs
- Document any deviations from expected results
