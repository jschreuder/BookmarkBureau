import { routes } from './app.routes';

describe('App Routes', () => {
  it('should export routes', () => {
    expect(routes).toBeDefined();
  });

  it('should have at least 4 routes', () => {
    expect(routes.length).toBeGreaterThanOrEqual(4);
  });

  it('should have empty path redirect to /dashboard', () => {
    const emptyRoute = routes.find(route => route.path === '');
    expect(emptyRoute).toBeDefined();
    expect(emptyRoute?.redirectTo).toBe('/dashboard');
    expect(emptyRoute?.pathMatch).toBe('full');
  });

  it('should have dashboard route', () => {
    const dashboardRoute = routes.find(route => route.path === 'dashboard');
    expect(dashboardRoute).toBeDefined();
  });

  it('should load dashboard children lazily', () => {
    const dashboardRoute = routes.find(route => route.path === 'dashboard');
    expect(dashboardRoute?.loadChildren).toBeDefined();
  });

  it('should have admin route', () => {
    const adminRoute = routes.find(route => route.path === 'admin');
    expect(adminRoute).toBeDefined();
  });

  it('should load admin children lazily', () => {
    const adminRoute = routes.find(route => route.path === 'admin');
    expect(adminRoute?.loadChildren).toBeDefined();
  });

  it('should have wildcard route that redirects to /dashboard', () => {
    const wildcardRoute = routes.find(route => route.path === '**');
    expect(wildcardRoute).toBeDefined();
    expect(wildcardRoute?.redirectTo).toBe('/dashboard');
  });

  it('should define routes array in correct order', () => {
    expect(routes[0].path).toBe('');
    expect(routes[routes.length - 1].path).toBe('**');
  });

  it('should not have duplicate paths', () => {
    const paths = routes.map(route => route.path);
    const uniquePaths = new Set(paths);
    expect(uniquePaths.size).toBe(paths.length);
  });
});
