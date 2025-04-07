## Laravel CRUD Generator
A custom Laravel CRUD Generator that allows developers to scaffold complete resource management (models, controllers, requests, views, and routes) using a single artisan command. Designed for flexibility, scalability, and ease of use, this tool helps you quickly bootstrap fully functional CRUD modules with relationship support.


## Feature

- Generate models, migrations, controllers, requests, and views via a single command
- Support for **hasMany** relationships
- API routes with Sanctum authentication
- Blade view generation with reusable components
- Nested resource route generation (both API and web)
- Organized blueprint services for maintainability
- Automatic duplicate prevention in route imports and declarations
- Reset command to clean up only generated files

## Debugging & Monitoring
# Laravel Telescope
Laravel Telescope is installed and available for local development debugging and monitoring.

- Access it via: **/telescope**
- It provides insights into:
  - Requests
  - Exceptions
  - Logs
  - Queues
  - Database queries
  - And more.

Make sure TELESCOPE_ENABLED=true is set in your .env file if you're not seeing it.


## File Structure

Generator logic is modularized in the App\Services\CrudGenerator directory, with responsibilities split into:

- ModelGenerator
- MigrationGenerator
- ApiControllerGenerator
- WebControllerGenerator
- RequestGenerator
- ViewsGenerator
- RoutesGenerator

Each class is responsible for generating specific parts of the Laravel resource.



## Installation

To set up the CRUD Generator on your local machine, follow these steps:

- Clone the repository using the following command:

```
https://github.com/saanchita-paul/crud_generator.git
```

- Navigate to the cloned directory:

```
cd crud_generator
```
- Install dependencies:

```
composer install
npm intall
```

- Copy the .env.example file to .env:

```
cp .env.example .env
```
- Generate an application key:

```
php artisan key:generate
```

- Configure the database in the .env file:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password
```
- Migrate the database:

```markdown
php artisan migrate
```
# Authentication
A default admin user is seeded for quick access to the web panel:
```
Email: admin@example.com
Password: password
```
- You can log in from both web and API interfaces.
- New users can register via:
```
Web: /register
API: /api/register
```

**Note: While an admin user is seeded for convenience, the system supports standard user registration through both web and API routes.**

- Run the following command to seed the database:

```
php artisan db:seed
```

- Start the development server:

```
php artisan serve
npm run dev
```

## Usage
Run the artisan command without relation:
```
php artisan make:crud Project --fields="name:string, description:text, status:enum(open,closed)"
```
Run the artisan command wit relation:
```
php artisan make:crud Project --fields="name:string, description:text, status:enum(open,closed)" --relations="tasks:hasMany"
```

This will generate:

- Project model with proper fillable attributes and relationship
- Migration file with all fields and types
- Form Request (ProjectRequest) with validation rules
- RESTful Controller (ProjectController) with all resource methods
- API and web routes
- Blade views (index, create, edit, show) using components
- If --relations provided: Related model/controller/migration + nested routes


Visit [localhost](http://localhost:8000) in your web browser to use the application.


## Reset Command

Use php artisan crud:reset --dry-run to preview which files and route entries would be deleted without actually removing anything.
Helpful for reviewing changes before running the full reset.
```
php artisan crud:reset --dry-run
```
Remove a previously generated CRUD resource:
```
php artisan crud:reset
```
This command only deletes files generated via **make:crud**, without touching unrelated resources.


##  Test Coverage
The generator includes test cases to verify:

- File generation
- Route registration
- No duplicate routes/imports
- Reset command cleanup


## Highlights
- Relationship-based nested route generation
- Auto-sanitization of route files to avoid duplicates
- Clean separation of concerns via services
- Support for both API and web routes


# Future Enhancements
- BelongsToMany / Morph relationships support
- Custom API Resource classes
- Web dashboard to track generated resources
- UI schema-based CRUD generation
- Currently, **crud:reset** deletes only the main model and its resources.
  - In the future, it will also delete related models (e.g., Task for Project with hasMany).
- Related Controller Namespaces Not Removed
    - When resetting a model using php artisan crud:reset, any related controller namespaces (like use App\Http\Controllers\TaskController; used in nested or relation-based routes) are not automatically removed from web.php or api.php.
      This is because the reset process only targets the model(s) directly listed in the crud_models.json file.
    - Will be considered in a future enhancement.
[Check Postman API Documentation](https://documenter.getpostman.com/view/15919922/2sB2cUCP3W)
