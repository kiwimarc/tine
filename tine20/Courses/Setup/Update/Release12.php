<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */
class Courses_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.1
     */
    public function update_0()
    {
        $release11 = new Courses_Setup_Update_Release11($this->_backend);
        $release11->update_0();
        $this->setApplicationVersion('Courses', '12.1');
    }
}
