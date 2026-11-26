import './bootstrap';

import Alpine from 'alpinejs';
import profileRegion from './profile-region';
import verificationChecklist from './verification-checklist';
import { downloadMemberCard } from './member-card-export';

window.downloadKkiMemberCard = downloadMemberCard;

window.Alpine = Alpine;

Alpine.data('profileRegion', profileRegion);
Alpine.data('verificationChecklist', verificationChecklist);
Alpine.start();

import './table-controls';

import './create-form-modal';

import './auth-modal';

import './page-loader';

import './public-statistics';
