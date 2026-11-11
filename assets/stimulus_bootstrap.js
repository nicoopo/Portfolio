import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// UX Inspector (dev) : lit l'application Stimulus pour repérer cibles manquantes, actions sans méthode…
// app.debug suit kernel.debug : jamais exposé en prod
if (app.debug) {
    window.Stimulus = app;
}
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
