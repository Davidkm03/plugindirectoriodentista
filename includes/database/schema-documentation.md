# Dental Directory System Database Schema

This document outlines the database schema for the Dental Directory System WordPress plugin.

## Database Tables Overview

The plugin creates the following custom tables in the WordPress database:

1. **wp_dental_chat_messages** - Stores chat messages between dentists and patients
2. **wp_dental_conversations** - Tracks conversations between dentists and patients
3. **wp_dental_subscriptions** - Manages dentist subscription plans and status
4. **wp_dental_subscription_payments** - Records payment history for subscriptions
5. **wp_dental_reviews** - Stores reviews for dentists written by patients
6. **wp_dental_review_votes** - Tracks helpful/unhelpful votes on reviews
7. **wp_dental_message_counters** - Tracks monthly message count for dentists (critical for free plan limit)
8. **wp_dental_profiles** - Stores detailed dentist profiles for the directory
9. **wp_dental_favorites** - Tracks which dentists patients have favorited

## Table Structures

### wp_dental_chat_messages

Stores individual chat messages between patients and dentists.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| sender_id | bigint(20) | User ID of message sender |
| receiver_id | bigint(20) | User ID of message recipient |
| message | text | Message content |
| status | varchar(20) | Message status: 'sent', 'delivered', 'read' |
| is_read | tinyint(1) | Whether message has been read (0/1) |
| attachment_url | varchar(255) | Optional file attachment URL |
| attachment_type | varchar(50) | Type of attachment if present |
| date_created | datetime | When message was sent |
| date_read | datetime | When message was read (if applicable) |
| conversation_id | bigint(20) | ID of conversation this message belongs to |

**Indexes:**
- Primary key on `id`
- Keys on `sender_id`, `receiver_id`, `status`, `is_read`, `conversation_id`, `date_created`

### wp_dental_conversations

Groups messages into conversations between a dentist and patient.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| dentist_id | bigint(20) | User ID of dentist |
| patient_id | bigint(20) | User ID of patient |
| status | varchar(20) | Conversation status: 'active', 'archived', 'blocked' |
| last_message_id | bigint(20) | ID of most recent message |
| date_created | datetime | When conversation started |
| date_modified | datetime | When conversation was last updated |
| patient_archived | tinyint(1) | Whether patient has archived conversation |
| dentist_archived | tinyint(1) | Whether dentist has archived conversation |

**Indexes:**
- Primary key on `id`
- Unique key on dentist-patient pair
- Keys on `dentist_id`, `patient_id`, `status`, `date_modified`

### wp_dental_subscriptions

Tracks dentist subscription plans and status.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| user_id | bigint(20) | User ID of dentist |
| plan_id | varchar(50) | Subscription plan identifier |
| plan_name | varchar(50) | Plan name ('free', 'premium') |
| amount | decimal(10,2) | Subscription amount |
| currency | varchar(3) | Currency code |
| interval | varchar(20) | Billing interval ('month', 'year') |
| payment_processor | varchar(20) | Payment processor used |
| processor_subscription_id | varchar(100) | Subscription ID from payment processor |
| processor_customer_id | varchar(100) | Customer ID from payment processor |
| status | varchar(20) | Status: 'active', 'cancelled', 'expired' |
| date_start | datetime | When subscription started |
| date_expiry | datetime | When subscription expires (if applicable) |
| date_cancelled | datetime | When subscription was cancelled (if applicable) |
| cancel_reason | text | Reason for cancellation (if applicable) |
| date_created | datetime | When record was created |
| date_modified | datetime | When record was last modified |
| payment_method | varchar(50) | Payment method used |
| payment_method_details | text | Additional payment method details |

**Indexes:**
- Primary key on `id`
- Unique key on `user_id` and `status` to ensure only one active subscription per user
- Keys on `user_id`, `status`, `plan_id`, `date_expiry`, `processor_subscription_id`

### wp_dental_subscription_payments

Stores individual payment transactions for subscriptions.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| subscription_id | bigint(20) | ID of associated subscription |
| user_id | bigint(20) | User ID of dentist |
| amount | decimal(10,2) | Payment amount |
| currency | varchar(3) | Currency code |
| status | varchar(20) | Payment status: 'completed', 'failed', 'refunded' |
| transaction_id | varchar(100) | Payment processor transaction ID |
| processor_fee | decimal(10,2) | Fee charged by payment processor |
| payment_method | varchar(50) | Payment method used |
| payment_details | text | Additional payment details |
| date_created | datetime | When payment was processed |

**Indexes:**
- Primary key on `id`
- Keys on `subscription_id`, `user_id`, `status`, `transaction_id`, `date_created`

### wp_dental_reviews

Stores reviews written by patients for dentists.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| dentist_id | bigint(20) | User ID of dentist being reviewed |
| author_id | bigint(20) | User ID of patient writing review |
| rating | tinyint | Rating (1-5) |
| review_text | text | Review content |
| dentist_response | text | Optional response from dentist |
| status | varchar(20) | Status: 'published', 'pending', 'rejected' |
| is_featured | tinyint(1) | Whether review is featured |
| is_verified | tinyint(1) | Whether reviewer is verified patient |
| date_created | datetime | When review was submitted |
| date_modified | datetime | When review was last modified |
| date_response | datetime | When dentist responded (if applicable) |
| helpful_count | int | Number of users who found review helpful |
| unhelpful_count | int | Number of users who found review unhelpful |

**Indexes:**
- Primary key on `id`
- Keys on `dentist_id`, `author_id`, `rating`, `status`, `is_featured`, `is_verified`, `date_created`

### wp_dental_review_votes

Tracks votes on review helpfulness.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| review_id | bigint(20) | ID of review being voted on |
| user_id | bigint(20) | User ID of voter |
| vote | tinyint | Vote type: 1 (helpful) or -1 (unhelpful) |
| date_created | datetime | When vote was cast |

**Indexes:**
- Primary key on `id`
- Unique key on review-user pair to prevent duplicate votes
- Keys on `review_id`, `user_id`, `vote`

### wp_dental_message_counters

Tracks monthly message counts for free tier message limit enforcement.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| dentist_id | bigint(20) | User ID of dentist |
| month | int | Month (1-12) |
| year | int | 4-digit year |
| message_count | int | Count of messages received this month |
| date_reset | datetime | When counter was last reset |
| date_created | datetime | When counter was created |
| date_modified | datetime | When counter was last updated |

**Indexes:**
- Primary key on `id`
- Unique key on dentist-month-year combination
- Keys on `dentist_id`, combined key on `month` and `year`

### wp_dental_profiles

Stores detailed dentist profile information.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| user_id | bigint(20) | User ID of dentist |
| speciality | varchar(100) | Dental speciality |
| license | varchar(50) | Professional license number |
| clinic_name | varchar(100) | Name of dental practice/clinic |
| address | text | Full formatted address |
| address_line_1 | varchar(255) | Address line 1 |
| address_line_2 | varchar(255) | Address line 2 |
| city | varchar(100) | City |
| state | varchar(100) | State/province |
| postal_code | varchar(20) | ZIP/postal code |
| country | varchar(2) | Country code |
| latitude | decimal(10,8) | Latitude for mapping |
| longitude | decimal(11,8) | Longitude for mapping |
| phone | varchar(30) | Contact phone number |
| website | varchar(255) | Practice website URL |
| working_hours | text | Working hours (JSON format) |
| education | text | Education history |
| experience | text | Professional experience |
| bio | text | Biographical information |
| services | text | Services offered (JSON format) |
| languages | varchar(255) | Languages spoken |
| profile_photo | varchar(255) | Profile photo URL |
| cover_photo | varchar(255) | Cover photo URL |
| gallery | text | Photo gallery (JSON format) |
| social_facebook | varchar(255) | Facebook profile URL |
| social_twitter | varchar(255) | Twitter profile URL |
| social_instagram | varchar(255) | Instagram profile URL |
| social_linkedin | varchar(255) | LinkedIn profile URL |
| featured | tinyint(1) | Whether profile is featured in directory |
| visibility | varchar(20) | Profile visibility: 'public', 'private', 'hidden' |
| profile_status | varchar(20) | Status: 'active', 'inactive', 'suspended' |
| rating_average | decimal(3,2) | Average review rating |
| rating_count | int | Number of reviews |
| views_count | int | Profile view count |
| last_active | datetime | When dentist was last active |
| date_created | datetime | When profile was created |
| date_modified | datetime | When profile was last updated |

**Indexes:**
- Primary key on `id`
- Unique key on `user_id`
- Keys on `speciality`, `city`, `state`, `country`, `featured`, `visibility`, `profile_status`, `rating_average`

### wp_dental_favorites

Tracks patients' favorite dentists.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| patient_id | bigint(20) | User ID of patient |
| dentist_id | bigint(20) | User ID of dentist |
| date_created | datetime | When favorite was added |

**Indexes:**
- Primary key on `id`
- Unique key on patient-dentist pair to prevent duplicates
- Keys on `patient_id`, `dentist_id`, `date_created`

## Migration and Versioning

The schema uses a migration-based version system:
- Current schema version is stored in the WordPress options table as `dental_db_version`
- Migration scripts handle schema updates in a backwards-compatible way
- Rollback functionality is provided for disaster recovery

## Performance Considerations

This schema has been optimized for performance with:
1. Appropriate indexes on frequently queried columns
2. Normalized structure to minimize data redundancy
3. Separation of concerns across tables
4. Optimized field types for storage efficiency

## Security Measures

1. All database operations use prepared statements via `$wpdb->prepare()`
2. Input sanitization is performed before any insert/update operations
3. User permissions are checked before database operations
4. Foreign keys maintain data integrity
