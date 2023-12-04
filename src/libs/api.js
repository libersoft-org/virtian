const os = require('os');
const fs = require('fs');
const path = require('path');
const Data = require('./data.js');
const { Common } = require('./common.js');
const { password } = require('./settings');
// TODO: This shouldn't be here, move to some class
const validCaptchas = {};
function cleanupOldCaptchas() {
  Common.addLog('Cleaning old captchas ...');
  const currentTime = new Date().getTime();
  for (const captchaId in validCaptchas) {
    if (currentTime - validCaptchas[captchaId].timestamp > 600000) delete validCaptchas[captchaId];
  }
}
setInterval(cleanupOldCaptchas, 60000);

class API {
  constructor() {
    this.apiMethods = {
      get_login: this.getLogin,
      get_html: this.getHTML,
      get_css: this.getCSS,
      get_images_basic: this.getImagesBasic,
      get_images_categories: this.getImagesCategories,
      get_images_items: this.getImagesItems,
      get_images_item: this.getImagesItem,
      get_captcha: this.getCaptcha,
    };
  }

  async getLogin(p = {}) {
    const { pass } = p;

    if (pass === password) {
      req.session.adminLogin = true;
      res.json({ error: 0, message: 'OK' });
    } else {
      res.json({ error: 1, message: 'Wrong password.' });
    }
  }

  async runAPI() {
    this.data = new Data();
    await this.data.init();
  }

  async processAPI(name, params) {
    console.log('API request: ', name);
    console.log('Parameters: ', params);
    const method = this.apiMethods[name];
    if (method) return await method.call(this, params);
    else return { error: 1, message: 'API not found' };
  }

  getHTML() {
    const dir = path.join(__dirname, '../web/html/user/');
    const files = fs.readdirSync(dir).filter(file => file.endsWith('.html'));
    let html = {};
    for (const file of files) html[file.replace(/\.html$/, '')] = fs.readFileSync(path.join(dir, file), 'utf8');
    return { error: 0, data: html };
  }

  getCSS(p = {}) {
    let css = '';
    // TODO: FIX THE POTENTIAL FS INJECTION (p.group) !!!!!!!!!!!!!!!!:
    for (const group of p.groups) {
      const dir = path.join(__dirname, '../web/css/', group);
      const files = fs.readdirSync(dir).filter(file => file.endsWith('.css'));
      for (const file of files) css += fs.readFileSync(path.join(dir, file), 'utf8') + os.EOL;
    }
    return { error: 0, data: css };
  }

  async getImagesBasic(p = {}) {
    const f = {};
    const ext = ['.svg', '.jpg', '.jpeg', '.gif', '.png', '.webp', '.avif', '.heif'];
    for (const group of p.groups) {
      const dir = path.join(__dirname, '../web/img/', group + '/');
      const files = fs.readdirSync(dir).filter(file => ext.some(ext => file.endsWith(ext)));
      for (const file of files) f[file] = await this.getBinaryFileToBase64(path.join(dir, file));
    }
    return { error: 0, data: f };
  }

  async getImagesCategories(p = {}) {
    const f = {};
    for (const file of p.files) f[file] = await this.getBinaryFileToBase64(path.join(Common.settings.storage.images, 'categories', file));
    return { error: 0, data: f };
  }

  async getImagesItems(p = {}) {
    const f = {};
    for (const file of p.files) f[file] = await this.getBinaryFileToBase64(path.join(Common.settings.storage.images, 'items', file));
    return { error: 0, data: f };
  }

  async getImagesItem(p = {}) {
    return { error: 0, data: await this.getBinaryFileToBase64(path.join(Common.settings.storage.images, 'items', p.file)) };
  }

  async getBinaryFileToBase64(file) {
    try {
      const data = Bun.file(file);
      return 'data:' + data.type + ';base64,' + Buffer.from(await data.arrayBuffer()).toString('base64');
    } catch {
      return null;
    }
  }

  async getCaptcha() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz123456789';
    let captchaText = '';
    for (let i = 0; i < 5; i++) captchaText += chars.charAt(Math.floor(Math.random() * chars.length));
    const randomColor = () => 'rgb(' + Math.floor(Math.random() * 160) + ',' + Math.floor(Math.random() * 160) + ',' + Math.floor(Math.random() * 160) + ')';
    const randomColorL = () => 'rgb(' + Math.floor(Math.random() * 128 + 128) + ',' + Math.floor(Math.random() * 128 + 128) + ',' + Math.floor(Math.random() * 128 + 128) + ')';
    let backgroundDots = '';
    for (let x = 0; x < 120; x += 6) {
      for (let y = 0; y < 40; y += 6) backgroundDots += '<circle cx="' + x + '" cy="' + y + '" r="3" fill="' + randomColor() + '" />';
    }
    let xPosition = 10;
    let coloredText = '';
    for (let i = 0; i < captchaText.length; i++) {
      const rotation = Math.floor(Math.random() * 30) - 15;
      coloredText += '<text x="' + xPosition + '" y="30" font-family="Arial" font-size="22" font-weight="bold" fill="' + randomColorL() + '" transform="rotate(' + rotation + ' ' + xPosition + ',30)">' + captchaText[i] + '</text>';
      xPosition += 22;
    }
    const svg = `
   <svg width="120" height="40" xmlns="http://www.w3.org/2000/svg" style="background-color: gray;">
    ${backgroundDots}
    ${coloredText}
   </svg>
  `;
    const base64Image = 'data:image/svg+xml;base64,' + Buffer.from(svg).toString('base64');
    const captchaId = new Date().getTime() + Math.random().toString(36).substring(2, 9);
    validCaptchas[captchaId] = captchaText;
    return { image: base64Image, capid: captchaId };
  }

  validateCaptcha(key, value) {
    if (!validCaptchas.hasOwnProperty(key) || !validCaptchas[key] === value) return false;
    delete validCaptchas[key];
    return true;
  }

  async setLogin(p = {}) {
    if (!this.validateCaptcha(p.cid, p.captcha)) return { error: 1, message: 'Wrong captcha!' };
    const res = await this.data.validateLogin(p);
    if (res.error) return res;
    return { error: 0, data: res.data };
  }

  async setSession(p = {}) {
    const resp = await this.data.setSession(p.sessionguid);
    if (!resp) return { error: 1, message: 'Session is not valid' };
    return { error: 0, data: p.sessionguid };
  }
}

module.exports = API;
