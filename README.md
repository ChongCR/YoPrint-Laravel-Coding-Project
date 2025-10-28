# CSV Upload System

A Laravel-based CSV file upload and processing system with real-time progress tracking.

**Developed by:** Chong Chun Rock  
**Contact:** 60182888835

---

## Features

- Drag-and-drop CSV file upload
- Background processing with Redis queue
- Real-time progress tracking
- Automatic UTF-8 encoding conversion
- Idempotent uploads (upsert based on UNIQUE_KEY)
- Batch processing for large files (500 records per batch)
- Sortable upload history

---

## Requirements

- PHP 8.1+
- Laravel 11.x
- Redis
- Composer

---

## Installation

1. **Clone the repository**
```bash
   git clone <repository-url>
   cd <project-folder>
```

2. **Install dependencies**
```bash
   composer install
```

3. **Configure environment**
```bash
   cp .env.example .env
   php artisan key:generate
```

4. **Update `.env` file**
```env
   DB_CONNECTION=sqlite
   QUEUE_CONNECTION=redis
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
```

5. **Create database**
```bash
   touch database/database.sqlite
```

6. **Run migrations**
```bash
   php artisan migrate
```

7. **Create storage link**
```bash
   php artisan storage:link
```

---

## Usage

1. **Start Redis server** (if not running)
```bash
   redis-server
```

2. **Start queue worker**
```bash
   php artisan queue:work
```

3. **Start development server**
```bash
   php artisan serve
```

4. **Access the application**
```
   http://localhost:8000/uploads
```

---

## CSV File Format

The system expects CSV files with the following columns:

| Column Name | Description |
|------------|-------------|
| UNIQUE_KEY | Unique identifier for the product |
| PRODUCT_TITLE | Product name |
| PRODUCT_DESCRIPTION | Product description |
| STYLE# | Style number |
| SANMAR_MAINFRAME_COLOR | Mainframe color |
| SIZE | Product size |
| COLOR_NAME | Color name |
| PIECE_PRICE | Price per piece |


## How to Use

1. **Upload CSV File**
   - Click "Choose File" or drag & drop a CSV file
   - File uploads automatically upon selection
   - Maximum file size: 50MB

2. **Monitor Progress**
   - The table updates every 3 seconds automatically
   - View upload status: Pending → Processing → Completed/Failed
   - Track progress with processed rows count

3. **Sort Results**
   - Click column headers (Time, File Name, Status) to sort
   - Click again to reverse sort order

---

## Database Schema

### `uploads` table
```sql
- id: Primary key
- filename: Uploaded file name
- status: pending|processing|completed|failed
- total_rows: Total number of records
- processed_rows: Number of processed records
- error: Error message (if failed)
- created_at, updated_at
```

### `products` table
```sql
- id: Primary key
- unique_key: Unique product identifier (indexed)
- product_title
- product_description
- style_number
- sanmar_mainframe_color
- size
- color_name
- piece_price
- created_at, updated_at
```

---

## Key Features Explained

### Idempotent Uploads
- Files can be uploaded multiple times
- Existing records are updated (upsert) based on `UNIQUE_KEY`
- No duplicate records created

### UTF-8 Encoding
- Automatically detects and converts file encoding
- Handles UTF-16 BOM (Byte Order Mark)
- Cleans non-UTF-8 characters

### Background Processing
- Large files processed in the background
- No browser timeout issues
- Server can handle multiple uploads simultaneously

---

## Troubleshooting

**Queue not processing:**
```bash
php artisan queue:restart
php artisan queue:work --tries=3
```

**Redis connection error:**
- Ensure Redis server is running
- Check Redis credentials in `.env`

**File not found error:**
- Run `php artisan storage:link`
- Check folder permissions: `storage/app/public`

---

## Technical Stack

- **Framework:** Laravel 11
- **Queue:** Redis
- **CSV Parser:** league/csv
- **Frontend:** Bootstrap 5.3, Vanilla JavaScript
- **Database:** SQLite (configurable)

---

## Contact

For questions or opportunities:

**Chong Chun Rock**  
Phone: 60182888835

Thank you for reviewing this project!