#!/bin/bash
# direct_db_migration.sh

echo "🔍 Finding all _nugget configs..."

# Get list of configs with _pod
lando drush @dsc.local sql-query "SELECT name FROM config WHERE name LIKE '%_nugget%';" > /tmp/nugget_configs.txt

echo "📊 Found $(wc -l < /tmp/nugget_configs.txt) configs to migrate"

while read config_name; do
    # Skip empty lines
    [ -z "$config_name" ] && continue

    new_name=$(echo "$config_name" | sed 's/_nugget/_pod/g')

    echo "🔄 Migrating: $config_name -> $new_name"

    # Update the config name and data in one SQL query
    lando drush @dsc.local sql-query "
        UPDATE config
        SET name = '$new_name',
            data = REPLACE(data, '_nugget', '_pod')
        WHERE name = '$config_name';
    "

done < /tmp/nugget_configs.txt

# Clean up
rm -f /tmp/nugget_configs.txt

# Clear cache
lando drush @dsc.local cr

echo "✅ Migration complete!"