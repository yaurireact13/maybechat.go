/** Configuración de Tailwind (antes estaba embebida en cada layout vía CDN). */
module.exports = {
  content: [
    './resources/views/**/*.blade.php',
    './public/js/**/*.js',
    '!./public/js/vendor/**',
    './app/**/*.php',
  ],
  theme: {
    extend: {
      fontFamily: { sans: ['Inter', 'sans-serif'] },
      colors: {
        navy: { DEFAULT: '#16344F', dark: '#0F2538', light: '#1E4366' },
        teal: { brand: '#2A9D8F', dark: '#21867A' },
        lime: { brand: '#A8D582' },
        plat: { DEFAULT: '#0B1120', light: '#131B2E', accent: '#6366F1' },
      },
    },
  },
  plugins: [],
};
