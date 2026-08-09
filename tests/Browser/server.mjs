import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { extname, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(fileURLToPath(new URL('../..', import.meta.url)));
const fixture = resolve(fileURLToPath(new URL('./editable-csp.html', import.meta.url)));
const contentTypes = {
    '.js': 'application/javascript; charset=UTF-8',
    '.css': 'text/css; charset=UTF-8',
    '.html': 'text/html; charset=UTF-8',
};

export function startServer() {
    const server = createServer(async (request, response) => {
        try {
            const pathname = new URL(request.url, 'http://127.0.0.1').pathname;
            const file = pathname === '/editable-csp' ? fixture : resolve(root, `.${pathname}`);

            if (file !== fixture && file !== root && ! file.startsWith(`${root}${sep}`)) {
                response.writeHead(404).end();
                return;
            }

            const content = await readFile(file);
            const headers = {
                'Content-Type': contentTypes[extname(file)] ?? 'application/octet-stream',
                'Content-Security-Policy': "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; connect-src 'self'",
            };

            response.writeHead(200, headers).end(content);
        } catch {
            response.writeHead(404).end();
        }
    });

    return new Promise((resolve, reject) => {
        server.once('error', reject);
        server.listen(49187, '127.0.0.1', () => resolve(server));
    });
}
