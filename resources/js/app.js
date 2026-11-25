import './bootstrap';
import './confirm-dialog';
import Alpine from 'alpinejs';
import combobox from './combobox';

window.Alpine = Alpine;
Alpine.data('combobox', combobox);
Alpine.start();
