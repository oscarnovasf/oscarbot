/**
 * @file
 * Custom scripts to render file fields with Swagger UI.
 */

(function ($, window, Drupal, drupalSettings) {

  Drupal.behaviors.swaggerUIFormatter = {
    attach: function (context) {
      var swaggerSettings = drupalSettings.swaggerUIFormatter;

      var validatorUrl = undefined;
      switch (swaggerSettings.validator) {
        case 'custom':
          validatorUrl = swaggerSettings.validatorUrl;
          break;

        case 'none':
          validatorUrl = null;
          break;
      }

      // See https://github.com/swagger-api/swagger-ui/blob/master/docs/usage/configuration.md.
      var options = {
        url: swaggerSettings.swaggerFile,
        dom_id: '#swagger-ui',
        defaultModelsExpandDepth: -1,
        presets: [
          SwaggerUIBundle.presets.apis,
          // This is a dirty hack but it works out of the box.
          // See https://github.com/swagger-api/swagger-ui/issues/3229.
          swaggerSettings.showTopBar ? SwaggerUIStandalonePreset : SwaggerUIStandalonePreset.slice(1)
        ],
        plugins: [
          SwaggerUIBundle.plugins.DownloadUrl
        ],
        validatorUrl: validatorUrl,
        docExpansion: swaggerSettings.docExpansion,
        layout: "StandaloneLayout",
        tagsSorter: swaggerSettings.sortTagsByName ? 'alpha' : '',
        supportedSubmitMethods: swaggerSettings.supportedSubmitMethods,
        oauth2RedirectUrl: swaggerSettings.oauth2RedirectUrl,
        persistAuthorization: "true"
      };

      $(window).trigger('swaggerUIFormatterOptionsAlter', options);
      window['swagger_ui'] = SwaggerUIBundle(options);
    }
  };

}(jQuery, window, Drupal, drupalSettings));
