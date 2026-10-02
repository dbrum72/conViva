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

Frontend

frontend/src/components
frontend/src/views
frontend/src/state
frontend/src/services

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

All API requests must be implemented in:

frontend/src/services

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
