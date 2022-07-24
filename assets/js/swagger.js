const SwaggerUI = require('swagger-ui')

require('swagger-ui/dist/swagger-ui.css');
require('buffer')
require('stream-browserify')

SwaggerUI({
    dom_id: '#swagger',
    spec: require('./swagger.json')
})