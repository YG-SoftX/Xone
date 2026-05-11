/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        brand: '#0ea5e9', // Base brand color
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
