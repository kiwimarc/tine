/*
 * Tine 2.0
 *
 * @package     Tine
 * @subpackage  Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

// message bus
var postal = require('postal');
require('postal.federation');
require('script-loader!store2');
require('script-loader!store2/src/store.bind.js');
require('postal.xwindow');
require('postal.request-response');

// custom ext build
require('../../library/ExtJS/ext-custom');

// include traditional stuff as defined in jsb2
require('./../../Tinebase/Tinebase.jsb2');
require('./CommunityIdentNr/explainer');

// UI style >= 2019
require('node-waves');
require('node-waves/src/less/waves.less');
require('../css/flat.less');

// other libs
var lodash = require('lodash');
var director = require('director');

require('./ux/util/screenshot');

module.exports = {
    director: director,
    postal: postal,
    lodash: lodash,
    _: lodash
};
