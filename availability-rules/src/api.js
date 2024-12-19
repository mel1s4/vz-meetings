import axios from 'axios';

function getRestUrl() {
  if (window && window.vz_availability_rules_params && window.vz_availability_rules_params.rest_url) {  
    return window.vz_availability_rules_params.rest_url;
  }
  return 'http://localhost/wp-json/';
}

function getRestNonce() {
  if (window && window.vz_availability_rules_params && window.vz_availability_rules_params.rest_nonce) {
    return window.vz_availability_rules_params.rest_nonce;
  }
  return '';
}

const api = {
  post: async (endpoint, params) => {
    const restUrl = getRestUrl();
    const restNonce = getRestNonce();
    try {
      return await axios.post(restUrl + 'vz-am/v1/' + endpoint, params, {
        headers: {
          'X-WP-Nonce': restNonce
        }
      });
    } catch(e) {
      console.log('Failed to fecth', e);
    }
  },
  get: async (endpoint, params) => {
    const restUrl = getRestUrl();
    const restNonce = getRestNonce();
    try {
      return await axios.get(restUrl + 'vz-am/v1/' + endpoint, {
        params,
        headers: {
          'X-WP-Nonce': restNonce
        }
      });
    } catch(e) {
      console.log('Failed to fecth', e);
    }
  }
}

export default api;
