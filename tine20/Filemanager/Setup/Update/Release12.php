<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

class Filemanager_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.0
     *
     * @return void
     */
    public function update_0()
    {
        $limit = 1000;
        $start = 0;
        $fmc = Filemanager_Controller::getInstance();

        do {
            $users = Tinebase_User::getInstance()->getFullUsers(null, 'accountId', 'ASC', $start, $limit);
            foreach ($users as $user) {
                $createPersonalFolder = false;
                try {
                    // check if user has personal folder
                    $path = Tinebase_FileSystem::getInstance()->getApplicationBasePath(
                            'Filemanager',
                            Tinebase_FileSystem::FOLDER_TYPE_PERSONAL
                        ) . '/' . $user->getId();
                    $node = Tinebase_FileSystem::getInstance()->stat($path);
                    $children = Tinebase_FileSystem::getInstance()->getTreeNodeChildren($node);
                    if (count($children) === 0) {
                        $createPersonalFolder = true;
                    }
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $createPersonalFolder = true;
                }

                if ($createPersonalFolder) {
                    $fmc->createPersonalFileFolder($user, 'Filemanager');
                }
            }
            $start += $limit;
        } while ($users->count() === $limit);

        $this->setApplicationVersion('Filemanager', '12.1');
    }
}
