#!/bin/bash
# migrate_both_patterns.sh

echo "🔄 Starting migration: _nugget -> _pod and _pea -> _pea"

# Start transaction
lando drush @dsc.local sql-query "START TRANSACTION;"

# 1. Migrate _nugget to _pod
echo "📝 Step 1: Migrating _nugget to _pod..."
lando drush @dsc.local sql-query "SELECT name FROM config WHERE name LIKE '%_nugget%' AND name NOT LIKE '%_pea%';" | while read config; do
    [ -z "$config" ] && continue
    new_config=$(echo "$config" | sed 's/_nugget/_pod/g')
    echo "  $config -> $new_config"
    lando drush @dsc.local sql-query "
        UPDATE config
        SET name = '$new_config',
            data = REPLACE(data, '_nugget', '_pod')
        WHERE name = '$config';
    "
done

# 2. Migrate _pea to _pea
echo "📝 Step 2: Migrating _pea to _pea..."
lando drush @dsc.local sql-query "SELECT name FROM config WHERE name LIKE '%_pea%';" | while read config; do
    [ -z "$config" ] && continue
    new_config=$(echo "$config" | sed 's/_pea/_pea/g')
    echo "  $config -> $new_config"
    lando drush @dsc.local sql-query "
        UPDATE config
        SET name = '$new_config',
            data = REPLACE(data, '_pea', '_pea')
        WHERE name = '$config';
    "
done

# Commit transaction
lando drush @dsc.local sql-query "COMMIT;"

# Clear cache
lando drush @dsc.local cr

echo "✅ Migration complete!"