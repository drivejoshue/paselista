import React from 'react';
import { createRoot } from 'react-dom/client';
import TeacherApp, { type TeacherBoot } from './TeacherApp';

import '../../css/schoolcore.css';

const root = document.getElementById('teacher-root');
const payload = document.getElementById('teacher-bootstrap')?.textContent;

if (!root || !payload) {
    throw new Error('No se encontraron los datos del portal docente.');
}

createRoot(root).render(
    <React.StrictMode>
        <TeacherApp boot={JSON.parse(payload) as TeacherBoot} />
    </React.StrictMode>
);
