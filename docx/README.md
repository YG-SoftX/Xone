# YG DocX - Google Docs-like Collaborative Editor

A full-featured document editor built with Laravel + React (Inertia), designed to compete with Google Docs.

## Features

### Document Editor
- **Rich Text Editing** - Bold, italic, underline, strikethrough, headings, quotes, code blocks
- **Text Formatting** - Font family (Arial, Times New Roman, Courier, Georgia, Verdana, Calibri), font sizes (8pt-36pt)
- **Alignment** - Left, center, right, justify
- **Lists** - Bullet lists, numbered lists
- **Insert** - Links, images, tables
- **Auto-save** - Saves every 30 seconds + manual save (Ctrl+S)
- **Keyboard Shortcuts** - Ctrl+B (bold), Ctrl+I (italic), Ctrl+U (underline), Ctrl+S (save), Ctrl+P (print)

### Collaboration
- **Real-time Sharing** - Share documents with view/comment/edit permissions
- **Comments** - Add comments, resolve threads
- **Suggestions** - Suggest edits, accept/reject changes
- **Version History** - Auto-saved versions, restore any version

### Organization
- **Folders** - Nested folder structure for document organization
- **Templates** - 8 pre-built templates (Blank, Business Letter, Meeting Notes, Project Proposal, Resume, Essay, Report, Invoice)
- **Search** - Search across all documents
- **Grid/List View** - Toggle between views

### Export
- **PDF** - Export as PDF
- **DOCX** - Export as Microsoft Word document (via PHPWord)
- **HTML** - Export as HTML
- **TXT** - Export as plain text

### Authentication
- **SSO Integration** - Single Sign-On with YG Account (same as YG Mail)
- **Automatic User Creation** - First-time SSO creates local account

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: React 18 + Inertia.js + Vite 7
- **Styling**: Tailwind CSS 3
- **Animations**: Framer Motion
- **Icons**: Lucide React
- **Database**: SQLite (local) / MySQL (production)
- **Document Export**: PHPWord

## Database Schema

| Table | Purpose |
|-------|---------|
| `users` | User accounts (via SSO) |
| `documents` | Documents with content, metadata, soft deletes |
| `folders` | Nested folder organization |
| `document_versions` | Auto-saved version history |
| `document_shares` | Sharing permissions (view/comment/edit) |
| `document_comments` | Comments with resolve capability |
| `document_suggestions` | Suggested edits with accept/reject |
| `document_activity` | Activity audit log |
| `templates` | System and user-created templates |

## Installation

```bash
cd "c:\Users\ASUS\Downloads\YG Soft1\YG DocX"

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env  # or use existing .env
php artisan key:generate

# Run migrations
php artisan migrate --force

# Seed templates
php artisan db:seed --class=TemplateSeeder

# Build frontend
npm run build

# Start development server
php artisan serve --port=8003
npm run dev  # for hot module replacement
```

## Usage

### Start the Server
```bash
cd "c:\Users\ASUS\Downloads\YG Soft1\YG DocX"
php artisan serve --port=8003
```

Then visit: `http://localhost:8003`

You'll be redirected to YG Account for SSO authentication.

### Creating Documents
1. Click **Blank Document** or choose a template
2. Use the toolbar to format your content
3. Auto-save runs every 30 seconds, or press Ctrl+S

### Sharing
1. Click the **Share** button in the editor
2. Enter an email address
3. Choose permission: View, Comment, or Edit
4. Copy the share link

### Version History
1. Click the clock icon in the editor
2. View all saved versions
3. Restore any previous version

### Export
1. Click the download icon in the editor
2. Choose format: PDF, DOCX, HTML, or TXT

## API Routes

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/` | Document home (list) |
| GET | `/documents/create` | Create document form |
| POST | `/documents` | Create new document |
| GET | `/documents/{id}` | View/edit document |
| PATCH | `/documents/{id}` | Save document changes |
| POST | `/documents/{id}/duplicate` | Duplicate document |
| POST | `/documents/{id}/archive` | Archive document |
| DELETE | `/documents/{id}` | Delete document |
| GET | `/folders` | List folders |
| POST | `/folders` | Create folder |
| PATCH | `/folders/{id}` | Update folder |
| DELETE | `/folders/{id}` | Delete folder |
| GET | `/documents/{id}/shares` | List shares |
| POST | `/documents/{id}/share` | Share document |
| PATCH | `/shares/{id}` | Update permission |
| DELETE | `/shares/{id}` | Revoke share |
| GET | `/documents/{id}/comments` | List comments |
| POST | `/documents/{id}/comments` | Add comment |
| POST | `/comments/{id}/resolve` | Resolve comment |
| GET | `/documents/{id}/suggestions` | List suggestions |
| POST | `/documents/{id}/suggestions` | Add suggestion |
| POST | `/suggestions/{id}/accept` | Accept suggestion |
| POST | `/suggestions/{id}/reject` | Reject suggestion |
| GET | `/documents/{id}/versions` | Version history |
| POST | `/documents/{id}/versions/{versionId}/restore` | Restore version |
| GET | `/templates` | Template gallery |
| POST | `/templates` | Save user template |
| POST | `/templates/{id}/apply/{documentId}` | Apply template |
| GET | `/documents/{id}/export/{format}` | Export (pdf/docx/html/txt) |
| GET | `/sso/callback` | SSO callback from YG Account |

## Configuration

### YG Account SSO
Set `YG_ACCOUNT_URL` in `.env` to point to your YG Account instance:
```
YG_ACCOUNT_URL=http://localhost:8000
```

### Database
For production, switch from SQLite to MySQL in `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yg_docx
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

## Production Deployment

1. Set `APP_DEBUG=false` and `APP_ENV=production`
2. Configure MySQL database
3. Run `php artisan config:cache` and `php artisan route:cache`
4. Build frontend: `npm run build`
5. Set up queue worker for background jobs
6. Configure YG Account SSO URL

## Architecture

```
YG DocX/
├── app/
│   ├── Http/Controllers/     # 9 controllers
│   ├── Http/Middleware/      # Auth + Inertia middleware
│   └── Models/               # 8 Eloquent models
├── database/
│   ├── migrations/           # 11 migrations (8 custom + 3 Laravel)
│   └── seeders/              # Template seeder
├── resources/
│   ├── js/
│   │   ├── Pages/            # 4 React pages
│   │   └── app.jsx           # Inertia entry point
│   ├── css/app.css           # Tailwind + custom styles
│   └── views/app.blade.php   # Blade template shell
├── routes/web.php            # 35 routes
└── vite.config.js            # Vite + React config
```

## Integration with YG Ecosystem

- **SSO**: Authenticates via YG Account (`/api/sso/validate`)
- **Admin Panel**: Managed from YG Account admin panel (`/admin`)
- **Shared Database**: Uses YG Account's user table via SSO
- **Service URL**: `VITE_YG_DOCX_URL` in YG Account config

## License

Proprietary - YGXone
