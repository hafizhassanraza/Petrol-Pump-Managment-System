# AGENTS.md

## Cursor Cloud specific instructions

This is a **Fuel Station Management System** built with Laravel 12, using SQLite, Blade templates with Alpine.js, and Tailwind CSS (built via Vite).

### Services

| Service | Command | Port |
|---------|---------|------|
| Laravel dev server | `php artisan serve --host=0.0.0.0 --port=8000` | 8000 |
| Vite HMR dev server | `npm run dev` | 5173 |
| Queue worker (optional) | `php artisan queue:listen` | — |

All services can be started together with `composer dev` (uses `concurrently`).

### Key commands

- **Lint:** `./vendor/bin/pint --test` (check) or `./vendor/bin/pint` (auto-fix)
- **Tests:** `php artisan test` (uses in-memory SQLite via phpunit.xml)
- **Build frontend:** `npm run build`
- **Setup from scratch:** `composer setup` (installs deps, copies .env, generates key, migrates, builds frontend)

### Non-obvious notes

- The `users.role` column is NOT NULL with no default. The `UserFactory` does not set it, causing all Feature tests that create users to fail. If writing tests, always include `'role' => 'owner'` (or `'manager'`) in User factory states.
- The registration controller (`RegisteredUserController@store`) does not assign a role, so registration via the form will fail at the DB level. Use Tinker or seed a user directly for testing: `php artisan tinker --execute="App\Models\User::create(['name'=>'Test','email'=>'test@example.com','password'=>bcrypt('password'),'role'=>'owner']);"`.
- The DB is SQLite at `database/database.sqlite`. If the file doesn't exist, create it with `touch database/database.sqlite` before migrating.
- All cache, session, and queue drivers default to `database` (SQLite). No Redis or external services are required.
- PHP 8.2+ with extensions `sqlite3`, `mbstring`, `xml`, `curl`, `zip`, `dom`, `bcmath` must be available on the system.
