import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';

export type CartItem = {
  id: number;
  name: string;
  price: string;
  currency: string;
  quantity: number;
  image_url?: string;
};

export function useCart() {
  const { auth } = usePage<SharedData>().props;
  const [cart, setCart] = useState<CartItem[]>([]);
  const [lastUserId, setLastUserId] = useState<number | null>(null);

  // Load cart from localStorage on mount
  useEffect(() => {
    if (typeof window === 'undefined') return;

    const cartData = window.localStorage.getItem('cart');
    const storedUserId = window.localStorage.getItem('cart_user_id');
    const currentUserId = auth.user?.id || null;

    // If user changed, clear the cart
    if (storedUserId && storedUserId !== String(currentUserId)) {
      window.localStorage.removeItem('cart');
      window.localStorage.removeItem('cart_user_id');
      setCart([]);
      setLastUserId(currentUserId);
      return;
    }

    try {
      const parsedCart = cartData ? JSON.parse(cartData) : [];
      setCart(parsedCart);
      setLastUserId(currentUserId);
      
      // Store current user ID
      if (currentUserId) {
        window.localStorage.setItem('cart_user_id', String(currentUserId));
      }
    } catch (e) {
      // If cart data is corrupted, clear it
      window.localStorage.removeItem('cart');
      window.localStorage.removeItem('cart_user_id');
      setCart([]);
      setLastUserId(currentUserId);
    }
  }, [auth.user?.id]);

  // Clear cart when user logs out
  useEffect(() => {
    if (!auth.user && lastUserId !== null) {
      window.localStorage.removeItem('cart');
      window.localStorage.removeItem('cart_user_id');
      setCart([]);
      setLastUserId(null);
    }
  }, [auth.user, lastUserId]);

  // Keep all hook instances in sync via a custom event
  useEffect(() => {
    if (typeof window === 'undefined') return;

    const handleCartUpdated = (event: Event) => {
      const customEvent = event as CustomEvent<CartItem[]>;
      if (Array.isArray(customEvent.detail)) {
        setCart(customEvent.detail);
      }
    };

    window.addEventListener('cart-updated', handleCartUpdated as EventListener);

    return () => {
      window.removeEventListener('cart-updated', handleCartUpdated as EventListener);
    };
  }, []);

  const setCartAndSync = (updater: (prev: CartItem[]) => CartItem[]) => {
    setCart((prev) => {
      const next = updater(prev);

      if (typeof window !== 'undefined') {
        window.localStorage.setItem('cart', JSON.stringify(next));
        // Store user ID with cart
        if (auth.user?.id) {
          window.localStorage.setItem('cart_user_id', String(auth.user.id));
        }
        window.dispatchEvent(new CustomEvent<CartItem[]>('cart-updated', { detail: next }));
      }

      return next;
    });
  };

  const addToCart = (item: Omit<CartItem, 'quantity'>, quantity = 1) => {
    setCartAndSync((prev) => {
      const existing = prev.find((prod) => prod.id === item.id);
      if (existing) {
        return prev.map((prod) =>
          prod.id === item.id ? { ...prod, quantity: prod.quantity + quantity } : prod
        );
      }
      return [...prev, { ...item, quantity }];
    });
  };

  const removeFromCart = (id: number) => {
    setCartAndSync((prev) => prev.filter((item) => item.id !== id));
  };

  const updateQuantity = (id: number, newQty: number) => {
    setCartAndSync((prev) =>
      prev
        .map((item) => (item.id === id ? { ...item, quantity: newQty } : item))
        .filter((item) => item.quantity > 0)
    );
  };

  const clearCart = () => setCartAndSync(() => []);

  const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
  const totalPrice = cart.reduce((sum, item) => sum + parseFloat(item.price) * item.quantity, 0);

  return { cart, addToCart, removeFromCart, updateQuantity, clearCart, itemCount, totalPrice };
}

