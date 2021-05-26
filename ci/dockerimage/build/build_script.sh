function activateReleaseMode()
{
    # utc datetime, like this: 2013-09-24 14:27:06
    local DATETIME=`date -u "+%F %T"`

    # set buildtype to DEBUG for beta releases
    if [[ $RELEASE == *beta* ]]; then
        local BUILDTYPE="DEBUG";
    else
        local BUILDTYPE="RELEASE";
    fi

    echo "RELEASE: $RELEASE REVISION: $REVISION CODENAME: $CODENAME BUILDTYPE: $BUILDTYPE";
 
    sed -i -e "s/'default' *=> *'DEVELOPMENT',/'default' => '$BUILDTYPE',/" ${TINE20ROOT}/tine20/Tinebase/Config.php

    sed -i -e "s#'TINE20_CODENAME', *Tinebase_Helper::getDevelopmentRevision()#'TINE20_CODENAME',      '$CODENAME'#" ${TINE20ROOT}/tine20/Tinebase/Core.php
    sed -i -e "s#'TINE20SETUP_CODENAME', *Tinebase_Helper::getDevelopmentRevision()#'TINE20SETUP_CODENAME',      '$CODENAME'#" ${TINE20ROOT}/tine20/Setup/Core.php
    sed -i -e "s/'TINE20_PACKAGESTRING', *'none'/'TINE20_PACKAGESTRING', '$RELEASE'/" ${TINE20ROOT}/tine20/Tinebase/Core.php
    sed -i -e "s/'TINE20SETUP_PACKAGESTRING', *'none'/'TINE20SETUP_PACKAGESTRING', '$RELEASE'/" ${TINE20ROOT}/tine20/Setup/Core.php
    sed -i -e "s/'TINE20_RELEASETIME', *'none'/'TINE20_RELEASETIME', '$DATETIME'/" ${TINE20ROOT}/tine20/Tinebase/Core.php
    sed -i -e "s/'TINE20SETUP_RELEASETIME', *'none'/'TINE20SETUP_RELEASETIME', '$DATETIME'/" ${TINE20ROOT}/tine20/Setup/Core.php
    sed -i -e "s/Tinebase_Helper::getDevelopmentRevision()/Tinebase_Helper::getCodename()/" ${TINE20ROOT}/tine20/build.xml

    if [ -x ${TINE20ROOT}/tine20/Tinebase/License/BusinessEdition.php ]
    then
        sed -i -e "s/= 500;/= 5;/" ${TINE20ROOT}/tine20/Tinebase/License/BusinessEdition.php
    fi

    sed -i -e "s/Tine.clientVersion.buildRevision[^;]*/Tine.clientVersion.buildRevision = '$REVISION'/" ${TINE20ROOT}/tine20/Tinebase/js/tineInit.js
    sed -i -e "s/Tine.clientVersion.codeName[^;]*/Tine.clientVersion.codeName = '$CODENAME'/" ${TINE20ROOT}/tine20/Tinebase/js/tineInit.js
    sed -i -e "s/Tine.clientVersion.packageString[^;]*/Tine.clientVersion.packageString = '$RELEASE'/" ${TINE20ROOT}/tine20/Tinebase/js/tineInit.js
    sed -i -e "s/Tine.clientVersion.releaseTime[^;]*/Tine.clientVersion.releaseTime = '$DATETIME'/" ${TINE20ROOT}/tine20/Tinebase/js/tineInit.js
}

function buildLangStats()
{
    echo -n "building lang stats ... "
    php -f ${TINE20ROOT}/tine20/langHelper.php -- --statistics
    echo "done"
}

function buildClient()
{
    cd ${TINE20ROOT}/tine20/ && ls vendor/bin && vendor/bin/phing build
}

function removeComposerDevDependencies() {
    cd ${TINE20ROOT}/tine20 && composer install --no-dev --no-ansi --no-progress --no-suggest --no-scripts
}

function cleanup() {
    cleanupTinebase
    cleanupCss
    cleanupJs
    cleanupFiles
}

function cleanupCss()
{
    echo "cleanup css files in:"
    CLIENTBUILDFILTER="FAT"

    for FILE in `ls ${TINE20ROOT}/tine20`; do
        # tine20 app needs translations OR Setup dir
        if [ -d "${TINE20ROOT}/tine20/$FILE/translations" ] || [ -d "${TINE20ROOT}/tine20/$FILE/Setup" ]; then
            if [ "$FILE" != "Tinebase"  ]; then
                echo "+ $FILE"
                if [ -d "${TINE20ROOT}/tine20/$FILE/css" ]; then
                    (cd ${TINE20ROOT}/tine20/$FILE/css; rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))
                fi
            fi
        fi
    done
}

function cleanupJs() {
    echo "cleanup js files in:"

    for FILE in `ls "${TINE20ROOT}/tine20"`; do
        # tine20 app needs translations OR Setup dir
        if [ -d "${TINE20ROOT}/tine20/${FILE}/translations" ] || [ -d "${TINE20ROOT}/tine20/${FILE}/Setup" ]; then
            echo "+ ${FILE}"
            if [ -d "${TINE20ROOT}/tine20/${FILE}/js" ]; then
                (cd ${TINE20ROOT}/tine20/$FILE/js;  rm -rf $(ls | grep -v "FAT" | grep -v "\-lang\-" | grep -v "empty\.js"  | grep -v "\.map" | grep -v "pollClient" | grep -v "Locale" | grep -v "ux" | grep -v "node_modules"))
            fi
        fi
    done
}

function cleanupJsWithAssetsJson()
{
    echo "cleanup js files in:"

    for FILE in `ls "${TINE20ROOT}/tine20"`; do
        # tine20 app needs translations OR Setup dir
        if [ -d "${TINE20ROOT}/tine20/${FILE}/translations" ] || [ -d "${TINE20ROOT}/tine20/${FILE}/Setup" ]; then
            echo "+ ${FILE}"
            if [ -d "${TINE20ROOT}/tine20/${FILE}/js" ]; then
                for JSFILE in `ls "${TINE20ROOT}/tine20/${FILE}/js/"`; do
                    if [ "${FILE}/js/${JSFILE}" != "Tinebase/js/webpack-assets-FAT.json" ]; then
                        if ! grep -q "\"${FILE}/js/${JSFILE}\"" "${TINE20ROOT}/tine20/Tinebase/js/webpack-assets-FAT.json"; then
                            rm -rf "${TINE20ROOT}/tine20/${FILE}/js/${JSFILE}"
                        fi
                    fi
                done
            fi
        fi
    done
}

function cleanupFiles() {
    echo "cleanup files:"

    for FILE in `ls ${TINE20ROOT}/tine20`; do
        # tine20 app needs translations OR Setup dir
        if [ ! -d "${TINE20ROOT}/tine20/$FILE/translations" ] && [ ! -d "${TINE20ROOT}/tine20/$FILE/Setup" ]; then
            local FILES="images|library|vendor|docs|fonts|CREDITS|LICENSE|PRIVACY|README|RELEASENOTES|init_plugins.php|favicon.ico"
            local FILES="$FILES|config.inc.php.dist|index.php|langHelper.php|setup.php|tine20.php|bootstrap.php|worker.php|status.php"

            if ! [[ "$FILE" =~ $(echo ^\($FILES\)$) ]]; then
                echo "- $FILE"
                rm -rf "${TINE20ROOT}/tine20/$FILE"
            else
                echo "+ $FILE"
            fi
        fi
    done
}

function cleanupTinebase() {
  echo "cleanup Tinebase:"

  CLIENTBUILDFILTER="FAT"

  (cd ${TINE20ROOT}/tine20/Addressbook/css; rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))
  (cd ${TINE20ROOT}/tine20/Admin/css;       rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))
  (cd ${TINE20ROOT}/tine20/Setup/css;       rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))

  (cd ${TINE20ROOT}/tine20/Tinebase/css;    rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css | grep -v widgets))
  (cd ${TINE20ROOT}/tine20/Tinebase/css/widgets;  rm -rf $(ls | grep -v ${CLIENTBUILDFILTER} | grep -v print.css))

  # cleanup ExtJS
  (cd ${TINE20ROOT}/tine20/library/ExtJS/adapter; rm -rf $(ls | grep -v ext))
  (cd ${TINE20ROOT}/tine20/library/ExtJS/src;     rm -rf $(ls | grep -v debug.js))
  (cd ${TINE20ROOT}/tine20/library/ExtJS;         rm -rf $(ls | grep -v adapter | grep -v ext-all-debug.js | grep -v ext-all.js | grep -v resources | grep -v src))

  # cleanup OpenLayers
  (cd ${TINE20ROOT}/tine20/library/OpenLayers;    rm -rf $(ls | grep -v img | grep -v license.txt | grep -v OpenLayers.js | grep -v theme))

  # cleanup qCal
  (cd ${TINE20ROOT}/tine20/library/qCal;  rm -rf docs tests)

  # save langStats
  (mv ${TINE20ROOT}/tine20/langstatistics.json ${TINE20ROOT}/tine20/Tinebase/translations/langstatistics.json)

  # remove composer dev requires (--no-scripts to prevent post-install-cmds like "git submodule --init")
  composer install --ignore-platform-reqs --no-dev --no-scripts -d ${TINE20ROOT}/tine20

  rm -rf ${TINE20ROOT}/tine20/Tinebase/js/node_modules
  rm -rf ${TINE20ROOT}/tine20/vendor/phpdocumentor
  rm -rf ${TINE20ROOT}/tine20/vendor/ezyang/htmlpurifier/{art,benchmarks,extras,maintenance,smoketests}

  find ${TINE20ROOT}/tine20/vendor -name .gitignore -type f -print0 | xargs -0 rm -rf
  find ${TINE20ROOT}/tine20/vendor -name .git       -type d -print0 | xargs -0 rm -rf
  find ${TINE20ROOT}/tine20/vendor -name docs       -type d -print0 | xargs -0 rm -rf
  find ${TINE20ROOT}/tine20/vendor -name examples   -type d -print0 | xargs -0 rm -rf
  find ${TINE20ROOT}/tine20/vendor -name tests      -type d -print0 | xargs -0 rm -rf

  composer dumpautoload -d ${TINE20ROOT}/tine20

  rm -rf ${TINE20ROOT}/tine20/composer.*
}

function createArchives()
{
    echo "building Tine 2.0 single archives... "
    CLIENTBUILDFILTER="FAT"
    mkdir -p ${TINE20PACKAGES}/source/${RELEASE}/

    for FILE in `ls ${TINE20ROOT}/tine20`; do
        UCFILE=`echo ${FILE} | tr '[A-Z]' '[a-z]'`

        # tine20 app needs translations OR Setup dir
        if [ -d "${TINE20ROOT}/tine20/$FILE/translations" ] || [ -d "${TINE20ROOT}/tine20/$FILE/Setup" ]; then
            case $FILE in
                Addressbook)
                    # handled in Tinebase
                    ;;
                Admin)
                    # handled in Tinebase
                    ;;
                Setup)
                    # handled in Tinebase
                    ;;

                Calendar)
                    echo " $FILE"
                    echo "building "
                    (cd ${TINE20ROOT}/tine20; tar cjf ${TINE20PACKAGES}/source/${RELEASE}/tine20-${UCFILE}_${RELEASE}.tar.bz2 $FILE)
                    (cd ${TINE20ROOT}/tine20; zip -qr ${TINE20PACKAGES}/source/${RELEASE}/tine20-${UCFILE}_${RELEASE}.zip     $FILE)
                    ;;

                Tinebase)
                    echo " $FILE"
                    echo -n "building "
                    local FILES="Addressbook Admin Setup Tinebase CoreData images library vendor docs fonts"
                    local FILES="$FILES config.inc.php.dist index.php langHelper.php setup.php tine20.php bootstrap.php worker.php status.php"
                    local FILES="$FILES CREDITS LICENSE PRIVACY README RELEASENOTES init_plugins.php favicon.ico"

                    # allow to pass files to build as a global var
                    if [ -n "$ADDITIONALRELEASEFILES" ]; then
                        local FILES="$FILES $ADDITIONALRELEASEFILES"
                    fi

                    (cd ${TINE20ROOT}/tine20; tar cjf ${TINE20PACKAGES}/source/${RELEASE}/tine20-${UCFILE}_${RELEASE}.tar.bz2 $FILES)
                    (cd ${TINE20ROOT}/tine20; zip -qr ${TINE20PACKAGES}/source/${RELEASE}/tine20-${UCFILE}_${RELEASE}.zip     $FILES)

                    echo ""
                    ;;

                *)
                    echo " $FILE"
                    echo "building "
                    (cd ${TINE20ROOT}/tine20; tar cjf ${TINE20PACKAGES}/source/${RELEASE}/tine20-${UCFILE}_${RELEASE}.tar.bz2 $FILE)
                    (cd ${TINE20ROOT}/tine20; zip -qr ${TINE20PACKAGES}/source/${RELEASE}/tine20-${UCFILE}_${RELEASE}.zip     $FILE)
                    ;;
            esac
        fi
    done
}

function createSpecialArchives()
{
    echo "building Tine 2.0 allinone archive... "
    mkdir /root/allinone

    for ARCHIVENAME in activesync calendar coredata tinebase crm felamimail filemanager sales tasks timetracker; do
        if [ -e "${TINE20PACKAGES}/source/${RELEASE}/tine20-${ARCHIVENAME}_${RELEASE}.tar.bz2" ]; then
            (cd /root/allinone; tar xjf ${TINE20PACKAGES}/source/${RELEASE}/tine20-${ARCHIVENAME}_${RELEASE}.tar.bz2)
        fi
    done

    (cd /root/allinone; tar cjf ${TINE20PACKAGES}/source/${RELEASE}/tine20-allinone_${RELEASE}.tar.bz2 .)
    (cd /root/allinone; zip -qr ${TINE20PACKAGES}/source/${RELEASE}/tine20-allinone_${RELEASE}.zip     .)


    echo "building Tine 2.0 voip archive... "
    mkdir /root/voip

    for ARCHIVENAME in phone voipmanager; do
        (cd /root/voip; tar xjf ${TINE20PACKAGES}/source/${RELEASE}/tine20-${ARCHIVENAME}_${RELEASE}.tar.bz2)
    done

    (cd /root/voip; tar cjf ${TINE20PACKAGES}/source/${RELEASE}/tine20-voip_${RELEASE}.tar.bz2 .)
    (cd /root/voip; zip -qr ${TINE20PACKAGES}/source/${RELEASE}/tine20-voip_${RELEASE}.zip     .)
}

function packageTranslations()
{
    echo -n "building translation files for translators... "
    php -d include_path=".:${TINE20ROOT}/tine20:${TINE20ROOT}/tine20/library"  -f ${TINE20ROOT}/tine20/langHelper.php -- --package=translations.tar.gz
    mv ${TINE20ROOT}/tine20/translations.tar.gz ${TINE20PACKAGES}/source/${RELEASE}/
    echo "done"
}

function buildChecksum()
{
    echo -n "calculating SHA1 checksums... "

    for fileName in ${TINE20PACKAGES}/source/${RELEASE}/*; do
        (cd ${TINE20PACKAGES}/source/${RELEASE}/; sha1sum `basename $fileName`) >> ${TINE20PACKAGES}/source/${RELEASE}/sha1sum_${RELEASE}.txt 2>&1
    done

    echo "done"
}
