# AGENTS.md

Guidelines for AI coding agents working in this repository.

---

# Project Stack

Backend

* Laravel
* REST API
* MySQL
* Eloquent ORM

Frontend

* Vue.js
* Pinia (shared state)
* Vite (build tool)
* Axios for API requests

---

# Project Structure

Backend (Laravel)

app/Models
app/Http/Controllers
routes/api.php
database/migrations

Authentication lives in `backend/app/Modules/Auth`, with controllers, requests,
middleware, services and its service provider. API routes are composed in
`backend/routes/api.php`.

Frontend

frontend/src/components
frontend/src/views
frontend/src/state
frontend/src/services

Authentication lives in `frontend/src/modules/auth`. Its public entry point
exports the Pinia store, route definitions and guard. Its HTTP requests and
token storage live in the module.

Groups live in `frontend/src/modules/groups` and `backend/app/Modules/Groups`.
The module owns group selection and context, members, roles and invitations.
The Auth store exposes the existing group context interface to callers.

Notifications live in `frontend/src/modules/notifications` and
`backend/app/Modules/Notifications`. The module owns the inbox, unread count,
reminder preferences and reminder delivery.

Care lives in `frontend/src/modules/care` and `backend/app/Modules/Care`.
It owns recipients, care records, their authorization, decisions, occurrence
generation and execution, availability, handover and documents. Import Care's
routes, stores and shared data through its frontend entry point and shared
components through `ui.js`. API routes are composed in
`backend/routes/api.php`. Eloquent models and the queued occurrence job keep
their application namespaces.

Agenda lives in `frontend/src/modules/agenda` and owns the calendar interface
and its query state. Care provides the occurrence engine and writes.
Expenses lives in `frontend/src/modules/expenses` and
`backend/app/Modules/Expenses`. It owns the financial query, payments and
receipts. Expense proposals and approvals use Care's record workflow.

Use `frontend/src/modules/http` for Axios clients. Import the authenticated or
public client from its public entry point. Keep endpoint definitions in the
domain service that consumes the client.

---

# Development Commands

Backend

composer install
php artisan serve

Frontend

npm install
npm run dev

---

# Database

Run migrations:

php artisan migrate

Rules:

* During this prototype phase, edit existing creation migrations directly for schema changes. Do not add incremental migrations for fields.
* Consolidate and remove superseded incremental migrations when requested.
* The user considers conviva_db disposable and authorizes rebuilding it when needed. Never rebuild or change any legalis database.

---

# Architecture Rules

Backend (Laravel)

Responsible for:

* business logic
* database operations
* validation
* exposing REST API endpoints

Frontend (Vue)

Responsible for:

* UI rendering
* user interaction
* consuming backend APIs

Important:

Business rules must remain in Laravel.

Vue components must not contain domain or business logic.

---

# Data Flow

Typical request flow in this project:

Vue Component
→ Pinia Store Action
→ Service (Axios request)
→ Laravel API Controller
→ Model / Database

Responses return in JSON.

Agents must respect this flow when implementing new features.

---

# Coding Conventions

Laravel

* Follow PSR-12
* Use Eloquent ORM
* Controllers must remain thin
* Use FormRequest for validation when appropriate
* Business logic should remain outside views

Naming conventions

Controllers
SomethingController

Models
Singular names (User, Product, Dependent)

Tables
Plural names (users, products, dependents)

---

Vue

* Sempre utilizar componentes ao invés de HTML nativo.
* Components must be small and reusable
* Avoid business logic inside components
* Use Pinia for shared or global state

Naming conventions

Components
PascalCase

Events
kebab-case

Props
camelCase

---

# API Communication

Implement API requests in `frontend/src/services` or in the owning module's
`api` directory.

Use axios for HTTP requests.

Vue components must not call the API directly.

---

# Project Conventions

State Management

* Global state must use Pinia
* Shared data must not be stored only in components

API Calls

* Must be centralized in services

Code Changes

Agents should:

* follow existing project patterns
* prefer minimal and safe changes
* reuse existing architecture

Agents must NOT:

* modify `.env`
* delete migrations except superseded migrations consolidated under the prototype policy above
* introduce new frameworks
* change project architecture

---

# Typical Development Tasks

When implementing a new feature, agents should follow this order:

1. Update the existing creation migration if database change is needed during the prototype phase
2. Create or update Model
3. Implement Controller logic
4. Create or update API route
5. Create Vue service method
6. Create or update Pinia store action
7. Update Vue component

Agents should ensure the application still builds after modifications.
