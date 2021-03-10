/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */


class Abstract {
    /**
     * @config areaName
     */
    /**
     * @config mfaDevice
     */

    constructor(config) {
        _.assign(this, config)
    }
    async unlock () {}
}

export default Abstract
