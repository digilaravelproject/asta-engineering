# ASTA Engineering Admin API cURL Collection

Base URL used below:

```text
http://localhost/asta-engineering/public/api
```

For protected customer and site APIs, replace `PASTE_BEARER_TOKEN_HERE` with the
`data.token` returned by signup, password login, or email OTP login.

## 1. Admin signup

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/auth/signup' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "name": "Asta Admin",
    "email": "admin@astaengineering.com",
    "phone": "+919876543210",
    "password": "Password123",
    "password_confirmation": "Password123"
  }'
```

## 2. Admin login with email or phone and password

Use either the email or phone in `login`.

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/auth/login' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "login": "+919876543210",
    "password": "Password123"
  }'
```

## 3. Forgot password (email only)

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/auth/forgot-password' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "email": "admin@astaengineering.com"
  }'
```

## 4. Verify password-reset OTP

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/auth/password/verify-otp' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "email": "admin@astaengineering.com",
    "otp": "1234"
  }'
```

Copy `data.reset_token` from the response for API 6.

## 5. Resend password-reset OTP

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/auth/password/resend-otp' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "email": "admin@astaengineering.com"
  }'
```

## 6. Reset password

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/auth/password/reset' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "email": "admin@astaengineering.com",
    "reset_token": "PASTE_RESET_TOKEN_HERE",
    "password": "NewPassword123",
    "password_confirmation": "NewPassword123"
  }'
```

## 7. Login using email OTP (send OTP)

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/auth/email-login' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "email": "admin@astaengineering.com"
  }'
```

## 8. Verify email-login OTP

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/auth/email-login/verify-otp' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "email": "admin@astaengineering.com",
    "otp": "1234"
  }'
```

This response contains the Bearer token used by protected APIs.

Optional resend endpoint for email login:

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/auth/email-login/resend-otp' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{"email":"admin@astaengineering.com"}'
```

## 9. Add customer

This request is multipart so a company logo can be uploaded. The example works
without a logo; to upload one, append `--form 'logo=@C:/full/path/logo.jpg'`.

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/customers' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --form 'company_name=A R Engineering' \
  --form 'contact_person_name=Mr. Sharma' \
  --form 'email=info@arengineering.com' \
  --form 'mobile_number=9876543211' \
  --form 'gst_number=27ABCDE1234F1Z5' \
  --form 'billing_address=Industrial Estate, Pune, Maharashtra' \
  --form 'pincode=411001' \
  --form 'status=active'
```

## 10. Customer listing with sites

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/customers?per_page=15&page=1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 11. Search customers

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/customers/search?q=Engineering&per_page=15' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 12. Get customer by ID

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/customers/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 13. Update customer

All fields are optional during update. Send only fields that need to change.

```bash
curl --request PATCH 'http://localhost/asta-engineering/public/api/admin/customers/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "company_name": "A R Engineering Pvt. Ltd.",
    "contact_person_name": "Mr. Sharma",
    "email": "accounts@arengineering.com",
    "mobile_number": "9876543211",
    "gst_number": "27ABCDE1234F1Z5",
    "billing_address": "Updated address, Pune, Maharashtra",
    "pincode": "411018",
    "status": "active"
  }'
```

## 14. Delete customer

Deleting a customer also deletes that customer's sites and their equipment
records.

```bash
curl --request DELETE 'http://localhost/asta-engineering/public/api/admin/customers/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 15. Add site

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/sites' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "customer_id": 1,
    "site_name": "MIDC Plant - Warehouse 1",
    "flat_door_block": "Block A",
    "building_premises": "Warehouse 1",
    "road_street_lane": "MIDC Main Road",
    "area_locality": "Pimpri MIDC",
    "pincode": "411018",
    "email": "plant@arengineering.com",
    "phone_number": "02027410000",
    "mobile_number": "9988776655",
    "additional_contact_details": "Contact the plant manager between 9 AM and 6 PM.",
    "status": "active"
  }'
```

## 16. Site listing with customer and equipment

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/sites?per_page=15&page=1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

Each site contains `customer`, `equipments`, and `equipments_count`.

## 17. Search sites

Searches site name, address/location, pincode, and customer company name.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/sites/search?q=MIDC&per_page=15' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 18. Get site by ID

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/sites/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 19. Update site

All fields are optional during update. Send only fields that need to change.

```bash
curl --request PATCH 'http://localhost/asta-engineering/public/api/admin/sites/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "site_name": "MIDC Plant - Warehouse 2",
    "building_premises": "Warehouse 2",
    "area_locality": "Pimpri MIDC",
    "pincode": "411018",
    "email": "warehouse2@arengineering.com",
    "mobile_number": "9988776655",
    "status": "active"
  }'
```

## 20. Delete site

```bash
curl --request DELETE 'http://localhost/asta-engineering/public/api/admin/sites/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## Local OTP testing

The local `.env` has `APP_DEBUG=true`, so OTP-send responses include
`data.debug_otp`. In production, set `APP_DEBUG=false`; the OTP is then available
only through the configured mail provider. OTPs expire after 10 minutes, allow a
maximum of five failed attempts, and can be resent after 60 seconds.

## Transactional emails

Emails are triggered after successful admin signup, password login, password
reset, email-OTP login, customer creation, and site creation. Customer passwords
use the part of the email before `@` followed by `@123`; for example,
`darshank@gmail.com` receives `darshank@123`. Only the hashed password is stored.

The local project currently uses `MAIL_MAILER=log`, so messages are written to
`storage/logs/laravel.log`. Configure the `MAIL_*` values in `.env` with an SMTP
provider to deliver real emails.

---

# Equipment, Staff, Dashboard and Notification APIs

All APIs below require a valid Bearer token from any logged-in role. Records are
scoped to the logged-in account's organization. IDs in the examples (`1`) should
be replaced with IDs returned by the create/list APIs.

## 21. Add equipment and create certificate

The API always generates the PDF certificate automatically. No certificate file
is accepted or required. The certificate is attached to the site owner email,
and the create response returns its direct URL in `data.certificate_url` as well
as in `data.certificates[0].file_url`.

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/equipment' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --form 'site_id=1' \
  --form 'name=Chain Pulley Block' \
  --form 'short_name=CPB' \
  --form 'manufacturer=Demag' \
  --form 'model=DC-Pro 5' \
  --form 'serial_number=SN-12345' \
  --form 'equipment_code=EQ-001' \
  --form 'rated_capacity=5 Ton' \
  --form 'swl_capacity=4.5 Ton' \
  --form 'description=Electric chain pulley block' \
  --form 'remarks=Installed in Bay A' \
  --form 'next_inspection_at=2027-11-19' \
  --form 'certificate_number=ASTA-CERT-001' \
  --form 'certificate_title=Form 11 Equipment Certificate' \
  --form 'certificate_issued_at=2026-11-19' \
  --form 'certificate_expires_at=2027-11-19' \
  --form 'status=active'
```

## 22. Site details with equipment data

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/sites/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 23. Get equipment by ID with all certificate links

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/equipment/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 24. Update equipment

```bash
curl --request PATCH 'http://localhost/asta-engineering/public/api/admin/equipment/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "name": "Chain Pulley Block - Bay A",
    "short_name": "CPB",
    "manufacturer": "Demag",
    "model": "DC-Pro 5",
    "serial_number": "SN-12345",
    "equipment_code": "EQ-001",
    "rated_capacity": "5 Ton",
    "swl_capacity": "4.5 Ton",
    "description": "Updated equipment description",
    "remarks": "Service completed",
    "next_inspection_at": "2027-11-19",
    "status": "active"
  }'
```

## 25. Delete equipment

```bash
curl --request DELETE 'http://localhost/asta-engineering/public/api/admin/equipment/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 26. List certificates by equipment ID

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/equipment/1/certificates?per_page=15&page=1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 27. Add staff / inspector

The password is generated from the full name (letters and digits only,
lowercase) plus `@123`. `Tushar Patil` receives `tusharpatil@123`. The email
contains the email address, mobile number, and temporary password.

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/staff' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --form 'full_name=Tushar Patil' \
  --form 'email=tushar.patil@example.com' \
  --form 'mobile_number=9876543212' \
  --form 'employee_id=INS-001' \
  --form 'role=inspector' \
  --form 'status=active' \
  --form 'profile_image=@C:/full/path/profile.jpg'
```

`profile_image` is optional; remove that line when no image is available.

## 28. Search staff

Search covers name, email, mobile number, and employee/inspector ID.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/staff/search?q=Tushar&per_page=15&page=1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 29. Get staff list with filters

Both `role` and `status` are optional. Role accepts `admin` or `inspector`;
status accepts `active` or `inactive`.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/staff?role=inspector&status=active&per_page=15&page=1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 30. Get staff by ID

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/staff/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 31. Update staff and send update email

```bash
curl --request PATCH 'http://localhost/asta-engineering/public/api/admin/staff/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "full_name": "Tushar Patil",
    "email": "tushar.patil@example.com",
    "mobile_number": "9876543213",
    "employee_id": "INS-001",
    "role": "inspector",
    "status": "active"
  }'
```

To update a profile image, use POST with method spoofing so PHP receives the
multipart file correctly:

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/staff/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --form '_method=PATCH' \
  --form 'profile_image=@C:/full/path/new-profile.jpg'
```

## 32. Delete staff

```bash
curl --request DELETE 'http://localhost/asta-engineering/public/api/admin/staff/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 33. Dashboard counts and recent feed

Returns customers, sites, equipment, audited equipment, certificates, active
inspectors, renewals due within 15 days, staff users, inspections, unread
notifications, and the ten most recent action notifications.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/dashboard/counts' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 34. Get notification list

Set `unread_only=1` to return only unread notifications.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/notifications?unread_only=0&per_page=15&page=1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 35. Mark one notification as read

```bash
curl --request PATCH 'http://localhost/asta-engineering/public/api/admin/notifications/1/read' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 36. Mark all notifications as read

```bash
curl --request PATCH 'http://localhost/asta-engineering/public/api/admin/notifications/read-all' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 37. Get notification details by ID

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/notifications/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 38. Get logged-in admin profile and preferences

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/profile' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 39. Update admin profile picture and preferences

Use a locale code such as `en-US`, `en-IN`, `hi-IN`, or `mr-IN` for `language`.

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/profile' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --form '_method=PATCH' \
  --form 'profile_image=@C:/full/path/profile.jpg' \
  --form 'language=en-IN' \
  --form 'push_notifications=1' \
  --form 'biometric_lock=1'
```

## 40. Add checkpoint

Supported input types are `radio`, `text`, `number`, `dropdown`, and `date`.
The server creates the `checkpoint_code` automatically.

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/checkpoints' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "name": "Visual Inspection of Wire Ropes",
    "equipment_category": "Lifting Tools",
    "input_type": "radio",
    "is_mandatory": true,
    "requires_failure_photo": true
  }'
```

## 41. Search checkpoints

Searches checkpoint code, name, and equipment category. The same optional
filters as the list API can also be supplied.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/checkpoints/search?q=wire&per_page=15' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 42. Checkpoint list

Optional filters: `equipment_category`, `input_type`, and `is_mandatory`.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/checkpoints?equipment_category=Lifting%20Tools&input_type=radio&per_page=15&page=1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 43. Get checkpoint by ID

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/checkpoints/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 44. Update checkpoint

```bash
curl --request PATCH 'http://localhost/asta-engineering/public/api/admin/checkpoints/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "name": "Updated Wire Rope Inspection",
    "input_type": "text",
    "is_mandatory": false
  }'
```

## 45. Delete checkpoint

```bash
curl --request DELETE 'http://localhost/asta-engineering/public/api/admin/checkpoints/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 46. Logout admin

This revokes the Bearer token used for this request. Other logged-in devices
remain signed in.

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/auth/logout' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## Required one-time local setup

```bash
php artisan migrate
php artisan storage:link
```

`storage:link` makes certificate and profile-image URLs accessible under
`/public/storage`. Configure SMTP in `.env` for real email delivery; with the
default `MAIL_MAILER=log`, emails are written to `storage/logs/laravel.log`.

---

# Shared Admin, Inspector and User Mobile APIs

These endpoints use the same URL for all three roles. The Bearer token decides
which records and dashboard fields are returned. `user` means the customer
account created through the admin customer API. Do not send a role during login;
the API identifies the account from `id` and the verified password. The `id`
may be an email or mobile number, and may also be an employee ID for inspectors.

## 47. Shared login - admin

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/auth/login' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "id": "admin@astaengineering.com",
    "password": "Password123"
  }'
```

## 48. Shared login - inspector

Use the email/mobile and generated password returned when staff is created.

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/auth/login' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "id": "INS-001",
    "password": "tusharpatil@123"
  }'
```

## 49. Shared login - customer user

Use the email/mobile and generated password returned when the customer is created.

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/auth/login' \
  --header 'Accept: application/json' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "id": "info@arengineering.com",
    "password": "info@123"
  }'
```

The login response includes the detected role in both `data.role` and
`data.user.role`. The complete user record is returned in `data.user`;
`data.account` remains as a backward-compatible alias.

## 49A. Get logged-in user and detected role

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/me' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 50. Assign an inspection/site to an inspector

The response ID is the `inspection_id` used by the inspector status API.

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/admin/inspections/assign' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "inspector_id": 1,
    "site_id": 1,
    "equipment_id": 1,
    "scheduled_at": "2026-11-21 14:00:00",
    "notes": "Annual statutory inspection"
  }'
```

`equipment_id` and `notes` are optional. The inspector and site must belong to
the logged-in admin, and the equipment must belong to the supplied site.

## 51. Shared dashboard

Use an admin, inspector, or user token with this same request. Admin keeps the
existing admin counts. Inspector returns `pending_inspections`, `assigned_sites`,
`completed_today`, `upcoming_renewals`, `unread_notifications`, and
`recent_assignments`. User returns only counts for their customer account.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/admin/dashboard/counts' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

`GET /api/dashboard` is also available as a shorter alias with the same response.

## 52. Get assigned/accessible sites

For inspectors this returns only sites having an inspection assigned to that
inspector. Admin gets all owned sites; customer users get their own sites.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/sites/assigned?q=MIDC&per_page=15&page=1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

Remove `q=MIDC&` to list without a search term. Each item includes customer,
location, contact fields, equipment count, and pending inspection count.

## 53. Get role-scoped site details by ID

The response contains site/customer/contact data, equipment with certificates,
pending count, and the inspections visible to the logged-in role. Inaccessible
site IDs return 404.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/sites/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 54. Update assigned inspection status (inspector only)

Allowed statuses are `in_progress` and `completed`. Completing an inspection
automatically sets `completed_at`.

```bash
curl --request PATCH 'http://localhost/asta-engineering/public/api/inspections/1/status' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_INSPECTOR_BEARER_TOKEN_HERE' \
  --header 'Content-Type: application/json' \
  --data-raw '{
    "status": "completed",
    "notes": "Inspection completed successfully"
  }'
```

## 55. Shared notification list

The same endpoint returns only notifications addressed to the logged-in admin,
inspector, or customer user.

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/notifications?unread_only=0&per_page=15&page=1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 56. Get one notification

```bash
curl --request GET 'http://localhost/asta-engineering/public/api/notifications/1' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 57. Mark one notification as read

```bash
curl --request PATCH 'http://localhost/asta-engineering/public/api/notifications/1/read' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 58. Mark all notifications as read

```bash
curl --request PATCH 'http://localhost/asta-engineering/public/api/notifications/read-all' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```

## 59. Shared logout

```bash
curl --request POST 'http://localhost/asta-engineering/public/api/auth/logout' \
  --header 'Accept: application/json' \
  --header 'Authorization: Bearer PASTE_BEARER_TOKEN_HERE'
```
