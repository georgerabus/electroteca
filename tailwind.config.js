/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/**/*.{js,jsx,ts,tsx,vue,blade.php}',
    './vendor/laravel/framework/src/Illuminate/**/resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      maxWidth: {
        '8xl': '1100px',
        '9xl': '1400px',
        '10xl': '2200px',
      },
    },
  },
  plugins: [],
};
