const path = require('path');
const dotenv = require('dotenv');

const casinoRoot = path.resolve(__dirname, '..');
const envFile = path.join(casinoRoot, '.env');
const parsedEnv = dotenv.config({ path: envFile }).parsed || {};

const sharedEnv = {
  NODE_ENV: 'production',
  APP_URL: parsedEnv.APP_URL || 'https://yourdomain.test',
};

module.exports = {
  apps: [
    {
      name: 'oss-casino-websocket',
      script: 'src/UnifiedServer.js',
      cwd: __dirname,
      watch: false,
      instances: 1,
      exec_mode: 'fork',
      env: {
        ...sharedEnv,
        INTERNAL_SOCKET_PORT: parsedEnv.INTERNAL_SOCKET_PORT || '3001',
        INTERNAL_SOCKET_PATH: parsedEnv.INTERNAL_SOCKET_PATH || '/socket.io',
        INTERNAL_SOCKET_SECRET: parsedEnv.INTERNAL_SOCKET_SECRET || '',
      }
    }
  ]
};
