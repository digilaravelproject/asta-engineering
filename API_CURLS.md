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
