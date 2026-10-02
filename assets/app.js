// 1. Bootstrap en premier
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';

// 2. Nos styles après : ils surchargent Bootstrap
import './styles/app.css';

// 3. Scripts globaux (présents sur toutes les pages)
import './scripts/stars.js';
import './scripts/navbar.js';
import './scripts/page_transition.js';
import './scripts/pdf_viewer.js';

// 4. Stimulus : comportements attachés à un élément via data-controller (assets/controllers/)
import './bootstrap.js';
