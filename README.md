# digital-wallet-platform
# Digital Wallet API Documentation

This API provides functionality for user authentication, wallet transactions, and QR-based payments.

## Base URL
```
http://localhost/digital-wallet-platform/wallet-server/user/v1/
```

---

## 🔑 Authentication

### 1️⃣ Register User
**Endpoint:** `POST /auth/register.php`  
**Description:** Registers a new user.  
**Request Body (JSON):**
```json
{
  "email": "user@example.com",
  "password": "securepassword",
  "confirm_password": "securepassword"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Registration successful"
}
```

### 2️⃣ Login
**Endpoint:** `POST /auth/login.php`  
**Description:** Logs in a user and starts a session.  
**Request Body (JSON):**
```json
{
  "email": "user@example.com",
  "password": "securepassword"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Login successful"
}
```

### 3️⃣ Logout
**Endpoint:** `POST /auth/logout.php`  
**Description:** Logs out a user and destroys the session.  
**Response:**
```json
{
  "status": "success",
  "message": "Logout successful"
}
```

---

## 💰 Wallet Operations

### 4️⃣ Get Balance
**Endpoint:** `GET /get_balance.php`  
**Description:** Fetches the current wallet balance.  
**Response:**
```json
{
  "balance": 100.50
}
```

### 5️⃣ Deposit Funds
**Endpoint:** `POST /deposit.php`  
**Description:** Deposits money into the user's wallet.  
**Request Body (JSON):**
```json
{
  "amount": 50.00
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Deposit successful",
  "new_balance": 150.50
}
```

### 6️⃣ Withdraw Funds
**Endpoint:** `POST /withdraw.php`  
**Description:** Withdraws money from the user's wallet.  
**Request Body (JSON):**
```json
{
  "amount": 20.00
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Withdrawal successful",
  "new_balance": 130.50
}
```

---

## 🔄 Transactions

### 7️⃣ Transfer Funds
**Endpoint:** `POST /transfer.php`  
**Description:** Transfers money from one user to another.  
**Request Body (JSON):**
```json
{
  "recipient_email": "receiver@example.com",
  "amount": 10.00
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Transfer successful",
  "new_balance": 120.50
}
```

### 8️⃣ Get Transaction History
**Endpoint:** `GET /get_transactions.php`  
**Description:** Retrieves the user's transaction history.  
**Response:**
```json
[
  {
    "transaction_id": 1,
    "type": "deposit",
    "amount": 50.00,
    "date": "2025-03-01"
  },
  {
    "transaction_id": 2,
    "type": "withdrawal",
    "amount": 20.00,
    "date": "2025-03-02"
  }
]
```

---

## 🔒 User Profile & Verification

### 9️⃣ Get Profile Information
**Endpoint:** `GET /get_profile.php`  
**Description:** Fetches user details.  
**Response:**
```json
{
  "email": "user@example.com",
  "full_name": "John Doe",
  "phone": "+123456789"
}
```

### 🔐 Update Profile
**Endpoint:** `POST /update_profile.php`  
**Description:** Updates user profile information.  
**Request Body (JSON):**
```json
{
  "full_name": "John Doe",
  "phone": "+123456789"
}
```
**Response:**
```json
{
  "status": "success",
  "message": "Profile updated"
}
```

---

## 💚 QR Code Payments

### 1️⃣️4️⃣ Generate QR Code
**Endpoint:** `GET /utils/generate_qr.php`  
**Description:** Generates a QR code containing a payment link.  
**Response:**  
Returns a **QR Code image**.

### 1️⃣️5️⃣ Receive Payment via QR Code
**Endpoint:** `GET /receive_payment.php`  
**Description:** Processes a payment when a QR code is scanned.  
**Query Parameters:**
```plaintext
recipient_id=123
```
**Response:**
```json
{
  "status": "success",
  "message": "Payment received successfully",
  "new_balance": 110.50
}
```

---

## 🚀 Future Enhancements
- Webhook support for real-time transaction updates.
- API rate limiting and security enhancements.
- Mobile app integration.

---

## 📞 Support
For API support or integration help, contact **support@yourdomain.com**.

---

This documentation provides a structured overview of all available endpoints, making it **developer-friendly** for third-party integrations. 🚀

