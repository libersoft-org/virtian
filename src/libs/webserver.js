const fs = require('fs');
const path = require('path');
const { Elysia } = require('elysia');
const API = require('./api');
const { Common } = require('./common');

class WebServer {
  async run() {
    try {
      this.api = new API();
      await this.api.runAPI();
      await this.startServer();
    } catch (ex) {
      Common.addLog('Cannot start web server.', 2);
      Common.addLog(ex, 2);
    }
  }

  // TODO - add HTTPS support
  async startServer() {
    const app = new Elysia()
      .onRequest((req) => {
        let url = '/' + req.request.url.split('/').slice(3).join('/');
        Common.addLog(req.request.method + ' request from: ' + req.request.headers.get('cf-connecting-ip') + ' (' + (req.request.headers.get('cf-ipcountry') + ')') + ', URL: ' + url);
        //Common.addLog(req.request.method + ' request from: ' + req.headers['cf-connecting-ip'] + ' (' + (req.headers['cf-ipcountry'] + ')') + ', URL path: ' + req.path);
      })
      .post('/api/:name', async (req) => this.getAPI(req))
      .get('/download/:hash/:name', async (req) => this.getDownload(req))
      .get('/upload/:hash/:name', async (req) => this.getUpload(req))
      .get('/admin/', async (req) => this.getAdmin(req))
      .get('/*', async (req) => this.getFile(req));
    const server = { fetch: app.fetch };
    if (Common.settings.web.standalone) server.port = Common.settings.web.port;
    else server.unix = Common.settings.web.socket_path;
    Bun.serve(server);
    if (!Common.settings.web.standalone) fs.chmodSync(Common.settings.web.socket_path, '777');
    Common.addLog('Web server is running on ' + (Common.settings.web.standalone ? 'port: ' + Common.settings.web.port : 'Unix socket: ' + Common.settings.web.socket_path));
  }

  async getAPI(req) {
    return new Response(JSON.stringify(await this.api.processAPI(req.params.name, req.body)), { headers: { 'Content-Type': 'application/json' } })
  }

  async getUpload(req) {
    if (!req.params.hash || !req.params.name) return this.getIndex(req);
    const file = Bun.file(path.join(Common.settings.storage.upload, req.params.hash));
    if (!await file.exists()) return this.getIndex(req);
    return new Response(file, {
      headers: {
        'Content-Type': 'application/octet-stream',
        'Content-Disposition': 'attachment; filename="' + req.params.name + '"'
      }
    });
  }

  async getDownload(req) {
    if (!req.params.hash || !req.params.name) return this.getIndex(req);
    const file = Bun.file(path.join(Common.settings.storage.download, req.params.hash));
    if (!await file.exists()) return this.getIndex(req);
    if (req.headers.range) {
      const chunk = Common.settings.storage.chunk_download;
      let [start = 0, end = Infinity] = req.headers.range.split('=').at(-1).split('-').map(Number);
      if (end == 0) end = start + chunk < file.size ? start + chunk - 1 : file.size - 1;
      return new Response(file.slice(start, start + chunk), {
        status: 206,
        headers: { 'Content-Range': 'bytes ' + start + '-' + end + '/' + file.size }
      });
    } else {
      return new Response(file, {
        headers: {
          'Content-Type': 'application/octet-stream',
          'Content-Disposition': 'attachment; filename="' + req.params.name + '"'
        }
      });
    }
  }

  async getAdmin(req) {
    const content = await Bun.file(path.join(__dirname, '../web/static/admin/index.html')).text();
    return new Response(Common.translate(content, {
      '{TITLE}': Common.settings.web.name + ' - Admin area'
    }), { headers: { 'Content-Type': 'text/html' } });
  }

  async getIndex(req) {
    const content = await Bun.file(path.join(__dirname, '../web/static/user/index.html')).text();
    return new Response(Common.translate(content, {
      '{TITLE}': Common.settings.web.name,
      '{DESCRIPTION}': Common.settings.web.description
    }), { headers: { 'Content-Type': 'text/html' } });
  }

  async getFile(req) {
    const file = Bun.file(path.join(__dirname, '../web/static/user/', req.path));
    if (!await file.exists()) return this.getIndex(req);
    else return new Response(file);
  };
}

module.exports = WebServer;
