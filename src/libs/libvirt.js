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

}
