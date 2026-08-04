import express from 'express';
import QRCode from 'qrcode';
import pkg from 'whatsapp-web.js';

const { Client, LocalAuth } = pkg;
const app = express();
const port = Number(process.env.PORT || 3100);
const token = process.env.WHATSAPP_GATEWAY_TOKEN || (process.env.APP_ENV === 'local' ? 'goterapis-local' : null);
let state = { status: 'starting' };

if (!token) {
    throw new Error('WHATSAPP_GATEWAY_TOKEN wajib diisi di luar lingkungan lokal.');
}

app.use(express.json({ limit: '16kb' }));
app.use((request, response, next) => {
    if (request.headers.authorization !== `Bearer ${token}`) {
        return response.sendStatus(401);
    }

    next();
});

const client = new Client({
    authStrategy: new LocalAuth({ dataPath: process.env.WHATSAPP_SESSION_PATH || '.session' }),
    userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
    puppeteer: {
        executablePath: process.env.PUPPETEER_EXECUTABLE_PATH || '/usr/bin/google-chrome',
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    },
});

client.on('qr', async qr => {
    state = { status: 'qr', qr: await QRCode.toDataURL(qr) };
    console.log('[whatsapp] QR baru siap dipindai dari dashboard admin.');
});
client.on('authenticated', () => {
    state = { status: 'authenticated' };
    console.log('[whatsapp] Perangkat berhasil diautentikasi, menyiapkan sesi.');
});
client.on('ready', () => {
    state = { status: 'ready', phone: client.info?.wid?.user };
    console.log(`[whatsapp] Terhubung dan siap mengirim dari nomor ***${state.phone?.slice(-4) ?? '----'}.`);
});
client.on('auth_failure', message => {
    state = { status: 'auth_failure' };
    console.error(`[whatsapp] Autentikasi gagal: ${message}`);
});
client.on('disconnected', reason => {
    state = { status: 'disconnected' };
    console.warn(`[whatsapp] Sesi terputus: ${reason}`);
});
client.on('change_state', currentState => {
    console.log(`[whatsapp] Status sesi: ${currentState}`);
});

app.get('/status', (request, response) => response.json(state));
app.post('/messages', async (request, response) => {
    const to = String(request.body.to || '').replace(/\D/g, '');
    const message = String(request.body.message || '').trim();

    if (state.status !== 'ready') {
        return response.status(503).json({ message: 'WhatsApp belum terhubung.' });
    }
    if (!/^62\d{8,13}$/.test(to) || !message || message.length > 2000) {
        return response.status(422).json({ message: 'Nomor atau pesan tidak valid.' });
    }

    const number = await client.getNumberId(to);
    if (!number) {
        return response.status(422).json({ message: 'Nomor tidak terdaftar di WhatsApp.' });
    }

    const sent = await client.sendMessage(number._serialized, message);
    console.log(`[whatsapp] Pesan terkirim ke nomor ***${to.slice(-4)}.`);

    response.status(201).json({ id: sent.id.id });
});

const server = app.listen(port, '127.0.0.1', () => {
    console.log(`[whatsapp] Gateway aktif di http://127.0.0.1:${port}, memulai WhatsApp Web.`);
    client.initialize().catch(error => {
        console.error(`[whatsapp] Gagal memulai WhatsApp Web: ${error.message}`);
        process.exit(1);
    });
});

async function shutdown() {
    server.close();
    await client.destroy().catch(() => {});
    process.exit(0);
}

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
