import { defineConfig } from 'vite';

export default defineConfig({
  server: {
    fs: {
      allow: ['.'],      // necessary if your .env is outside src
    }
  },
  define: {
    'process.env': {}    // if you need to shim process.env
  }
});