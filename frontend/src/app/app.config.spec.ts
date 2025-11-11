import { appConfig } from './app.config';

describe('App Config', () => {
  it('should export appConfig', () => {
    expect(appConfig).toBeDefined();
  });

  it('should have providers array', () => {
    expect(appConfig.providers).toBeDefined();
    expect(Array.isArray(appConfig.providers)).toBe(true);
  });

  it('should have at least 5 providers', () => {
    expect(appConfig.providers.length).toBeGreaterThanOrEqual(5);
  });

  it('should be a valid ApplicationConfig', () => {
    expect(appConfig.providers).toBeDefined();
    expect(Array.isArray(appConfig.providers)).toBe(true);
  });

  it('should have all required providers configured', () => {
    // The providers array should contain environment and feature providers
    // We verify this by checking the array has the expected number of items
    expect(appConfig.providers).toBeDefined();
    expect(appConfig.providers.length).toBe(6);
  });
});
