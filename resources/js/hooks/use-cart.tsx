import { useState, useEffect } from 'react';

export type CartItem = {
  id: number;
  name: string;
  price: string;
  currency: string;
  quantity: number;
  image_url?: string;
};

export function useCart() {
  const [cart, setCart] = useState<CartItem[]>([]);

  // Load cart from localStorage on mount
  useEffect(() => {
    const cartData = localStorage.getItem('cart');
    setCart(cartData ? JSON.parse(cartData) : []);
  }, []);

  // Persist to localStorage
  useEffect(() => {
    localStorage.setItem('cart', JSON.stringify(cart));
  }, [cart]);

  const addToCart = (item: Omit<CartItem, 'quantity'>, quantity = 1) => {
    setCart((prev) => {
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
    setCart((prev) => prev.filter((item) => item.id !== id));
  };

  const updateQuantity = (id: number, newQty: number) => {
    setCart((prev) => prev.map((item) =>
      item.id === id ? { ...item, quantity: newQty } : item
    ).filter((item) => item.quantity > 0));
  };

  const clearCart = () => setCart([]);

  const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
  const totalPrice = cart.reduce((sum, item) => sum + parseFloat(item.price) * item.quantity, 0);

  return { cart, addToCart, removeFromCart, updateQuantity, clearCart, itemCount, totalPrice };
}
