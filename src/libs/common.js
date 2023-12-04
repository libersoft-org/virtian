const fs = require('fs');
const os = require('os');
const { dirname } = require('path');
const { execSync } = require('child_process');
class Common {
  static appName = 'Virtian';
  static appVersion = '0.01';
  static settingsFile = 'settings.json';
  static appPath = dirname(require.main.filename) + '/';
  static settings;

  static getHumanSize(bytes) {
    const type = ['', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
    let i = 0;
    while (bytes >= 1024) {
      bytes /= 1024;
      i++;
    }
    return `${bytes.toFixed(2)} ${type[i]}B`;
  }

  static getRAMUsage() {
    const data = require('child_process').execSync('cat /proc/meminfo').toString().split('\n');
    const meminfo = {};
    data.forEach((line) => {
      const [key, val] = line.split(':');
      meminfo[key] = parseInt(val.trim()) * 1024;
    });

    const result = {
      used: meminfo.MemTotal - meminfo.MemAvailable,
      free: meminfo.MemAvailable,
      total: meminfo.MemTotal,
      'used-percent': ((meminfo.MemTotal - meminfo.MemAvailable) / meminfo.MemAvailable) * 100,
    };
    return result;
  }

  static getStorageUsage() {
    const storage = require('child_process').execSync('df -B1 -T -x tmpfs -x devtmpfs | tail -n +2 | sed -e \'s/\\s\\+/|/g\'').toString().split('\n');
    storage.pop(); // Remove the last empty element
    const storageArray = storage.map((line) => {
      const data = line.split('|');
      data[3] = data[2] - data[4];
      return data;
    });
    return storageArray;
  }

  static htmlReplace(array, html) {
    const regex = new RegExp(Object.keys(array).join('|'), 'g');
    return html.replace(regex, match => array[match]);
  }

  static getClientIP() {
    let ip = '';

    if (typeof process !== 'undefined' && process.env.NODE_ENV === 'development') {
      // In a Node.js environment (for testing purposes)
      ip = '127.0.0.1';
    } else if (typeof window !== 'undefined') {
      // In a browser environment
      ip = window.location.hostname;
    }

    return ip;
  }

  static getVMStates() {
    return [
      { state: 'No state', color: 'white' },
      { state: 'Running', color: 'green' },
      { state: 'Blocked on resource', color: 'gray' },
      { state: 'Suspended', color: 'orange' },
      { state: 'Shutting down ...', color: 'yellow' },
      { state: 'Shut off', color: 'red' },
      { state: 'Crashed', color: 'black' },
      { state: 'Suspended by guest power management', color: 'blue' }
    ];
  }

  static getNetworkInterfaces() {
    const nets = execSync("cat /proc/net/dev | awk '{print $1}' | grep : | sed 's/.$/'").toString().split('\n');

    // Filter out unwanted interfaces
    const filteredNets = nets.filter(v => v !== 'lo' && v !== '' && !v.startsWith('macvtap'));

    return filteredNets;
  }

  static getNetworkDetails(interface) {
    const output = execSync(`ifconfig ${interface} | grep 'inet ' | awk '{print $2, $4, $6}'`).toString().split(' ');

    const nets = {
      ip: output[0],
      mask: output[1],
      broadcast: output[2]
    };

    return nets;
  }

  static getRandomPassword(min, max) {
    const alphabet = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789';
    let pass = '';
    const passwordLength = Math.floor(Math.random() * (max - min + 1)) + min;

    for (let i = 1; i <= passwordLength; i++) {
      const randomIndex = Math.floor(Math.random() * alphabet.length);
      pass += alphabet.charAt(randomIndex);
    }

    return pass;
  }

  static getDevice(prefix, id) {
    return prefix + getDeviceLetter(id);
  }

  static getImageFormat(id) {
    const type = { '0': 'raw', '1': 'qcow', '2': 'qcow2' };
    return type[id];
  }

  static getDeviceLetter(id) {
    const devs = 'abcdefghijklmnopqrstuvwxyz'.split('');
    const numeric = id % devs.length;
    const letter = devs[numeric];
    const num = Math.floor(id / devs.length);

    if (num > 0) {
      return getDeviceLetter(num - 1) + letter;
    } else {
      return letter;
    }
  }

  static getSizeUnit(id) {
    const units = { '0': 'B', '1': 'KiB', '2': 'MiB', '3': 'GiB', '4': 'TiB', '5': 'PiB', '6': 'EiB' };
    return units[id];
  }
}

module.exports = { Common: Common };
