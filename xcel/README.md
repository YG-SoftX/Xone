# YG Xcel - Microsoft Excel-like Spreadsheet Application

A full-featured spreadsheet application built with Laravel + React (Inertia), designed to compete with Microsoft Excel and Google Sheets.

## Features

### Spreadsheet Editor
- **Full Cell Grid** - 26 columns (A-Z) × 100 rows, expandable
- **Formula Engine** - `=SUM(A1:A10)`, `=AVERAGE()`, `=COUNT()`, `=MAX()`, `=MIN()`, `=IF(condition,true,false)`, `=CONCATENATE()`, cell references, arithmetic operators
- **Cell Formatting** - Bold, italic, underline, text alignment, font size, text color, background color
- **Formula Bar** - Edit formulas and values in the top bar
- **Auto-Save** - Every 15 seconds + Ctrl+S manual save
- **Keyboard Navigation** - Tab (next cell), Shift+Tab (previous), Enter (edit), Escape (cancel)

### Multiple Sheets
- Add/rename/duplicate/delete sheets
- Tab navigation at bottom
- Each sheet has independent cells, formatting

### Charts
- Bar, Line, Pie, Area, Scatter, Doughnut charts
- Configurable data ranges (e.g., A1:B10)
- Position and size control

### Collaboration
- **Sharing** - Share with view/edit permissions by email
- **Comments** - Comment on any cell, resolve threads
- **Activity Log** - Full audit trail of changes

### Import/Export
- **XLSX** - Real Excel files via PhpSpreadsheet (preserves formulas, formatting)
- **CSV** - Export active sheet
- **PDF** - Generate printable PDF
- **HTML** - Raw HTML table

### Templates
- 6 built-in templates: Blank, Budget Tracker, Invoice, Grade Book, Inventory, Weekly Schedule
- Save custom templates
- Apply templates to create new spreadsheets

### Organization
- Search spreadsheets
- Archive/restore/delete
- Grid and List views
- Status indicators (draft, published, archived)

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: React 18 + Inertia.js + Vite 7
- **Styling**: Tailwind CSS 3
- **Animations**: Framer Motion
- **Icons**: Lucide React
- **Database**: SQLite (local) / MySQL (production)
- **Export**: PhpSpreadsheet (real .xlsx generation)

## Database Schema

| Table | Purpose |
|-------|---------|
| `spreadsheets` | Main documents with soft deletes |
| `sheets` | Individual sheets within a workbook |
| `cells` | Individual cell data (address, value, formula, format) |
| `sheet_shares` | Sharing permissions |
| `charts` | Chart configurations |
| `cell_comments` | Comments on cells |
| `templates` | System and user templates |
| `spreadsheet_activity` | Audit log |

## Installation

```bash
cd "c:\Users\ASUS\Downloads\YG Soft1\YG Xcel"

# Already installed: composer install, npm install
# Already run: php artisan migrate, npm run build

# Start server
php artisan serve --port=8004
```

Visit: `http://localhost:8004` (redirects to YG Account for SSO)

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+S` | Save |
| `Ctrl+B` | Bold |
| `Ctrl+I` | Italic |
| `Tab` | Next cell |
| `Shift+Tab` | Previous cell |
| `Enter` | Start/finish editing |
| `Escape` | Cancel editing |
| `Double-click` | Edit cell |

## Formula Examples

```
=SUM(A1:A10)           Sum range
=AVERAGE(B2:B20)       Average
=COUNT(C1:C100)        Count numbers
=MAX(D1:D50)           Maximum value
=MIN(D1:D50)           Minimum value
=IF(A1>100,"Yes","No") Conditional
=A1+B1*C1              Arithmetic
=CONCATENATE(A1," ",B1) Text join
```

## Production Deployment

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://xcel.ygxone.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=yg_xcel
YG_ACCOUNT_URL=https://account.ygxone.com
```

## API Routes (41 total)

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/` | Spreadsheet home |
| POST | `/` | Create spreadsheet |
| GET | `/{id}` | Open spreadsheet editor |
| PATCH | `/{id}` | Update spreadsheet |
| POST | `/{id}/duplicate` | Duplicate |
| POST | `/{id}/archive` | Archive |
| DELETE | `/{id}` | Delete |
| POST | `spreadsheets/{id}/sheets` | Add sheet |
| PATCH | `sheets/{id}` | Update sheet |
| DELETE | `sheets/{id}` | Delete sheet |
| POST | `sheets/{id}/cells/batch` | Batch update cells |
| GET | `sheets/{id}/range/{start}:{end}` | Get cell range |
| POST | `sheets/{id}/charts` | Create chart |
| POST | `spreadsheets/{id}/share` | Share spreadsheet |
| POST | `sheets/{id}/comments` | Add comment |
| GET | `/{id}/export/{format}` | Export (xlsx/csv/pdf/html) |
| POST | `templates/{id}/apply` | Apply template |

## File Structure

```
YG Xcel/
├── app/
│   ├── Http/Controllers/     # 9 controllers + SSO
│   ├── Http/Middleware/      # Auth + Inertia
│   ├── Services/             # FormulaEngine
│   └── Models/               # 8 models
├── database/
│   ├── migrations/           # 11 migrations
│   └── seeders/              # Template seeder (6 templates)
├── resources/
│   ├── js/Pages/             # 2 React pages (Editor + Home)
│   ├── css/app.css           # Tailwind
│   └── views/app.blade.php   # Inertia shell
├── routes/web.php            # 41 routes
└── vite.config.js            # Vite + React
```

## License

Proprietary - YGXone
