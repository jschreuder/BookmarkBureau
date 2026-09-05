/**
 * Restores a working Web Storage implementation on the global object when it
 * is shadowed by Node's native storage accessors.
 *
 * Starting with Node 25 the Web Storage API is exposed by default as own
 * accessor properties on `globalThis`, but those accessors are non-functional
 * unless Node is started with `--localstorage-file`. Because the properties
 * already exist, vitest 4's jsdom environment does not install jsdom's
 * working Storage implementation on the globals, so `localStorage` and
 * `sessionStorage` resolve to `undefined` in tests.
 *
 * Upstream regression: vitest-dev/vitest#8757 (fixed in vitest 5, which
 * @angular/build does not support yet since it pins `vitest ^4.0.8`). This
 * setup file, registered via the `setupFiles` option of the
 * `@angular/build:unit-test` builder in angular.json, substitutes the Storage
 * of a private jsdom instance so the suite runs unchanged on every supported
 * Node version.
 */

import { JSDOM } from 'jsdom';

function isUsableStorage(value: unknown): value is Storage {
  return (
    typeof value === 'object' &&
    value !== null &&
    typeof (value as Storage).clear === 'function'
  );
}

function exposeStorage(key: 'localStorage' | 'sessionStorage'): void {
  const descriptor = Object.getOwnPropertyDescriptor(globalThis, key);
  if (descriptor?.get === undefined && isUsableStorage(descriptor?.value)) {
    // A working storage implementation is already exposed (Node < 25).
    return;
  }

  const dom = new JSDOM('', { url: 'http://localhost/' });
  Object.defineProperty(globalThis, key, {
    value: dom.window[key],
    configurable: true,
    writable: true,
    enumerable: true,
  });
}

exposeStorage('localStorage');
exposeStorage('sessionStorage');
