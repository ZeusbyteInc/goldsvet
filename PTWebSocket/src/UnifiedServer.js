const fs = require('fs');
const logger = require('./services/Logger');

// Guard: engine.io (polling) on Node 22 can throw ERR_HTTP_HEADERS_SENT
// when a client disconnects mid-response — the response was already sent,
// the server state is safe, not worth killing the whole process. The
// client reconnects automatically. Other errors still crash (pm2 restart).
process.on('uncaughtException', (err) => {
    if (err && (err.code === 'ERR_HTTP_HEADERS_SENT' || /ERR_HTTP_HEADERS_SENT/.test(String(err && err.message)))) {
        try { (global.__ptLogger || console).warn('[UnifiedServer] Suppressed engine.io write-after-send: ' + String(err && err.message).slice(0, 120)); } catch (e) {}
        return;
    }
    try { (global.__ptLogger || console).error('[UnifiedServer] Uncaught: ' + (err && err.stack ? err.stack.split('\n').slice(0, 4).join('\n') : err)); } catch (e) {}
    process.exit(1);
});
const config = require('./config/config');


const originalReadFileSync = fs.readFileSync;
fs.readFileSync = function(path, options) {
    try {
        return originalReadFileSync(path, options);
    } catch (e) {
        logger.error(`[FS_SAFE] Failed to read file: ${path}. Using safe fallback.`);
        if (typeof path === 'string') {
            if (path.endsWith('.json')) {
                return '{}'; // Fallback JSON
            } else if (path.endsWith('.txt')) {
                return ''; // Fallback Text
            }
        }
        throw e; 
    }
};

const BinaryServer = require('./servers/BinaryServer');
const ArcadeServer = require('./servers/ArcadeServer');
const SlotsServer = require('./servers/SlotsServer');
const InternalSocketServer = require('./servers/InternalSocketServer');
const NullEngineServer = require('./servers/NullEngineServer');

logger.info('================================================');
logger.info('   STARTING UNIFIED PTWEBSOCKET SERVER (MODERN)  ');
logger.info('================================================');

try {
    logger.info(`[UnifiedServer] SSL mode: ${config.ssl.enabled ? `enabled (${config.ssl.source || 'manual'})` : 'disabled'}`);
    logger.info(`[UnifiedServer] Ports => server:${config.endpoints.server} arcade:${config.endpoints.arcade} slots:${config.endpoints.slots}`);

    // Start Binary Server
    BinaryServer.start();

    // Start Arcade Server
    ArcadeServer.start();

    // Start Slots Server
    SlotsServer.start();

    // Start Internal Socket Server
    InternalSocketServer.start();

    // Start NullCatalog engine (loopback math service for the Pragmatic PHP kernel)
    NullEngineServer.start();

    logger.info('[UnifiedServer] All subsystems initialized successfully.');

} catch (e) {
    logger.error(`[UnifiedServer] Startup Fatal Error: ${e.message}`);
    process.exit(1);
}

// Graceful Shutdown
const stopAllServers = () => {
    logger.info('[UnifiedServer] Stopping all servers...');
    try {
        InternalSocketServer.stop();
    } catch (e) {
        logger.error(`[UnifiedServer] Error stopping InternalSocketServer: ${e.message}`);
    }
    process.exit(0);
};

process.on('SIGINT', stopAllServers);
process.on('SIGTERM', stopAllServers);

