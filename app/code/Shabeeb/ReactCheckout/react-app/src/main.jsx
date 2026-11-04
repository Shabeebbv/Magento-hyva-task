import { createRoot } from 'react-dom/client';
import App from './App.jsx';

// The PHP template prints <div id="react-checkout" data-config="{...}">; React takes over that element.
const element = document.getElementById('react-checkout');
if (element) {
    const config = JSON.parse(element.dataset.config);
    createRoot(element).render(<App config={config} />);
}
