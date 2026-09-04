# Housing Offers API

REST API for asynchronous importing, searching and reserving housing offers from external suppliers.

## Requirements

* PHP 8.2+
* Laravel 11+
* MySQL 8+
* Redis
* Composer

## Installation

Install dependencies:

```bash
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application encryption key:

```bash
php artisan key:generate
```

Configure the database and Redis connection in `.env`.

Run migrations and seed the suppliers:

```bash
php artisan migrate --seed
```

The seeder creates the following suppliers:

* `supplier-a`
* `supplier-b`

## Configuration

The application uses Redis for queues.

Relevant `.env` settings:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=accommodation
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

For local development, debug bar and automatic Swagger generation can be enabled:

```env
DEBUGBAR_ENABLED=true
L5_SWAGGER_GENERATE_ALWAYS=true
```

Do not commit `.env` or any credentials/secrets to the repository.

## Queue Worker

Import processing is asynchronous and is performed by a queued job.

Start the queue worker:

```bash
php artisan queue:work
```

The `POST /api/imports` endpoint only creates the import and dispatches the processing job. The HTTP request does not process the offers synchronously.

The import status can be monitored through:

```http
GET /api/imports/{import}
```

### Restarting Queue Workers After Code Changes

When using a long-running queue worker, changes to application code are not automatically picked up by the already running worker process.

After modifying code used by queued jobs, restart the queue worker before testing the changes:

```bash
php artisan queue:restart
```

For a manually started local worker, stop it and start it again:

```bash
Ctrl+C
php artisan queue:work
```

This is especially important when testing import processing, because the worker may otherwise execute an older version of the job code.

> Note: Redis itself does not need to be restarted. Only the Laravel queue worker needs to be restarted.

## API

### 1. Create Import

```http
POST /api/imports
Content-Type: application/json
```

Example:

```json
{
    "supplier": "supplier-a",
    "external_import_id": "supplier-a-import-001",
    "sent_at": "2026-09-04T08:00:00Z",
    "offers": [
        {
            "external_id": "supplier-a-offer-001",
            "property": {
                "code": "hotel-001",
                "name": "Example Hotel",
                "city": "Kyiv"
            },
            "check_in": "2026-09-10",
            "check_out": "2026-09-12",
            "max_guests": 2,
            "price": 150.00,
            "currency": "USD",
            "available_units": 3,
            "expires_at": "2026-09-09T23:59:59Z"
        }
    ]
}
```

Successful response:

```http
202 Accepted
```

```json
{
    "data": {
        "id": 1,
        "status": "pending"
    }
}
```

The import is processed asynchronously by the queue worker.

### 2. Get Import Status

```http
GET /api/imports/{import}
```

Example response:

```json
{
    "data": {
        "id": 1,
        "supplier": "supplier-a",
        "external_import_id": "supplier-a-import-001",
        "sent_at": "2026-09-04T08:00:00.000000Z",
        "status": "completed",
        "total_offers": 1,
        "processed_offers": 1,
        "error": null,
        "created_at": "2026-09-04T08:00:01.000000Z",
        "completed_at": "2026-09-04T08:00:02.000000Z"
    }
}
```

Possible import statuses:

* `pending`
* `processing`
* `completed`
* `failed`

### 3. Search Properties

```http
GET /api/properties
```

Required query parameters:

* `check_in`
* `check_out`
* `guests`
* `currency`

Optional:

* `city`
* `page`

Example:

```http
GET /api/properties?check_in=2026-09-10&check_out=2026-09-12&guests=2&currency=USD&city=Kyiv
```

An offer is considered current when:

* `check_in` exactly matches the requested date;
* `check_out` exactly matches the requested date;
* `max_guests >= guests`;
* `available_units > 0`;
* `expires_at > now()`;
* currency matches the requested currency;
* city matches when `city` is specified.

For every property only the cheapest matching offer is returned.

The cheapest offer is selected at the database level using a window function rather than loading all offers and grouping them in PHP.

Results are sorted by the best offer price and paginated by the database.

Example response:

```json
{
    "data": [
        {
            "id": 1,
            "code": "hotel-001",
            "name": "Example Hotel",
            "city": "Kyiv",
            "best_offer": {
                "id": 10,
                "check_in": "2026-09-10",
                "check_out": "2026-09-12",
                "max_guests": 2,
                "price": "150.00",
                "currency": "USD",
                "available_units": 3,
                "expires_at": "2026-09-09T23:59:59.000000Z"
            }
        }
    ],
    "links": {
        "first": "...",
        "last": "...",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 15,
        "to": 1,
        "total": 1
    }
}
```

#### Currency

The task specification allows offers from suppliers to contain different currencies but does not define exchange rates or a target currency.

Therefore the search API requires a `currency` parameter and compares offers only within the requested currency. No implicit currency conversion is performed.

This keeps price comparison deterministic and avoids introducing an undocumented exchange-rate source.

### 4. Reserve an Offer

```http
POST /api/offers/{offer}/reservations
Content-Type: application/json
```

Request:

```json
{
    "client_reference": "booking-001",
    "customer_name": "John Doe",
    "customer_email": "john@example.com"
}
```

Successful response:

```http
201 Created
```

```json
{
    "data": {
        "id": 1,
        "offer_id": 10,
        "client_reference": "booking-001",
        "customer_name": "John Doe",
        "customer_email": "john@example.com",
        "created_at": "2026-09-04T08:10:00.000000Z"
    }
}
```

If the offer has no available units:

```http
409 Conflict
```

```json
{
    "message": "Offer is no longer available."
}
```

## Import Idempotency

Imports are identified by:

```text
supplier + external_import_id
```

A unique database constraint prevents the same import from being created more than once.

If the same import is submitted again, the existing import is returned and a new processing job is not dispatched.

Offers are identified by:

```text
supplier + external_id
```

This combination is also protected by a unique database constraint.

When an offer with the same supplier and external ID is received in another import, the existing offer is updated rather than duplicated.

Property records are identified by their unique supplier-independent property code:

```text
property.code
```

The import job uses `updateOrCreate` for properties and `upsert` for offers.

Offer processing is performed in chunks and each chunk is processed inside a database transaction.

## Concurrent Reservations

Reservation of an offer is performed inside a database transaction.

The offer row is locked using:

```php
lockForUpdate()
```

while checking and decrementing `available_units`.

The relevant operation is effectively:

```text
BEGIN
    lock offer row
    check available_units
    decrement available_units
    create reservation
COMMIT
```

This prevents two concurrent transactions from successfully reserving the same last available unit.

If another transaction has already consumed the last unit, the subsequent reservation receives `409 Conflict`.

A full parallel-process integration test is not included because the task specification explicitly states that such a test is not required. The implementation relies on the database row lock and transaction to provide the required concurrency protection.

## Database Structure

Main entities:

* `suppliers`
* `imports`
* `properties`
* `offers`
* `reservations`

Important constraints:

```text
suppliers.code
    UNIQUE

imports.(supplier_id, external_import_id)
    UNIQUE

properties.code
    UNIQUE

offers.(supplier_id, external_id)
    UNIQUE
```

Foreign keys are used for relationships between the entities.

## Factories and Seeders

Factories are provided for:

* Supplier
* Import
* Property
* Offer
* Reservation

The database seeder creates the required suppliers:

```text
supplier-a
supplier-b
```

Run:

```bash
php artisan migrate --seed
```

## Tests

Run the complete test suite:

```bash
php artisan test
```

The feature tests cover, among other things:

* property search;
* cheapest matching offer selection;
* search criteria filtering;
* city filtering and ordering;
* successful reservation;
* reservation of an unavailable offer;
* import-related functionality.

## Swagger / OpenAPI

API documentation is provided through L5-Swagger.

Generate the documentation:

```bash
php artisan l5-swagger:generate
```

The Swagger UI is available at:

```text
/api/documentation
```

The OpenAPI specification is generated in JSON/YAML format.

The documented endpoints are:

```text
POST /api/imports
GET  /api/imports/{import}
GET  /api/properties
POST /api/offers/{offer}/reservations
```

## Implementation Notes

The implementation intentionally keeps the application structure close to standard Laravel:

* Form Requests handle input validation;
* API Resources handle response formatting;
* Eloquent models define relationships and persistence;
* queued Jobs handle asynchronous import processing;
* database transactions are used where atomicity is required;
* database constraints provide idempotency guarantees;
* the property search performs filtering, cheapest-offer selection, ordering and pagination at the database level.

No additional Service/Repository/DDD layers were introduced because they would add abstraction without providing meaningful value for the scope of this task.
