#!/usr/bin/env bash

APP=$1
URI=$2

declare -A lookArr
declare -A cmdArr
lookArr[cron]="drush --root=/home/ubuntu/apps/${APP}/current/web"
cmdArr[cron]="sudo -u ubuntu /home/ubuntu/apps/${APP}/current/vendor/drush/drush/${lookArr[cron]} --uri=${URI} --quiet cron"
lookArr[backup]="/bin/bash /home/ubuntu/apps/${APP}/current/bin/backup-s3.sh"
cmdArr[backup]="${lookArr[backup]} >> /var/log/backups/${APP}.log"


check_add_to_cron() {
  local ACTION=$1
  local MIN_MAX=$2
  local MIN_INCREASE=$3

  echo -e "\033[31m No ${ACTION} entry \033[0m"
  last=$(crontab -l | sed -n "/# add app-$ACTION/,/# $ACTION---/p" | tail -2 | head -n 1)
  minutes=$(echo "$last" | cut -d' ' -f-2 | cut -d' ' -f1)
  hours=$(echo "$last" | cut -d' ' -f-2 | cut -d' ' -f2)

  if [ $((minutes)) -le $((MIN_MAX)) ]; then
   ((minutes+=MIN_INCREASE))
  else
   minutes=0
   ((hours+=1))
  fi

  cronjob="${minutes} ${hours} * * * ${cmdArr[$ACTION]}"
  cronjob_esc=$(echo "$cronjob" | sed 's/\//\\\//g')
  crontab -l | sed "s/# $ACTION-----/$cronjob_esc\n# $ACTION-----/g" | crontab
  echo -e "Created $ACTION: \033[32m ${cronjob} \033[0m"
}


##################################
# check for Drupal cron
##################################
existing=$(crontab -l | grep -F "${lookArr[cron]}")
if (crontab -l | grep -q '# add app-cron'); then
  if [ ${#existing} == 0 ]; then
    check_add_to_cron cron 58 2
  fi
else
  echo -e "\033[32m Cron not needed on this server \033[0m"
fi


##################################
# check for backup part in crontab
##################################
existing=$(crontab -l | grep -F "${lookArr[backup]}")
if (crontab -l | grep -q '# add app-backup'); then
  if [ ${#existing} == 0 ]; then
    check_add_to_cron backup 50 5

    # check that putting the instance back is after inserted
    back=$(crontab -l | grep "# put instance back" -A1 | grep -v "# put instance back")
    back_minutes=$(echo "$back" | cut -d' ' -f-2 | cut -d' ' -f1)
    back_hours=$(echo "$back" | cut -d' ' -f-2 | cut -d' ' -f2)

    time=$((hours * 60 + minutes))
    back_time=$((back_hours * 60 + back_minutes))

    if [ $((time)) -ge $((back_time)) ]; then
      if [ $((back_minutes)) -le 50 ]; then
        ((back_minutes+=5))
      else
        back_minutes=0
        ((back_hours+=1))
      fi

      # replace thew line with command for back
      backcmd="/bin/bash /home/ubuntu/curl-standby-remove.sh"
      backcmd_esc=$(echo "$backcmd" | sed 's/\//\\\//g')
      crontab -l | sed "/$backcmd_esc/c$back_minutes $back_hours * * * $backcmd_esc" | crontab
    fi

  else
    curr_len_log=`grep -E "\.log$" <<< "$existing" | wc -c`
  
    if [ $curr_len_log == 0 ]; then
      echo -e "Backup: \033[31m No log -UPDATING \033[0m"
      backup_look_esc=$(echo "${lookArr[backup]}" | sed 's/\//\\\//g')
      backup_cmd_esc=$(echo "${cmdArr[backup]}" | sed 's/\//\\\//g')
      crontab -l | sed "s/$backup_look_esc/$backup_cmd_esc/g" | crontab
    else
      echo -e "Backup existing: \033[36m ${existing} \033[0m"
    fi
  fi
else
  echo -e "\033[32m Backup not needed on this server \033[0m"
fi
