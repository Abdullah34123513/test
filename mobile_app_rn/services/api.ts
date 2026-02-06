import axios from 'axios';
import { CONFIG } from '../constants/Config';

const api = axios.create({
    baseURL: CONFIG.API_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

export default api;
