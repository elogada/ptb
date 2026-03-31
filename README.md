# PTB - Order Management Console

A lightweight, self-hosted web-based order management system designed for small-scale "pabili" operations.

Built using:
- PHP 8.2
- MySQL (via XAMPP)
- Minimal dependencies (no frameworks)

This project focuses on simplicity, clarity, and operational usability—especially for users with little to no prior work experience, enabling them to function effectively as non-voice customer service representatives (CSR).

---

## ✨ Features

### 🔐 Authentication
- Session-based login system (PHPSESSID)
- Role-based access:
  - **Admin**
  - **CSR (standard user)**
- Login/logout audit logging

---

### 📋 Order Management
- Create, view, edit orders
- Status lifecycle:
  - New → For Confirmation → Confirmed → Preparing → Out for Delivery → Completed
  - Cancelled / Rejected (with closure reasons)
- Automatic computation of:
  - Subtotal
  - Delivery fee
  - Total amount
- Product selection with quantity controls
- Structured + human-readable order storage

---

### 📦 Product Catalog
- Admin CRUD interface (`admin_products.php`)
- Read-only CSR view (`products.php`)
- Categories:
  - Snacks
  - Frozen Goods
  - Canned Goods
- Stock modes:
  - Stocked
  - On-Demand

---

### 💬 Mga Spiel (Script System)
- Centralized message templates for CSRs
- Copy-paste friendly responses
- Admin-managed content
- Read-only access for users

---

### 📜 Audit Trail
- Tracks key actions:
  - Login / Logout
  - Add / Edit Orders
  - Add / Edit Products
  - Add / Edit Spiels
- Admin-only access
- CSV export support
- Displays latest 20 events in UI

---

### 📊 Dashboard (Navigation Page)
- Displays:
  - Current date
  - Total products
  - Total orders
- Quick access navigation cards

---

## 🛠️ Installation Guide

### 1. Environment Setup

Tested on:
- **XAMPP 8.2**
- **Windows 11**

Install XAMPP if you haven’t yet.

---

### 2. Place Web Files

Copy the repository contents into:
```C:/xampp/ptb-web```


---

### 3. Configure Virtual Host (Optional but Recommended)

A sample config is provided:
```httpd-vhosts.conf```


You can either:
- Replace your existing file, OR
- Copy its contents into your current Apache vhosts config

Then restart Apache.

---

### 4. Database Setup

1. Open **phpMyAdmin**
2. Create a new database (or just run the script directly)
3. Open the file:
```populate_command.txt```


You can either:
- Replace your existing file, OR
- Copy its contents into your current Apache vhosts config

Then restart Apache.


4. Copy everything inside and execute it

This will:
- Create all tables
- Insert initial users
- Populate sample data

---

### 5. Default Credentials

```Admin:
username: admin
password: admin_pass

User:
username: user
password: user_pass```


---

## ⚠️ Limitations / Design Trade-offs

This project intentionally prioritizes simplicity over completeness.

### 🔒 Security
- Uses **MD5 hashing** (not recommended for production)
- No rate limiting or brute-force protection
- No CSRF protection
- No prepared WAF layer (assumed external like Cloudflare Tunnel)

---

### 🌐 Networking
- No HTTPS configuration included
- No domain setup
- No port forwarding guidance

➡️ These are **left to the user**, as deployment environments vary.

---

### 🧱 Architecture
- No framework (pure PHP)
- Minimal abstraction
- No API layer
- Tight coupling between UI and backend logic

---

### 📦 Data Model
- Orders store:
  - Human-readable text (`order_items`)
  - Structured JSON (`order_items_json`)
- No normalization for order line items (intentional for simplicity)

---

### 👥 Scalability
- Not designed for high concurrency
- No queue system
- No caching layer

---

### 🎨 UI/UX
- Basic styling (no frontend framework)
- Designed for clarity over aesthetics
- Taglish/Tagalog-leaning interface for accessibility

---

## 🎯 Project Philosophy

This system is designed to:

- Lower the barrier to entry for employment
- Provide structure to informal "pabili" operations
- Give users a sense of professionalism and dignity in their work
- Avoid overengineering while still maintaining traceability (audit logs)

---

## 📌 Notes

- The system assumes **trusted internal usage**
- Best used in **controlled environments**
- Recommended behind:
  - VPN
  - Cloudflare Tunnel
  - Local network deployment

---

## 📄 License

This project is licensed under the **GNU General Public License (GPL)**.

You are free to:
- Use
- Modify
- Distribute

As long as derivative works remain under the same license.

---

## 🙌 Acknowledgment

Built as a practical system with real-world operational constraints in mind.

If you're using this, improving it, or learning from it — that's already a win.