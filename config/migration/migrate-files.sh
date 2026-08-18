#!/bin/bash
# rename_files.sh

echo "🔄 Renaming files: _nugget → _pod and _pea → _pea"

# First, handle _pea → _pea (do this first to avoid conflicts)
echo "📝 Step 1: Renaming _pea → _pea..."
find . -type f -name "*_pea*" -not -path "./vendor/*" -not -path "./node_modules/*" -not -path "./.git/*" | while read file; do
    new_file=$(echo "$file" | sed 's/_pea/_pea/g')
    if [ "$file" != "$new_file" ]; then
        mv -v "$file" "$new_file"
    fi
done

# Then handle _nugget → _pod
echo "📝 Step 2: Renaming _nugget → _pod..."
find . -type f -name "*_nugget*" -not -path "./vendor/*" -not -path "./node_modules/*" -not -path "./.git/*" | while read file; do
    new_file=$(echo "$file" | sed 's/_nugget/_pod/g')
    if [ "$file" != "$new_file" ]; then
        mv -v "$file" "$new_file"
    fi
done

echo "✅ File renaming complete!"