# FrankenBike Backend

A Symfony 7 (PHP 8.3) backend powering a custom bike project sharing application.  
Provides user accounts, JWT (cookie-based) authentication, projects, components, likes, comments, timelines and profiles.

## Tech Stack

- PHP 8.3 / Symfony 7
- Doctrine ORM / Migrations
- MySQL 8
- JWT (Lexik JWT Authentication Bundle) – cookie transport
- API Platform (installed, partially used)
- Nelmio CORS
- Monolog
- Docker (runtime + MySQL)
- PHPUnit / BrowserKit / Foundry (test scaffolding)

## Main Features

| Domain | Description |
|--------|-------------|
| Users / Profiles | Registration + profile (name, birthdate, avatar, etc.) |
| Auth | Email/password login, JWT stored in `AUTH_TOKEN_COOKIE`, logout, optional remember-me (30d) |
| Projects | CRUD, owned by a user, image + metadata |
| Components | Enum-based category + origin, attached to a project |
| Timeline | Aggregates a user’s projects + components |
| Likes / Comments | Entities present; endpoints to extend |
| Security | Role system (`user`, `admin`) mapped to `ROLE_USER`, `ROLE_ADMIN` |

## Directory Overview

```
src/
  Controller/        # HTTP controllers
  Entity/            # Doctrine entities
  Enum/              # Domain enums
  Repository/        # Doctrine repositories
  Security/          # Token extractor (cookie)
config/
  packages/          # Bundle & framework config
  routes/            # Attribute-based + extra routing
tests/
  Unit/              # Unit tests
  Integration/       # HTTP + DB interaction
docker/              # PHP runtime Dockerfile
```

## Core Domain Model

- [`App\Entity\User`](src/Entity/User.php) ↔ OneToOne [`App\Entity\Profile`](src/Entity/Profile.php)  
- [`App\Entity\User`](src/Entity/User.php) ↔ OneToMany [`App\Entity\Project`](src/Entity/Project.php)  
- [`App\Entity\Project`](src/Entity/Project.php) ↔ OneToMany [`App\Entity\Component`](src/Entity/Component.php)  
- [`App\Entity\Project`](src/Entity/Project.php) ↔ OneToMany [`App\Entity\Comment`](src/Entity/Comment.php)  
- [`App\Entity\Project`](src/Entity/Project.php) ↔ OneToMany [`App\Entity\Like`](src/Entity/Like.php)  
- Enums:  
  - [`App\Enum\ComponentCategory`](src/Enum/ComponentCategory.php)  
  - [`App\Enum\ComponentOrigin`](src/Enum/ComponentOrigin.php)  
  - [`App\Enum\UserRole`](src/Enum/UserRole.php)

## Authentication & Security

- Login: [`AuthController::login`](src/Controller/AuthController.php)  
  - POST `/api/login` with JSON: `{ "email": "", "password": "", "rememberMe": true|false }`
  - On success: Sets `AUTH_TOKEN_COOKIE` (HTTP-only, Secure, SameSite=Lax).
- Protected routes: All `/api/**` except:
  - `/api/login`, `/api/ping`, `POST /api/users`, `/api/logout`
- Cookie extraction configured in  [`lexik_jwt_authentication.yaml`](config/packages/lexik_jwt_authentication.yaml).
- Access control in [`security.yaml`](config/packages/security.yaml).
- Roles derived from `User::getRole()` → `ROLE_<UPPER>`.

## API Endpoints (Current)

| Method | Path | Description | Auth |
|--------|------|-------------|------|
| POST | `/api/users` | Register user (+profile) | Public |
| GET | `/api/users` | List users | JWT |
| GET | `/api/users/{id}` | Get user | JWT |
| DELETE | `/api/users/{id}` | Delete user | JWT |
| POST | `/api/login` | Login, sets JWT cookie | Public |
| POST | `/api/logout` | Expire auth cookie | Public |
| GET | `/api/ping` | Health check | Public |
| GET | `/api/me` | (Returns projects of current user) | JWT |
| POST | `/api/projects` | Create project | JWT |
| GET | `/api/projects` | Paginated list | JWT |
| GET | `/api/projects/{id}` | Get project | JWT |
| PUT/PATCH | `/api/projects/{id}` | Update project | JWT (owner) |
| DELETE | `/api/projects/{id}` | Delete | JWT (owner) |
| POST | `/api/projects/{projectId}/components` | Add component | JWT (owner) |
| GET | `/api/projects/{projectId}/components` | List components | JWT (owner) |
| GET | `/api/components/{id}` | Get component | JWT (owner) |
| PUT/PATCH | `/api/components/{id}` | Update component | JWT (owner) |
| DELETE | `/api/components/{id}` | Delete component | JWT (owner) |
| GET | `/api/timelines/{userId}` | User timeline (self only) | JWT |
| GET | `/api/profiles` | List profiles | JWT |
| GET | `/api/profiles/user/{userId}` | Get profile | JWT |
| PUT | `/api/profiles/user/{userId}` | Update profile | JWT (needs ownership validation improvement) |

(Additional Like / Comment endpoints can be added.)

## Example Login Flow (cURL)

```bash
curl -i -X POST http://localhost:8787/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret","rememberMe":true}'
```

Subsequent requests automatically include the auth cookie.

## Pagination (Projects)

`GET /api/projects?page=1&limit=10` returns:
```json
{
  "data": [ ... ],
  "page": 1,
  "limit": 10,
  "total": 42,
  "hasMore": true
}
```

## Local Development

### Prerequisites
- Docker & Docker Compose
- Make sure ports `3306` (MySQL) & `8787` (PHP built-in server) are free.

### Start Stack
```bash
docker compose up --build
```

PHP server: http://localhost:8787

### Install Dependencies (inside container)
(Handled automatically on first start if `vendor/` absent.)

Manual:
```bash
docker exec -it fkb-php bash
composer install
```

### Generate JWT Keys (if missing)
```bash
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
# Update JWT_PASSPHRASE in .env.dev
```

### Database & Migrations
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Test Environment
`.env.test` sets base DB name; Symfony appends `_test`.  
Run tests:
```bash
php bin/phpunit
```

## Testing

Current tests:
- Unit: [`tests/Unit/Entity/UserTest.php`](tests/Unit/Entity/UserTest.php)
- Integration: Auth workflow in [`tests/Integration/Controller/AuthControllerTest.php`](tests/Integration/Controller/AuthControllerTest.php)

Add more (projects/components ownership, timeline).

## Serialization

- Entities use Symfony Serializer groups (e.g. `project:read`, `user:read`, `component:read`).
- Circular relation risk is mitigated by selective group usage.

## CORS

Configured in [`nelmio_cors.yaml`](config/packages/nelmio_cors.yaml) to allow `http://localhost:3000` with credentials for `/api/*`.

## Known Issues / Improvements

| Area | Issue |
|------|-------|
| Component Ownership | In [`ComponentController`](src/Controller/ComponentController.php) some checks use `$component->getUser()` (method does not exist). Should reference `$component->getProject()->getUser()`. |
| PUT/PATCH Component | `setUpdatedAt()` called but `Component` entity has no `updatedAt` field. Remove call or add field. |
| Role Consistency | `User::getRole()` returns raw value (`user`, `admin`); ensure UI expects transformed `ROLE_*`. |
| Missing Validation | No request DTO / validation constraints yet. |
| Profile Update Authorization | Should verify current user matches target user ID or has admin role. |
| Comment / Like Endpoints | Not yet exposed. |
| Error Handling | Some controllers return raw exception messages (could leak info). |
| Password Policy | No validation rules enforced. |
| API Platform | Installed but not fully leveraged (could auto-expose resources). |

## Extending

Suggested next steps:
1. Add Like + Comment CRUD endpoints.
2. Introduce DTO + `symfony/validator`.
3. Add refresh token or short-lived access + long-lived refresh strategy.
4. Add OpenAPI/Swagger (API Platform auto-doc).
5. Add rate limiting (Symfony RateLimiter component).
6. Add CI (GitHub Actions) for tests + coding standards.

## Environment Variables (Sample)

```
APP_ENV=dev
APP_SECRET=...
DATABASE_URL="mysql://root:root@mysql:3306/fkb_db"
CORS_ALLOW_ORIGIN="http://localhost:3000"
JWT_PASSPHRASE=your_passphrase
```

## Deployment Notes

- Use real web server (NGINX/Apache + PHP-FPM) instead of `php -S`.
- Serve over HTTPS (cookies are `Secure`).
- Run `composer install --no-dev --optimize-autoloader`.
- Warmup cache: `php bin/console cache:clear --env=prod`.

## License

Specify a license (e.g. MIT) before publishing.

## Contact / Contributing

Open issues or submit PRs with:
- Problem statement
- Proposed change
- Tests

---
Happy hacking.