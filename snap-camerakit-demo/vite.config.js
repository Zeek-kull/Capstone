export default {
  server: {
    proxy: {
      '^/.*\\.php$': 'http://localhost'
    }
  }
}