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


VERSION=`cat /tmp/$APP/.version`

if [[ $SERVER_TYPE ]]; then
  ENV="${SERVER_TYPE}"
elif [[ $DEPLOYMENT_GROUP_NAME ]]; then
  # cut string after - found
  ENV="${DEPLOYMENT_GROUP_NAME%-*}"
else
  ENV="develop"
fi

echo -e "\033[36m Starting \033[0m \033[32m${APP}\033[0m - \033[35m${VERSION}\033[0m \033[33m($ENV)\033[0m"

# if production then run script for mounting else create folder
if [ $ENV == "production" ]; then
  if [ ! -d /home/ubuntu/shared-folders ]; then
      /bin/bash /home/ubuntu/prod-server-ef.sh
  fi
else
  if [ ! -d /home/ubuntu/shared-folders ]; then
      mkdir -p /home/ubuntu/shared-folders
  fi
fi

if [ ! -d /home/ubuntu/apps/$APP/releases ]; then
  echo -ne "\033[36m Creating releases directory ... \033[0m "
  mkdir -p /home/ubuntu/apps/$APP/releases
  echo -e "\033[36m OK \033[0m"
fi

# move files to project dir with version number
mv /tmp/$APP /home/ubuntu/apps/$APP/releases/$VERSION

if [ ! -d /home/ubuntu/shared-folders/public-files/$APP ]; then
  echo -ne "\033[36m Creating & syncing public-files directory ...  \033[0m"
  mkdir -p /home/ubuntu/shared-folders/public-files/$APP
  /usr/bin/aws s3 sync s3://s3-$APP/$ENV/public-files/ /home/ubuntu/shared-folders/public-files/$APP/ --region eu-west-1
  echo -e "\033[36m Copy public - OK \033[0m"
fi

if [ ! -d /home/ubuntu/shared-folders/private-files/$APP ]; then
  echo -ne "\033[36m Creating & syncing private-files directory ...  \033[0m"
  mkdir -p /home/ubuntu/shared-folders/private-files/$APP

  cp "$(realpath -s "$SCRIPT_DIR/../default/default.htaccess")" /home/ubuntu/shared-folders/private-files/$APP/.htaccess
  chmod 644 /home/ubuntu/shared-folders/private-files/$APP/.htaccess

  /usr/bin/aws s3 sync s3://s3-$APP/$ENV/private-files/ /home/ubuntu/shared-folders/private-files/$APP/ --region eu-west-1 --exclude "backups/*" --exclude "backup_migrate/*"
  echo -e "\033[36m Copy private - OK \033[0m"
fi

if [ ! -d /home/ubuntu/shared-folders/temp-files/$APP ]; then
  echo -ne "\033[36m Creating temp-files directory ...  \033[0m"
  mkdir -p /home/ubuntu/shared-folders/temp-files/$APP
  # don't sync from s3, they're only tmp files
  echo -e "\033[36m Copy temp - OK \033[0m"
fi

# create symlink for public files
ln -s /home/ubuntu/shared-folders/public-files/$APP /home/ubuntu/apps/$APP/releases/$VERSION/web/sites/default/files

# remove if symlink contains project name
if [ -L /home/ubuntu/apps/$APP/releases/$VERSION/web/sites/default/files/$APP ]; then
  rm /home/ubuntu/apps/$APP/releases/$VERSION/web/sites/default/files/$APP
fi

# create translations folder if not exist
if [ ! -d /home/ubuntu/shared-folders/public-files/$APP/translations ]; then
  echo -ne "\033[36m Creating translations directory ...  \033[0m"
  mkdir -p /home/ubuntu/shared-folders/public-files/$APP/translations
  echo -e "\033[36m OK \033[0m"
fi

# NGINX and confd/supervisor update
echo -ne "\033[36m Nginx and confd ...  \033[0m"
rm -f /etc/nginx/sites-enabled/$APP
rm -f /etc/nginx/sites-available/$APP
cp -f /home/ubuntu/apps/$APP/releases/$VERSION/aws/default/nginx.conf /etc/nginx/sites-available/$APP
cp -f "$(realpath -s "$SCRIPT_DIR/../default/custom.conf")" /etc/nginx/sites-available/$APP-custom.conf

if [ ! -f /etc/nginx/sites-available/drupal-shared.conf ]; then
    cp -f /home/ubuntu/apps/$APP/releases/$VERSION/vendor/mrm-remora/aws-code-deploy/assets/aws/default/drupal-shared.conf /etc/nginx/sites-available/drupal-shared.conf
fi
ln -s /etc/nginx/sites-available/$APP /etc/nginx/sites-enabled/$APP

if [ ! -d /etc/confd/templates/$APP ]; then
  mkdir -p /etc/confd/templates/$APP
fi

if [ ! -d /etc/confd/conf.d ]; then
  mkdir -p /etc/confd/conf.d
fi

if [ ! -d /etc/confd/conf.d/$APP ]; then
  mkdir -p /etc/confd/conf.d/$APP
fi

# remove civicrm if not in composer
if ! grep "civicrm/civicrm" /home/ubuntu/apps/$APP/releases/$VERSION/composer.json; then
  echo -ne "\033[36m Have CiviCRM - copying confd files ...  \033[0m"
  rm /home/ubuntu/apps/$APP/releases/$VERSION/vendor/mrm-remora/aws-code-deploy/assets/aws/default/confd/conf.d/*civicrm*
  rm /home/ubuntu/apps/$APP/releases/$VERSION/vendor/mrm-remora/aws-code-deploy/assets/aws/default/confd/templates/*civicrm*
  echo -e "\033[36m OK \033[0m"
fi

cp -f /home/ubuntu/apps/$APP/releases/$VERSION/vendor/mrm-remora/aws-code-deploy/assets/aws/default/confd/conf.d/*.toml /etc/confd/conf.d/$APP/
cp -f /home/ubuntu/apps/$APP/releases/$VERSION/vendor/mrm-remora/aws-code-deploy/assets/aws/default/confd/templates/*.tmpl /etc/confd/templates/$APP/

# if there is an override of file or any new confs copy those over and override
if [ -d /home/ubuntu/apps/$APP/releases/$VERSION/aws/default/confd/conf.d ]; then
  echo -ne "\033[36m Overriding confd files ...  \033[0m"
  cp -f /home/ubuntu/apps/$APP/releases/$VERSION/aws/default/confd/conf.d/*.toml /etc/confd/conf.d/$APP/
  echo -e "\033[36m OK \033[0m"
fi
if [ -d /home/ubuntu/apps/$APP/releases/$VERSION/aws/default/confd/templates ]; then
  echo -ne "\033[36m Overriding confd templates ...  \033[0m"
  cp -f /home/ubuntu/apps/$APP/releases/$VERSION/aws/default/confd/templates/*.tmpl /etc/confd/templates/$APP/
  echo -e "\033[36m OK \033[0m"
fi

# replace in confd env variables and put it into supervisor if not there
if [ ! -f /etc/supervisor/conf.d/confd.conf ]; then
  envsubst < /home/ubuntu/apps/$APP/releases/$VERSION/vendor/mrm-remora/aws-code-deploy/assets/aws/default/supervisor/confd.conf | sudo tee /etc/supervisor/conf.d/confd.conf
fi
echo -e "\033[36m OK \033[0m"

# remove current symlink and replace with new one
if [ -L /home/ubuntu/apps/$APP/current ]; then
  echo -ne "\033[36m Removing symlink to current release ...  \033[0m"
  rm /home/ubuntu/apps/$APP/current
  echo -e "\033[36m OK \033[0m"
fi
echo -e "\033[36m Linking new release to current ...  \033[0m"
ln -s /home/ubuntu/apps/$APP/releases/$VERSION /home/ubuntu/apps/$APP/current

# generate settings from template
echo -e "\033[36m Restarting supervisor ...  \033[0m"
service supervisor restart

# set the backup files to S3 once per day
# set the cron run every hour
# first get the domain name to pass for drupal cron run
if [ $ENV == "develop" ]; then
  URI=$(cat /home/ubuntu/apps/$APP/current/aws/default/nginx.conf | grep -E "mrmdev2|remora-dev" | sed "s/;//" | sed "s/.* //")
elif [ $ENV == "staging" ]; then
  URI=$(cat /home/ubuntu/apps/$APP/current/aws/default/nginx.conf | grep -E "mrmstaging|remora-staging" | sed "s/;//" | sed "s/.* //")
else
  URI=$(cat /home/ubuntu/apps/$APP/current/aws/default/nginx.conf | grep server_name | tail -1 | sed "s/;//" | sed "s/.* //")
fi

/bin/bash "$(realpath -s "$SCRIPT_DIR/../cron-check.sh")" $APP $URI

# runs script for nginx to see client real IP behind AWS ALB
/bin/bash "$(realpath -s "$SCRIPT_DIR/../nginx-check.sh")"

# deletes flood control file with space at the end
FLOOD_FILE="/tmp/${APP}/web/modules/contrib/flood_control/migrations/state/flood_control.migrate_drupal.yml "
[ -e $FLOOD_FILE ] && rm $FLOOD_FILE

# fix permissions
echo -ne "\033[36m Fixing permissions ...  \033[0m"
chown -R ubuntu:ubuntu /home/ubuntu/apps/$APP
chown -R ubuntu:ubuntu /home/ubuntu/shared-folders/public-files/$APP
chown -R ubuntu:ubuntu /home/ubuntu/shared-folders/private-files/$APP
chown -R ubuntu:ubuntu /home/ubuntu/shared-folders/temp-files/$APP
chmod 444 /home/ubuntu/apps/$APP/current/web/sites/default/*.yml
echo -e "\033[36m OK \033[0m"

# EOF
