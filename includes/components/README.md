# Reusable Components Documentation

This directory contains reusable PHP components for the my-wisata application. These components help maintain consistency across admin pages and reduce code duplication.

## Available Components

### 1. Page Header Component (`page_header.php`)

Renders a standardized page header with title, subtitle, and action buttons.

**Usage:**
```php
require_once __DIR__ . '/../includes/components/page_header.php';

render_page_header([
    'title' => 'Page Title',
    'subtitle' => 'Page description',
    'actions' => [
        [
            'label' => 'Add New',
            'icon' => 'bi-plus-circle',
            'class' => 'btn-primary',
            'modal' => '#addModal'
        ],
        [
            'label' => 'Export',
            'icon' => 'bi-download',
            'class' => 'btn-secondary',
            'onclick' => 'exportData()'
        ]
    ]
]);
```

**Configuration:**
- `title` (required): Page title
- `subtitle` (optional): Page subtitle/description
- `actions` (optional): Array of action buttons
  - `label`: Button text
  - `icon`: Bootstrap icon class
  - `class`: CSS classes
  - `type`: Button type (button, link, submit)
  - `href`: Link URL for link type
  - `onclick`: JavaScript function
  - `modal`: Modal target for modal triggers
  - `badge`: Badge text (optional)

---

### 2. Search Filter Component (`search_filter.php`)

Renders a search form with optional additional filters.

**Usage:**
```php
require_once __DIR__ . '/../includes/components/search_filter.php';

// Simple search only
render_search_filter([
    'placeholder' => 'Search by name or email...',
    'search_value' => $search,
    'action_url' => '',
    'method' => 'GET'
]);

// With additional filters
render_search_filter([
    'placeholder' => 'Search...',
    'search_value' => $search,
    'filters' => [
        [
            'name' => 'status',
            'type' => 'select',
            'label' => 'Status',
            'options' => ['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'],
            'value' => $selected_status
        ],
        [
            'name' => 'date_from',
            'type' => 'date',
            'value' => $date_from
        ]
    ]
]);
```

**Configuration:**
- `placeholder`: Search input placeholder
- `search_value`: Current search value
- `action_url`: Form action URL
- `method`: Form method (GET, POST)
- `show_reset`: Show reset button (default: true)
- `reset_url`: Reset button URL
- `filters`: Array of additional filter fields
- `button_text`: Search button text

**Filter Configuration:**
- `name`: Field name
- `type`: Field type (select, text, date, etc.)
- `label`: Field label
- `options`: Options for select type
- `value`: Current field value
- `placeholder`: Field placeholder
- `class`: Additional CSS classes

---

### 3. Data Table Component (`data_table.php`)

Renders a responsive data table with various column types and action buttons.

**Usage:**
```php
require_once __DIR__ . '/../includes/components/data_table.php';

render_data_table([
    'title' => 'Data List',
    'data' => $items,
    'total_count' => $total_items,
    'empty_message' => 'No data available',
    'empty_icon' => 'bi-database',
    'columns' => [
        [
            'key' => 'id',
            'label' => 'ID',
            'type' => 'badge'
        ],
        [
            'key' => 'name',
            'label' => 'Name',
            'type' => 'avatar',
            'subtitle' => 'role'
        ],
        [
            'key' => 'email',
            'label' => 'Email',
            'type' => 'text'
        ],
        [
            'key' => 'created_at',
            'label' => 'Date',
            'type' => 'date',
            'format' => 'd M Y'
        ],
        [
            'key' => 'actions',
            'label' => 'Actions',
            'type' => 'actions'
        ]
    ],
    'actions' => [
        [
            'label' => 'Edit',
            'icon' => 'bi-pencil',
            'class' => 'btn btn-sm btn-outline-primary',
            'onclick' => 'editItem({id})',
            'id_key' => 'id'
        ],
        [
            'label' => 'Delete',
            'icon' => 'bi-trash',
            'class' => 'btn btn-sm btn-outline-danger',
            'onclick' => 'deleteItem({id})',
            'id_key' => 'id',
            'condition' => [
                'field' => 'status',
                'operator' => '!=',
                'value' => 'deleted'
            ]
        ]
    ]
]);
```

**Configuration:**
- `title` (required): Table title
- `data` (required): Array of data items
- `columns` (required): Array of column definitions
- `total_count`: Total number of items
- `empty_message`: Message for empty state
- `empty_icon`: Bootstrap icon for empty state
- `actions`: Array of action buttons

**Column Types:**
- `text`: Simple text display
- `badge`: Display as badge with # prefix
- `avatar`: Display with avatar icon and optional subtitle
- `date`: Formatted date display
- `actions`: Action buttons column

**Action Configuration:**
- `label`: Button label
- `icon`: Bootstrap icon class
- `class`: CSS classes
- `onclick`: JavaScript function (use {id} placeholder)
- `id_key`: Data key for ID (default: 'id')
- `condition`: Show/hide condition
  - `field`: Field to check
  - `operator`: Comparison operator (==, !=, >, <)
  - `value`: Value to compare against

---

### 4. Pagination Component (`pagination.php`)

Renders pagination controls with navigation and item count.

**Usage:**
```php
require_once __DIR__ . '/../includes/components/pagination.php';

render_pagination([
    'current_page' => $page,
    'total_pages' => $total_pages,
    'total_items' => $total_items,
    'per_page' => $per_page,
    'offset' => $offset,
    'base_url' => 'users.php',
    'query_params' => ['search' => $search, 'status' => $status]
]);
```

**Configuration:**
- `current_page`: Current page number
- `total_pages`: Total number of pages
- `total_items`: Total number of items
- `per_page`: Items per page
- `offset`: Current offset
- `base_url`: Base URL for pagination links
- `query_params`: Additional query parameters to preserve

---

## Implementation Example

Here's a complete example of how to use all components in a new admin page:

```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/components/page_header.php';
require_once __DIR__ . '/../includes/components/search_filter.php';
require_once __DIR__ . '/../includes/components/data_table.php';
require_once __DIR__ . '/../includes/components/pagination.php';

require_admin();

$page_title = 'Data Management';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$where = '';
$params = [];
if (!empty($search)) {
    $where .= "WHERE name LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

if (!empty($status)) {
    $where .= ($where ? ' AND' : 'WHERE') . " status = ?";
    $params[] = $status;
}

// Get data
$count_query = "SELECT COUNT(*) as total FROM items $where";
$stmt = $db->prepare($count_query);
$stmt->execute($params);
$total_items = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_items / $per_page);

$query = "SELECT * FROM items $where ORDER BY created_at DESC LIMIT $offset, $per_page";
$stmt = $db->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<!-- Sidebar -->
<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<!-- Main content -->
<main role="main" class="main-content">
    <?php
    // Page Header
    render_page_header([
        'title' => 'Data Management',
        'subtitle' => 'Manage application data',
        'actions' => [
            [
                'label' => 'Add New',
                'icon' => 'bi-plus-circle',
                'class' => 'btn-primary',
                'modal' => '#addModal'
            ]
        ]
    ]);
    ?>

    <?php
    // Search Filter
    render_search_filter([
        'placeholder' => 'Search by name or email...',
        'search_value' => $search,
        'filters' => [
            [
                'name' => 'status',
                'type' => 'select',
                'options' => ['' => 'All Status', 'active' => 'Active', 'inactive' => 'Inactive'],
                'value' => $status
            ]
        ]
    ]);
    ?>

    <?php
    // Data Table
    render_data_table([
        'title' => 'Data List',
        'data' => $items,
        'total_count' => $total_items,
        'columns' => [
            ['key' => 'id', 'label' => 'ID', 'type' => 'badge'],
            ['key' => 'name', 'label' => 'Name', 'type' => 'text'],
            ['key' => 'email', 'label' => 'Email', 'type' => 'text'],
            ['key' => 'created_at', 'label' => 'Date', 'type' => 'date'],
            ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions']
        ],
        'actions' => [
            [
                'label' => 'Edit',
                'icon' => 'bi-pencil',
                'class' => 'btn btn-sm btn-outline-primary',
                'onclick' => 'editItem({id})'
            ],
            [
                'label' => 'Delete',
                'icon' => 'bi-trash',
                'class' => 'btn btn-sm btn-outline-danger',
                'onclick' => 'deleteItem({id})'
            ]
        ]
    ]);
    ?>

    <?php
    // Pagination
    render_pagination([
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_items,
        'per_page' => $per_page,
        'offset' => $offset,
        'base_url' => basename($_SERVER['PHP_SELF']),
        'query_params' => ['search' => $search, 'status' => $status]
    ]);
    ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
```

## Benefits

1. **Consistency**: All admin pages have the same look and feel
2. **Maintainability**: Changes to component design affect all pages
3. **Reusability**: Components can be used across different modules
4. **Reduced Code Duplication**: Less repetitive HTML/PHP code
5. **Easier Development**: New pages can be created quickly
6. **Flexible Configuration**: Components are highly configurable

## Best Practices

1. Always include the component files at the top of your PHP file
2. Use descriptive configuration arrays for better readability
3. Follow the established naming conventions
4. Test components thoroughly before implementing across multiple pages
5. Keep component logic focused and avoid over-complicating them
