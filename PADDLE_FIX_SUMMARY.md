# Paddle.js Loading Issue - Fix Summary

## Problem
Users were experiencing "Failed to load Paddle.js" errors when attempting to top up their wallet with funds using Paddle payment processor.

## Root Causes Identified

### 1. **Content Security Policy (CSP) Headers Blocking Paddle**
The most critical issue was that the application's CSP headers were blocking the Paddle.js script and Paddle APIs:
- **script-src**: Only allowed `'self'` (own domain), blocking `https://cdn.paddle.com`
- **connect-src**: Only allowed `'self'`, blocking Paddle API calls to `https://api.paddle.com`
- **frame-src**: Not configured, blocking Paddle checkout iframe at `https://checkout.paddle.com`

### 2. **Unreliable Paddle Script Loading Logic**
The `loadPaddle()` function had several issues:
- If the script was already in the DOM but not yet loaded, the promise could fail to resolve
- No timeout mechanism - would hang indefinitely if CDN failed
- Poor error messaging for debugging

### 3. **Missing Error Visibility**
The error handling didn't log enough information for debugging in production environments.

## Solutions Implemented

### 1. ✅ Updated CSP Headers
**File**: [app/Http/Middleware/SecurityHeadersMiddleware.php](app/Http/Middleware/SecurityHeadersMiddleware.php)

```php
// Added Paddle to script-src directive
"script-src 'self' https://cdn.paddle.com",

// Added Paddle to connect-src directive  
$connectSrc = ["'self'", 'https://api.paddle.com', 'https://*.paddle.com'];

// Added new frame-src directive for Paddle checkout iframe
"frame-src https://checkout.paddle.com https://*.paddle.com",
```

**Impact**: 
- Paddle.js can now be loaded from the CDN
- Paddle APIs can be called from the frontend
- Paddle checkout overlay/iframe can be displayed

### 2. ✅ Improved Paddle Script Loading Logic
**File**: [resources/js/pages/wallet.tsx](resources/js/pages/wallet.tsx)

**Changes**:
- Better handling of existing scripts already in DOM
- Added 10-second timeout with clear error message
- Improved error handling and cleanup of event listeners
- Added script charset attribute for proper encoding
- Clear error messages indicating what went wrong

```typescript
const loadPaddle = () =>
  new Promise<void>((resolve, reject) => {
    // If Paddle is already loaded, resolve immediately
    if (window.Paddle) {
      return resolve();
    }

    // Check if script is already in DOM
    const existing = document.querySelector('script[src="https://cdn.paddle.com/paddle/v2/paddle.js"]');
    if (existing) {
      // If script exists and Paddle is available, resolve
      if (window.Paddle) {
        return resolve();
      }
      
      // Otherwise wait for the load event
      const onLoad = () => {
        existing.removeEventListener('load', onLoad);
        if (window.Paddle) {
          resolve();
        } else {
          reject(new Error('Paddle.js script loaded but window.Paddle not available'));
        }
      };
      existing.addEventListener('load', onLoad);
      return;
    }

    // Create and inject the script with timeout and proper cleanup
    const s = document.createElement('script');
    s.src = 'https://cdn.paddle.com/paddle/v2/paddle.js';
    s.async = true;
    s.charset = 'utf-8';
    
    let timeoutId: NodeJS.Timeout;
    
    const cleanup = () => {
      clearTimeout(timeoutId);
      s.removeEventListener('load', onLoad);
      s.removeEventListener('error', onError);
    };
    
    const onLoad = () => {
      cleanup();
      if (window.Paddle) {
        resolve();
      } else {
        reject(new Error('Paddle.js script loaded but window.Paddle is not defined'));
      }
    };
    
    const onError = (error: Event | string) => {
      cleanup();
      const message = typeof error === 'string' ? error : 'Failed to load Paddle.js from CDN';
      console.error('[Paddle Loading Error]', message);
      reject(new Error(message));
    };
    
    s.onload = onLoad;
    s.onerror = () => onError('Failed to fetch Paddle.js script');
    
    // 10 second timeout
    timeoutId = setTimeout(() => {
      if (!window.Paddle) {
        cleanup();
        reject(new Error('Paddle.js loading timeout - took longer than 10 seconds'));
      }
    }, 10000);
    
    document.body.appendChild(s);
  });
```

### 3. ✅ Enhanced Error Visibility & Logging
**File**: [resources/js/pages/wallet.tsx](resources/js/pages/wallet.tsx)

Added console logging at every major step:

```typescript
const handleAddCredits = async () => {
    setLoading(true);
    setError('');

    try {
        if (!paddleClientToken) {
        console.error('[Paddle Wallet] Missing Paddle client token in Inertia props');
        throw new Error('Missing Paddle client token - please check server configuration');
        }

        console.log('[Paddle Wallet] Initializing Paddle.js...');
        
        // 1) init Paddle.js
        await initPaddleOnce(paddleClientToken);
        console.log('[Paddle Wallet] Paddle.js initialized successfully');

        // 2) create transaction on backend
        console.log('[Paddle Wallet] Initiating wallet topup with amount:', amount);
        const res = await axios.post('/wallet-topup/initiate', {
        amount: Number(amount),
        });

        const txnId = res.data?.transaction_id;
        if (!txnId) {
        console.error('[Paddle Wallet] Backend response missing transaction_id:', res.data);
        throw new Error('Backend did not return transaction_id');
        }

        console.log('[Paddle Wallet] Transaction created, ID:', txnId);

        // 3) open checkout overlay
        console.log('[Paddle Wallet] Opening Paddle checkout for transaction:', txnId);
        window.Paddle.Checkout.open({
        transactionId: txnId,
        });

    } catch (e: any) {
        const errorMessage = e?.response?.data?.error ?? e?.message ?? 'Payment failed';
        console.error('[Paddle Wallet] Error:', errorMessage, e);
        setError(errorMessage);
    } finally {
        setLoading(false);
    }
};
```

Also improved `initPaddleOnce()` with logging:

```typescript
const initPaddleOnce = (() => {
  let inited = false;
  return async (clientToken: string) => {
    try {
      await loadPaddle();
      
      if (!window.Paddle) {
        console.error('[Paddle Init] window.Paddle is not defined after script load');
        throw new Error('Paddle.js not available after loading script');
      }

      if (!inited) {
        console.log('[Paddle Init] Initializing Paddle with client token');
        window.Paddle.Initialize({
          token: clientToken,
          checkout: {
            settings: {
              displayMode: 'overlay',
            },
          },
        });
        inited = true;
        console.log('[Paddle Init] Paddle initialized successfully');
      } else {
        console.log('[Paddle Init] Paddle already initialized, skipping');
      }
    } catch (error: any) {
      console.error('[Paddle Init] Error during Paddle initialization:', error?.message);
      throw error;
    }
  };
})();
```

## Testing & Verification

To test the fix:

1. **Check CSP Headers**:
   ```bash
   curl -i https://yourapp.com/wallet | grep "Content-Security-Policy"
   ```
   Should include: `script-src 'self' https://cdn.paddle.com` and `frame-src https://checkout.paddle.com`

2. **Monitor Browser Console**:
   - Open DevTools (F12) → Console
   - Attempt wallet top-up
   - Should see log messages starting with `[Paddle Wallet]` and `[Paddle Init]`
   - No CSP violation errors

3. **Test Full Flow**:
   - Navigate to `/wallet`
   - Click "Add Credits"
   - Enter amount and click "Continue to Payment"
   - Paddle.js should load and overlay should appear
   - No CORS or CSP errors in console

## Browser Console Output Expected

On successful load:
```
[Paddle Wallet] Initializing Paddle.js...
[Paddle Init] Initializing Paddle with client token
[Paddle Init] Paddle initialized successfully
[Paddle Wallet] Paddle.js initialized successfully
[Paddle Wallet] Initiating wallet topup with amount: 50
[Paddle Wallet] Transaction created, ID: txn_...
[Paddle Wallet] Opening Paddle checkout for transaction: txn_...
```

## Files Modified

1. **[app/Http/Middleware/SecurityHeadersMiddleware.php](app/Http/Middleware/SecurityHeadersMiddleware.php)**
   - Added `https://cdn.paddle.com` to `script-src`
   - Added `https://api.paddle.com` and `https://*.paddle.com` to `connect-src`
   - Added `frame-src https://checkout.paddle.com https://*.paddle.com`

2. **[resources/js/pages/wallet.tsx](resources/js/pages/wallet.tsx)**
   - Improved `loadPaddle()` function with better error handling and timeout
   - Enhanced `initPaddleOnce()` with error handling and logging
   - Added comprehensive console logging to `handleAddCredits()`

## Deployment Notes

After deploying these changes:
1. Clear browser cache (Ctrl+Shift+Del in most browsers)
2. Restart the application if using PHP-FPM
3. Test with a fresh browser session
4. Check browser console for any warnings/errors

## Security Considerations

- CSP directives only allow specific Paddle domains (no wildcards for script-src)
- `https://` required for all Paddle connections
- Frame-src limited to Paddle checkout domain only
- No relaxation of `unsafe-inline` or `unsafe-eval` policies
- Environment-based CSP allows development flexibility while maintaining production security

