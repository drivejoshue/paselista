import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import PaseListaLanding from './PaseListaLanding';

const mount = document.getElementById('paselista-landing-root');

if (mount) {
  const coreUrl = mount.dataset.coreUrl || '/';
  const loginUrl = mount.dataset.loginUrl || '/login';
  const demoUrl = mount.dataset.demoUrl || '/soporte';

  createRoot(mount).render(
    <StrictMode>
      <PaseListaLanding coreUrl={coreUrl} loginUrl={loginUrl} demoUrl={demoUrl} />
    </StrictMode>,
  );
}
