/*

`post` is a function like `(url, body, (err, result) => ...)`

Version 0.0.0
Licence : https://unlicense.org/UNLICENSE

*/
//============================================================================

function StorageNaive(baseUrl, post) {

  return {

    _url: baseUrl,
    _post: post ? post : _xhrPost,

    permanently_delete: function(request, path, callback) {
    
      let url = `${this._url}?action=permanently_delete&path=${path}`;
      return this._post(url, null, callback);
    },

    upload: function(request, path, fileContent, callback) {

      let url = `${this._url}?action=upload&path=${path}`;
      return this._post(url, fileContent, callback);
    },

    list_folder: function(request, path, callback) {

      let url = `${this._url}?action=list_folder&path=${path}`;
      return this._post(url, null, callback);
    },
  }
}

//============================================================================

function _xhrPost(url, payload, callback) {
  // liked this 'old' ref : https://blog.garstasio.com/you-dont-need-jquery/ajax/
  let xhr = new XMLHttpRequest();
  xhr.open('POST', url);
  xhr.onload = function() {
    let err = null;
    let result = null;
    if (xhr.status === 200) {

      try {
        result = JSON.parse(xhr.responseText);
      }
      catch (e) {
        err = e;
      }
    }
    else {
      err = xhr.status;
    }
    callback(err, result);
  };
  xhr.send(payload);
  return xhr;
}

//============================================================================

module.exports = StorageNaive
