'use strict';

/**
 * 1. In your webpack.config.js, create an alias with : 
 * 	  config.resolve.alias.emsch = path.resolve(__dirname, 'PATH_TO_THIS_FILE');
 * 2. In your app.js add  : import {} from 'emsch';
 * 3. Import scss into your sass master file
 * 4. Create the minified files with yarn or webpack
 */
import showAdminMenu from './js/showAdminMenu';

showAdminMenu();