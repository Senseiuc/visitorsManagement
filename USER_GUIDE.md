# Visitor Management System - User Guide

## Table of Contents
1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Reception Desk Operations](#reception-desk-operations)
5. [Visitor Management](#visitor-management)
6. [Visit Management](#visit-management)
7. [Administrative Functions](#administrative-functions)
8. [Reports & Analytics](#reports--analytics)
9. [Blacklist Management](#blacklist-management)
10. [Troubleshooting](#troubleshooting)

---

## Introduction

The Visitor Management System is a comprehensive solution for managing visitors, visits, and staff interactions across multiple locations. The system provides real-time tracking, automated notifications, and detailed analytics to ensure secure and efficient visitor management.

### Key Features
- **Real-time Dashboard**: Auto-refreshing widgets showing current visitor status
- **Multi-location Support**: Manage visitors across different buildings and floors
- **Role-based Access**: Three distinct user roles with customized permissions
- **Visitor Check-in/Check-out**: Streamlined process with photo capture
- **Blacklist Management**: Security feature to restrict unwanted visitors
- **Automated Notifications**: SMS/email alerts to staff when visitors arrive
- **Comprehensive Reporting**: Track visitor patterns and generate reports

---

## Getting Started

### Accessing the System

1. Navigate to your organization's Visitor Management System URL
2. Enter your email and password
3. Click **Login**

### Dashboard Overview

Upon login, you'll see different dashboards based on your role:

- **Receptionists**: Reception Desk with pending approvals and active visitors
- **Admins/SuperAdmins**: Analytics dashboard with comprehensive statistics

All dashboards **auto-refresh every 30 seconds** to show real-time data.

---

## User Roles & Permissions

### SuperAdmin
**Full system access** - Can manage everything including:
- All CRUD operations on all resources
- User and role management
- System configuration
- Blacklist management
- Access to all locations

### Admin
**Administrative access** - Can manage:
- Locations, floors, and departments
- Users and visitors
- Visits (view, create, update)
- Blacklist (view and update)
- Reports and analytics

### Receptionist
**Operational access** - Can:
- View and create visits
- Approve pending visits
- Check visitors in and out
- View and create visitor records
- Assign visitor tags
- **Cannot**: Delete records or manage blacklist

> [!NOTE]
> **Staff Members**: Regular staff members do not use the platform directly. They receive notifications when visitors arrive to see them, but do not need to log in to the system.

---

## Reception Desk Operations

The Reception Desk is the primary interface for receptionists to manage daily visitor flow.

### Dashboard Widgets

#### 1. Receptionist Stats (Auto-refreshes every 30s)
- **Pending Approvals**: Number of visits awaiting approval
- **Currently Checked-In**: Visitors on-site right now
- **Today's Visits**: Total visits checked in today

#### 2. Visits Awaiting Approval
Shows all pending visit requests with visitor details.

**Actions Available**:
- **Approve**: Opens approval form to:
  - Upload/verify visitor photo (required if no photo exists)
  - Assign staff member
  - Select location
  - Assign tag number
  - Confirm reason for visit
  - Automatically checks visitor in upon approval

**Important Notes**:
- Blacklisted visitors cannot be approved (marked with ⚠️ warning)
- Tag numbers must be unique for active visitors
- System validates no duplicate check-ins at the same location

#### 3. Visitors Still On-site
Lists all currently checked-in visitors.

**Columns**:
- Visitor name
- Staff member being visited
- Tag number
- Check-in time

**Actions Available**:
- **Check out**: Records visitor departure time

#### 4. Recent Visitors
Shows recently registered visitors (Admin/SuperAdmin only).

---

## Visitor Management

### Viewing Visitors

Navigate to **Visitors** in the sidebar to see all registered visitors.

**Table Columns**:
- Photo (circular thumbnail)
- Full name
- Email
- Mobile number
- Blacklist status
- Registration date

**Available Actions**:
- **View**: See detailed visitor information and visit history
- **Edit**: Update visitor details
- **Delete**: Remove visitor record (Admin/SuperAdmin only)

### Creating a New Visitor

1. Click **New Visitor**
2. Fill in required fields:
   - First name (required)
   - Last name (required)
   - Email (optional but recommended)
   - Mobile number (optional)
3. Upload visitor photo (recommended)
4. Click **Create**

**Tips**:
- Photos help with visitor identification
- Email enables automated notifications
- Mobile number required for SMS notifications

### Editing Visitor Information

1. Find the visitor in the list
2. Click the **Edit** action
3. Update necessary fields
4. Click **Save**

**Editable Fields**:
- Name
- Contact information
- Photo
- Blacklist status (Admin/SuperAdmin only)

### Visitor Detail View

Click **View** on any visitor to see:
- Complete visitor information
- Photo (large display)
- Visit history
- Blacklist status and reason (if applicable)

---

## Visit Management

### Understanding Visit Statuses

- **Pending**: Visit requested but not yet approved
- **Approved**: Visit approved and visitor checked in
- **Checked Out**: Visit completed (has checkout time)

### Creating a Visit Request

**Method 1: Through Admin Panel**
1. Navigate to **Visits**
2. Click **New Visit**
3. Fill in the form:
   - Select visitor (or create new)
   - Select staff member to visit
   - Choose location
   - Select reason for visit
   - Optionally assign tag number
4. Click **Create**

**Method 2: Public Check-in (if enabled)**
- Visitors can self-register via public check-in URL
- Creates pending visit for receptionist approval

### Approving Visits (Receptionist)

From the **Reception Desk** dashboard:

1. Locate visit in "Visits Awaiting Approval" widget
2. Click **Approve** button
3. In the approval form:
   - **Verify/Upload Photo**: Required if visitor has no photo
   - **Confirm Staff Member**: Ensure correct staff selected
   - **Select Location**: Choose appropriate location
   - **Assign Tag Number**: Enter unique visitor tag/badge number
   - **Verify Reason**: Confirm purpose of visit
4. Click **Approve**

**What Happens**:
- Visit status changes to "approved"
- Check-in time is automatically recorded
- Staff member receives notification (if configured)
- Visitor appears in "Visitors Still On-site" widget

### Checking Out Visitors

From the **Reception Desk** dashboard:

1. Find visitor in "Visitors Still On-site" widget
2. Click **Check out** button
3. Confirm the action
4. Checkout time is recorded
5. Visitor disappears from on-site list

**Important**: Ensure visitors return their tags before checkout.

### Viewing Visit History

Navigate to **Visits** to see all visits with filters:

**Available Filters**:
- Status (Pending/Approved)
- Date range
- Location
- Staff member
- Visitor

**Table Columns**:
- Visitor name (with blacklist warning if applicable)
- Staff member
- Location
- Reason for visit
- Check-in time
- Check-out time
- Status
- Tag number

---

## Administrative Functions

### Location Management

**Locations** represent physical buildings or sites.

**Creating a Location**:
1. Navigate to **Locations**
2. Click **New Location**
3. Enter location name and details
4. Click **Create**

**Managing Locations**:
- Edit location details
- View associated floors and departments
- Assign staff to locations

### Floor Management

**Floors** belong to locations and help organize spaces.

**Creating a Floor**:
1. Navigate to **Floors**
2. Click **New Floor**
3. Select parent location
4. Enter floor name/number
5. Click **Create**

### Department Management

**Departments** organize staff by function.

**Creating a Department**:
1. Navigate to **Departments**
2. Click **New Department**
3. Enter department name
4. Select associated location
5. Click **Create**

### User Management

**Managing Staff and Users**:

1. Navigate to **Users**
2. View all system users

**Creating a User**:
1. Click **New User**
2. Fill in required information:
   - Name
   - Email
   - Password
   - Staff ID (optional)
   - Assigned location
3. Assign role(s)
4. Select departments
5. Click **Create**

**User Fields**:
- **Assigned Location**: Primary location for the user
- **Departments**: Can belong to multiple departments
- **Roles**: Determines permissions (SuperAdmin, Admin, Receptionist)

### Role Management

**Roles** define what users can do in the system.

**Standard Roles** (pre-configured):
- SuperAdmin
- Admin
- Receptionist

**Custom Roles**:
1. Navigate to **Roles**
2. Click **New Role**
3. Enter role name and slug
4. Select permissions from the list
5. Click **Create**

**Available Permissions**:
- `locations.*` - Location management
- `floors.*` - Floor management
- `departments.*` - Department management
- `users.*` - User management
- `visitors.*` - Visitor management
- `visits.*` - Visit management
- `blacklist.*` - Blacklist management
- `roles.*` - Role management

### Reason for Visit Management

**Managing Visit Reasons**:

1. Navigate to **Reasons for Visit**
2. View existing reasons (Meeting, Delivery, Interview, etc.)

**Creating a Reason**:
1. Click **New Reason for Visit**
2. Enter reason name
3. Click **Create**

---

## Reports & Analytics

### Dashboard Statistics

**Admin/SuperAdmin Dashboard** shows:

#### Today's Visits Widget
- Total visits today (by check-in time)
- Breakdown: approved vs pending
- Currently on-site count

#### Visitor Statistics Widget
- Total registered visitors
- New visitors this week
- Blacklisted visitors count
- Active visits (currently on-site)

### Generating Reports

Navigate to **Reports** to access reporting features:

**Available Reports**:
- Visit history by date range
- Visitor frequency analysis
- Location usage statistics
- Staff visit patterns
- Peak visit times

**Export Options**:
- CSV export
- PDF reports
- Excel spreadsheets

---

## Blacklist Management

### Understanding Blacklist

The blacklist feature prevents unwanted visitors from being approved for visits.

**Who Can Manage**:
- SuperAdmin: Full access
- Admin: Can view and update
- Receptionist: Cannot access

### Adding to Blacklist

**Method 1: From Visitor Record**
1. Navigate to **Visitors**
2. Find the visitor
3. Click **Edit**
4. Toggle **Is Blacklisted** to ON
5. Enter **Reason for Blacklisting** (required)
6. Click **Save**

**Method 2: From Blacklist Resource**
1. Navigate to **Blacklist**
2. Click **Add to Blacklist**
3. Select visitor
4. Enter reason
5. Click **Create**

### Blacklist Indicators

**Visual Warnings**:
- ⚠️ icon next to blacklisted visitor names
- Red text highlighting
- Description showing blacklist reason
- **Approve** button hidden for blacklisted visitors

### Removing from Blacklist

1. Navigate to **Visitors** or **Blacklist**
2. Find the visitor
3. Click **Edit**
4. Toggle **Is Blacklisted** to OFF
5. Clear the blacklist reason
6. Click **Save**

---

## Troubleshooting

### Common Issues

#### Dashboard Not Updating
**Solution**: The dashboard auto-refreshes every 30 seconds. If data seems stale:
- Wait for the next refresh cycle
- Manually refresh your browser (F5 or Cmd+R)
- Check your internet connection

#### Cannot Approve Visit
**Possible Reasons**:
1. **Visitor is blacklisted**: Check for ⚠️ warning, contact admin
2. **Missing photo**: Upload visitor photo in approval form
3. **Tag number in use**: Choose a different tag number
4. **Visitor already checked in**: Check "Visitors Still On-site" widget

#### Tag Number Already Assigned
**Solution**: 
- Check "Visitors Still On-site" widget to see who has the tag
- Use a different tag number
- Check out the visitor with that tag if they've already left

#### Cannot See Certain Features
**Reason**: Your role doesn't have permission
**Solution**: Contact your administrator to request access

#### Visitor Already Checked In Error
**Solution**:
- Check if visitor is in "Visitors Still On-site" widget
- If they've left, check them out first
- Then create a new visit

#### Staff Not in Location List
**Reason**: Staff member not assigned to the selected location
**Solution**:
- Verify the location selection
- Contact admin to assign staff to the location
- Or select a different location where the staff is assigned

### Getting Help

**For Technical Issues**:
1. Check this user guide
2. Contact your system administrator
3. Check system notifications for announcements

**For Permission Issues**:
- Contact your administrator to review your role and permissions

---

## Best Practices

### For Receptionists

1. **Check the dashboard regularly** - It updates every 30 seconds
2. **Verify visitor photos** - Always ensure clear, identifiable photos
3. **Validate tag numbers** - Double-check tag assignment before approval
4. **Check out visitors promptly** - Maintain accurate on-site counts
5. **Watch for blacklist warnings** - Never approve blacklisted visitors

### For Administrators

1. **Keep locations updated** - Ensure all buildings/floors are configured
2. **Review blacklist regularly** - Remove entries when appropriate
3. **Monitor user permissions** - Assign appropriate roles
4. **Generate regular reports** - Track visitor patterns and trends
5. **Maintain staff assignments** - Keep location assignments current

### For All Users

1. **Use strong passwords** - Protect your account
2. **Log out when finished** - Especially on shared computers
3. **Report suspicious activity** - Contact security immediately
4. **Keep visitor information confidential** - Follow privacy policies
5. **Verify visitor identity** - When in doubt, ask for ID

---

## Keyboard Shortcuts

- **Ctrl/Cmd + K**: Global search
- **Esc**: Close modals/dialogs
- **Tab**: Navigate form fields
- **Enter**: Submit forms (when focused on submit button)

---

## System Requirements

### Supported Browsers
- Google Chrome (recommended)
- Mozilla Firefox
- Safari
- Microsoft Edge

### Recommended Setup
- Modern browser (latest version)
- Stable internet connection
- Screen resolution: 1280x720 or higher

---

## Glossary

- **Check-in**: The process of recording a visitor's arrival
- **Check-out**: The process of recording a visitor's departure
- **Tag Number**: Physical badge or identifier given to visitors
- **Pending Visit**: Visit request awaiting receptionist approval
- **Active Visit**: Visitor currently on-site (checked in, not checked out)
- **Blacklist**: List of visitors restricted from approval
- **Location Scoping**: Filtering data by assigned locations

---

## Quick Reference

### Visit Workflow
1. Visitor arrives or pre-registers
2. Visit appears in "Visits Awaiting Approval"
3. Receptionist reviews and approves
4. Visitor is checked in automatically
5. Visitor appears in "Visitors Still On-site"
6. Receptionist checks out visitor when leaving
7. Visit record is complete

### Permission Quick Reference

| Action | SuperAdmin | Admin | Receptionist |
|--------|-----------|-------|--------------||
| View Visits | ✅ | ✅ | ✅ |
| Create Visits | ✅ | ✅ | ✅ |
| Approve Visits | ✅ | ✅ | ✅ |
| Check In/Out | ✅ | ✅ | ✅ |
| Delete Visits | ✅ | ✅ | ❌ |
| Manage Blacklist | ✅ | ✅ | ❌ |
| Manage Users | ✅ | ✅ | ❌ |
| View Reports | ✅ | ✅ | ✅ |

---

**Document Version**: 1.0  
**Last Updated**: November 2025  
**For Support**: Contact your system administrator
