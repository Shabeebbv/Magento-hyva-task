import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Build src/main.jsx into ONE file that Magento serves from the module's web folder:
// app/code/Shabeeb/ReactCheckout/view/frontend/web/js/checkout.js
export default defineConfig({
    plugins: [react()],
    build: {
        outDir: '../view/frontend/web/js',
        emptyOutDir: false,
        rollupOptions: {
            input: 'src/main.jsx',
            output: {
                entryFileNames: 'checkout.js',
                inlineDynamicImports: true
            }
        }
    }
});
