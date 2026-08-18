#!/bin/bash
# complete_migration_with_cleanup.sh

echo "🔄 Complete Migration: _nugget → _pod and _nugglet → _pea"

# Step 1: Backup everything
echo "📦 Creating backups..."
lando drush @dsc.local sql:dump --result-file=/tmp/db_backup_$(date +%Y%m%d_%H%M%S).sql
lando drush @dsc.local config:export --destination=/tmp/config_backup_$(date +%Y%m%d_%H%M%S)

# Step 2: Get all pod modules that need to be installed
POD_MODULES=$(lando drush @dsc.local pm:list --type=module --status=disabled --format=list | grep -E "(_pod|_pea)")

# Step 3: For each pod module, delete its configs first
for module in $POD_MODULES; do
    echo ""
    echo "🔍 Processing: $module"

    # Find module machine name (without status)
    MODULE_NAME=$(echo "$module" | cut -d',' -f1)

    # Get all configs that might conflict with this module
    # This is tricky - we need to delete configs from the old module
    OLD_MODULE=$(echo "$MODULE_NAME" | sed 's/_pod$/_nugget/g' | sed 's/_pea$/_nugglet/g')

    echo "  📝 Deleting configs from old module: $OLD_MODULE"

    # Delete all configs containing the old module name
    CONFIGS=$(lando drush @dsc.local config:list --format=list | grep "$OLD_MODULE" | grep -v "config:list")
    if [ -n "$CONFIGS" ]; then
        echo "$CONFIGS" | while read config; do
            echo "    Deleting: $config"
            lando drush @dsc.local config:delete "$config" -y 2>/dev/null
        done
    else
        echo "    No configs found for $OLD_MODULE"
    fi
done

# Step 4: Delete any remaining _nugget/_nugglet configs
echo ""
echo "🧹 Cleaning up remaining _nugget/_nugglet configs..."
lando drush @dsc.local config:list --format=list | grep -E "(_nugget|_nugglet)" | while read config; do
    echo "  Deleting: $config"
    lando drush @dsc.local config:delete "$config" -y 2>/dev/null
done

# Step 5: Clear cache
echo ""
echo "🧹 Clearing cache..."
lando drush @dsc.local cr

# Step 6: Uninstall old modules
echo ""
echo "🗑️  Uninstalling old _nugget/_nugglet modules..."
lando drush @dsc.local pm:list --type=module --status=enabled --format=list | grep -E "(_nugget|_nugglet)" | while read module; do
    MODULE_NAME=$(echo "$module" | cut -d',' -f1)
    echo "  Uninstalling: $MODULE_NAME"
    lando drush @dsc.local pm:uninstall "$MODULE_NAME" -y 2>/dev/null
done

# Step 7: Enable new modules
echo ""
echo "📦 Installing _pod/_pea modules..."
for module in $POD_MODULES; do
    MODULE_NAME=$(echo "$module" | cut -d',' -f1)
    echo "  Installing: $MODULE_NAME"
    lando drush @dsc.local pm:enable "$MODULE_NAME" -y
done

# Step 8: Final cache clear and updates
echo ""
echo "🧹 Final cleanup..."
lando drush @dsc.local cr
lando drush @dsc.local updb -y

echo ""
echo "✅ Migration complete!"
echo "📊 Verification:"
echo "  Remaining _nugget configs: $(lando drush @dsc.local config:list --format=list | grep '_nugget' | wc -l)"
echo "  Remaining _nugglet configs: $(lando drush @dsc.local config:list --format=list | grep '_nugglet' | wc -l)"
echo "  Installed _pod modules: $(lando drush @dsc.local pm:list --type=module --status=enabled --format=list | grep '_pod' | wc -l)"
echo "  Installed _pea modules: $(lando drush @dsc.local pm:list --type=module --status=enabled --format=list | grep '_pea' | wc -l)"