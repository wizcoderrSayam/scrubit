import Alpine from 'alpinejs';
import bookingChat from './chat';

window.Alpine = Alpine;

Alpine.data('bookingChat', bookingChat);

Alpine.start();
