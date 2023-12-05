const sharp = require('sharp');

class Libvirt {
  constructor(debug = false) {
    this.conn = null;
    this.lastError = null;
    this.allowCached = true;
    this.dominfos = [];

    if (debug) {
      this.setLogfile(debug);
    }
  }

  setLogfile(debug) {
    console.log(`Setting logfile to: ${debug}`);
  }
  _setLastError() {
    this.lastError = libvirt_get_last_error();
    return false;
  }

  setLogfile(filename) {
    if (!libvirt_logfile_set(filename)) {
      return this._setLastError();
    }
    return true;
  }

  printResources() {
    return libvirt_print_binding_resources();
  }

  connect(uri = 'null') {
    this.conn = libvirt_connect(uri, false);
    if (this.conn === false) {
      return this._setLastError();
    }
    return true;
  }

  getMaxVCpus() {
    return libvirt_connect_get_maxvcpus(this.conn);
  }

  generateUuid() {
    const ret = [];
    for (let i = 0; i < 16; i++) {
      ret.push(this.macbyte(Math.floor(Math.random() * 256)));
    }

    const a = ret[0] + ret[1] + ret[2] + ret[3];
    const b = ret[4] + ret[5];
    const c = ret[6] + ret[7];
    const d = ret[8] + ret[9];
    const e = ret[10] + ret[11] + ret[12] + ret[13] + ret[14] + ret[15];

    return `${a}-${b}-${c}-${d}-${e}`;
  }

  storageVolumeGetXmlDesc(pool, volume) {
    const resPool = libvirt_storagepool_lookup_by_name(this.conn, pool);
    const resVol = libvirt_storagevolume_lookup_by_name(resPool, volume);
    return libvirt_storagevolume_get_xml_desc(resVol, null);
  }

  storagePoolCreate(res) {
    return libvirt_storagepool_create(res);
  }

  storagePoolDefineXml(xml) {
    return libvirt_storagepool_define_xml(this.conn, xml);
  }

  storagePoolUndefine(pool) {
    const res = libvirt_storagepool_lookup_by_name(this.conn, pool);

    if (libvirt_storagepool_is_active(res)) {
      libvirt_storagepool_destroy(res);
    }

    return libvirt_storagepool_undefine(res);
  }

  storagePoolLookupByName(name) {
    return libvirt_storagepool_lookup_by_name(this.conn, name);
  }

  storagePoolGetXmlDesc(res) {
    return libvirt_storagepool_get_xml_desc(res);
  }

  getIsoImages(path) {
    return libvirt_get_iso_images(path)
  }

  domainGetDiskDevices(domain) {
    return libvirt_domain_get_disk_devices(domain);
  }

  domainGetInterfaceDevices(res) {
    return libvirt_domain_get_interface_devices(res);
  }

  domainGenerateUuid() {
    let uuid = generateUuid();
    while (libvirt_domain_lookup_by_uuid_string(this.conn, uuid)) {
      uuid = generateUuid();
    }

    return uuid;
  }

  domainDiskAdd(domain, img, dev, type = 'scsi', driver = 'raw') {
    const dom = getDomainObject(domain);
    const tmp = libvirt_domain_disk_add(dom, img, dev, type, driver);

    return tmp ? tmp : _setLastError();
  }

  domainChangeNumVCpus(domain, num) {
    const dom = getDomainObject(domain);
    const tmp = libvirt_domain_change_vcpus(dom, num);

    return tmp ? tmp : _setLastError();
  }

  domainChangeMemoryAllocation(domain, memory, maxmem) {
    const dom = getDomainObject(domain);
    const tmp = libvirt_domain_change_memory(dom, memory, maxmem);

    return tmp ? tmp : _setLastError();
  }

  domainChangeBootDevices(domain, first, second) {
    const dom = getDomainObject(domain);
    const tmp = libvirt_domain_change_boot_devices(dom, first, second);

    return tmp ? tmp : _setLastError();
  }

  domainCreate(name, ram, cpu, iso, disks, nets) {
    return libvirt_domain_new(this.conn, name, null, ram, ram, cpu, iso, disks, nets, VIR_DOMAIN_FLAG_FEATURE_ACPI);
  }

  async domainGetScreenshot(domain, convert = true) {
    const dom = getDomainObject(domain);
    const tmp = libvirt_domain_get_screenshot_api(dom);

    if (!tmp) {
      return _setLastError();
    }

    let mime = tmp.mime;
    let data;

    if (convert && tmp.mime !== "image/png") {
      try {
        const buffer = await sharp(tmp.file)
          .png()
          .toBuffer();

        data = buffer.toString('base64');
        mime = "image/png";
      } catch (error) {
        console.error(`Error converting image format: ${error.message}`);
        return _setLastError();
      }
    } else {
      const fs = require('fs');
      data = fs.readFileSync(tmp.file, 'base64');
    }

    fs.unlinkSync(tmp.file);

    const result = { data, mime };
    return result;
  }

  async domainGetScreenshotThumbnail(domain, width = 120) {
    const screen = await domainGetScreenshot(domain);

    if (!screen) {
      return false;
    }

    try {
      const thumbnailBuffer = await sharp(Buffer.from(screen.data, 'base64'))
        .resize(width, null)
        .toBuffer();

      screen.data = thumbnailBuffer.toString('base64');
      return screen;
    } catch (error) {
      console.error(`Error creating thumbnail: ${error.message}`);
      return false;
    }
  }

  async domainGetScreenDimensions(domain) {
    const screen = await domainGetScreenshot(domain);

    if (!screen) {
      return { height: false, width: false };
    }

    try {
      const imgBuffer = Buffer.from(screen.data, 'base64');
      const dimensions = await sharp(imgBuffer).metadata();
      return { height: dimensions.height, width: dimensions.width };
    } catch (error) {
      console.error(`Error getting screen dimensions: ${error.message}`);
      return { height: false, width: false };
    }
  }

  domainSendKeys(domain, keys) {
    const dom = getDomainObject(domain);
    const tmp = libvirt_domain_send_keys(dom, getHostname(), keys);

    return tmp ? tmp : _setLastError();
  }

  domainSendPointerEvent(domain, x, y, clicked = 1) {
    const dom = getDomainObject(domain);
    const tmp = libvirt_domain_send_pointer_event(dom, getHostname(), x, y, clicked, true);

    return tmp ? tmp : _setLastError();
  }

  domainDiskRemove(domain, dev) {
    const dom = getDomainObject(domain);
    const tmp = libvirt_domain_disk_remove(dom, dev);

    return tmp ? tmp : _setLastError();
  }

  supports(name) {
    return libvirt_has_feature(name);
  }

  macbyte(val) {
    return (val < 16) ? '0' + val.toString(16) : val.toString(16);
  }

  generateRandomMacAddr(seed = false) {
    if (!seed) seed = 1;

    let prefix;
    const hypervisorName = getHypervisorName();

    if (hypervisorName === 'qemu') {
      prefix = '52:54:00';
    } else if (hypervisorName === 'xen') {
      prefix = '00:16:3e';
    } else {
      const macbyte = val => (val < 16) ? '0' + val.toString(16) : val.toString(16);

      prefix = macbyte((seed * Math.random()) % 256) +
        ':' + macbyte((seed * Math.random()) % 256) +
        ':' + macbyte((seed * Math.random()) % 256);
    }

    return prefix +
      ':' + macbyte((seed * Math.random()) % 256) +
      ':' + macbyte((seed * Math.random()) % 256) +
      ':' + macbyte((seed * Math.random()) % 256);
  }

  domainNicAdd(domain, mac, network, model = false) {
    const dom = getDomainObject(domain);

    if (model === 'default') {
      model = false;
    }
    const tmp = libvirt_domain_nic_add(dom, mac, network, model);

    return tmp ? tmp : _setLastError();
  }

  domainNicRemove(domain, mac) {
    const dom = getDomainObject(domain);
    const tmp = libvirt_domain_nic_remove(dom, mac);

    return tmp ? tmp : _setLastError();
  }

  getConnection() {
    return this.conn;
  }

  getHostname() {
    return libvirt_connect_get_hostname(this.conn);
  }

  getDomainObject(nameRes) {
    if (is_resource(nameRes)) {
      return nameRes;
    }

    let dom = libvirt_domain_lookup_by_name(this.conn, nameRes);
    if (!dom) {
      dom = libvirt_domain_lookup_by_uuid_string(this.conn, nameRes);
      if (!dom) {
        return this._setLastError();
      }
    }

    return dom;
  }

  getXpath(domain, xpath, inactive = false) {
    const dom = this.get_domain_object(domain);
    let flags = 0;
    if (inactive) {
      flags = VIR_DOMAIN_XML_INACTIVE;
    }
    const tmp = libvirt_domain_xml_xpath(dom, xpath, flags);

    if (!tmp) {
      return this._setLastError();
    }

    return tmp;
  }

  getCdromStats(domain, sort = true) {
    const dom = this.getDomainObject(domain);
    const buses = this.getXpath(dom, '//domain/devices/disk[@device="cdrom"]/target/@bus', false);
    const disks = this.getXpath(dom, '//domain/devices/disk[@device="cdrom"]/target/@dev', false);
    const ret = [];

    for (let i = 0; i < disks['num']; i++) {
      const tmp = libvirt_domain_get_block_info(dom, disks[i]);
      if (tmp) {
        tmp['bus'] = buses[i];
        ret.push(tmp);
      } else {
        this._setLastError();
      }
    }

    if (sort) {
      ret.sort((a, b) => a['device'].localeCompare(b['device']));
    }

    return ret;
  }

  getDiskStats(domain, sort = true) {
    const dom = this.getDomainObject(domain);
    const buses = this.getXpath(dom, '//domain/devices/disk[@device="disk"]/target/@bus', false);
    const disks = this.getXpath(dom, '//domain/devices/disk[@device="disk"]/target/@dev', false);
    const ret = [];

    for (let i = 0; i < disks['num']; i++) {
      const tmp = libvirt_domain_get_block_info(dom, disks[i]);
      if (tmp) {
        tmp['bus'] = buses[i];
        ret.push(tmp);
      } else {
        this._setLastError();
      }
    }

    if (sort) {
      ret.sort((a, b) => a['device'].localeCompare(b['device']));
    }

    return ret;
  }

  getNicInfo(domain) {
    const dom = this.getDomainObject(domain);
    const macs = this.getXpath(dom, '//domain/devices/interface[@type="network"]/mac/@address', false);

    if (!macs) {
      this._setLastError();
      return [];
    }

    const ret = [];

    for (let i = 0; i < macs['num']; i++) {
      const tmp = libvirt_domain_get_network_info(dom, macs[i]);

      if (tmp) {
        ret.push(tmp);
      } else {
        this._setLastError();
      }
    }

    return ret;
  }

  getDomainType(domain) {
    const dom = this.getDomainObject(domain);
    const tmp = this.getXpath(dom, '//domain/@type', false);

    if (tmp['num'] === 0) {
      this._setLastError();
      return null;
    }

    const ret = tmp[0];
    return ret;
  }

  getDomainEmulator(domain) {
    const dom = this.getDomainObject(domain);
    const tmp = this.getXpath(dom, '//domain/devices/emulator', false);

    if (tmp['num'] === 0) {
      this._setLastError();
      return null;
    }

    const ret = tmp[0];
    return ret;
  }

  get_network_cards(domain) {
    const dom = this.getDomainObject(domain);
    const nics = this.getXpath(dom, '//domain/devices/interface[@type="network"]', false);

    if (!Array.isArray(nics)) {
      this._setLastError();
      return null;
    }

    return nics['num'];
  }

  get_disk_capacity(domain, physical = false, disk = '*', unit = '?') {
    const dom = this.getDomainObject(domain);
    const tmp = this.getXpath(dom);
    let ret = 0;

    for (let i = 0; i < tmp.length; i++) {
      if (disk === '*' || tmp[i]['device'] === disk) {
        if (physical) {
          ret += tmp[i]['physical'];
        } else {
          ret += tmp[i]['capacity'];
        }
      }
    }

    return this.formatSize(ret, 2, unit);
  }
}
