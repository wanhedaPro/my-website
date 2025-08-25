```javascript
// script.js
document.addEventListener('DOMContentLoaded', init);

// Global data stores using localStorage
const dataKeys = {
    products: 'bakeryProducts',
    customers: 'bakeryCustomers',
    sales: 'bakerySales',
    expenses: 'bakeryExpenses',
    vendors: 'bakeryVendors'
};

let data = {
    products: JSON.parse(localStorage.getItem(dataKeys.products)) || [],
    customers: JSON.parse(localStorage.getItem(dataKeys.customers)) || [],
    sales: JSON.parse(localStorage.getItem(dataKeys.sales)) || [],
    expenses: JSON.parse(localStorage.getItem(dataKeys.expenses)) || [],
    vendors: JSON.parse(localStorage.getItem(dataKeys.vendors)) || []
};

let currentOrder = {
    items: [],
    customerId: null,
    taxRate: 0.06
};

function init() {
    updateDateTime();
    setInterval(updateDateTime, 60000); // Update time every minute
    
    // Initial data loads for each tab
    renderProducts();
    renderCustomers();
    renderSales();
    renderVendors();
    renderExpenses();
    
    // Set default expense dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('expense-date').value = today;
    document.getElementById('expense-from').value = today;
    document.getElementById('expense-to').value = today;
    
    // Event listeners for forms
    document.getElementById('product-form').addEventListener('submit', handleProductFormSubmit);
    document.getElementById('vendor-form').addEventListener('submit', handleVendorFormSubmit);
}

// === General Utility Functions ===

function saveData(key) {
    localStorage.setItem(key, JSON.stringify(data[key]));
}

function updateDateTime() {
    const now = new Date();
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
    document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active-tab'));
    
    document.getElementById(`${tabName}-tab-content`).classList.remove('hidden');
    document.getElementById(`${tabName}-tab`).classList.add('active-tab');
}

function showModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Custom alert function to avoid window.alert
function customAlert(message) {
    // You can implement a custom modal or message box here
    console.log(`ALERT: ${message}`);
    // For now, we will use a simple alert as a placeholder
    alert(message);
}

// === Order Input Tab Functions ===

function renderProducts() {
    const productsGrid = document.getElementById('products-grid');
    if (data.products.length === 0) {
        productsGrid.innerHTML = '<div class="text-center text-gray-500 p-8 col-span-full">No products found. Add some in the Product Manager tab!</div>';
        return;
    }
    
    productsGrid.innerHTML = '';
    data.products.forEach(product => {
        const itemHtml = `
            <div class="product-item" onclick="addOrderItem('${product.id}')">
                <img src="${product.image || 'https://placehold.co/150x150/f3f4f6/a3a3a3?text=No+Image'}" alt="${product.name}" class="w-full h-24 object-cover mb-2 rounded-lg">
                <div class="font-medium truncate">${product.name}</div>
                <div class="text-amber-700">RM ${product.price.toFixed(2)}</div>
            </div>
        `;
        productsGrid.innerHTML += itemHtml;
    });
    
    // Add custom item button
    const customItemHtml = `
        <div class="product-item" onclick="showModal('custom-item-modal')">
            <div class="w-full h-24 flex items-center justify-center bg-gray-100 mb-2 rounded-lg">
                <i class="fas fa-plus text-3xl text-gray-400"></i>
            </div>
            <div class="font-medium">Custom Item</div>
            <div class="text-amber-700">Set Price</div>
        </div>
    `;
    productsGrid.innerHTML += customItemHtml;
}

function addOrderItem(productId) {
    const product = data.products.find(p => p.id === productId);
    if (product) {
        currentOrder.items.push({
            id: productId,
            name: product.name,
            price: product.price,
            description: ''
        });
        updateOrderDisplay();
    }
}

function addCustomItem() {
    const name = document.getElementById('custom-item-name').value.trim();
    const description = document.getElementById('custom-item-desc').value.trim();
    const price = parseFloat(document.getElementById('custom-item-price').value);

    if (!name || isNaN(price) || price < 0) {
        customAlert('Please enter valid name and price');
        return;
    }

    currentOrder.items.push({
        id: 'custom-' + Date.now(),
        name,
        price,
        description
    });
    updateOrderDisplay();
    closeModal('custom-item-modal');
}

function removeOrderItem(index) {
    currentOrder.items.splice(index, 1);
    updateOrderDisplay();
}

function updateOrderDisplay() {
    const orderItemsContainer = document.getElementById('order-items');
    const subtotalElement = document.getElementById('subtotal');
    const taxElement = document.getElementById('tax');
    const totalElement = document.getElementById('total');
    
    if (currentOrder.items.length === 0) {
        orderItemsContainer.innerHTML = '<p class="text-gray-500 text-center py-4">No items in order</p>';
        subtotalElement.textContent = 'RM 0.00';
        taxElement.textContent = 'RM 0.00';
        totalElement.textContent = 'RM 0.00';
        return;
    }
    
    const subtotal = currentOrder.items.reduce((sum, item) => sum + item.price, 0);
    const tax = subtotal * currentOrder.taxRate;
    const total = subtotal + tax;
    
    subtotalElement.textContent = `RM ${subtotal.toFixed(2)}`;
    taxElement.textContent = `RM ${tax.toFixed(2)}`;
    totalElement.textContent = `RM ${total.toFixed(2)}`;
    
    orderItemsContainer.innerHTML = '';
    currentOrder.items.forEach((item, index) => {
        const itemElement = document.createElement('div');
        itemElement.className = 'flex justify-between items-center py-2 border-b';
        itemElement.innerHTML = `
            <div>
                <div>${item.name}${item.description ? ` (${item.description})` : ''}</div>
            </div>
            <div class="flex items-center">
                <span class="mr-4">RM ${item.price.toFixed(2)}</span>
                <button onclick="removeOrderItem(${index})" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        orderItemsContainer.appendChild(itemElement);
    });
}

function clearOrder() {
    currentOrder = { items: [], customerId: null, taxRate: 0.06 };
    document.getElementById('customer-select').value = '';
    updateOrderDisplay();
}

function processOrder() {
    if (currentOrder.items.length === 0) {
        customAlert('Please add items to the order.');
        return;
    }
    
    const customerSelect = document.getElementById('customer-select');
    const customerId = customerSelect.value || null;
    
    const subtotal = currentOrder.items.reduce((sum, item) => sum + item.price, 0);
    const tax = subtotal * currentOrder.taxRate;
    const total = subtotal + tax;
    
    const sale = {
        id: 'ORD-' + Date.now(),
        date: new Date().toISOString(),
        customerId: customerId,
        items: [...currentOrder.items],
        subtotal: subtotal,
        tax: tax,
        total: total
    };
    
    data.sales.unshift(sale);
    saveData(dataKeys.sales);
    
    generateReceipt(sale);
    
    clearOrder();
    renderSales();
}

// === Product Manager Tab Functions ===

function renderProductsManager() {
    const productList = document.getElementById('product-list');
    if (data.products.length === 0) {
        productList.innerHTML = '<tr><td colspan="4" class="p-4 text-center text-gray-500">No products found</td></tr>';
        return;
    }
    
    productList.innerHTML = '';
    data.products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="p-2 border"><img src="${product.image || 'https://placehold.co/50x50/f3f4f6/a3a3a3?text=No+Image'}" alt="${product.name}" class="w-12 h-12 object-cover rounded-lg"></td>
            <td class="p-2 border">${product.name}</td>
            <td class="p-2 border">RM ${product.price.toFixed(2)}</td>
            <td class="p-2 border">
                <button onclick="editProduct('${product.id}')" class="text-blue-500 mr-2"><i class="fas fa-edit"></i></button>
                <button onclick="deleteProduct('${product.id}')" class="text-red-500"><i class="fas fa-trash"></i></button>
            </td>
        `;
        productList.appendChild(row);
    });
}

function handleProductFormSubmit(event) {
    event.preventDefault();
    const id = document.getElementById('product-id').value;
    const name = document.getElementById('product-name').value.trim();
    const price = parseFloat(document.getElementById('product-price').value);
    const image = document.getElementById('product-image').value.trim();
    
    if (id) {
        // Edit existing product
        const productIndex = data.products.findIndex(p => p.id === id);
        if (productIndex !== -1) {
            data.products[productIndex] = { ...data.products[productIndex], name, price, image };
        }
    } else {
        // Add new product
        const newProduct = {
            id: 'PROD-' + Date.now(),
            name,
            price,
            image
        };
        data.products.push(newProduct);
    }
    
    saveData(dataKeys.products);
    clearProductForm();
    renderProducts();
    renderProductsManager();
}

function editProduct(productId) {
    const product = data.products.find(p => p.id === productId);
    if (product) {
        document.getElementById('product-form-title').textContent = 'Edit Product';
        document.getElementById('product-id').value = product.id;
        document.getElementById('product-name').value = product.name;
        document.getElementById('product-price').value = product.price;
        document.getElementById('product-image').value = product.image;
    }
}

function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        data.products = data.products.filter(p => p.id !== productId);
        saveData(dataKeys.products);
        renderProducts();
        renderProductsManager();
    }
}

function clearProductForm() {
    document.getElementById('product-form-title').textContent = 'Add New Product';
    document.getElementById('product-form').reset();
    document.getElementById('product-id').value = '';
}

// === Customer Data Tab Functions ===

function renderCustomers() {
    const customerList = document.getElementById('customer-list');
    const customerSelect = document.getElementById('customer-select');
    
    if (data.customers.length === 0) {
        customerList.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">No customers found</td></tr>';
        customerSelect.innerHTML = '<option value="">New Customer</option>';
        return;
    }
    
    customerList.innerHTML = '';
    customerSelect.innerHTML = '<option value="">New Customer</option>';
    
    data.customers.forEach(customer => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="p-2 border">${customer.id}</td>
            <td class="p-2 border">${customer.name}</td>
            <td class="p-2 border">${customer.phone}</td>
            <td class="p-2 border">${customer.email}</td>
            <td class="p-2 border">
                <button onclick="deleteCustomer('${customer.id}')" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        customerList.appendChild(row);
        
        const option = document.createElement('option');
        option.value = customer.id;
        option.textContent = `${customer.name} (${customer.phone})`;
        customerSelect.appendChild(option);
    });
}

function addCustomer() {
    const name = document.getElementById('cust-name').value.trim();
    const phone = document.getElementById('cust-phone').value.trim();
    const email = document.getElementById('cust-email').value.trim();
    
    if (!name || !phone) {
        customAlert('Name and phone number are required.');
        return;
    }
    
    const customer = {
        id: 'CUST-' + Date.now(),
        name,
        phone,
        email,
        joinDate: new Date().toISOString()
    };
    
    data.customers.unshift(customer);
    saveData(dataKeys.customers);
    
    document.getElementById('cust-name').value = '';
    document.getElementById('cust-phone').value = '';
    document.getElementById('cust-email').value = '';
    
    renderCustomers();
}

function deleteCustomer(id) {
    if (confirm('Are you sure you want to delete this customer?')) {
        data.customers = data.customers.filter(c => c.id !== id);
        saveData(dataKeys.customers);
        renderCustomers();
    }
}

// === Sales Records Tab Functions ===

function renderSales(filteredSales = data.sales) {
    const salesList = document.getElementById('sales-list');
    
    if (filteredSales.length === 0) {
        salesList.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">No sales records found</td></tr>';
        return;
    }
    
    salesList.innerHTML = '';
    filteredSales.forEach(sale => {
        let customerName = 'Walk-in Customer';
        if (sale.customerId) {
            const customer = data.customers.find(c => c.id === sale.customerId);
            if (customer) customerName = customer.name;
        }
        
        const row = document.createElement('tr');
        const date = new Date(sale.date);
        row.innerHTML = `
            <td class="p-2 border">${sale.id}</td>
            <td class="p-2 border">${date.toLocaleDateString()}</td>
            <td class="p-2 border">${customerName}</td>
            <td class="p-2 border">RM ${sale.total.toFixed(2)}</td>
            <td class="p-2 border">
                <button onclick="viewReceipt('${sale.id}')" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-receipt"></i> View
                </button>
            </td>
        `;
        salesList.appendChild(row);
    });
}

function searchOrders() {
    const searchTerm = document.getElementById('search-order').value.toLowerCase();
    if (!searchTerm) {
        renderSales();
        return;
    }
    
    const filteredSales = data.sales.filter(sale => {
        if (sale.id.toLowerCase().includes(searchTerm)) return true;
        
        if (sale.customerId) {
            const customer = data.customers.find(c => c.id === sale.customerId);
            if (customer && customer.name.toLowerCase().includes(searchTerm)) return true;
        }
        
        return false;
    });
    
    renderSales(filteredSales);
}

function exportSales() {
    customAlert('Export functionality would create a downloadable file with all sales data.');
}

function generateReceipt(sale) {
    const receipt = document.getElementById('receipt-content');
    
    let customerName = 'Walk-in Customer';
    if (sale.customerId) {
        const customer = data.customers.find(c => c.id === sale.customerId);
        if (customer) customerName = customer.name;
    }
    
    const date = new Date(sale.date);
    let itemsHtml = sale.items.map(item => `
        <div class="mb-1">
            <div class="flex justify-between">
                <div>${item.name}${item.description ? ` (${item.description})` : ''}</div>
                <div>RM ${item.price.toFixed(2)}</div>
            </div>
        </div>
    `).join('');
    
    receipt.innerHTML = `
        <div class="text-center mb-2">
            <h2 class="font-bold text-lg">Sweet Treats Bakery</h2>
            <p class="text-sm">123 Baker Street, Kuala Lumpur</p>
            <p class="text-sm">Phone: 03-1234 5678</p>
        </div>
        
        <div class="border-t-2 border-b-2 border-black my-2 py-2 text-center">
            <div>ORDER RECEIPT</div>
            <div class="text-sm">${sale.id}</div>
        </div>
        
        <div class="mb-2">
            <div><strong>Date:</strong> ${date.toLocaleDateString()} ${date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>
            <div><strong>Customer:</strong> ${customerName}</div>
        </div>
        
        <div class="mb-2">
            ${itemsHtml}
        </div>
        
        <div class="border-t-2 border-black mt-2 pt-2">
            <div class="flex justify-between">
                <div>Subtotal:</div>
                <div>RM ${sale.subtotal.toFixed(2)}</div>
            </div>
            <div class="flex justify-between">
                <div>Tax (6%):</div>
                <div>RM ${sale.tax.toFixed(2)}</div>
            </div>
            <div class="flex justify-between font-bold">
                <div>TOTAL:</div>
                <div>RM ${sale.total.toFixed(2)}</div>
            </div>
        </div>
        
        <div class="text-center mt-4 text-sm">
            Thank you for your purchase!<br>
            Please come again
        </div>
    `;
    showModal('receipt-modal');
}

function viewReceipt(orderId) {
    const sale = data.sales.find(s => s.id === orderId);
    if (sale) {
        generateReceipt(sale);
    }
}

// === Expenses Tab Functions ===

function renderExpenses(filteredExpenses = data.expenses) {
    const expensesList = document.getElementById('expenses-list');
    
    if (filteredExpenses.length === 0) {
        expensesList.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-gray-500">No expenses recorded</td></tr>';
        return;
    }
    
    expensesList.innerHTML = '';
    filteredExpenses.forEach(expense => {
        const row = document.createElement('tr');
        const date = new Date(expense.date + 'T00:00:00'); // Normalize date for consistent display
        row.innerHTML = `
            <td class="p-2 border">${date.toLocaleDateString()}</td>
            <td class="p-2 border">${expense.category.charAt(0).toUpperCase() + expense.category.slice(1)}</td>
            <td class="p-2 border">${expense.description}</td>
            <td class="p-2 border">RM ${expense.amount.toFixed(2)}</td>
            <td class="p-2 border">
                <button onclick="deleteExpense('${expense.id}')" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        expensesList.appendChild(row);
    });
}

function addExpense() {
    const date = document.getElementById('expense-date').value;
    const category = document.getElementById('expense-category').value;
    const description = document.getElementById('expense-desc').value.trim();
    const amount = parseFloat(document.getElementById('expense-amount').value);
    
    if (!date || !description || isNaN(amount) || amount <= 0) {
        customAlert('Please fill all fields with valid values.');
        return;
    }
    
    const expense = {
        id: 'EXP-' + Date.now(),
        date,
        category,
        description,
        amount
    };
    
    data.expenses.unshift(expense);
    saveData(dataKeys.expenses);
    
    document.getElementById('expense-desc').value = '';
    document.getElementById('expense-amount').value = '';
    
    renderExpenses();
}

function filterExpenses() {
    const fromDate = document.getElementById('expense-from').value;
    const toDate = document.getElementById('expense-to').value;
    
    if (!fromDate || !toDate) {
        renderExpenses();
        return;
    }
    
    const filtered = data.expenses.filter(expense => {
        return expense.date >= fromDate && expense.date <= toDate;
    });
    
    renderExpenses(filtered);
}

function deleteExpense(id) {
    if (confirm('Are you sure you want to delete this expense?')) {
        data.expenses = data.expenses.filter(e => e.id !== id);
        saveData(dataKeys.expenses);
        renderExpenses();
    }
}

// === Vendor/Consignment Tab Functions ===

function renderVendors() {
    const vendorList = document.getElementById('vendor-list');
    const consignmentVendorSelect = document.getElementById('consignment-vendor-select');
    
    if (data.vendors.length === 0) {
        vendorList.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-gray-500">No vendors found</td></tr>';
        consignmentVendorSelect.innerHTML = '<option value="">Select a Vendor</option>';
        return;
    }
    
    vendorList.innerHTML = '';
    consignmentVendorSelect.innerHTML = '<option value="">Select a Vendor</option>';
    
    data.vendors.forEach(vendor => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td class="p-2 border">${vendor.name}</td>
            <td class="p-2 border">${vendor.contact || 'N/A'} (${vendor.phone || 'N/A'})</td>
            <td class="p-2 border">
                <button onclick="editVendor('${vendor.id}')" class="text-blue-500 mr-2"><i class="fas fa-edit"></i></button>
                <button onclick="deleteVendor('${vendor.id}')" class="text-red-500"><i class="fas fa-trash"></i></button>
            </td>
        `;
        vendorList.appendChild(row);

        const option = document.createElement('option');
        option.value = vendor.id;
        option.textContent = vendor.name;
        consignmentVendorSelect.appendChild(option);
    });
}

function handleVendorFormSubmit(event) {
    event.preventDefault();
    const id = document.getElementById('vendor-id').value;
    const name = document.getElementById('vendor-name').value.trim();
    const contact = document.getElementById('vendor-contact').value.trim();
    const phone = document.getElementById('vendor-phone').value.trim();

    if (!name) {
        customAlert('Vendor name is required.');
        return;
    }
    
    if (id) {
        // Edit existing vendor
        const vendorIndex = data.vendors.findIndex(v => v.id === id);
        if (vendorIndex !== -1) {
            data.vendors[vendorIndex] = { ...data.vendors[vendorIndex], name, contact, phone };
        }
    } else {
        // Add new vendor
        const newVendor = {
            id: 'VEND-' + Date.now(),
            name,
            contact,
            phone
        };
        data.vendors.push(newVendor);
    }
    
    saveData(dataKeys.vendors);
    clearVendorForm();
    renderVendors();
}

function editVendor(vendorId) {
    const vendor = data.vendors.find(v => v.id === vendorId);
    if (vendor) {
        document.getElementById('vendor-form-title').textContent = 'Edit Vendor';
        document.getElementById('vendor-id').value = vendor.id;
        document.getElementById('vendor-name').value = vendor.name;
        document.getElementById('vendor-contact').value = vendor.contact;
        document.getElementById('vendor-phone').value = vendor.phone;
    }
}

function deleteVendor(vendorId) {
    if (confirm('Are you sure you want to delete this vendor?')) {
        data.vendors = data.vendors.filter(v => v.id !== vendorId);
        saveData(dataKeys.vendors);
        renderVendors();
    }
}

function clearVendorForm() {
    document.getElementById('vendor-form-title').textContent = 'Add New Vendor';
    document.getElementById('vendor-form').reset();
    document.getElementById('vendor-id').value = '';
}

function showCreateOrderModal() {
    const selectedVendorId = document.getElementById('consignment-vendor-select').value;
    if (!selectedVendorId) {
        customAlert('Please select a vendor first.');
        return;
    }
    const vendor = data.vendors.find(v => v.id === selectedVendorId);
    document.getElementById('delivery-vendor').value = vendor ? vendor.name : 'Unknown Vendor';

    const deliveryItemsList = document.getElementById('delivery-items-list');
    deliveryItemsList.innerHTML = '';
    
    if (data.products.length === 0) {
        deliveryItemsList.innerHTML = '<p class="text-gray-500 text-center">No products available to add to order.</p>';
    } else {
        data.products.forEach(product => {
            const itemHtml = `
                <div class="flex justify-between items-center py-2 border-b">
                    <span>${product.name}</span>
                    <input type="number" data-product-id="${product.id}" class="w-20 p-1 border rounded-lg text-right" value="0" min="0">
                </div>
            `;
            deliveryItemsList.innerHTML += itemHtml;
        });
    }

    const today = new Date().toISOString().split('T')[0];
    document.getElementById('delivery-date').value = today;
    
    showModal('create-order-modal');
}

function generateDeliveryOrder() {
    const vendorName = document.getElementById('delivery-vendor').value;
    const deliveryDate = document.getElementById('delivery-date').value;
    
    if (!deliveryDate) {
        customAlert('Please select a delivery date.');
        return;
    }

    const itemInputs = document.querySelectorAll('#delivery-items-list input[type="number"]');
    const items = [];
    itemInputs.forEach(input => {
        const quantity = parseInt(input.value);
        if (quantity > 0) {
            const productId = input.dataset.productId;
            const product = data.products.find(p => p.id === productId);
            if (product) {
                items.push({
                    name: product.name,
                    quantity: quantity
                });
            }
        }
    });

    if (items.length === 0) {
        customAlert('Please specify a quantity for at least one item.');
        return;
    }

    const orderContent = document.getElementById('order-form-content');
    let itemsHtml = items.map(item => `
        <div class="flex justify-between">
            <span>${item.name}</span>
            <span>${item.quantity} units</span>
        </div>
    `).join('');

    orderContent.innerHTML = `
        <div class="text-center mb-2">
            <h2 class="font-bold text-lg">Sweet Treats Bakery</h2>
            <p class="text-sm">Delivery Order</p>
        </div>
        
        <div class="border-t-2 border-b-2 border-black my-2 py-2">
            <div class="flex justify-between">
                <div><strong>Vendor:</strong></div>
                <div>${vendorName}</div>
            </div>
            <div class="flex justify-between">
                <div><strong>Delivery Date:</strong></div>
                <div>${deliveryDate}</div>
            </div>
            <div class="flex justify-between">
                <div><strong>Order ID:</strong></div>
                <div>DEL-${Date.now()}</div>
            </div>
        </div>
        
        <div class="mb-2">
            <strong>Order Items:</strong>
            ${itemsHtml}
        </div>
    `;

    closeModal('create-order-modal');
    showModal('order-form-modal');
}

function printOrderForm() {
    window.print();
}