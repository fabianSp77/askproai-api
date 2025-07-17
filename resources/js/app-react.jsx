import './bootstrap';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ConfigProvider } from 'antd';
import deDE from 'antd/locale/de_DE';
import ErrorBoundary from './components/ErrorBoundary';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'AskProAI';

// Check if we're using the full Inertia or our fallback
const pageData = document.getElementById('app')?.dataset?.page;
if (pageData) {
    // Fallback mode
    const page = JSON.parse(pageData);
    const PageComponent = resolvePageComponent(`./Pages/${page.component}.jsx`, import.meta.glob('./Pages/**/*.jsx'));
    
    PageComponent.then(({ default: Component }) => {
        const root = createRoot(document.getElementById('app'));
        root.render(
            <ErrorBoundary>
                <ConfigProvider locale={deDE}>
                    <Component {...page.props} />
                </ConfigProvider>
            </ErrorBoundary>
        );
    });
} else {
    // Full Inertia mode
    createInertiaApp({
        title: (title) => `${title} - ${appName}`,
        resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
        setup({ el, App, props }) {
            const root = createRoot(el);

            root.render(
                <ErrorBoundary>
                    <ConfigProvider locale={deDE}>
                        <App {...props} />
                    </ConfigProvider>
                </ErrorBoundary>
            );
        },
        progress: {
            color: '#4B5563',
        },
    });
}