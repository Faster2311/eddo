if(window.location.pathname.includes('admin.html')) {
    let products = [], users = [], orders = [];

    function loadAdminData() {
        products = JSON.parse(localStorage.getItem('eshop_products')) || [];
        users = JSON.parse(localStorage.getItem('eshop_users')) || [];
        orders = JSON.parse(localStorage.getItem('eshop_orders')) || [];
        renderProductsTable();
        renderOrdersTable();
        renderUsersTable();
    }

    function renderProductsTable() {
        const tbody = document.getElementById('productsTableBody');
        if(!tbody) return;
        tbody.innerHTML = products.map(p => `
            <tr>
                <td>${p.id}</td><td>${p.name}</td><td>${p.category}</td><td>${p.price}₽</td>
                <td><img src="${p.image}" width="40"></td>
                <td><button class="edit-product" data-id="${p.id}">✏️</button> <button class="delete-product" data-id="${p.id}">🗑️</button></td>
            </tr>
        `).join('');
        document.querySelectorAll('.edit-product').forEach(btn => {
            btn.addEventListener('click', () => { let id = parseInt(btn.dataset.id); editProduct(id); });
        });
        document.querySelectorAll('.delete-product').forEach(btn => {
            btn.addEventListener('click', () => { let id = parseInt(btn.dataset.id); deleteProduct(id); });
        });
    }

    function editProduct(id) {
        let prod = products.find(p => p.id === id);
        if(prod) {
            document.getElementById('productModalTitle').innerText = "Редактировать товар";
            document.getElementById('productId').value = prod.id;
            document.getElementById('prodName').value = prod.name;
            document.getElementById('prodPrice').value = prod.price;
            document.getElementById('prodCategory').value = prod.category;
            document.getElementById('prodImage').value = prod.image;
            document.getElementById('prodDesc').value = prod.desc || '';
            document.getElementById('productModal').style.display = 'flex';
        }
    }

    function deleteProduct(id) {
        if(confirm('Удалить товар?')) {
            products = products.filter(p => p.id !== id);
            localStorage.setItem('eshop_products', JSON.stringify(products));
            loadAdminData();
        }
    }

    function renderOrdersTable() {
        const tbody = document.getElementById('ordersTableBody');
        if(!tbody) return;
        tbody.innerHTML = orders.map(o => `
            <tr>
                <td>${o.id}</td><td>${o.userName}</td><td>${o.items.map(i=>i.name).join(', ')}</td><td>${o.total}₽</td>
                <td><select class="order-status" data-id="${o.id}"><option ${o.status==='Новый'?'selected':''}>Новый</option><option ${o.status==='В обработке'?'selected':''}>В обработке</option><option ${o.status==='Доставлен'?'selected':''}>Доставлен</option></select></td>
                <td><button class="delete-order" data-id="${o.id}">Удалить</button></td>
            </tr>
        `).join('');
        document.querySelectorAll('.order-status').forEach(sel => {
            sel.addEventListener('change', (e) => {
                let id = parseInt(sel.dataset.id);
                let order = orders.find(o=>o.id === id);
                if(order) order.status = sel.value;
                localStorage.setItem('eshop_orders', JSON.stringify(orders));
            });
        });
        document.querySelectorAll('.delete-order').forEach(btn => {
            btn.addEventListener('click', () => { let id = parseInt(btn.dataset.id); orders = orders.filter(o=>o.id !==id); localStorage.setItem('eshop_orders',JSON.stringify(orders)); loadAdminData(); });
        });
    }

    function renderUsersTable() {
        const tbody = document.getElementById('usersTableBody');
        if(!tbody) return;
        tbody.innerHTML = users.map(u => `
            <tr><td>${u.id}</td><td>${u.login}</td><td>${u.email}</td><td>${u.role}</td>
            <td>${u.role !== 'admin' ? `<button class="delete-user" data-id="${u.id}">Удалить</button>` : '—'}</td></tr>
        `).join('');
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', () => { let id = parseInt(btn.dataset.id); users = users.filter(u=>u.id !== id); localStorage.setItem('eshop_users',JSON.stringify(users)); loadAdminData(); });
        });
    }

    document.getElementById('addProductBtn')?.addEventListener('click', () => {
        document.getElementById('productModalTitle').innerText = "Добавить товар";
        document.getElementById('productId').value = '';
        document.getElementById('prodName').value = '';
        document.getElementById('prodPrice').value = '';
        document.getElementById('prodCategory').value = 'phones';
        document.getElementById('prodImage').value = 'https://via.placeholder.com/200';
        document.getElementById('prodDesc').value = '';
        document.getElementById('productModal').style.display = 'flex';
    });

    document.getElementById('productForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        let id = document.getElementById('productId').value;
        let name = document.getElementById('prodName').value;
        let price = parseInt(document.getElementById('prodPrice').value);
        let category = document.getElementById('prodCategory').value;
        let image = document.getElementById('prodImage').value;
        let desc = document.getElementById('prodDesc').value;
        if(id) {
            let index = products.findIndex(p => p.id == id);
            if(index !== -1) products[index] = { ...products[index], name, price, category, image, desc };
        } else {
            let newId = Date.now();
            products.push({ id: newId, name, price, category, image, desc });
        }
        localStorage.setItem('eshop_products', JSON.stringify(products));
        document.getElementById('productModal').style.display = 'none';
        loadAdminData();
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.getElementById(btn.dataset.tab + 'Tab').classList.add('active');
            document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
    document.getElementById('adminLogoutBtn')?.addEventListener('click', () => { localStorage.removeItem('eshop_current'); window.location.href='index.html'; });
    loadAdminData();
    const currentUser = localStorage.getItem('eshop_current') ? JSON.parse(localStorage.getItem('eshop_current')) : null;
    if(!currentUser || currentUser.role !== 'admin') alert('Доступ только администратору!'); 
}