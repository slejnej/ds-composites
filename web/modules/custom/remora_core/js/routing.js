import routes from './../../../../sites/default/files/remora_core.routes.json.js';

const PARAMETER_REGEX= /\{(\w+)}/g;
((Drupal) =>  {

  Drupal.Routing = {};

  /**
   * Generate a URL for a route.
   * Will replace any placeholders in the route path with the provided parameters.
   * Will throw an error if the route does not exist.
   * Will throw an error if a required parameter is missing.
   *
   * @param {String} name The machine name of the route to generate a URL for.
   * @param {{[key: string]: string|number}} parameters An object of key/value pairs to replace placeholders with. The key should be the placeholder name, and the value should be the replacement.
   * @return {String} The generated URL.
   */
  Drupal.Routing.generate = (name, parameters = {}) => {
    const route = routes[name];
    if (route === undefined) {
      throw new Error(`The route "${name}" does not exist.`);
    }

    // replace all the parameters that are provided
    let url = route.path;
    for (let key in parameters) {
      url = url.replaceAll(`{${key}}`, parameters[key]);
    }

    // replace all the leftover parameters with the default values
    let missingParameters = [...url.matchAll(PARAMETER_REGEX)];
    if (missingParameters.length > 0) {
      for(let key in route.default_parameters) {
        url = url.replaceAll(`{${key}}`, route.default_parameters[key]);
      }
    }

    // check if there are any parameters left
    missingParameters = [...url.matchAll(PARAMETER_REGEX)];
    if(missingParameters.length > 0) {
      throw new Error(`The route "${name}" is missing required parameters: ${missingParameters.map(match => match[1]).join(', ')}`);
    }

    return url;
  }
})(Drupal);
