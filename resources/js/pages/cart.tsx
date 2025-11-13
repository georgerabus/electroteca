import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { useCart } from '@/hooks/use-cart';

export default function CartPage() {
  const { cart, itemCount, totalPrice, removeFromCart, updateQuantity, clearCart } = useCart();

  return (
    <AppLayout breadcrumbs={[{ title: 'Shop', href: '/shop' }, { title: 'Cart', href: '/cart' }] }>
      <Head title="Cart" />
      <div className="mx-auto max-w-2xl p-4 sm:p-8">
        <h1 className="text-3xl font-bold mb-6">My Cart</h1>
        {cart.length === 0 ? (
          <div className="text-center my-24">
            <div className="mb-4 text-4xl opacity-20">🛒</div>
            <p className="mb-4 text-lg text-gray-300">Your cart is empty.</p>
            <Link href="/shop" className="rounded-xl px-6 py-3 bg-red-600 text-white font-semibold hover:bg-red-700 transition">
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
                    <div className="text-sm text-gray-400">{item.price} {item.currency} x {item.quantity}</div>
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
                  <div className="font-semibold text-white text-right w-20">{(parseFloat(item.price) * item.quantity).toFixed(2)}</div>
                </div>
              ))}
            </div>
            <div className="flex items-center justify-between mt-8">
              <button className="text-sm text-gray-400 underline" onClick={() => clearCart()}>Clear cart</button>
              <div className="text-xl font-bold text-white">Total: {totalPrice.toFixed(2)}</div>
            </div>
            <button className="mt-8 w-full rounded-xl bg-red-600 px-8 py-4 font-semibold text-white text-lg ring-2 ring-red-600 cursor-not-allowed opacity-60" disabled>
              Checkout (coming soon)
            </button>
          </>
        )}
      </div>
    </AppLayout>
  );
}
