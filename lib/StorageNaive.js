/*

`request` must follow the API as in https://github.com/request/request ; this
allows to use `browser-request` if needed; or wrap `request` to handle requests
headers.

Licence : https://unlicense.org/UNLICENSE

*/
//============================================================================

function StorageNaive(baseUrl) {

  return {

    _url: baseUrl,

    permanently_delete: function(request, path, callback) {
    
      let url = `${this._url}?action=permanently_delete&path=${path}`;
      return request(url, _wrapUserCallback(callback));
    },

    upload: function(request, path, fileContent, callback) {

      let url = `${this._url}?action=upload&path=${path}`;
      return request({
        url: url,
        method: 'POST',
        body: fileContent,
        encoding: null
      }, _wrapUserCallback(callback));
    },

    list_folder: function(request, path, callback) {

      let url = `${this._url}?action=list_folder&path=${path}`;
      return request(url, _wrapUserCallback(callback));
    },
  }
}

//============================================================================

function _wrapUserCallback(callback) {

  return function(err, response, body) {
    
    let result = null;
    if (!err) {
      try {
        result = body;
        if (result) {
          result = JSON.parse(result);
          if (result && result.error) {
            err = result;
            result = null;
          }
        }
      }
      catch (e) {
        err = 'Could not parse JSON response ' + e;
      }
    }
    callback(err, result);
  }
}

//============================================================================

module.exports = StorageNaive