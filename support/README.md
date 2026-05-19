# YGXONE Support

A customer support portal for the YGXONE ecosystem — ticketing, knowledge base, and FAQ, with centralized management through the Master admin panel.

## Features

- **📬 Ticketing System** — Create, track, and manage support tickets with threaded messaging
- **📚 Knowledge Base** — Browse articles by category (Getting Started, Mail, DocX, Xcel, Billing, etc.)
- **❓ FAQ Section** — Expandable accordion of frequently asked questions
- **🔍 Article Search** — Full-text search across knowledge base articles
- **🔐 SSO Integration** — Seamless login via YG Account with cross-subdomain session sharing
- **📧 Email Notifications** — Ticket created and reply confirmations sent via email
- **📊 Master Admin Control** — All entities (tickets, articles, users) manageable through `master.ygxone.com/admin`

## Architecture

The support module shares the `ygmarket_account` MySQL database with the rest of the YGXONE ecosystem. It uses:

- **Laravel 12** with Sanctum for API token authentication
- **Tailwind CSS** + Alpine.js for the dark-themed UI
- **Filament** (via the Master module) for admin CRUD operations
- **Guzzle** for SSO token validation against the Account service

### Database Tables

| Table | Purpose | Managed By |
|-------|---------|------------|
| `users` | Shared user accounts (across all modules) | Account module |
| `tickets` | Support tickets with JSON message threads | Support module |
| `articles` | Knowledge base articles & FAQs | Support module |

## Setup

### Prerequisites

- PHP 8.2+
- Composer
- MySQL database (shared: `ygmarket_account`)
- Access to YG Account SSO service

### Installation

```bash
# 1. Navigate to the support module
cd support

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Edit .env with your database credentials
#    DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. Run migrations
php artisan migrate

# 6. (Optional) Seed knowledge base articles
php artisan db:seed --class=Database\\Seeders\\KnowledgeBaseSeeder
```

### Web Server Configuration

Point your web server to `support/public/` and ensure URL rewriting is enabled.

#### Apache (provided)

The `.htaccess` in `public/` handles URL rewriting and security headers automatically.

#### Nginx

```nginx
server {
    listen 80;
    server_name support.ygxone.com;
    root /path/to/support/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Deployment

### cPanel (automated)

```bash
# From your local machine:
cd support
bash deploy.sh
```

The `deploy.sh` script will:
1. Install production Composer dependencies
2. Cache config, routes, and views
3. Rsync files to the cPanel server
4. Set proper permissions
5. Run pending migrations

### Manual deployment

1. Upload all files to `support.ygxone.com/` on your server
2. Set `storage/` and `bootstrap/cache/` to 775
3. Run `php artisan migrate --force`
4. Configure the cron job for task scheduling:
   ```
   * * * * * cd /path-to-support && php artisan schedule:run >> /dev/null 2>&1
   ```

## Administration

Support entities are managed through the **Master Admin Panel** at `master.ygxone.com/admin`:

| Resource | Description |
|----------|-------------|
| **Tickets** | View all tickets, filter by status/priority/category, reply as agent, close/re-open |
| **Articles** | Full CRUD for knowledge base articles and FAQs, manage categories, toggle publish state |
| **Users** | Read-only view of support users with ticket counts |

## Email Configuration

Ticket notifications are sent via the configured mail driver (default: `log` for development, `smtp` for production):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.ygxone.com
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=support@ygxone.com
MAIL_FROM_NAME="YG Support"
```

## API Endpoints

| Method | URI | Auth | Description |
|--------|-----|------|-------------|
| GET | `/login` | Guest | Login page |
| GET | `/auth/sso/redirect` | Guest | Redirect to YG Account SSO |
| GET | `/auth/sso/callback` | Guest | SSO callback handler |
| POST | `/logout` | Auth | Logout |
| GET | `/` | Auth | Dashboard |
| GET | `/tickets` | Auth | List tickets |
| GET | `/tickets/create` | Auth | Create ticket form |
| POST | `/tickets` | Auth | Store new ticket |
| GET | `/tickets/{id}` | Auth | View ticket thread |
| POST | `/tickets/{id}/reply` | Auth | Reply to ticket |
| POST | `/tickets/{id}/close` | Auth | Close ticket |
| GET | `/knowledge` | Auth | Knowledge base homepage |
| GET | `/knowledge/search` | Auth | Search articles |
| GET | `/knowledge/category/{category}` | Auth | Filter by category |
| GET | `/knowledge/{slug}` | Auth | View article |

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `APP_URL` | `https://support.ygxone.com` | Application URL |
| `DB_DATABASE` | `ygmarket_account` | Shared database name |
| `YG_ACCOUNT_URL` | `https://account.ygxone.com` | YG Account base URL |
| `YG_ACCOUNT_API_BASE` | `https://account.ygxone.com` | YG Account API URL |
| `MAIL_MAILER` | `log` | Mail driver (smtp/log/array) |

## License

Proprietary — YGXONE Ecosystem
