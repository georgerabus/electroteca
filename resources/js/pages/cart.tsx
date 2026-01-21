import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useCart } from '@/hooks/use-cart';
import { type SharedData } from '@/types';

export default function CartPage() {
  const { auth } = usePage<SharedData>().props;
  const { cart, itemCount, totalPrice, removeFromCart, updateQuantity, clearCart } = useCart();

  const handleCheckout = () => {
    if (!auth.user) {
      router.visit('/login');
      return;
    }

    if (cart.length === 0) {
      return;
    }

    // Pass cart data to checkout
    router.visit('/checkout', {
      data: { cart: JSON.stringify(cart) },
      method: 'get',
    });
  };

  return (
    <AppLayout breadcrumbs={[{ title: 'Shop', href: '/shop' }, { title: 'Cart', href: '/cart' }] }>
      <Head title="Cart" />
      <div className="mx-auto max-w-2xl p-4 sm:p-8">
        <h1 className="text-3xl font-bold mb-6">My Cart</h1>
        {cart.length === 0 ? (
          <div className="text-center my-24">
            <div className="mb-4 text-4xl opacity-20">🛒</div>
             <p className="mb-4 text-lg text-black dark:text-white">Your cart is empty.</p>
             <Link href="/shop" className="mt-6 inline-block rounded-xl px-6 py-3 bg-red-600 text-white font-semibold hover:bg-red-700 transition">
               Back to shop
             </Link>
          </div>
        ) : (
          <>
            <div className="mb-6 divide-y divide-white/10 rounded-2xl border border-white/10 bg-white/5">
              {cart.map(item => (
                <div key={item.id} className="flex items-center gap-5 p-4">
                  <div className="h-20 w-20 bg-zinc-900 rounded-xl flex-shrink-0 flex items-center justify-center overflow-hidden">
                    {item.image_url ? (
                      <img src={item.image_url} alt={item.name} className="h-full w-full object-cover" />
                    ) : (
                      <div className="text-2xl opacity-30">📦</div>
                    )}
                  </div>
                  <div className="flex-1">
                    <div className="font-semibold text-white">{item.name}</div>
                    <div className="text-sm text-gray-400">
                      {item.discountPercent && item.discountPercent > 0 ? (
                        <>
                          <span className="text-xs text-gray-500 line-through mr-2">{item.original_price ?? item.price} {item.currency}</span>
                          <span className="font-medium text-white">{parseFloat(item.price).toFixed(2)} {item.currency}</span>
                          <span className="ml-2">x {item.quantity}</span>
                        </>
                      ) : (
                        <>{item.price} {item.currency} x {item.quantity}</>
                      )}
                    </div>
                    <div className="mt-2 flex items-center gap-2">
                      <button className="rounded px-3 py-1 bg-zinc-800 text-white" onClick={() => updateQuantity(item.id, item.quantity - 1)} disabled={item.quantity <= 1}>-</button>
                      <span className="px-2">{item.quantity}</span>
                      <button className="rounded px-3 py-1 bg-zinc-800 text-white" onClick={() => updateQuantity(item.id, item.quantity + 1)}>+</button>
                      <button
                        className="ml-5 text-xs text-red-500 hover:underline"
                        onClick={() => removeFromCart(item.id)}
                        type="button"
                      >
                        Remove
                      </button>
                    </div>
                  </div>
                  <div className="font-semibold text-white text-right w-20">
                    {(() => {
                      // If original_price exists then `item.price` is expected to already be the discounted unit price.
                      if (item.discountPercent && item.original_price) {
                        return (parseFloat(item.price || '0') * item.quantity).toFixed(2);
                      }
                      // Otherwise, if discountPercent is present but original_price missing, compute discounted from item.price
                      if (item.discountPercent) {
                        const base = parseFloat(item.price || '0');
                        const discounted = base * (1 - (item.discountPercent || 0) / 100);
                        return (discounted * item.quantity).toFixed(2);
                      }
                      return (parseFloat(item.price || '0') * item.quantity).toFixed(2);
                    })()}
                  </div>
                </div>
              ))}
            </div>
            <div className="flex items-center justify-between mt-8">
              <button className="text-sm text-gray-400 underline" onClick={() => clearCart()}>Clear cart</button>
              <div className="text-xl font-bold text-white">Total: {totalPrice.toFixed(2)}</div>
            </div>
            <button
              onClick={handleCheckout}
              disabled={!auth.user || cart.length === 0}
              className={`mt-8 w-full rounded-xl px-8 py-4 font-semibold text-white text-lg ring-2 transition ${
                auth.user && cart.length > 0
                  ? 'bg-red-600 ring-red-600 hover:bg-red-700 cursor-pointer'
                  : 'bg-red-600 ring-red-600 cursor-not-allowed opacity-60'
              }`}
            >
              {!auth.user ? 'Login to Checkout' : 'Proceed to Checkout'}
            </button>
          </>
        )}
      </div>
    </AppLayout>
  );
}

