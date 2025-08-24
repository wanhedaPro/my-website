<?php
session_start();

// --- Simulated Database & Data Initialization ---
if (!isset($_SESSION['products'])) {
    $_SESSION['products'] = [
        ['id' => 1, 'name' => 'Croissant', 'price' => 2.50, 'category' => 'Pastries', 'image_url' => 'https://placehold.co/400x300/FAD69F/2A2C31?text=Croissant'],
        ['id' => 2, 'name' => 'Espresso', 'price' => 3.00, 'category' => 'Coffee', 'image_url' => 'https://placehold.co/400x300/6A4D3A/F9F9F9?text=Espresso'],
        ['id' => 3, 'name' => 'Chocolate Cake Slice', 'price' => 5.50, 'category' => 'Cakes', 'image_url' => 'https://placehold.co/400x300/4B2B1B/E0D5C9?text=Chocolate+Cake'],
        ['id' => 4, 'name' => 'Latte', 'price' => 4.00, 'category' => 'Coffee', 'image_url' => 'https://placehold.co/400x300/B5967E/3C2719?text=Latte'],
        ['id' => 5, 'name' => 'Muffin', 'price' => 3.25, 'category' => 'Pastries', 'image_url' => 'https://placehold.co/400x300/E3C3A7/4A4A4A?text=Muffin'],
        ['id' => 6, 'name' => 'Red Velvet Cupcake', 'price' => 4.50, 'category' => 'Cakes', 'image_url' => 'https://placehold.co/400x300/961A39/F0F0F0?text=Red+Velvet']
    ];
}
if (!isset($_SESSION['sales'])) {
    $_SESSION['sales'] = [];
}
if (!isset($_SESSION['expenses'])) {
    $_SESSION['expenses'] = [];
}
if (!isset($_SESSION['pre_orders'])) {
    $_SESSION['pre_orders'] = [];
}
if (!isset($_SESSION['completed_pre_orders'])) {
    $_SESSION['completed_pre_orders'] = [];
}
if (!isset($_SESSION['vendors'])) {
    $_SESSION['vendors'] = [
        ['id' => 1, 'name' => 'Baked Goodies Co.', 'contact_person' => 'Jane Doe', 'phone' => '012-3456789', 'email' => 'jane.doe@bakedgoodies.com'],
        ['id' => 2, 'name' => 'Coffee Roasters Inc.', 'contact_person' => 'John Smith', 'phone' => '013-4567890', 'email' => 'john.smith@coffeeinc.com'],
        ['id' => 3, 'name' => 'Sweet Delights Bakery', 'contact_person' => 'Alex Chen', 'phone' => '014-5678901', 'email' => 'alex.chen@sweetdelights.com'],
    ];
}
if (!isset($_SESSION['vendor_orders'])) {
    $_SESSION['vendor_orders'] = [];
}
if (!isset($_SESSION['next_product_id'])) {
    $_SESSION['next_product_id'] = 7;
}
if (!isset($_SESSION['next_sales_id'])) {
    $_SESSION['next_sales_id'] = 1;
}
if (!isset($_SESSION['next_expense_id'])) {
    $_SESSION['next_expense_id'] = 1;
}
if (!isset($_SESSION['next_pre_order_id'])) {
    $_SESSION['next_pre_order_id'] = 1;
}
if (!isset($_SESSION['next_vendor_id'])) {
    $_SESSION['next_vendor_id'] = 4;
}
if (!isset($_SESSION['next_vendor_order_id'])) {
    $_SESSION['next_vendor_order_id'] = 1;
}

// Get unique categories for tabs
$categories = array_unique(array_column($_SESSION['products'], 'category'));
sort($categories);


// Check for today's pre-orders for the reminder
$today_pre_orders = array_filter($_SESSION['pre_orders'], function($order) {
    return $order['pickup_date'] == date('Y-m-d');
});

// --- Form Handling Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'login':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            if ($username === 'admin' && $password === 'admin123') {
                $_SESSION['is_admin'] = true;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            break;

        case 'logout':
            unset($_SESSION['is_admin']);
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;

        case 'add_product':
        case 'edit_product':
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'];
            $price = floatval($_POST['price']);
            $category = $_POST['category'];
            $image_url = $_POST['image_url'] ?? 'https://placehold.co/400x300/CCCCCC/333333?text=No+Image';

            $new_product = [
                'name' => $name,
                'price' => $price,
                'category' => $category,
                'image_url' => $image_url
            ];

            if ($action === 'add_product') {
                $new_product['id'] = $_SESSION['next_product_id']++;
                $_SESSION['products'][] = $new_product;
            } else { // Edit product
                foreach ($_SESSION['products'] as &$product) {
                    if ($product['id'] == $id) {
                        $product['name'] = $name;
                        $product['price'] = $price;
                        $product['category'] = $category;
                        $product['image_url'] = $image_url;
                        break;
                    }
                }
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
        case 'add_custom_order':
            $name = $_POST['name'];
            $price = floatval($_POST['price']);
            $description = $_POST['description'];
            
            // Create a new sales record for the custom order
            $custom_order_data = [
                'id' => $_SESSION['next_sales_id']++,
                'total_amount' => $price,
                'order_items' => [
                    [
                        'name' => $name,
                        'price' => $price,
                        'quantity' => 1,
                        'description' => $description
                    ]
                ],
                'order_date' => date('Y-m-d H:i:s'),
                'note' => 'Custom Order'
            ];
            
            $_SESSION['sales'][] = $custom_order_data;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
        case 'ajax_add_product':
            header('Content-Type: application/json');
            if (empty($_POST['name']) || !isset($_POST['price']) || empty($_POST['category'])) {
                echo json_encode(['error' => 'Missing required fields.']);
                exit;
            }
            $new_product = [
                'id' => $_SESSION['next_product_id']++,
                'name' => $_POST['name'],
                'price' => floatval($_POST['price']),
                'category' => $_POST['category'],
                'image_url' => $_POST['image_url'] ?? 'https://placehold.co/400x300/CCCCCC/333333?text=No+Image'
            ];
            $_SESSION['products'][] = $new_product;
            echo json_encode($new_product);
            exit;
            break;

        case 'delete_product':
            $id = $_POST['id'];
            $_SESSION['products'] = array_filter($_SESSION['products'], function($product) use ($id) {
                return $product['id'] != $id;
            });
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
            
        case 'add_expense':
            $expense_data = [
                'id' => $_SESSION['next_expense_id']++,
                'description' => $_POST['description'],
                'amount' => floatval($_POST['amount']),
                'date' => $_POST['date']
            ];
            $_SESSION['expenses'][] = $expense_data;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;

        case 'delete_expense':
            $id = $_POST['id'];
            $_SESSION['expenses'] = array_filter($_SESSION['expenses'], function($expense) use ($id) {
                return $expense['id'] != $id;
            });
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
        
        case 'place_order':
            $order_items = json_decode($_POST['order_items'], true);
            $total = floatval($_POST['total_amount']);
            
            $sale_record = [
                'id' => $_SESSION['next_sales_id']++,
                'total_amount' => $total,
                'order_items' => $order_items,
                'order_date' => date('Y-m-d H:i:s')
            ];
            $_SESSION['sales'][] = $sale_record;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;

        case 'place_pre_order':
            $order_items = json_decode($_POST['order_items'], true);
            $total = floatval($_POST['total_amount']);
            $pickup_date = $_POST['pickup_date'];
            $pickup_time = $_POST['pickup_time'];
            $delivery_type = $_POST['delivery_type'];
            $customer_name = $_POST['customer_name'] ?? '';
            $customer_contact = $_POST['customer_contact'] ?? '';

            $pre_order_record = [
                'id' => $_SESSION['next_pre_order_id']++,
                'total_amount' => $total,
                'order_items' => $order_items,
                'pickup_date' => $pickup_date,
                'pickup_time' => $pickup_time,
                'delivery_type' => $delivery_type,
                'customer_name' => $customer_name,
                'customer_contact' => $customer_contact,
                'order_date' => date('Y-m-d H:i:s')
            ];
            $_SESSION['pre_orders'][] = $pre_order_record;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;

        case 'update_pre_order':
            $id = $_POST['id'];
            $customer_name = $_POST['customer_name'] ?? '';
            $customer_contact = $_POST['customer_contact'] ?? '';
            $pickup_date = $_POST['pickup_date'];
            $pickup_time = $_POST['pickup_time'];
            $delivery_type = $_POST['delivery_type'];

            foreach ($_SESSION['pre_orders'] as &$order) {
                if ($order['id'] == $id) {
                    $order['customer_name'] = $customer_name;
                    $order['customer_contact'] = $customer_contact;
                    $order['pickup_date'] = $pickup_date;
                    $order['pickup_time'] = $pickup_time;
                    $order['delivery_type'] = $delivery_type;
                    break;
                }
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
        
        case 'complete_pre_order':
            $id = $_POST['id'];
            $payment_amount = floatval($_POST['payment_amount']);
            $completed_order = null;

            $_SESSION['pre_orders'] = array_filter($_SESSION['pre_orders'], function($order) use ($id, &$completed_order) {
                if ($order['id'] == $id) {
                    $completed_order = $order;
                    return false; // Remove this order from the list
                }
                return true;
            });

            if ($completed_order) {
                $completed_order['completion_date'] = date('Y-m-d H:i:s');
                $_SESSION['completed_pre_orders'][] = $completed_order;

                // Create a new sales record from the completed pre-order
                $sale_record = [
                    'id' => $_SESSION['next_sales_id']++,
                    'total_amount' => $payment_amount,
                    'order_items' => $completed_order['order_items'],
                    'order_date' => $completed_order['order_date'],
                    'note' => 'Pre-order confirmation'
                ];
                $_SESSION['sales'][] = $sale_record;
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
            
        case 'void_order':
            $id = $_POST['id'];
            $_SESSION['sales'] = array_filter($_SESSION['sales'], function($sale) use ($id) {
                return $sale['id'] != $id;
            });
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;

        case 'void_pre_order':
            $id = $_POST['id'];
            $_SESSION['pre_orders'] = array_filter($_SESSION['pre_orders'], function($order) use ($id) {
                return $order['id'] != $id;
            });
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
            
        case 'add_vendor':
        case 'edit_vendor':
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'];
            $contact_person = $_POST['contact_person'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
        
            $new_vendor = [
                'name' => $name,
                'contact_person' => $contact_person,
                'phone' => $phone,
                'email' => $email,
            ];
        
            if ($action === 'add_vendor') {
                $new_vendor['id'] = $_SESSION['next_vendor_id']++;
                $_SESSION['vendors'][] = $new_vendor;
            } else { // Edit vendor
                foreach ($_SESSION['vendors'] as &$vendor) {
                    if ($vendor['id'] == $id) {
                        $vendor['name'] = $name;
                        $vendor['contact_person'] = $contact_person;
                        $vendor['phone'] = $phone;
                        $vendor['email'] = $email;
                        break;
                    }
                }
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
        
        case 'delete_vendor':
            $id = $_POST['id'];
            $_SESSION['vendors'] = array_filter($_SESSION['vendors'], function($vendor) use ($id) {
                return $vendor['id'] != $id;
            });
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;

        case 'add_vendor_order':
            $order_items = json_decode($_POST['order_items'], true);
            $total_amount = floatval($_POST['total_amount']);
            
            $vendor_order_data = [
                'id' => $_SESSION['next_vendor_order_id']++,
                'vendor_id' => intval($_POST['vendor_id']),
                'order_date' => $_POST['order_date'],
                'amount' => $total_amount,
                'order_items' => $order_items,
                'status' => 'pending'
            ];
            $_SESSION['vendor_orders'][] = $vendor_order_data;
            
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
            
        case 'edit_vendor_order':
            $id = intval($_POST['id']);
            $order_items = json_decode($_POST['order_items'], true);
            $new_total_amount = floatval($_POST['total_amount']);
            $vendor_id = intval($_POST['vendor_id']);
            $order_date = $_POST['order_date'];
            
            foreach ($_SESSION['vendor_orders'] as &$order) {
                if ($order['id'] == $id) {
                    $expense_found_and_updated = false;
                    // Find and update the associated expense record
                    foreach ($_SESSION['expenses'] as &$expense) {
                        if (strpos($expense['description'], 'Vendor Order (#' . $id . ')') !== false) {
                            $expense['amount'] = $new_total_amount;
                            $expense['date'] = $order_date;
                            $expense_found_and_updated = true;
                            break;
                        }
                    }
                    unset($expense); // Unset reference to avoid side effects

                    // If the expense was deleted or never existed, create it now
                    if (!$expense_found_and_updated) {
                        $expense_data = [
                            'id' => $_SESSION['next_expense_id']++,
                            'description' => 'Vendor Order (#' . $id . ')',
                            'amount' => $new_total_amount,
                            'date' => $order_date
                        ];
                        $_SESSION['expenses'][] = $expense_data;
                    }
                    
                    // Update the vendor order itself
                    $order['vendor_id'] = $vendor_id;
                    $order['order_date'] = $order_date;
                    $order['amount'] = $new_total_amount;
                    $order['order_items'] = $order_items;
                    break;
                }
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;

        case 'confirm_vendor_order':
            $id = intval($_POST['id']);
            $confirmed_amount = floatval($_POST['confirmed_amount']);
            foreach ($_SESSION['vendor_orders'] as &$order) {
                if ($order['id'] === $id && isset($order['status']) && $order['status'] === 'pending') {
                    // Update the status to 'completed'
                    $order['status'] = 'completed';
                    
                    // Now, create the sales record with the confirmed amount
                    $sales_data = [
                        'id' => $_SESSION['next_sales_id']++,
                        'total_amount' => $confirmed_amount,
                        'order_items' => $order['order_items'],
                        'order_date' => $order['order_date'],
                        'note' => 'Vendor Order Confirmation (#' . $order['id'] . ')'
                    ];
                    $_SESSION['sales'][] = $sales_data;
                    break;
                }
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;

        case 'delete_vendor_order':
            $id = $_POST['id'];
            $_SESSION['vendor_orders'] = array_filter($_SESSION['vendor_orders'], function($order) use ($id) {
                return $order['id'] != $id;
            });
            // Also remove the associated expense record
            $_SESSION['expenses'] = array_filter($_SESSION['expenses'], function($expense) use ($id) {
                return strpos($expense['description'], 'Vendor Order (#' . $id . ')') === false;
            });
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
    }
}

// Calculate totals for admin view
$total_sales = array_sum(array_column($_SESSION['sales'], 'total_amount'));
$total_expenses = array_sum(array_column($_SESSION['expenses'], 'amount'));
$net_profit = $total_sales - $total_expenses;

// Get pre-order data for the calendar (only for non-completed orders)
$pre_order_events = array_map(function($order) {
    $title = 'Pre-Order #' . $order['id'];
    $title .= ' (' . ucfirst($order['delivery_type']) . ' @ ' . $order['pickup_time'] . ')';
    $title .= ' - ' . $order['customer_name'];
    return [
        'title' => $title,
        'start' => $order['pickup_date'] . 'T' . $order['pickup_time'],
        'id' => $order['id'],
    ];
}, $_SESSION['pre_orders']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakery POS System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .product-card {
            display: flex;
            flex-direction: column;
            background-color: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .product-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .product-info {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-grow: 1;
        }
        .product-name {
            font-weight: 600;
            font-size: 1.125rem;
            color: #333;
        }
        .product-price {
            margin-top: 0.25rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #b8860b;
        }
        .tab-btn {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 500;
            transition: background-color 0.2s, color 0.2s;
            color: #4b5563;
        }
        .tab-btn.active {
            background-color: #b8860b;
            color: #fff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .admin-tab.active {
            border-bottom: 3px solid #b8860b;
            font-weight: 600;
            color: #333;
        }
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.6);
        }
        .modal-content {
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        #pre-order-calendar {
            max-width: 100%;
        }
        /* Make admin modal scrollable on smaller screens */
        .modal-content {
             max-height: 90vh;
        }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen font-sans antialiased text-gray-800">

    <div class="flex-1 flex flex-col p-6 max-w-7xl mx-auto w-full">
        <header class="flex flex-col md:flex-row justify-between items-center mb-6">
            <h1 class="text-4xl font-extrabold text-[#b8860b] mb-2 md:mb-0">
                Bakery POS
                <span id="current-datetime" class="text-sm font-light text-gray-500 block">Loading...</span>
            </h1>
            
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <div class="flex flex-wrap justify-center space-x-2 md:space-x-4 text-sm font-medium text-gray-600 mb-4 md:mb-0">
                    <div id="sales-display" class="bg-white p-3 rounded-lg shadow-md min-w-[150px] text-center">
                        <span class="block text-gray-400">Sales</span>
                        <span class="text-green-600 font-bold text-lg">RM<?php echo number_format($total_sales, 2); ?></span>
                    </div>
                    <div id="expenses-display" class="bg-white p-3 rounded-lg shadow-md min-w-[150px] text-center">
                        <span class="block text-gray-400">Expenses</span>
                        <span class="text-red-600 font-bold text-lg">RM<?php echo number_format($total_expenses, 2); ?></span>
                    </div>
                    <div id="profit-display" class="bg-white p-3 rounded-lg shadow-md min-w-[150px] text-center">
                        <span class="block text-gray-400">Profit</span>
                        <span class="text-blue-600 font-bold text-lg">RM<?php echo number_format($net_profit, 2); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="flex space-x-2 mt-4 md:mt-0">
                <button id="vendor-order-btn" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200">
                    Vendor Order
                </button>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <button id="admin-panel-btn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200">
                        Admin Panel
                    </button>
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200">
                            Logout
                        </button>
                    </form>
                <?php else: ?>
                    <button id="login-btn" class="bg-gray-700 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded-lg shadow-md transition-colors duration-200">
                        Admin Login
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <?php if (!empty($today_pre_orders)): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg shadow-md mb-6" role="alert">
                <p class="font-bold">Reminder: We have pre-orders available for pickup today. Please check the pre-order calendar below for details.</p>
            </div>
        <?php endif; ?>

        <main class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2 bg-white rounded-lg shadow-xl p-6 flex flex-col overflow-hidden">
                <h2 class="text-3xl font-bold text-gray-800 mb-4 flex-none">Menu</h2>
                
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 flex-none">
                    <input type="text" id="product-search" placeholder="Search products..." class="w-full md:w-1/2 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#b8860b] mb-4 md:mb-0">
                    <div id="category-tabs" class="flex flex-wrap space-x-2 text-sm font-medium">
                        <button class="tab-btn active" data-category="All">All</button>
                        <?php 
                            // Re-calculate categories to ensure they are up-to-date
                            $display_categories = array_unique(array_column($_SESSION['products'], 'category'));
                            sort($display_categories);
                        ?>
                        <?php foreach ($display_categories as $cat): ?>
                            <button class="tab-btn" data-category="<?php echo htmlspecialchars(strtolower($cat)); ?>">
                                <?php echo htmlspecialchars($cat); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="bg-gray-100 p-4 rounded-lg mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Special Orders</h3>
                        <div id="special-orders-list" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
                            <div id="custom-order-card" class="product-card cursor-pointer mb-6" onclick="document.getElementById('custom-order-modal').showModal()">
                                <img src="https://placehold.co/400x300/F0F0F0/555555?text=Custom+Order" alt="Custom Order" class="product-image">
                                <div class="product-info justify-center items-center text-center">
                                <span class="product-name">Create Custom Order</span>
                                <p class="text-sm text-gray-500">Add a unique item to the sales record</p>
                            </div>
                        </div>
                        </div>
                    </div>
                    <!-- Custom Order Modal -->
<dialog id="custom-order-modal" class="p-6 bg-white rounded-lg shadow-xl max-w-lg w-full">
    <h3 class="text-2xl font-bold mb-4">Create Custom Order</h3>
    <form id="custom-order-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <input type="hidden" name="action" value="add_custom_order">
        <div class="space-y-4">
            <div>
                <label for="custom-item-name" class="block text-sm font-medium text-gray-700">Item Name</label>
                <input type="text" id="custom-item-name" name="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2" required>
            </div>
            <div>
                <label for="custom-item-price" class="block text-sm font-medium text-gray-700">Price (RM)</label>
                <input type="number" id="custom-item-price" name="price" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2" required>
            </div>
            <div>
                <label for="custom-item-description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea id="custom-item-description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></textarea>
            </div>
        </div>
        <div class="mt-6 flex justify-end space-x-3">
            <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300" onclick="document.getElementById('custom-order-modal').close()">Cancel</button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Add Custom Order</button>
        </div>
    </form>
</dialog>
                    <div id="product-list" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6 p-2">
                        <?php foreach ($_SESSION['products'] as $product): ?>
                            <div class="product-card" data-id="<?php echo htmlspecialchars($product['id']); ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo htmlspecialchars($product['price']); ?>" data-category="<?php echo htmlspecialchars(strtolower($product['category'])); ?>" data-image-url="<?php echo htmlspecialchars($product['image_url']); ?>">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" onerror="this.onerror=null;this.src='https://placehold.co/400x300/CCCCCC/333333?text=No+Image';" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <div class="product-info">
                                    <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                    <span class="product-price">RM<?php echo number_format($product['price'], 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="md:col-span-1 bg-white rounded-lg shadow-xl p-4 md:p-6 flex flex-col md:sticky md:top-6" style="height: calc(100vh - 6rem);">
    <h2 class="text-3xl font-bold text-gray-800 mb-4 flex-none">Current Order</h2>
    
    <form id="main-order-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="h-full flex flex-col">
        <input type="hidden" name="action" id="order-action-input" value="place_order">
        <input type="hidden" name="total_amount" id="total_amount_input">
        <input type="hidden" name="order_items" id="order_items_input">

        <div class="flex-none mb-4 p-4 bg-gray-100 rounded-lg">
            <h3 class="text-xl font-semibold mb-2">Order Type</h3>
            <div class="flex space-x-4">
                <label class="inline-flex items-center">
                    <input type="radio" name="order_type" value="walk_in" checked class="form-radio text-[#b8860b]">
                    <span class="ml-2">Walk-in</span>
                </label>
                <label class="inline-flex items-center">
                    <input type="radio" name="order_type" value="pre_order" class="form-radio text-purple-500">
                    <span class="ml-2">Pre-order</span>
                </label>
            </div>
            <div id="pre-order-details" class="hidden mt-4 space-y-3">
                <div>
                    <label for="customer-name-input" class="block text-gray-700 text-sm font-medium mb-1">Customer Name</label>
                    <input type="text" name="customer_name" id="customer-name-input" placeholder="Enter customer name" class="w-full p-2 border rounded-lg">
                </div>
                <div>
                    <label for="customer-contact-input" class="block text-gray-700 text-sm font-medium mb-1">Contact No.</label>
                    <input type="tel" name="customer_contact" id="customer-contact-input" placeholder="e.g., 012-3456789" class="w-full p-2 border rounded-lg">
                </div>
                <div class="flex items-center space-x-2">
                    <input type="date" name="pickup_date" id="pickup-date-input" class="w-full p-2 border rounded-lg">
                    <input type="time" name="pickup_time" id="pickup-time-input" class="w-full p-2 border rounded-lg">
                </div>
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="delivery_type" value="pickup" checked class="form-radio text-purple-500">
                        <span class="ml-2">Pickup</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="delivery_type" value="delivery" class="form-radio text-purple-500">
                        <span class="ml-2">Delivery</span>
                    </label>
                </div>
            </div>
        </div>

        <div id="order-list" class="flex-1 overflow-y-auto space-y-4">
            <p id="empty-cart-message" class="text-gray-400 text-center mt-8">Click on menu items to add them to the order.</p>
        </div>
    
        <div class="mt-auto pt-4 border-t border-gray-200 flex-none">
            <div class="flex justify-between font-bold text-2xl mb-4">
                <span>Total:</span>
                <span id="order-total">RM0.00</span>
            </div>
            <button type="submit" id="place-order-btn" class="w-full bg-[#b8860b] hover:bg-[#a0740a] text-white font-bold py-4 rounded-lg transition-colors duration-200 shadow-md">
                Place Order
            </button>
            <button type="button" id="clear-order-btn" class="w-full mt-2 bg-gray-400 hover:bg-gray-500 text-white font-bold py-3 rounded-lg transition-colors duration-200 shadow-md">
                Clear Order
            </button>
        </div>
    </form>
</div>
        
        <div class="mt-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Pre-Order Calendar</h2>
            <div class="bg-white rounded-lg shadow-xl p-6">
                <div id="pre-order-calendar"></div>
            </div>
        </div>
        
    </div>

    <!-- MODALS SECTION -->
    <div id="login-modal" class="fixed inset-0 modal-overlay hidden justify-center items-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-sm w-full modal-content">
            <h3 class="text-xl font-bold mb-4">Admin Login</h3>
            <form id="login-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <input type="hidden" name="action" value="login">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-medium mb-1">Username</label>
                    <input type="text" id="username" name="username" class="w-full p-2 border rounded-lg" required>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 font-medium mb-1">Password</label>
                    <input type="password" id="password" name="password" class="w-full p-2 border rounded-lg" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="close-login-modal-btn" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Login</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="admin-modal" class="fixed inset-0 modal-overlay hidden justify-center items-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-6xl w-full h-5/6 flex flex-col modal-content">
            <h3 class="text-3xl font-bold mb-4">Admin Panel</h3>
            <div class="flex space-x-4 mb-4 border-b border-gray-200 flex-none overflow-x-auto">
                <button data-tab="products" class="admin-tab px-4 py-2 text-sm font-medium active whitespace-nowrap">Products</button>
                <button data-tab="vendors" class="admin-tab px-4 py-2 text-sm font-medium whitespace-nowrap">Vendors</button>
                <button data-tab="sales" class="admin-tab px-4 py-2 text-sm font-medium whitespace-nowrap">Sales Records</button>
                <button data-tab="pre-orders" class="admin-tab px-4 py-2 text-sm font-medium whitespace-nowrap">Pre-Orders</button>
                <button data-tab="completed-pre-orders" class="admin-tab px-4 py-2 text-sm font-medium whitespace-nowrap">Completed Pre-Orders</button>
                <button data-tab="expenses" class="admin-tab px-4 py-2 text-sm font-medium whitespace-nowrap">Expense Records</button>
            </div>

            <div class="flex-1 overflow-y-auto">
                <div id="products-tab" class="tab-content grid grid-cols-1 md:grid-cols-2 gap-8 h-full">
                    <div class="p-6 border rounded-lg h-full overflow-y-auto">
                        <h4 class="text-xl font-semibold mb-4" id="admin-form-title">Add New Product</h4>
                        <form id="product-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                            <input type="hidden" name="action" id="product-form-action" value="add_product">
                            <input type="hidden" id="product-id" name="id">
                            <div class="mb-4">
                                <label for="product-name" class="block text-gray-700 font-medium mb-1">Product Name</label>
                                <input type="text" id="product-name" name="name" class="w-full p-2 border rounded-lg" required>
                            </div>
                            <div class="mb-4">
                                <label for="product-price" class="block text-gray-700 font-medium mb-1">Price</label>
                                <input type="number" id="product-price" name="price" step="0.01" class="w-full p-2 border rounded-lg" required>
                            </div>
                            <div class="mb-4">
                                <label for="product-category" class="block text-gray-700 font-medium mb-1">Category</label>
                                <input list="product-categories-list" id="product-category" name="category" class="w-full p-2 border rounded-lg" placeholder="Select or type a new category" required>
                                <datalist id="product-categories-list">
                                    <?php foreach ($display_categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="mb-4">
                                <label for="product-image-url" class="block text-gray-700 font-medium mb-1">Image URL</label>
                                <input type="text" id="product-image-url" name="image_url" class="w-full p-2 border rounded-lg" placeholder="e.g. https://placehold.co/400x300">
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button type="button" id="cancel-edit-btn" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hidden hover:bg-gray-300">Cancel</button>
                                <button type="submit" id="submit-product-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Add Product</button>
                            </div>
                        </form>
                    </div>
                    <div class="p-6 border rounded-lg overflow-y-auto">
                        <h4 class="text-xl font-semibold mb-4">Existing Products</h4>
                        <table class="w-full min-w-[500px] text-left table-auto">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2">ID</th>
                                    <th class="p-2">Name</th>
                                    <th class="p-2">Price (RM)</th>
                                    <th class="p-2">Category</th>
                                    <th class="p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="product-list-body">
                                <?php foreach ($_SESSION['products'] as $product): ?>
                                    <tr>
                                        <td class="p-2 text-sm"><?php echo htmlspecialchars($product['id']); ?></td>
                                        <td class="p-2 text-sm"><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td class="p-2 text-sm">RM<?php echo number_format($product['price'], 2); ?></td>
                                        <td class="p-2 text-sm"><?php echo htmlspecialchars($product['category']); ?></td>
                                        <td class="p-2 text-sm flex space-x-2">
                                            <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" class="text-blue-500 hover:text-blue-700">Edit</button>
                                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                                <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
<div id="vendors-tab" class="tab-content hidden grid grid-cols-1 md:grid-cols-2 gap-8 h-full">
    <div class="p-6 border rounded-lg h-full overflow-y-auto">
        <h4 class="text-xl font-semibold mb-4" id="vendor-form-title">Add New Vendor</h4>
        <form id="vendor-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <input type="hidden" name="action" id="vendor-form-action" value="add_vendor">
            <input type="hidden" id="vendor-id" name="id">
            <div class="mb-4">
                <label for="vendor-name" class="block text-gray-700 font-medium mb-1">Vendor Name</label>
                <input type="text" id="vendor-name" name="name" class="w-full p-2 border rounded-lg" required>
            </div>
            <div class="mb-4">
                <label for="vendor-contact-person" class="block text-gray-700 font-medium mb-1">Contact Person</label>
                <input type="text" id="vendor-contact-person" name="contact_person" class="w-full p-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label for="vendor-phone" class="block text-gray-700 font-medium mb-1">Phone Number</label>
                <input type="tel" id="vendor-phone" name="phone" class="w-full p-2 border rounded-lg">
            </div>
            <div class="mb-4">
                <label for="vendor-email" class="block text-gray-700 font-medium mb-1">Email</label>
                <input type="email" id="vendor-email" name="email" class="w-full p-2 border rounded-lg">
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" id="cancel-edit-vendor-btn" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hidden hover:bg-gray-300">Cancel</button>
                <button type="submit" id="submit-vendor-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Add Vendor</button>
            </div>
        </form>
    </div>
    <div class="p-6 border rounded-lg overflow-y-auto">
        <h4 class="text-xl font-semibold mb-4">Existing Vendors</h4>
        <table class="w-full min-w-[500px] text-left table-auto">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-2">ID</th>
                    <th class="p-2">Name</th>
                    <th class="p-2">Contact</th>
                    <th class="p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['vendors'] as $vendor): ?>
                    <tr>
                        <td class="p-2 text-sm"><?php echo htmlspecialchars($vendor['id']); ?></td>
                        <td class="p-2 text-sm">
                            <?php echo htmlspecialchars($vendor['name']); ?><br>
                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($vendor['contact_person']); ?></span>
                        </td>
                        <td class="p-2 text-sm">
                            <?php echo htmlspecialchars($vendor['phone']); ?><br>
                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($vendor['email']); ?></span>
                        </td>
                        <td class="p-2 text-sm flex space-x-2">
                            <button onclick="editVendor(<?php echo htmlspecialchars(json_encode($vendor)); ?>)" class="text-blue-500 hover:text-blue-700">Edit</button>
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                                <input type="hidden" name="action" value="delete_vendor">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($vendor['id']); ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4 class="text-xl font-semibold mt-6 mb-4">Vendor Orders</h4>
        <table class="w-full min-w-[500px] text-left table-auto">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-2">ID</th>
                    <th class="p-2">Vendor</th>
                    <th class="p-2">Amount (RM)</th>
                    <th class="p-2">Date</th>
                    <th class="p-2">Status</th>
                    <th class="p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($_SESSION['vendor_orders'] as $order): ?>
                    <tr>
                        <td class="p-2 text-sm"><?php echo htmlspecialchars($order['id']); ?></td>
                        <td class="p-2 text-sm">
                            <?php
                                $vendor_id = $order['vendor_id'] ?? null;
                                $vendor_name = 'N/A';
                                if ($vendor_id !== null) {
                                    $vendor_key = array_search($vendor_id, array_column($_SESSION['vendors'], 'id'));
                                    if ($vendor_key !== false) {
                                        $vendor_name = $_SESSION['vendors'][$vendor_key]['name'];
                                    }
                                }
                                echo htmlspecialchars($vendor_name);
                            ?>
                        </td>
                        <td class="p-2 text-sm">RM<?php echo number_format($order['amount'] ?? 0, 2); ?></td>
                        <td class="p-2 text-sm"><?php echo htmlspecialchars($order['order_date'] ?? 'N/A'); ?></td>
                        <td class="p-2 text-sm">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                <?php echo ($order['status'] ?? '') === 'pending' ? 'bg-yellow-200 text-yellow-800' : 'bg-green-200 text-green-800'; ?>">
                                <?php echo htmlspecialchars(ucfirst($order['status'] ?? 'N/A')); ?>
                            </span>
                        </td>
                        <td class="p-2 text-sm">
                            <div class="flex items-center space-x-2">
                                <?php if (($order['status'] ?? '') === 'pending'): ?>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="flex items-center space-x-2">
                                        <input type="hidden" name="action" value="confirm_vendor_order">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                        <input type="number" name="confirmed_amount" step="0.01" value="<?php echo htmlspecialchars($order['amount']); ?>" class="w-24 p-1 border rounded-md text-sm" required>
                                        <button type="submit" class="text-green-500 hover:text-green-700">Confirm</button>
                                    </form>
                                <?php endif; ?>
                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this vendor order?');">
                                    <input type="hidden" name="action" value="delete_vendor_order">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                    <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

                <div id="sales-tab" class="tab-content hidden h-full">
                    <h4 class="text-xl font-semibold mb-4">Sales Records</h4>
                    <div class="overflow-x-auto h-full">
                        <table class="w-full min-w-[600px] text-left table-auto">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2">ID</th>
                                    <th class="p-2">Date/Time</th>
                                    <th class="p-2">Items</th>
                                    <th class="p-2">Total (RM)</th>
                                    <th class="p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
    <?php foreach ($_SESSION['sales'] as $sale): ?>
        <tr>
            <td class="p-2 text-sm"><?php echo htmlspecialchars($sale['id'] ?? 'N/A'); ?></td>
            <td class="p-2 text-sm"><?php echo htmlspecialchars($sale['order_date'] ?? 'N/A'); ?></td>
            <td class="p-2 text-sm">
                <ul class="list-disc list-inside">
                    <?php 
                    // Check if 'order_items' key exists and is an array before iterating
                    if (isset($sale['order_items']) && is_array($sale['order_items'])):
                        foreach ($sale['order_items'] as $item): ?>
                            <li><?php echo htmlspecialchars($item['name'] ?? 'N/A'); ?> (x<?php echo htmlspecialchars($item['quantity'] ?? 'N/A'); ?>)</li>
                        <?php endforeach; 
                    else: ?>
                        <li>No items available</li>
                    <?php endif; ?>
                </ul>
            </td>
            <td class="p-2 text-sm">RM<?php echo number_format($sale['total_amount'] ?? 0, 2); ?></td>
            <td class="p-2 text-sm">
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure you want to void this order?');">
                    <input type="hidden" name="action" value="void_order">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($sale['id'] ?? ''); ?>">
                    <button type="submit" class="text-red-500 hover:text-red-700">Void</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                        </table>
                    </div>
                </div>

                <div id="pre-orders-tab" class="tab-content hidden h-full">
                    <h4 class="text-xl font-semibold mb-4">Upcoming Pre-Orders</h4>
                    <div class="overflow-x-auto h-full">
                        <table class="w-full min-w-[700px] text-left table-auto">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2">ID</th>
                                    <th class="p-2">Customer</th>
                                    <th class="p-2">Date/Time</th>
                                    <th class="p-2">Items</th>
                                    <th class="p-2">Total (RM)</th>
                                    <th class="p-2">Status</th>
                                    <th class="p-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($_SESSION['pre_orders'])): ?>
                                    <tr><td colspan="7" class="text-center text-gray-400 py-4">No upcoming pre-orders.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($_SESSION['pre_orders'] as $order): ?>
                                        <tr>
                                            <td class="p-2 text-sm"><?php echo htmlspecialchars($order['id']); ?></td>
                                            <td class="p-2 text-sm"><?php echo htmlspecialchars($order['customer_name']); ?><br><span class="text-gray-500 text-xs"><?php echo htmlspecialchars($order['customer_contact']); ?></span></td>
                                            <td class="p-2 text-sm"><?php echo htmlspecialchars($order['pickup_date']); ?> @ <?php echo htmlspecialchars($order['pickup_time']); ?></td>
                                            <td class="p-2 text-sm">
                                                <ul class="list-disc list-inside">
                                                    <?php foreach ($order['order_items'] as $item): ?>
                                                        <li><?php echo htmlspecialchars($item['name']); ?> (x<?php echo htmlspecialchars($item['quantity']); ?>)</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                            <td class="p-2 text-sm">RM<?php echo number_format($order['total_amount'], 2); ?></td>
                                            <td class="p-2 text-sm capitalize"><?php echo htmlspecialchars($order['delivery_type']); ?></td>
                                            <td class="p-2 text-sm flex space-x-2">
                                                <button onclick="openCompleteOrderModal(<?php echo htmlspecialchars(json_encode($order)); ?>)" class="text-green-500 hover:text-green-700">Complete</button>
                                                <button onclick="openEditPreOrderModal(<?php echo htmlspecialchars(json_encode($order)); ?>)" class="text-blue-500 hover:text-blue-700">Edit</button>
                                                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure you want to void this pre-order?');">
                                                    <input type="hidden" name="action" value="void_pre_order">
                                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                                    <button type="submit" class="text-red-500 hover:text-red-700">Void</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="completed-pre-orders-tab" class="tab-content hidden h-full">
                    <h4 class="text-xl font-semibold mb-4">Completed Pre-Orders</h4>
                    <div class="overflow-x-auto h-full">
                        <table class="w-full min-w-[700px] text-left table-auto">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2">ID</th>
                                    <th class="p-2">Customer</th>
                                    <th class="p-2">Date/Time</th>
                                    <th class="p-2">Items</th>
                                    <th class="p-2">Total (RM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($_SESSION['completed_pre_orders'])): ?>
                                    <tr><td colspan="5" class="text-center text-gray-400 py-4">No completed pre-orders.</td></tr>
                                <?php else: ?>
                                    <?php foreach (array_reverse($_SESSION['completed_pre_orders']) as $order): ?>
                                        <tr>
                                            <td class="p-2 text-sm"><?php echo htmlspecialchars($order['id']); ?></td>
                                            <td class="p-2 text-sm"><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td class="p-2 text-sm"><?php echo htmlspecialchars($order['completion_date']); ?></td>
                                            <td class="p-2 text-sm">
                                                <ul class="list-disc list-inside">
                                                    <?php foreach ($order['order_items'] as $item): ?>
                                                        <li><?php echo htmlspecialchars($item['name']); ?> (x<?php echo htmlspecialchars($item['quantity']); ?>)</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                            <td class="p-2 text-sm">RM<?php echo number_format($order['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="expenses-tab" class="tab-content hidden h-full">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 h-full">
                        <div class="p-6 border rounded-lg h-full overflow-y-auto">
                            <h4 class="text-xl font-semibold mb-4">Add New Expense</h4>
                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                                <input type="hidden" name="action" value="add_expense">
                                <div class="mb-4">
                                    <label for="expense-description" class="block text-gray-700 font-medium mb-1">Description</label>
                                    <input type="text" id="expense-description" name="description" class="w-full p-2 border rounded-lg" required>
                                </div>
                                <div class="mb-4">
                                    <label for="expense-amount" class="block text-gray-700 font-medium mb-1">Amount (RM)</label>
                                    <input type="number" id="expense-amount" name="amount" step="0.01" class="w-full p-2 border rounded-lg" required>
                                </div>
                                <div class="mb-4">
                                    <label for="expense-date" class="block text-gray-700 font-medium mb-1">Date</label>
                                    <input type="date" id="expense-date" name="date" class="w-full p-2 border rounded-lg" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Add Expense</button>
                                </div>
                            </form>
                        </div>
                        <div class="p-6 border rounded-lg overflow-y-auto">
                            <h4 class="text-xl font-semibold mb-4">Existing Expenses</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[400px] text-left table-auto">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="p-2">ID</th>
                                            <th class="p-2">Description</th>
                                            <th class="p-2">Amount (RM)</th>
                                            <th class="p-2">Date</th>
                                            <th class="p-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($_SESSION['expenses'])): ?>
                                            <tr><td colspan="5" class="text-center text-gray-400 py-4">No expenses recorded.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($_SESSION['expenses'] as $expense): ?>
                                                <tr>
                                                    <td class="p-2 text-sm"><?php echo htmlspecialchars($expense['id']); ?></td>
                                                    <td class="p-2 text-sm"><?php echo htmlspecialchars($expense['description']); ?></td>
                                                    <td class="p-2 text-sm">RM<?php echo number_format($expense['amount'], 2); ?></td>
                                                    <td class="p-2 text-sm"><?php echo htmlspecialchars($expense['date']); ?></td>
                                                    <td class="p-2 text-sm">
                                                        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                                            <input type="hidden" name="action" value="delete_expense">
                                                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($expense['id']); ?>">
                                                            <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex-none mt-4 text-right">
                <button type="button" id="close-admin-modal-btn" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Close</button>
            </div>
        </div>
    </div>

    <div id="vendor-order-modal" class="fixed inset-0 modal-overlay hidden justify-center items-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-6xl w-full h-5/6 flex flex-col modal-content">
            <h3 class="text-3xl font-bold mb-4">Vendor Orders</h3>
            <div class="flex-1 overflow-y-auto grid grid-cols-1 md:grid-cols-2 gap-8 h-full">
                <div class="p-6 border rounded-lg h-full flex flex-col overflow-hidden">
    <h4 class="text-xl font-semibold mb-4">Add New Vendor Order</h4>
    <form id="vendor-order-form" action="/bakery-pos/bakery-pos.php" method="post" class="p-6 border rounded-lg overflow-y-auto">
        <input type="hidden" name="action" id="vendor-order-action" value="add_vendor_order">
        <input type="hidden" name="id" id="vendor-order-id">
        <input type="hidden" name="total_amount" id="vendor-order-total-input">
        <input type="hidden" name="order_items" id="vendor-order-items-input">
        <div class="mb-4">
            <label for="vendor-order-vendor" class="block text-gray-700 font-medium mb-1">Vendor</label>
            <select id="vendor-order-vendor" name="vendor_id" class="w-full p-2 border rounded-lg" required="">
                <option value="">Select a vendor</option>
                <option value="1">
                    MUHAMAD SHAFIQ SHAMSUL
                </option>
            </select>
        </div>
        <div class="mb-4">
            <label for="vendor-order-date" class="block text-gray-700 font-medium mb-1">Order Date</label>
            <input type="date" id="vendor-order-date" name="order_date" class="w-full p-2 border rounded-lg" value="2025-08-24" required="">
        </div>
        <div class="flex-grow flex flex-col border border-gray-200 rounded-lg p-4 mb-4">
    <h5 class="text-lg font-semibold mb-2">Order Items</h5>
    <div class="flex-none mb-4">
        <div class="flex flex-col space-y-2"> 
            <select id="vendor-product-select" class="flex-grow p-2 border rounded-lg">
                <option value="">Select an item to add</option>
                <option value="1" data-name="Brownies Mix Premium Toppings" data-price="60">
                    Brownies Mix Premium Toppings (RM60.00)
                </option>
                <option value="2" data-name="Espresso" data-price="3">
                    Espresso (RM3.00)
                </option>
                <option value="5" data-name="Brownies Triple Chocolate" data-price="55">
                    Brownies Triple Chocolate (RM55.00)
                </option>
                <option value="7" data-name="Brownies Kedut (No Topping)" data-price="45">
                    Brownies Kedut (No Topping) (RM45.00)
                </option>
                <option value="8" data-name="Bento Cake" data-price="30">
                    Bento Cake (RM30.00)
                </option>
                <option value="9" data-name="BROWNIES DECO/TOWER" data-price="60">
                    BROWNIES DECO/TOWER (RM60.00)
                </option>
                <option value="10" data-name="Slice Cakes" data-price="10">
                    Slice Cakes (RM10.00)
                </option>
                <option value="11" data-name="Kek Batik Ganache" data-price="40">
                    Kek Batik Ganache (RM40.00)
                </option>
                <option value="12" data-name="7 inch Birthday Cake" data-price="80">
                    7 inch Birthday Cake (RM80.00)
                </option>
                <option value="13" data-name="Mini Cupcake" data-price="1">
                    Mini Cupcake (RM1.00)
                </option>
            </select>
            <button type="button" id="add-selected-item-btn" class="w-full bg-gray-200 text-gray-700 p-2 rounded-lg hover:bg-gray-300">Add</button>
        </div>
        <div class="mt-2">
            <button type="button" id="show-add-product-modal-btn" class="w-full bg-purple-600 text-white p-2 rounded-lg hover:bg-purple-700 text-sm">
                ...or Add New Product to System
            </button>
        </div>
    </div>
    <div id="vendor-order-items" class="flex-grow overflow-y-scroll space-y-2">
        <div class="flex justify-between items-center bg-gray-50 p-2 rounded-lg">
            <div>
                <h5 class="font-semibold">Slice Cakes</h5>
                <p class="text-sm text-gray-600">RM10.00 each</p>
            </div>
            <div class="flex items-center space-x-2">
                <input type="number" value="3" min="1" onchange="updateVendorQuantity(0, this.value)" class="w-16 p-1 text-center border rounded-lg">
                <span class="font-bold w-20 text-right">RM30.00</span>
                <button type="button" class="text-red-500 hover:text-red-700 ml-2" onclick="removeVendorItem(0)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <div class="flex justify-between font-bold text-lg mt-4 border-t pt-2">
        <span>Total:</span>
        <span id="vendor-order-total">RM30.00</span>
    </div>
</div>
        <div class="flex justify-end space-x-2">
            <button type="button" id="cancel-vendor-order-edit-btn" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hidden hover:bg-gray-300">Cancel</button>
            <button type="submit" id="submit-vendor-order-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Add Vendor Order</button>
        </div>
    </form>
</div>
                <div class="p-6 border rounded-lg overflow-y-auto">
                    <h4 class="text-xl font-semibold mb-4">Existing Vendor Orders</h4>
                    <table class="w-full min-w-[500px] text-left table-auto">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2">ID</th>
                                <th class="p-2">Vendor</th>
                                <th class="p-2">Date</th>
                                <th class="p-2">Amount (RM)</th>
                                <th class="p-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($_SESSION['vendor_orders'])): ?>
                                <tr><td colspan="5" class="text-center text-gray-400 py-4">No vendor orders recorded.</td></tr>
                            <?php else: ?>
                                <?php
                                $vendors_map = array_column($_SESSION['vendors'], 'name', 'id');
                                foreach (array_reverse($_SESSION['vendor_orders']) as $order): ?>
                                    <tr>
                                        <td class="p-2 text-sm"><?php echo htmlspecialchars($order['id']); ?></td>
                                        <td class="p-2 text-sm"><?php echo htmlspecialchars($vendors_map[$order['vendor_id']] ?? 'N/A'); ?></td>
                                        <td class="p-2 text-sm"><?php echo htmlspecialchars($order['order_date']); ?></td>
                                        <td class="p-2 text-sm">RM<?php echo number_format($order['amount'], 2); ?></td>
                                        <td class="p-2 text-sm flex space-x-2">
                                            <button onclick="editVendorOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)" class="text-blue-500 hover:text-blue-700">Edit</button>
                                            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this vendor order? This will also remove the associated expense.');">
                                                <input type="hidden" name="action" value="delete_vendor_order">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($order['id']); ?>">
                                                <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="flex-none mt-4 text-right">
                <button type="button" id="close-vendor-order-modal-btn" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Close</button>
            </div>
        </div>
    </div>

    <div id="complete-pre-order-modal" class="fixed inset-0 modal-overlay hidden justify-center items-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-lg w-full modal-content">
            <h3 class="text-xl font-bold mb-4">Complete Pre-Order</h3>
            <p>Confirm order details for **Pre-Order #<span id="complete-order-id"></span>**</p>
            <p class="font-bold my-2">Total Amount: <span id="complete-order-total" class="text-green-600"></span></p>
            <form id="complete-pre-order-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <input type="hidden" name="action" value="complete_pre_order">
                <input type="hidden" name="id" id="complete-pre-order-id-input">
                <input type="hidden" name="payment_amount" id="complete-payment-amount-input">
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeModal('complete-pre-order-modal')" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Mark as Completed</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-pre-order-modal" class="fixed inset-0 modal-overlay hidden justify-center items-center z-50">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-lg w-full modal-content">
            <h3 class="text-xl font-bold mb-4">Edit Pre-Order</h3>
            <form id="edit-pre-order-form" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <input type="hidden" name="action" value="update_pre_order">
                <input type="hidden" name="id" id="edit-pre-order-id-input">
                <div class="space-y-4">
                    <div>
                        <label for="edit-customer-name-input" class="block text-gray-700 text-sm font-medium mb-1">Customer Name</label>
                        <input type="text" name="customer_name" id="edit-customer-name-input" class="w-full p-2 border rounded-lg">
                    </div>
                    <div>
                        <label for="edit-customer-contact-input" class="block text-gray-700 text-sm font-medium mb-1">Contact No.</label>
                        <input type="tel" name="customer_contact" id="edit-customer-contact-input" class="w-full p-2 border rounded-lg">
                    </div>
                    <div>
                        <label for="edit-pickup-date-input" class="block text-gray-700 text-sm font-medium mb-1">Pickup Date</label>
                        <input type="date" name="pickup_date" id="edit-pickup-date-input" class="w-full p-2 border rounded-lg">
                    </div>
                    <div>
                        <label for="edit-pickup-time-input" class="block text-gray-700 text-sm font-medium mb-1">Pickup Time</label>
                        <input type="time" name="pickup_time" id="edit-pickup-time-input" class="w-full p-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-medium mb-1">Delivery Type</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="delivery_type" value="pickup" id="edit-pickup-radio" class="form-radio text-purple-500">
                                <span class="ml-2">Pickup</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="delivery_type" value="delivery" id="edit-delivery-radio" class="form-radio text-purple-500">
                                <span class="ml-2">Delivery</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-2 mt-4">
                    <button type="button" onclick="closeModal('edit-pre-order-modal')" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add New Product Modal (from Vendor Order) -->
    <div id="add-product-from-vendor-modal" class="fixed inset-0 modal-overlay hidden justify-center items-center z-[60]">
        <div class="bg-white p-8 rounded-lg shadow-xl max-w-lg w-full modal-content">
            <h3 class="text-xl font-bold mb-4">Add New Product to System</h3>
            <form id="add-product-from-vendor-form">
                <div class="mb-4">
                    <label for="new-product-name" class="block text-gray-700 font-medium mb-1">Product Name</label>
                    <input type="text" id="new-product-name" name="name" class="w-full p-2 border rounded-lg" required>
                </div>
                <div class="mb-4">
                    <label for="new-product-price" class="block text-gray-700 font-medium mb-1">Price</label>
                    <input type="number" id="new-product-price" name="price" step="0.01" class="w-full p-2 border rounded-lg" required>
                </div>
                <div class="mb-4">
                    <label for="new-product-category" class="block text-gray-700 font-medium mb-1">Category</label>
                    <input list="product-categories-list" id="new-product-category" name="category" class="w-full p-2 border rounded-lg" placeholder="Select or type a new category" required>
                </div>
                 <div class="mb-4">
                    <label for="new-product-image-url" class="block text-gray-700 font-medium mb-1">Image URL</label>
                    <input type="text" id="new-product-image-url" name="image_url" class="w-full p-2 border rounded-lg" placeholder="Optional">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="cancel-add-product-from-vendor-btn" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Add Product</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- Constant Element Selectors ---
            const productList = document.getElementById('product-list');
            const productSearch = document.getElementById('product-search');
            const categoryTabs = document.getElementById('category-tabs');
            const orderList = document.getElementById('order-list');
            const orderTotalSpan = document.getElementById('order-total');
            const emptyCartMessage = document.getElementById('empty-cart-message');
            const loginBtn = document.getElementById('login-btn');
            const loginModal = document.getElementById('login-modal');
            const closeLoginModalBtn = document.getElementById('close-login-modal-btn');
            const adminPanelBtn = document.getElementById('admin-panel-btn');
            const adminModal = document.getElementById('admin-modal');
            const closeAdminModalBtn = document.getElementById('close-admin-modal-btn');
            const adminTabs = document.querySelectorAll('.admin-tab');
            const adminTabContents = document.querySelectorAll('.tab-content');
            const clearOrderBtn = document.getElementById('clear-order-btn');
            const placeOrderForm = document.getElementById('main-order-form');
            const placeOrderBtn = document.getElementById('place-order-btn');
            const orderTypeRadios = document.querySelectorAll('input[name="order_type"]');
            const preOrderDetails = document.getElementById('pre-order-details');
            const customOrderCard = document.getElementById('custom-order-card');
            const productForm = document.getElementById('product-form');
            const adminFormTitle = document.getElementById('admin-form-title');
            const productFormAction = document.getElementById('product-form-action');
            const submitProductBtn = document.getElementById('submit-product-btn');
            const cancelEditBtn = document.getElementById('cancel-edit-btn');
            const completeOrderModal = document.getElementById('complete-pre-order-modal');
            const editPreOrderModal = document.getElementById('edit-pre-order-modal');
            const vendorForm = document.getElementById('vendor-form');
            const vendorFormTitle = document.getElementById('vendor-form-title');
            const vendorFormAction = document.getElementById('vendor-form-action');
            const submitVendorBtn = document.getElementById('submit-vendor-btn');
            const cancelEditVendorBtn = document.getElementById('cancel-edit-vendor-btn');
            const vendorOrderForm = document.getElementById('vendor-order-form');
            const vendorOrderItemsList = document.getElementById('vendor-order-items');
            const vendorOrderTotalSpan = document.getElementById('vendor-order-total');
            const addSelectedItemBtn = document.getElementById('add-selected-item-btn');
            const vendorProductSelect = document.getElementById('vendor-product-select');
            const cancelVendorOrderEditBtn = document.getElementById('cancel-vendor-order-edit-btn');
            const submitVendorOrderBtn = document.getElementById('submit-vendor-order-btn');
            const showAddProductModalBtn = document.getElementById('show-add-product-modal-btn');
            const addProductFromVendorModal = document.getElementById('add-product-from-vendor-modal');
            const cancelAddProductFromVendorBtn = document.getElementById('cancel-add-product-from-vendor-btn');
            const addProductFromVendorForm = document.getElementById('add-product-from-vendor-form');
            
            // --- New Elements for Public Vendor Order ---
            const vendorOrderBtn = document.getElementById('vendor-order-btn');
            const vendorOrderModal = document.getElementById('vendor-order-modal');
            const closeVendorOrderModalBtn = document.getElementById('close-vendor-order-modal-btn');

            let cart = [];
            let vendorOrderItems = [];

            // --- Date & Time Display ---
            function updateDateTime() {
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
                document.getElementById('current-datetime').textContent = now.toLocaleDateString('en-US', options);
            }
            setInterval(updateDateTime, 1000);
            updateDateTime();

            // --- Modal Functions ---
            function openModal(modal) {
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            }

            window.closeModal = (modalId) => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                }
            }

            loginBtn?.addEventListener('click', () => openModal(loginModal));
            closeLoginModalBtn?.addEventListener('click', () => closeModal('login-modal'));
            adminPanelBtn?.addEventListener('click', () => openModal(adminModal));
            closeAdminModalBtn?.addEventListener('click', () => closeModal('admin-modal'));
            vendorOrderBtn?.addEventListener('click', () => openModal(vendorOrderModal));
            closeVendorOrderModalBtn?.addEventListener('click', () => closeModal('vendor-order-modal'));


            // --- POS Logic ---
            const updateCartDisplay = () => {
                orderList.innerHTML = '';
                if (cart.length === 0) {
                    emptyCartMessage.classList.remove('hidden');
                    placeOrderBtn.disabled = true;
                } else {
                    emptyCartMessage.classList.add('hidden');
                    placeOrderBtn.disabled = false;
                    cart.forEach((item, index) => {
                        const itemElement = document.createElement('div');
                        itemElement.classList.add('flex', 'justify-between', 'items-center', 'bg-gray-50', 'p-4', 'rounded-lg', 'shadow-sm');
                        itemElement.innerHTML = `
                            <div>
                                <h4 class="font-bold text-lg">${item.name}</h4>
                                <p class="text-sm text-gray-600">RM${item.price.toFixed(2)} each</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-2 rounded-full" onclick="updateQuantity(${index}, -1)">-</button>
                                <span class="font-bold w-6 text-center">${item.quantity}</span>
                                <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-2 rounded-full" onclick="updateQuantity(${index}, 1)">+</button>
                                <span class="font-bold text-lg w-20 text-right">RM${(item.price * item.quantity).toFixed(2)}</span>
                                <button type="button" class="text-red-500 hover:text-red-700 ml-2" onclick="removeItem(${index})">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        `;
                        orderList.appendChild(itemElement);
                    });
                }
                updateTotal();
            };

            const updateTotal = () => {
                const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                orderTotalSpan.textContent = `RM${total.toFixed(2)}`;
            };

            window.updateQuantity = (index, change) => {
                cart[index].quantity += change;
                if (cart[index].quantity <= 0) {
                    cart.splice(index, 1);
                }
                updateCartDisplay();
            };

            window.removeItem = (index) => {
                cart.splice(index, 1);
                updateCartDisplay();
            };

            productList?.addEventListener('click', (e) => {
                const card = e.target.closest('.product-card');
                if (!card) return;

                const id = card.dataset.id;
                const name = card.dataset.name;
                const price = parseFloat(card.dataset.price);

                const existingItem = cart.find(item => item.id == id);
                if (existingItem) {
                    existingItem.quantity++;
                } else {
                    cart.push({ id, name, price, quantity: 1 });
                }
                updateCartDisplay();
            });


            clearOrderBtn?.addEventListener('click', () => {
                cart = [];
                updateCartDisplay();
            });

            placeOrderForm?.addEventListener('submit', (e) => {
                e.preventDefault();
                if (cart.length === 0) {
                    alert('Please add items to the order first.');
                    return;
                }

                const totalAmount = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                document.getElementById('total_amount_input').value = totalAmount.toFixed(2);
                document.getElementById('order_items_input').value = JSON.stringify(cart);

                const selectedOrderType = document.querySelector('input[name="order_type"]:checked').value;
                if (selectedOrderType === 'pre_order') {
                    const customerName = document.getElementById('customer-name-input').value;
                    const customerContact = document.getElementById('customer-contact-input').value;
                    const pickupDate = document.getElementById('pickup-date-input').value;
                    const pickupTime = document.getElementById('pickup-time-input').value;
                    if (!customerName || !customerContact || !pickupDate || !pickupTime) {
                        alert('Please fill in all customer and pre-order details.');
                        return;
                    }
                    document.getElementById('order-action-input').value = 'place_pre_order';
                } else {
                    document.getElementById('order-action-input').value = 'place_order';
                }

                e.target.submit();
            });

            // --- Search & Filter Logic ---
            productSearch?.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                document.querySelectorAll('#product-list .product-card').forEach(card => {
                    const name = card.dataset.name.toLowerCase();
                    if (name.includes(searchTerm)) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });

            categoryTabs?.addEventListener('click', (e) => {
                const target = e.target.closest('.tab-btn');
                if (!target) return;

                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                target.classList.add('active');

                const category = target.dataset.category.toLowerCase();
                document.querySelectorAll('#product-list .product-card').forEach(card => {
                    const cardCategory = card.dataset.category;
                    if (category === 'all' || cardCategory === category) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });

            // --- Admin Panel Logic ---
            adminTabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    const selectedTab = e.target.dataset.tab;
                    adminTabs.forEach(t => t.classList.remove('active'));
                    e.target.classList.add('active');
                    adminTabContents.forEach(content => {
                        if (content.id === `${selectedTab}-tab`) {
                            content.classList.remove('hidden');
                        } else {
                            content.classList.add('hidden');
                        }
                    });
                });
            });

            // --- Product Management Logic ---
            window.editProduct = (product) => {
                adminFormTitle.innerText = 'Edit Product';
                productFormAction.value = 'edit_product';
                submitProductBtn.innerText = 'Update Product';
                cancelEditBtn.classList.remove('hidden');

                document.getElementById('product-id').value = product.id;
                document.getElementById('product-name').value = product.name;
                document.getElementById('product-price').value = product.price;
                document.getElementById('product-category').value = product.category;
                document.getElementById('product-image-url').value = product.image_url;
            };

            cancelEditBtn?.addEventListener('click', () => {
                productForm.reset();
                adminFormTitle.innerText = 'Add New Product';
                productFormAction.value = 'add_product';
                submitProductBtn.innerText = 'Add Product';
                cancelEditBtn.classList.add('hidden');
            });
            
            // --- Vendor Management Logic ---
            window.editVendor = (vendor) => {
                vendorFormTitle.innerText = 'Edit Vendor';
                vendorFormAction.value = 'edit_vendor';
                submitVendorBtn.innerText = 'Update Vendor';
                cancelEditVendorBtn.classList.remove('hidden');

                document.getElementById('vendor-id').value = vendor.id;
                document.getElementById('vendor-name').value = vendor.name;
                document.getElementById('vendor-contact-person').value = vendor.contact_person;
                document.getElementById('vendor-phone').value = vendor.phone;
                document.getElementById('vendor-email').value = vendor.email;
            };

            cancelEditVendorBtn?.addEventListener('click', () => {
                vendorForm.reset();
                vendorFormTitle.innerText = 'Add New Vendor';
                vendorFormAction.value = 'add_vendor';
                submitVendorBtn.innerText = 'Add Vendor';
                cancelEditVendorBtn.classList.add('hidden');
            });
            
            // --- Vendor Order Management Logic ---
            const updateVendorOrderDisplay = () => {
                vendorOrderItemsList.innerHTML = '';
                if (vendorOrderItems.length === 0) {
                    vendorOrderItemsList.innerHTML = `<p class="text-gray-400 text-center">No items added yet.</p>`;
                } else {
                    vendorOrderItems.forEach((item, index) => {
                        const itemElement = document.createElement('div');
                        itemElement.classList.add('flex', 'justify-between', 'items-center', 'bg-gray-50', 'p-2', 'rounded-lg');
                        itemElement.innerHTML = `
                            <div>
                                <h5 class="font-semibold">${item.name}</h5>
                                <p class="text-sm text-gray-600">RM${item.price.toFixed(2)} each</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="number" value="${item.quantity}" min="1" onchange="updateVendorQuantity(${index}, this.value)" class="w-16 p-1 text-center border rounded-lg">
                                <span class="font-bold w-20 text-right">RM${(item.price * item.quantity).toFixed(2)}</span>
                                <button type="button" class="text-red-500 hover:text-red-700 ml-2" onclick="removeVendorItem(${index})">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        `;
                        vendorOrderItemsList.appendChild(itemElement);
                    });
                }
                updateVendorOrderTotal();
            };

            const updateVendorOrderTotal = () => {
                const total = vendorOrderItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                vendorOrderTotalSpan.textContent = `RM${total.toFixed(2)}`;
            };

            window.updateVendorQuantity = (index, newQuantity) => {
                const quantity = parseInt(newQuantity);
                if (isNaN(quantity) || quantity <= 0) {
                    removeVendorItem(index);
                } else {
                    vendorOrderItems[index].quantity = quantity;
                    updateVendorOrderDisplay();
                }
            };

            window.removeVendorItem = (index) => {
                vendorOrderItems.splice(index, 1);
                updateVendorOrderDisplay();
            };

            addSelectedItemBtn?.addEventListener('click', () => {
                const selectedOption = vendorProductSelect.options[vendorProductSelect.selectedIndex];
                const id = selectedOption.value;
                if (!id) return;
                
                const name = selectedOption.dataset.name;
                const price = parseFloat(selectedOption.dataset.price);

                const existingItem = vendorOrderItems.find(item => item.id == id);
                if (existingItem) {
                    existingItem.quantity++;
                } else {
                    vendorOrderItems.push({ id, name, price, quantity: 1 });
                }
                updateVendorOrderDisplay();
            });

            vendorOrderForm?.addEventListener('submit', (e) => {
                e.preventDefault();
                if (vendorOrderItems.length === 0) {
                    alert('Please add at least one item to the vendor order.');
                    return;
                }

                const totalAmount = vendorOrderItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                document.getElementById('vendor-order-total-input').value = totalAmount.toFixed(2);
                document.getElementById('vendor-order-items-input').value = JSON.stringify(vendorOrderItems);
                
                e.target.submit();
            });
            
            window.editVendorOrder = (order) => {
                document.getElementById('vendor-order-action').value = 'edit_vendor_order';
                document.getElementById('vendor-order-id').value = order.id;
                document.getElementById('vendor-order-vendor').value = order.vendor_id;
                document.getElementById('vendor-order-date').value = order.order_date;
                vendorOrderItems = order.order_items;
                
                updateVendorOrderDisplay();
                
                submitVendorOrderBtn.textContent = 'Save Changes';
                cancelVendorOrderEditBtn.classList.remove('hidden');
            };

            cancelVendorOrderEditBtn?.addEventListener('click', () => {
                vendorOrderForm.reset();
                vendorOrderItems = [];
                updateVendorOrderDisplay();
                document.getElementById('vendor-order-action').value = 'add_vendor_order';
                document.getElementById('vendor-order-id').value = '';
                submitVendorOrderBtn.textContent = 'Add Vendor Order';
                cancelVendorOrderEditBtn.classList.add('hidden');
            });

            // --- New Feature: Add Product from Vendor Order Panel ---
            showAddProductModalBtn?.addEventListener('click', () => {
                openModal(addProductFromVendorModal);
            });

            cancelAddProductFromVendorBtn?.addEventListener('click', () => {
                closeModal('add-product-from-vendor-modal');
            });
            
            addProductFromVendorForm?.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(addProductFromVendorForm);
                formData.append('action', 'ajax_add_product');

                try {
                    const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                        method: 'POST',
                        body: formData
                    });

                    if (!response.ok) throw new Error('Network response was not ok');
                    
                    const newProduct = await response.json();

                    if (newProduct && newProduct.id) {
                        // 1. Add to the vendor product dropdown
                        const newOption = document.createElement('option');
                        newOption.value = newProduct.id;
                        const price = parseFloat(newProduct.price);
                        newOption.textContent = `${newProduct.name} (RM${price.toFixed(2)})`;
                        newOption.dataset.name = newProduct.name;
                        newOption.dataset.price = newProduct.price;
                        vendorProductSelect.appendChild(newOption);

                        // 2. Add to the main product list table in the Products tab for consistency
                        const productListBody = document.getElementById('product-list-body');
                        if(productListBody) {
                             const newRowHTML = `
                                <td class="p-2 text-sm">${newProduct.id}</td>
                                <td class="p-2 text-sm">${newProduct.name}</td>
                                <td class="p-2 text-sm">RM${price.toFixed(2)}</td>
                                <td class="p-2 text-sm">${newProduct.category}</td>
                                <td class="p-2 text-sm flex space-x-2">
                                    <button onclick='editProduct(${JSON.stringify(newProduct)})' class="text-blue-500 hover:text-blue-700">Edit</button>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="id" value="${newProduct.id}">
                                        <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                                    </form>
                                </td>`;
                            const newRow = productListBody.insertRow(0); // Add to top
                            newRow.innerHTML = newRowHTML;
                        }

                        // 3. Close the modal and reset the form
                        closeModal('add-product-from-vendor-modal');
                        addProductFromVendorForm.reset();

                        // 4. Automatically add the new product to the current vendor order
                        vendorOrderItems.push({ 
                            id: newProduct.id, 
                            name: newProduct.name, 
                            price: price, 
                            quantity: 1 
                        });
                        updateVendorOrderDisplay();

                        alert('New product added successfully and added to your order.');

                    } else {
                        alert('Error: Could not add product. ' + (newProduct.error || ''));
                    }
                } catch (error) {
                    console.error('Error adding product:', error);
                    alert('An error occurred while adding the product.');
                }
            });


            // --- Order Type Toggle ---
            orderTypeRadios.forEach(radio => {
                radio.addEventListener('change', () => {
                    if (radio.value === 'pre_order') {
                        preOrderDetails.classList.remove('hidden');
                    } else {
                        preOrderDetails.classList.add('hidden');
                    }
                });
            });

            // --- Pre-order Modals ---
            window.openCompleteOrderModal = (order) => {
                document.getElementById('complete-order-id').innerText = order.id;
                document.getElementById('complete-order-total').innerText = `RM${parseFloat(order.total_amount).toFixed(2)}`;
                document.getElementById('complete-pre-order-id-input').value = order.id;
                document.getElementById('complete-payment-amount-input').value = order.total_amount;
                openModal(completeOrderModal);
            };

            window.openEditPreOrderModal = (order) => {
                document.getElementById('edit-pre-order-id-input').value = order.id;
                document.getElementById('edit-customer-name-input').value = order.customer_name;
                document.getElementById('edit-customer-contact-input').value = order.customer_contact;
                document.getElementById('edit-pickup-date-input').value = order.pickup_date;
                document.getElementById('edit-pickup-time-input').value = order.pickup_time;

                if (order.delivery_type === 'pickup') {
                    document.getElementById('edit-pickup-radio').checked = true;
                } else {
                    document.getElementById('edit-delivery-radio').checked = true;
                }
                openModal(editPreOrderModal);
            };


            // --- FullCalendar Initialization ---
            const calendarEl = document.getElementById('pre-order-calendar');
            if (calendarEl) {
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: <?php echo json_encode($pre_order_events); ?>,
                    eventClick: function(info) {
                        const orderId = info.event.id;
                        const allPreOrders = <?php echo json_encode(array_values($_SESSION['pre_orders'])); ?>;
                        const order = allPreOrders.find(o => o.id == orderId);
                        
                        if (order) {
                            let orderDetails = `
                                Pre-Order Details:
                                --------------------
                                Order ID: #${order.id}
                                Customer: ${order.customer_name} (${order.customer_contact})
                                Pickup: ${order.pickup_date} @ ${order.pickup_time}
                                Type: ${order.delivery_type.charAt(0).toUpperCase() + order.delivery_type.slice(1)}
                                Total: RM${parseFloat(order.total_amount).toFixed(2)}
                                Items:
                                ${order.order_items.map(item => ` - ${item.name} (x${item.quantity})`).join('\n')}
                            `;
                            alert(orderDetails);
                        }
                    }
                });
                calendar.render();
            }
        });
    </script>
</body>
</html>
