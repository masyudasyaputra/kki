import './bootstrap';

import Alpine from 'alpinejs';
import profileRegion from './profile-region';

window.Alpine = Alpine;

Alpine.data('profileRegion', profileRegion);
Alpine.start();

import './table-controls';

import './create-form-modal';

import './auth-modal';

import './page-loader';
