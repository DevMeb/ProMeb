import axios from 'axios';
window.axios = axios;

axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
