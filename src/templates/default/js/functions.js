$(document).ready(function() {
 $('#vm-edit').submit(function (e) {
  e.preventDefault();
  var formdata = new FormData($('#vm-edit')[0]);
  $.ajax({
   url: 'vm-edit.php',
   type: 'POST',
   data: formdata,
   contentType: false,
   processData: false
  }).done(function (response) {
   var result = jQuery.parseJSON(response);
   if (result.error != 0) {
    $('#error').show();
    $('#error-message').text(result.message);
   } else window.location.href = './?page=vm&id=' + formdata.get('id');
  });
 });
 
 $('#storage-new').submit(function (e) {
  e.preventDefault();
  var formdata = new FormData($('#storage-new')[0]);
  $.ajax({
   url: 'storage-new.php',
   type: 'POST',
   data: formdata,
   contentType: false,
   processData: false
  }).done(function (response) {
   var result = jQuery.parseJSON(response);
   if (result.error != 0) {
    $('#error').show();
    $('#error-message').text(result.message);
   } else window.location.href = './?page=storage';
  });
 });

 $('#storage-volume-new').submit(function (e) {
  e.preventDefault();
  var formdata = new FormData($('#storage-volume-new')[0]);
  $.ajax({
   url: 'storage-volume-new.php',
   type: 'POST',
   data: formdata,
   contentType: false,
   processData: false
  }).done(function (response) {
   var result = jQuery.parseJSON(response);
   if (result.error != 0) {
    $('#error').show();
    $('#error-message').text(result.message);
   } else window.location.href = './?page=storage-volumes&id=' + formdata.get('pool');
  });
 });

 $('#vm-new').submit(function (e) {
  e.preventDefault();
  var formdata = new FormData($('#vm-new')[0]);
  $.ajax({
   url: 'vm-new.php',
   type: 'POST',
   data: formdata,
   contentType: false,
   processData: false
  }).done(function (response) {
   var result = jQuery.parseJSON(response);
   if (result.error != 0) {
    $('#error').show();
    $('#error-message').text(result.message);
   } else window.location.href = './?page=vms';
  });
 });
});

function getNetworkUsage() {
 $.post('net_usage.php')
  .done(function(data) {
    $('#net').html(data);
  });
}

function DeleteVM(id, name) {
 if (confirm('Do you really want to delete this VM: ' + name + ' ?')) {
  VMControl('delete', id);
 }
}

function VMControl(action, id) {
 $.ajax({
  url: 'vm-control.php?action=' + action + '&id=' + id,
 }).done(function (response) {
  var result = jQuery.parseJSON(response);
  if (result.error != 0) alert('Error:' + result.message);
  else {
   if (action == 'delete') window.location.href = './?page=vms';
  }
 });
}

function DeleteVolume(pool, volume) {
 if (confirm('Do you really want to delete this storage volume: ' + volume + ' ?')) {
  $.ajax({
   url: 'storage-volume-delete.php?pool=' + pool + '&volume=' + volume,
  }).done(function (response) {
   var result = jQuery.parseJSON(response);
   if (result.error != 0) alert('Error:' + result.message);
   else window.location.href = './?page=storage-volumes&id=' + pool;
  });
 }
}

function DeletePool(pool) {
 if (confirm('Do you really want to delete this storage pool: ' + pool + ' ?')) {
  $.ajax({
   url: 'storage-delete.php?pool=' + pool,
  }).done(function (response) {
   var result = jQuery.parseJSON(response);
   if (result.error != 0) alert('Error:' + result.message);
   else window.location.href = './?page=storage';
  });
 }
}

function UpdateVMs() {
 // TODO: we need to update VM states, VNC port and other stuff
}

function UpdateVM(id) {
 UpdateVMLoop(id);
 setInterval(function() { UpdateVMLoop(id); }, 2000);
}

function UpdateVMLoop(id) {
 $.ajax({
  url: 'vm-state.php?id=' + id
 }).done(function(response) {
  var result = jQuery.parseJSON(response);
  if (result.error == 0) {
   switch (result.message) {
    case 0: // No state
     $('#vm-button-start').show();
     $('#vm-button-shutdown').show();
     $('#vm-button-force-shutdown').show();
     $('#vm-button-restart').show();
     $('#vm-button-force-restart').show();
     $('#vm-button-suspend').show();
     $('#vm-button-resume').show();
     $('#vm-button-delete').show();
     break;
    case 1: // Running
     $('#vm-button-start').hide();
     $('#vm-button-shutdown').show();
     $('#vm-button-force-shutdown').show();
     $('#vm-button-restart').show();
     $('#vm-button-force-restart').show();
     $('#vm-button-suspend').show();
     $('#vm-button-resume').hide();
     $('#vm-button-delete').hide();
     break;
    case 2: // Blocked on resource
     $('#vm-button-start').show();
     $('#vm-button-shutdown').show();
     $('#vm-button-force-shutdown').show();
     $('#vm-button-restart').show();
     $('#vm-button-force-restart').show();
     $('#vm-button-suspend').show();
     $('#vm-button-resume').show();
     $('#vm-button-delete').show();
     break;
    case 3: // Suspended
     $('#vm-button-start').hide();
     $('#vm-button-shutdown').show();
     $('#vm-button-force-shutdown').show();
     $('#vm-button-restart').show();
     $('#vm-button-force-restart').show();
     $('#vm-button-suspend').hide();
     $('#vm-button-resume').show();
     $('#vm-button-delete').hide();
     break;
    case 4: // Shutting down
     $('#vm-button-start').hide();
     $('#vm-button-shutdown').hide();
     $('#vm-button-force-shutdown').show();
     $('#vm-button-restart').show();
     $('#vm-button-force-restart').show();
     $('#vm-button-suspend').show();
     $('#vm-button-resume').show();
     $('#vm-button-delete').hide();
     break;
    case 5: // Shut off
     $('#vm-button-start').show();
     $('#vm-button-shutdown').hide();
     $('#vm-button-force-shutdown').hide();
     $('#vm-button-restart').hide();
     $('#vm-button-force-restart').hide();
     $('#vm-button-suspend').hide();
     $('#vm-button-resume').hide();
     $('#vm-button-delete').show();
     break;
    case 6: // Crashed
     $('#vm-button-start').show();
     $('#vm-button-shutdown').show();
     $('#vm-button-force-shutdown').show();
     $('#vm-button-restart').show();
     $('#vm-button-force-restart').show();
     $('#vm-button-suspend').show();
     $('#vm-button-resume').show();
     $('#vm-button-delete').show();
     break;
    case 7: // Suspended by guest power management
     $('#vm-button-start').show();
     $('#vm-button-shutdown').show();
     $('#vm-button-force-shutdown').show();
     $('#vm-button-restart').show();
     $('#vm-button-force-restart').show();
     $('#vm-button-suspend').show();
     $('#vm-button-resume').show();
     $('#vm-button-delete').show();
     break;
   }
  }
 });
}

function ReloadScreenshot(path) {
 $('#screenshot').attr('src', path + '&' + new Date().getTime());
}

window.network_id = 0;
function VMNewAddNetwork() {
 $.get('vm-new-add-network.php?id=' + window.network_id, function(data) {
  window.network_id++;
  $('#networks').append(data);
 });
}

window.disk_id = 0;
function VMNewAddNewDisk() {
 $.get('vm-new-add-new-disk.php?id=' + window.disk_id, function(data) {
  window.disk_id++;
  $('#disks').append(data);
 });
}

function VMNewAddExistingDisk() {
 $.get('vm-new-add-existing-disk.php?id=' + window.disk_id, function(data) {
  window.disk_id++;
  $('#disks').append(data);
 });
}

function VMNewAddExistingDiskImageList(storage, id) {
 $.get('vm-new-add-existing-disk-images.php?storage=' + storage, function(data) {
  $('#image_' + id).html(data);
 });
}

function VMNewAddDiskDevice() {
 $.get('vm-new-add-disk-device.php?id=' + window.disk_id, function(data) {
  window.disk_id++;
  $('#disks').append(data);
 });
}

function VMNewRemoveDisk(elem) {
 $(elem).parent().parent().parent().parent().parent().parent().remove();
}

function VMNewRemoveNetwork(elem) {
 $(elem).parent().parent().parent().parent().parent().parent().remove();
}

function startConsole(elem) {
 $(elem).parent().css('border', '10px solid green');
}

function stopConsole(elem) {
 $(elem).parent().css('border', '10px solid red');
}
