#!/usr/bin/env bash

APP='ds-composites'

# check if override exists then run it
SCRIPT_DIR="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
SCRIPT_NAME=$(basename "$0")
OVERRIDE="$(realpath -s "$SCRIPT_DIR/../../../../../../aws/hooks")"

if [ -f "$OVERRIDE/$SCRIPT_NAME" ]; then
  /bin/bash "$OVERRIDE/$SCRIPT_NAME"
  exit 0
fi


VERSION=`cat /home/ubuntu/apps/$APP/current/.version`

if [[ $SERVER_TYPE ]]; then
  ENV="${SERVER_TYPE}"
elif [[ $DEPLOYMENT_GROUP_NAME ]]; then
  # cut string after - found
  ENV="${DEPLOYMENT_GROUP_NAME%-*}"
else
  ENV="develop"
fi

echo -e "\033[36m ApplicationStart \033[0m \033[32m${APP}\033[0m - \033[35m${VERSION}\033[0m \033[33m($ENV)\033[0m"

# run Build stuff
echo -ne "\033[36m Running ANT ...  \033[0m"
cd /home/ubuntu/apps/$APP/current

# switch to correct Node version
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"  # This loads nvm
[ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"  # This loads nvm bash_completion
nvm use 18

ant assets
echo -e "\033[36m ANT - OK \033[0m"

echo -ne "\033[36m Running Drupal stuff ...  \033[0m"
./vendor/drush/drush/drush rli --library jquery-ui-touch-punch.zip
./vendor/drush/drush/drush locale-update
./vendor/drush/drush/drush cr
./vendor/drush/drush/drush cim --no-interaction
./vendor/drush/drush/drush updatedb
# run webform repair if webform_nugget exists
if ./vendor/drush/drush/drush pm:list --type=module --status=enabled | grep -q 'webform_nugget'; then
  ./vendor/drush/drush/drush webform-repair --no-interaction
else
  echo "webform_nugget module not found, skipping webform-repair."
fi
./vendor/drush/drush/drush cr
echo -e "\033[36m Drush - OK \033[0m"

# take generic media icons from media module if they don't exist in public folder
echo -ne "\033[36m Generating generic media icons ...  \033[0m"
MEDIA_ICON_DIRECTORY_PUBLIC="/home/ubuntu/shared-folders/public-files/${APP}/styles/thumbnail/public/media-icons/generic"
if ! [ -d "$MEDIA_ICON_DIRECTORY_PUBLIC" ]; then
  mkdir -p $MEDIA_ICON_DIRECTORY_PUBLIC
fi

MEDIA_ICON_DIRECTORY="/home/ubuntu/shared-folders/public-files/${APP}/media-icons/generic"
if ! [ -d "$MEDIA_ICON_DIRECTORY" ]; then
  mkdir -p $MEDIA_ICON_DIRECTORY
  cp web/core/modules/media/images/icons/* $MEDIA_ICON_DIRECTORY
fi
echo -e "\033[36m OK \033[0m"

# ADD HERE PROJECT SPECIFIC THINGS!!!!
