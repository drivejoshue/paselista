import React from 'react';
import { createRoot } from 'react-dom/client';

import App from './App';
import '../../css/schoolcore.css';

const rootElement = document.getElementById('schoolcore-root');

if (!rootElement) {
    throw new Error('No existe #schoolcore-root');
}

createRoot(rootElement).render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
);
