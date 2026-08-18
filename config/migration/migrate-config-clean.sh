#!/bin/bash
# simple_single_module.sh

MODULE_NAME="$1"

if [ -z "$MODULE_NAME" ]; then
    echo "Usage: $0 <module_name>"
    echo "Example: $0 banners_pod"
    exit 1
fi

echo "🔄 Processing: $MODULE_NAME"

# Determine old module name
if [[ "$MODULE_NAME" == *_pod ]]; then
    OLD_MODULE="${MODULE_NAME/_pod/_nugget}"
elif [[ "$MODULE_NAME" == *_pea ]]; then
    OLD_MODULE="${MODULE_NAME/_pea/_nugglet}"
else
    echo "⚠️  Module name must end with _pod or _pea"
    exit 1
fi

echo "  Old module: $OLD_MODULE"

# Delete configs from new module's config/install folder
CONFIG_DIR="web/modules/custom/${MODULE_NAME}/config/install"

if [ -d "$CONFIG_DIR" ]; then
    echo "📂 Found config folder: $CONFIG_DIR"

    for config_file in "$CONFIG_DIR"/*.yml; do
        if [ -f "$config_file" ]; then
            CONFIG_NAME=$(basename "$config_file" .yml)
            echo "  🗑️  Deleting: $CONFIG_NAME"
            lando drush @dsc.local config:delete "$CONFIG_NAME" -y 2>/dev/null
        fi
    done
else
    echo "⚠️  No config/install folder found for $MODULE_NAME"
fi

# Delete configs from old module's config/install folder
OLD_CONFIG_DIR="web/modules/custom/${OLD_MODULE}/config/install"

if [ -d "$OLD_CONFIG_DIR" ]; then
    echo "📂 Found old config folder: $OLD_CONFIG_DIR"

    for config_file in "$OLD_CONFIG_DIR"/*.yml; do
        if [ -f "$config_file" ]; then
            CONFIG_NAME=$(basename "$config_file" .yml)
            # Replace old suffix with new suffix in config name
            if [[ "$MODULE_NAME" == *_pod ]]; then
                NEW_CONFIG_NAME="${CONFIG_NAME/_nugget/_pod}"
            elif [[ "$MODULE_NAME" == *_pea ]]; then
                NEW_CONFIG_NAME="${CONFIG_NAME/_nugglet/_pea}"
            fi
            echo "  🗑️  Deleting: $NEW_CONFIG_NAME"
            lando drush @dsc.local config:delete "$NEW_CONFIG_NAME" -y 2>/dev/null
        fi
    done
else
    echo "⚠️  No config/install folder found for $OLD_MODULE"
fi

# Delete any remaining configs with old pattern
echo "  🧹 Cleaning up remaining configs..."
lando drush @dsc.local config:list --format=list | grep -E "(_nugget|_nugglet)" | while read config; do
    lando drush @dsc.local config:delete "$config" -y 2>/dev/null
done

# Clear cache
lando drush @dsc.local cr

# Uninstall old module
echo "🗑️  Uninstalling: $OLD_MODULE"
lando drush @dsc.local pm:uninstall "$OLD_MODULE" -y 2>/dev/null

# Install new module
echo "📦 Installing: $MODULE_NAME"
lando drush @dsc.local pm:enable "$MODULE_NAME" -y

# Final cache clear
lando drush @dsc.local cr

echo "✅ Done!"