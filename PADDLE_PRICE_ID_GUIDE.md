# 🔍 How to Get Your Paddle PRICE_ID

## Step-by-Step Guide

### 1. **Go to Paddle Products**
   - Login to https://app.paddle.com
   - Go to **Catalog** → **Products**

### 2. **Create a Product (if you don't have one)**
   - Click **"Create product"** button
   - Fill in:
     - **Name**: Your product name (e.g., "Electronics Kit")
     - **Type**: Choose "Standard" or "Subscription"
     - **Description**: Add description
   - Click **Save**

### 3. **Add a Price to Your Product**
   - Open your product
   - Go to the **Prices** tab
   - Click **"Add price"**
   - Fill in:
     - **Currency**: USD, EUR, GBP, etc.
     - **Price**: Enter amount (e.g., 99.99)
     - **Billing cycle**: One-time or recurring
   - Click **Save**

### 4. **Find Your Price ID**
   **Option A: In the Paddle Dashboard**
   - Go to **Catalog** → **Products**
   - Click on your product
   - Go to **Prices** tab
   - Look for the **Price ID** column
   - It looks like: `pri_xxxxxxxxxxxxx` (always starts with `pri_`)
   - Copy it

   **Option B: Via API**
   ```bash
   curl https://api.paddle.com/products \
     -H "Authorization: Bearer YOUR_PADDLE_KEY"
   ```

### 5. **Update Your .env**
```env
PADDLE_KEY=YOUR_API_KEY
PADDLE_ENVIRONMENT=sandbox
PADDLE_PRICE_ID=pri_xxxxxxxxxxxxx
```

---

## 📍 Expected Format

- **Starts with**: `pri_`
- **Example**: `pri_01ARZ3NDEKTSV4RRFFQ69G5FAV`
- **Length**: Usually 25-30 characters

---

## ✅ Testing

Once added, test with:
```bash
php artisan payment:test
```

If it says "✓ Paddle configuration found", you're all set!

---

## 🎯 In Paddle Dashboard

The Price ID location:
```
Catalog 
  └── Products
        └── [Your Product Name]
              └── Prices tab
                    └── Price ID column ← HERE
```

---

## ⚠️ Important Notes

- **Sandbox vs Production**:
  - For testing: Use `PADDLE_ENVIRONMENT=sandbox` with sandbox price ID
  - For live: Use `PADDLE_ENVIRONMENT=production` with production price ID
  
- **One Price ID per environment**
  - Sandbox and Production have different price IDs
  - Make sure they match your environment setting

- **Multiple prices**: You can create multiple prices for same product (different currencies)
  - Just pick one and use its ID

---

## 💡 Quick Tip

If you're just getting started:
1. Create a simple test product (name: "Test Product")
2. Add a test price (e.g., $9.99)
3. Copy the Price ID
4. Add to .env
5. Run `php artisan payment:test`

That's it! 🚀
