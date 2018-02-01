const fs = require('fs');
const request = require('request');
const StorageNaive = require('../lib/StorageNaive');

const _BASE_URL = 'http://localhost/php-naive/test'

srcData = fs.readFileSync('test_image.png');

let n = StorageNaive(_BASE_URL+'/filesystem.php');
n.upload(request, '/test_image.png', srcData, function (err, result) { 
  if (err) {
    console.log("Could not upload", err);
    return;
  }
  else {
    n.list_folder(request, '/', function (err, result) {
      
      console.log("hello world", err, result);
      n.permanently_delete(request, '/test_image.png', function(err, result) {

        console.log("Deleted", err);
      })
    });
  }
});
