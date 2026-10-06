/**
 * Asynchronous Google Maps Script Loader
 * Prevents multiple script injections, handles authentication and load errors gracefully.
 */
let googleMapsPromise = null;

export function loadGoogleMaps(apiKey) {
  // If already loaded on window
  if (typeof window !== 'undefined' && window.google && window.google.maps && window.google.maps.places) {
    return Promise.resolve(window.google.maps);
  }

  // If a load request is currently in flight
  if (googleMapsPromise) {
    return googleMapsPromise;
  }

  googleMapsPromise = new Promise((resolve, reject) => {
    if (typeof window === 'undefined') {
      return reject(new Error('Google Maps can only be loaded in a browser environment.'));
    }

    // Check if script tag already exists in the DOM
    const existingScript = document.getElementById('google-maps-api-script');
    if (existingScript) {
      const pollInterval = setInterval(() => {
        if (window.google && window.google.maps && window.google.maps.places) {
          clearInterval(pollInterval);
          resolve(window.google.maps);
        }
      }, 100);
      return;
    }

    const callbackName = '__initGoogleMapsApiCallback_' + Math.random().toString(36).substring(2, 9);
    window[callbackName] = () => {
      delete window[callbackName];
      resolve(window.google.maps);
    };

    const script = document.createElement('script');
    script.id = 'google-maps-api-script';
    script.type = 'text/javascript';
    const keyParam = apiKey ? `key=${encodeURIComponent(apiKey)}&` : '';
    script.src = `https://maps.googleapis.com/maps/api/js?${keyParam}libraries=places&callback=${callbackName}`;
    script.async = true;
    script.defer = true;

    script.onerror = (err) => {
      delete window[callbackName];
      googleMapsPromise = null;
      reject(new Error('Failed to load Google Maps JavaScript API script. Check your API key or network connection.'));
    };

    document.head.appendChild(script);
  });

  return googleMapsPromise;
}
